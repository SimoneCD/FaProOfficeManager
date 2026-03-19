<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Email;
use App\Entity\Invoice;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * todo translation.
 */
class PHPMailerService
{
    private readonly Environment $twig;

    private array $parameter;

    private SlackService $slackService;

    private UserInterface $user;

    public function __construct(private TranslatorInterface $translator, Environment $twig, ParameterBagInterface $parameter, SlackService $slackService)
    {
        $this->twig = $twig;
        $this->parameter = $parameter->all();
        $this->slackService = $slackService;
    }

    public function setUserToLog(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @throws Exception
     */
    public function sendMail(
        string $customerName,
        string $to,
        string $subject,
        string $html,
        array $attachments = [],
        string $slackKey = 'slack_kv',
        string $slackMessage = '',
    ): bool {
        // dd($this->parameter['app_email']);
        $mail = (new PHPMailer());
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->SMTPKeepAlive = true;
        $mail->Debugoutput = 'html';
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 465;
        $mail->SMTPSecure = 'ssl';
        $mail->SMTPAuth = true;
        $mail->Username = $this->parameter['app_email'];
        $mail->Password = $this->parameter['app_password'];
        $mail->setFrom($this->parameter['app_email'], $this->parameter['app_email_name']);
        $mail->addAddress($to, $customerName);
        $mail->Subject = utf8_decode($subject);
        // dd($html);
        $mail->msgHTML(utf8_decode($html));
        $mail->AltBody = $this->translator->trans('e.send.errorView', [
            'subjectName' => $this->parameter['sf']['name'],
            'subject' => $subject,
        ]);
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment['file'], $attachment['name']);
            }
        }
        try {
            if (false === $mail->send()) {
                if ('dev' !== $_ENV['APP_ENV'] && !empty($this->parameter['app_active_log']['slack_active'])) {
                    try {
                        $this->slackService->addSlackLogToChannel($slackKey, 'Nicht gesendet (FEHLER): '.$customerName.', Betr.: '.$subject);
                    } catch (\Exception $exception) {
                    }
                }

                return false;
            }
            if ('dev' !== $_ENV['APP_ENV'] && !empty($this->parameter['app_active_log']['slack_active'])) {
                try {
                    if ($this->user instanceof User) {
                        $this->slackService->addSlackLogToChannel($slackKey, 'Gesendet von '.$this->user->getUsername().' an: '.$customerName.', Betr.: '.$subject);
                    } else {
                        $this->slackService->addSlackLogToChannel($slackKey, 'Gesendet an: '.$customerName.', Betr.: '.$subject);
                    }
                } catch (\Exception $exception) {
                }
            }

            return true;
        } catch (\Exception $e) {
            if ('dev' !== $_ENV['APP_ENV'] && !empty($this->parameter['app_active_log']['slack_active'])) {
                try {
                    $this->slackService->addSlackLogToChannel($slackKey, 'FEHLER beim senden: '.$e->getMessage());
                } catch (\Exception $exception) {
                }
                if (false === $mail->send()) {
                    return false;
                }
            }

            return false;
        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendOrder(Order $order, string $pdfPath, TranslatorInterface $translator): bool
    {
        /** @var string $body */
        $body = $this->twig->render('app/mail/offer/offer.html.twig', [
            'customer' => $order->getOffer()->getCustomer(),
            'order' => $order,
            'offer' => $order->getOffer(),
        ]);
        $offer = $order->getOffer();
        if ($offer->getWallboxProduct() instanceof Product) {
            $p = $offer->getWallboxProduct()->getName();
            if (!empty($offer->getSubTitle())) {
                $p = $offer->getSubTitle();
            }
        } else {
            $p = $offer->getSubTitle();
        }
        $s = $p.'';
        if ('besichtigung' === $offer->getStatus() || 'estimate' === $offer->getStatus()) {
            $t = 'slack_kv';
            $s = $s.' - Ihr Kostenvoranschlag: '.$offer->getNumber().' - '.$this->parameter['sf']['name'];
        } else {
            $t = 'slack_angebot';
            $s = $s.' - '.$translator->trans('o.yourOffer').': '.$offer->getNumber().' - '.$this->parameter['sf']['name'];
        }
        $customer = $offer->getCustomer();
        $files = [];
        array_push($files, ['file' => $pdfPath, 'name' => $translator->trans('w.offer').'_'.$offer->getNumber().'.pdf']);
        // todo - settings upload in offer_subcategory.
        if ($offer->getWallboxProduct() instanceof Product && 'PV Tracker' === $offer->getWallboxProduct()->getName()) {
            array_push($files, ['file' => 'hd/app/pdf/intern/PVmover_sunOister.pdf', 'name' => 'PvMover-SunOister_Informationen.pdf']);
        } elseif ($offer->getWallboxProduct() instanceof Product) {
            /* todo dynamic ad pdf's */
            if (stristr($offer->getWallboxProduct()->getName(), 'Copper')
                || stristr($offer->getWallboxProduct()->getName(), 'Pulsar')
                || stristr($offer->getWallboxProduct()->getName(), 'Commander')) {
                array_push($files, ['file' => 'hd/app/pdf/intern/Produktdaten-Wallbox.pdf', 'name' => 'Produktdaten-Wallbox_Informationen.pdf']);
            }
        }

        return $this->sendMail($this->getName($customer), $this->getEmail($customer), $s, $body, $files, $t);
    }

    public function sendInvoice(Invoice $invoice, string $pdf, string $type, TranslatorInterface $translator): bool
    {
        if ('part' === $type) {
            $order = $invoice->getInvoiceOrder();
            $offer = $order->getOffer();
            $subject = $this->parameter['sf']['name'].' '.$translator->trans('w.invoicePart').' '.$offer->getNumber().'.1';
            $template = 'app/mail/invoice/part_invoice.html.twig';
            $attachName = $translator->trans('w.invoicePart').'-'.$offer->getNumber().'.1.pdf';
        } elseif ('part-plus' === $type) {
            $order = $invoice->getInvoiceOrder();
            $subject = $this->parameter['sf']['name'].' '.$translator->trans('w.invoicePart').' '.$invoice->getNumber();
            $template = 'app/mail/invoice/part_invoice.html.twig';
            $attachName = $translator->trans('w.invoicePart').'-'.$invoice->getNumber().'.pdf';
        } elseif ('rest' === $type) {
            $order = $invoice->getInvoiceOrder();
            $subject = $this->parameter['sf']['name'].' '.$translator->trans('w.invoiceRest').' '.$invoice->getNumber();
            $template = 'app/mail/invoice/invoice.html.twig';
            $attachName = $translator->trans('w.invoiceRest').'-'.$invoice->getNumber().'.pdf';
        } elseif ('individual' === $type) {
            $subject = $this->parameter['sf']['name'].' Rechnung '.$invoice->getNumber();
            $template = 'app/mail/invoice/invoice_individual.html.twig';
            $attachName = $translator->trans('w.invoice').'-'.$invoice->getNumber().'.pdf';
        } else {
            return false;
        }

        $customer = $invoice->getCustomer();
        /** @var string $body */
        $body = $this->twig->render($template, [
            'invoice' => $invoice,
            'customer' => $customer,
            'order' => $order ?? null,
        ]);

        return $this->sendMail($this->getName($customer), $this->getEmail($customer), $subject, $body, [['file' => $pdf, 'name' => $attachName]], 'slack_rechnung');
    }

    public function sendIndividualInvoice(Invoice $invoice, string $pdf, string $emailtext): bool
    {
        $invoice->getInvoiceOrder();

        $subject = $this->parameter['sf']['name'].' - Rechnung '.$invoice->getNumber();

        $template = 'app/mail/invoice/invoice_individual.html.twig';
        $attachName = 'Rechnung-'.$invoice->getNumber().'.pdf';

        $customer = $invoice->getCustomer();
        /** @var string $body */
        $body = $this->twig->render($template, [
            'invoice' => $invoice,
            'customer' => $customer,
            'emailtext' => $emailtext,
        ]);

        return $this->sendMail($this->getName($customer), $this->getEmail($customer), $subject, $body, [['file' => $pdf, 'name' => $attachName]], 'slack_rechnung');
    }

    public function sendIndividualMail(
        Email $email,
        string $template,
    ): bool {
        $subject = $this->parameter['sf']['name'].' - '.$email->getTitle();
        /** @var string $body */
        $body = $this->twig->render($template, [
            'email' => $email,
            'customer' => $email->getCustomer(),
            'emailtext' => $email->getMessage(),
            'title' => $email->getTitle(),
        ]);
        $customer = $email->getCustomer();

        if (!empty($email->getAttachment())) {
            return $this->sendMail($this->getName($customer), $this->getEmail($customer), $subject, $body, [
                ['file' => $email->getAttachment(), 'name' => $email->getAttachmentName()], 'slack_emails',
            ]
            );
        }

        return $this->sendMail($this->getName($customer), $this->getEmail($customer), $subject, $body, [], 'slack_emails');
    }

    private function getName(Customer $customer): string
    {
        return $customer->getFullNormalName();
    }

    private function getEmail(Customer $customer): string
    {
        return $customer->getEmail();
    }
}
