<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Document;
use App\Entity\Email;
use App\Entity\Invoice;
use App\Entity\Offer;
use App\Entity\Reminder;
use App\Service\PHPMailerService;
use App\Service\PriceService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AjaxController.
 */
#[Route(path: '/ajax/ajax-mail')]
class AjaxMailController extends BaseController
{
    /**
     * @todo db settings
     */
    public const MAIL_REMINDER_DAYS = 7;
    public $mailTemplates;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, string $hdDir)
    {
        $mailTemplate = $params->get('mail_templates');
        $this->mailTemplates = Yaml::parseFile($mailTemplate);
        $this->hdDir = $hdDir;
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    // cat
    #[Route(path: '/loadqr', name: 'ajax_qr', methods: ['GET'])]
    public function getTemplateDetailsQR(): Response
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create('https://www.zoe-solar.de/news')
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->setSize(150)
            ->setMargin(0)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $label = Label::create('Gato perdito - Video - Images');

        // $text = 'ZOE-Solar.de/pv/news';
        $qrCodes['withImage'] = $writer->write(
            $qrCode,
            null,
            // $label->setText($text)->setFont(new NotoSans(14))
        )->getDataUri();

        return $this->render('winni.html.twig', [
            'code' => $qrCodes['withImage'],
        ]);
    }

    private function setReminder1Message(string &$title, string &$message, int $invoiceId, PriceService $priceService)
    {
        $date = new \DateTime();
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        $id = $invoice->getSendDate()->modify('+9 days');
        $title = str_replace('##invoice_number##', $invoice->getNumber(), $title);
        $message = str_replace(['##invoice_number##', '##seit##', '##newDate##', '##invoice_date##', '##betrag##', '##account##'], [
            $invoice->getNumber(),
            $id->format('d.m.Y'),
            $date->modify('+9 days')->format('d.m.Y'),
            $invoice->getSendDate()->format('d.m.Y'),
            number_format($priceService->calculateInvoicePrice($invoice), 2, ',', '.'),
            '<br>'.$this->getParameter('sf')['name_complete'].'<br>'.
            $this->getParameter('sf')['iban'].'<br>'.
            $this->getParameter('sf')['bic'].'',
        ], $message);
    }

    private function setReminder2Message(string &$title, string &$message, int $invoiceId, PriceService $priceService)
    {
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        $title = str_replace('##invoice_number##', $invoice->getNumber(), $title);
        /** @var Reminder $reminder */
        $date = new \DateTime();
        $message = str_replace(['##invoice_number##', '##invoice_date##', '##betrag##', '##pay_date##', '##account##'], [
            $invoice->getNumber(),
            $invoice->getSendDate()->format('d.m.Y'),
            // count($invoice->getReminder()) ? $reminder->getSendDate()->format('d.m.Y') : 'NICHT GESENDET',
            number_format($priceService->calculateInvoicePrice($invoice), 2, ',', '.'),
            $date->modify('+9 days')->format('d.m.Y'),
            '<br>'.$this->getParameter('sf')['name_complete'].'<br>'.
            $this->getParameter('sf')['iban'].'<br>'.
            $this->getParameter('sf')['bic'].'',
        ], $message);
    }

    private function setReminder3Message(string &$title, string &$message, int $invoiceId, PriceService $priceService, $late = false)
    {
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        $title = str_replace('##invoice_number##', $invoice->getNumber(), $title);
        /** @var Reminder $reminder */
        /** @var Reminder $reminder2 */
        $date = new \DateTime();
        $nineDaysFromNow = clone $date;
        $reminder = $invoice->getReminderByNumber(0);
        $reminder2 = $invoice->getReminderByNumber(1);
        $price = $priceService->calculateInvoicePrice($invoice);
        $mustPayFirstDate = $invoice->getSendDate()->modify('+9 days');
        if ($late) {
            $prozentsatz = $this->getParameter('basiszins') + 5;
            $dateDiff = date_diff($mustPayFirstDate, $date);
            // Rechnungsbetrag x (Basiszinssatz + 5 %) x Verzugstage / 365* = Verzugszinsen für Verbraucher. Rechnungsbetrag x (Basiszinssatz + 9 %) x Verzugstage / 365* = Verzugszinsen für Unternehmen.
            $zins = (($price * $prozentsatz / 100) / 365 * $dateDiff->days);
            $zinsMwSt = ($zins * 4.75 / 100);
            $message = str_replace([
                //                Sie haben leider auf unsere Zahlungserinnerung vom ##rem_date1## und unserer Mahnung vom ##rem_date2## nicht
                // reagiert. Dies ist die dritte und letzte Mahnung, bevor wir das offene Konto an ein Inkassounternehmen übergeben müssen.
                //            Folgende Verzugszinsen sind bereits fällig.
                // <br>
                //            Rechnungsbetrag: ##betrag##<br>
                // Verzugszinsen: ##betrag_zins##<br>
                // <small>##vershow##</small>
                //            MwSt auf Verzugszinsen (4,75%): ##mwst##<br>
                // <strong style="font-weight: bold">Brutto Rechnungsbetrag: ##gesamt##</strong>
                // <br><br>
                //            Wir bitten Sie dringlichst den fälligen Betrag von ##gesamt## &euro; bis spätestens zum ##payDate## an uns zu überweisen.
                //            <br>
                // <strong style="font-weight: bold">Unsere Bankdaten:</strong>
                // <br>
                // ##account##
                // <br><br>
                //            Es liegt nicht in unserem Interesse, rechtliche Schritte einleiten zu müssen und somit Mehrkosten für Sie zu verursachen. Bitte zahlen Sie den Betrag umgehend.

                '##invoice_date##',
                '##payDate##',
                '##vershow##',
                // '##zinsday##',
                '##rem_date1##',
                '##rem_date2##',
                '##betrag##',

                '##account##',
                '##betrag_zins##',
                '##mwst##',
                '##gesamt##',
            ], [
                $invoice->getSendDate()->format('d.m.Y'),
                $nineDaysFromNow->modify('+9 days')->format('d.m.Y'),
                '('.$prozentsatz.'% vom Nettorechnungsbetrag seit dem '.$mustPayFirstDate->format('d.m.Y').')',
                count($invoice->getReminder()) ? $reminder->getSendDate()->format('d.m.Y') : '(Mahnung 1 NICHT GESENDET)',
                count($invoice->getReminder()) > 1 ? $reminder2->getSendDate()->format('d.m.Y') : '(Mahnung 2 NICHT GESENDET)',
                number_format($price, 2, ',', '.'),

                '<br>'.$this->getParameter('sf')['name_complete'].'<br>'.
                    $this->getParameter('sf')['iban'].'<br>'.
                    $this->getParameter('sf')['bic'].'',
                number_format($zins, 2, ',', '.'),
                number_format($zinsMwSt, 2, ',', '.'),
                number_format($price + $zins + $zinsMwSt, 2, ',', '.'),
            ], $message);
        } else {
            $message = str_replace([
                '##invoice_date##',
                '##seit##',
                '##payDate##',
                '##rem_date1##',
                '##rem_date2##',
                '##betrag##',
                '##account##',
            ], [
                $invoice->getSendDate()->format('d.m.Y'),
                $mustPayFirstDate->format('d.m.Y'),
                $nineDaysFromNow->modify('+9 days')->format('d.m.Y'),
                count($invoice->getReminder()) ? $reminder->getSendDate()->format('d.m.Y') : '(Mahnung 1 NICHT GESENDET)',
                count($invoice->getReminder()) > 1 ? $reminder2->getSendDate()->format('d.m.Y') : '(Mahnung 2 NICHT GESENDET)',
                number_format($price, 2, ',', '.'),
                '<br>'.$this->getParameter('sf')['name_complete'].'<br>'.
                $this->getParameter('sf')['iban'].'<br>'.
                $this->getParameter('sf')['bic'].'',
            ], $message);
        }
    }

    #[Route(path: '/load/{id}/detail/{template}', name: 'ajax_load_detail_mail', methods: ['GET'])]
    public function getTemplateDetails(Request $request, LoginLinkHandlerInterface $loginLinkHandler, PriceService $priceService, Customer $customer, string $template): Response
    {
        if (empty($this->mailTemplates[$template])) {
            $template = 'default';
        }

        $templateName = $template;
        $template = $this->mailTemplates[$templateName];
        $title = $template['name'];
        $message = $template['message'];

        $invoiceId = $request->query->get('invoice');

        if ('reminder_1' === $templateName) {
            $this->setReminder1Message($title, $message, (int) $invoiceId, $priceService);
        } elseif ('reminder_2' === $templateName) {
            $this->setReminder2Message($title, $message, (int) $invoiceId, $priceService);
        } elseif ('reminder_3' === $templateName) {
            $this->setReminder3Message($title, $message, (int) $invoiceId, $priceService);
        } elseif ('reminder_31' === $templateName) {
            $this->setReminder3Message($title, $message, (int) $invoiceId, $priceService, true);
        }
        if ('codelink' === $templateName) {
            /** @var Customer $customer */
            $customer = $this->getUser();
            $loginLinkDetails = $loginLinkHandler->createLoginLink($customer);
            $loginLink = $loginLinkDetails->getUrl();
            $message = str_replace('--loginCode--', $this->generateQrCode($loginLink, $customer->getId()), $message);
            $message = str_replace('--loginLink--', $loginLink, $message);
        }

        return $this->json([
            'title' => $title,
            'message' => $message,
            'name' => $customer->getEmail(),
        ]);
    }

    #[Route(path: '/attachments/{id}', name: 'ajax_attachments_mail', methods: ['GET'])]
    public function getAttachments(Offer $offer): Response
    {
        return $this->render('offer/components/_attachments.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route(path: '/create/{id}/{template}', name: 'ajax_create_default_mail', methods: ['GET', 'POST'])]
    public function createTemplate(Request $request, LoginLinkHandlerInterface $loginLinkHandler, PriceService $priceService, Customer $customer, string $template): Response
    {
        if (empty($this->mailTemplates[$template])) {
            $template = 'default';
        }

        $templateName = $template;
        $template = $this->mailTemplates[$templateName];
        $templateUrl = $template['url'];
        $title = $template['name'];
        $message = $template['message'];
        // dd($request->query->all());
        if (!empty($request->request->get('name'))) {
            $title = $request->request->get('name');
        }
        if (!empty($request->request->get('title'))) {
            $title = $request->request->get('title');
        }
        $invoiceId = $request->query->get('invoice');
        if ('reminder_1' === $templateName) {
            $this->setReminder1Message($title, $message, (int) $invoiceId, $priceService);
        } elseif ('reminder_2' === $templateName) {
            $this->setReminder2Message($title, $message, (int) $invoiceId, $priceService);
        } elseif ('reminder_3' === $templateName) {
            $this->setReminder3Message($title, $message, (int) $invoiceId, $priceService);
        } elseif ('reminder_31' === $templateName) {
            $this->setReminder3Message($title, $message, (int) $invoiceId, $priceService, true);
        }
        if (!empty($request->request->get('message'))) {
            $message = $request->request->get('message');
        }

        if ('codelink' === $template) {
            $loginLinkDetails = $loginLinkHandler->createLoginLink($customer);
            $loginLink = $loginLinkDetails->getUrl();
            $message = str_replace('--loginLink--', $loginLink, $message);
            $message = str_replace('--loginCode--', $this->generateQrCode($loginLink, $customer->getId()), $message);
        }

        $email = new Email();
        $email->setCustomer($customer);
        $email->setTitle($title);
        $email->setMessage($message);

        return $this->render('app/mail/'.$templateUrl, [
            'email' => $email,
            'message' => $message,
            'customer' => $email->getCustomer(),
        ]);
    }

    private function createReminderInvoice(Invoice $invoice, Customer $customer)
    {
        $date = new \DateTime();
        $invoiceN = new Invoice();
        $invoiceN->setDate($date);
        $invoiceN->setUser($this->getUser());
        $invoiceN->setCustomer($customer);
        $invoiceN->setType('individual');
        $invoiceN->setPos0Date($date->format('d.m.Y'));
        $invoiceN->setPos0Text('Verzugszinsen Rechnungsnummer: '.$invoice->getNumber());
        $invoiceN->setPos0Price(0);
        $invoiceN->setBauvorhaben($customer->getAddress().', '.$customer->getZip());
        $number = 1;
        foreach ($customer->getInvoices() as $invoice) {
            if (null === $invoice->getInvoiceOrder()) {
                ++$number;
            }
        }
        if (277 !== $customer->getId()) { // !BSH
            // $name = $customer->getFullNormalName();
            $invoice->setLeistung('');
            $invoice->setZusatz('');
            $invoice->setNumber('4'.$customer->getId().'.'.$number);
            $invoice->setBauherr($customer->getFullNormalName());
            $invoice->setBauvorhaben($customer->getFullAddress());
            $invoice->setLv('');
        }
        $invoice->setText(sprintf('wir bedanken uns für die freundliche Zusammenarbeit und überreichen Ihnen hiermit Ihre Rechnung RNr.: %s zu folgendem Projekt.',
            $invoice->getNumber()
        )
        );
    }

    #[Route(path: '/send/{id}/{template?}', name: 'ajax_send_mail', defaults: ['template' => 'default'], methods: ['GET', 'POST'])]
    public function sendSelectedMail(Request $request, Customer $customer, string $template, string $protocolDirectory): Response
    {
        // $requestContent = json_decode(v->getContent());
        $email = new Email();
        $email->setUser($this->getUser());
        $email->setDate(new \DateTime());
        $email->setCustomer($customer);
        $requestJson = json_decode($request->getContent());

        $invoiceID = $requestJson->invoice ?? null;
        $email->setTitle($requestJson->title);
        $email->setMessage(html_entity_decode((string) $requestJson->message));
        if (empty($this->mailTemplates[$template])) {
            $template = 'default';
        }
        $templateName = $template;
        $email->setTemplate($templateName);
        $template = $this->mailTemplates[$templateName];
        if (stristr($template, 'reminder')) {
            $reminder = new Reminder();
            $invoice = $this->em->getRepository(Invoice::class)->find($requestJson->invoice);
            $reminder->setInvoice($invoice);
            $reminder->setUser($email->getUser());
            $reminder->setCustomer($customer);
            $reminder->setText(html_entity_decode((string) $requestJson->message));
            $reminder->setType('reminder_1' === $template ? '1' : ('reminder_2' === $template ? '2' : '3'));
            $reminder->setNumber('reminder_1' === $template ? '1' : ('reminder_2' === $template ? '2' : '3'));
            $reminder->setSendDate(new \DateTime());

            $this->em->persist($reminder);
            $this->em->flush();
        }

        $form = $this->createForm(EmailType::class, $email);
        $form->submit($request->getContent());
        $form->handleRequest($request);
        $email->setAttachment(null);
        $email->setAttachmentName(null);
        if (!empty($requestJson->attachment)) {
            $document = $this->em->getRepository(Document::class)->find((int) $requestJson->attachment);
            if ($document instanceof Document) {
                $email->setAttachment($protocolDirectory.'/'.$document->getOffer()->getId().'/'.$document->getFilename());
                $names = explode('.', $document->getFilename());
                $ext = end($names);
                $email->setAttachmentName(!empty($requestJson->attachmentName) ? $requestJson->attachmentName.'.'.$ext : $document->getFilename());
            }
        }

        if (!stristr($email->getTitle(), 'ersetzen durch') && !empty(trim($email->getMessage())) && ($form->isSubmitted() && $form->isValid())) {
            switch ($template) {
                case 'n-berlin':
                    $email->setAttachment($this->getParameter('kernel.project_dir').'/pdf_AggSF-2/netzanschluss/anmeldung-netzanschluss.pdf');
                    $email->setAttachmentName('Anmeldung-Netzanschluss-Berlin.pdf');
                    $this->em->persist($email);
                    $this->em->flush();
                    $this->em->refresh($email);
                    $this->mailerService->setUserToLog($this->getUser());
                    $this->mailerService->sendIndividualMail(
                        $email,
                        'app/mail/'.$template,
                    );
                    break;
                default:
                    $this->em->persist($email);
                    $this->em->flush();
                    $this->em->refresh($email);
                    $this->mailerService->setUserToLog($this->getUser());
                    $this->mailerService->sendIndividualMail(
                        $email,
                        'app/mail/'.$template
                    );
                    break;
            }

            return $this->json(['success' => true, 'redirect' => $this->generateUrl('customer_index', ['id' => $customer->getId()])]);
        }

        return $this->json(['success' => false]);
    }

    private function generateQrCode(string $route, int $number)
    {
        $writer = new PngWriter();
        $qrCode = QrCode::create($route)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->setSize(150)
            ->setMargin(0)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        $logo = Logo::create($this->hdDir.'/logo/round.png')
            ->setResizeToWidth(60);
        $label = Label::create(''); // ->setFont(new NotoSans(8));
        //
        //        $qrCodes = [];
        //        $qrCodes['img'] = $writer->write($qrCode, $logo)->getDataUri();
        //        $qrCodes['simple'] = $writer->write(
        //            $qrCode,
        //            null,
        //            $label->setText('Simple')
        //        )->getDataUri();
        //
        //        $qrCode->setForegroundColor(new Color(255, 0, 0));
        //        $qrCodes['changeColor'] = $writer->write(
        //            $qrCode,
        //            null,
        //            $label->setText('Color Change')
        //        )->getDataUri();
        //
        //        $qrCode->setForegroundColor(new Color(0, 0, 0))->setBackgroundColor(new Color(255, 0, 0));
        //        $qrCodes['changeBgColor'] = $writer->write(
        //            $qrCode,
        //            null,
        //            $label->setText('Background Color Change')
        //        )->getDataUri();

        $text = ''.(Customer::CUSTOMER_START + $number);
        $qrCodes['withImage'] = $writer->write(
            $qrCode,
            $logo,
            $label->setText($text)->setFont(new NotoSans(20))
        )->getDataUri();

        return $qrCodes['withImage'];
    }
}
