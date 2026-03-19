<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Image;
use App\Entity\Invoice;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferItem;
use App\Entity\OfferQuestion;
use App\Entity\OfferSubCategory;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProjectTeam;
use App\Entity\ProjectTeamCategory;
use App\Entity\User;
use App\Form\OfferEmptyWallboxType;
use App\Repository\InvoiceRepository;
use App\Service\FileUploader;
use App\Service\PHPMailerService;
use App\Service\PriceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AjaxController.
 */
#[Route(path: '/ajax/ajax-offer')]
class AjaxOfferController extends BaseController
{
    public function __construct(private string $offersDirectory, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain)
    {
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/{id}/offer-product', name: 'ajax_product', methods: ['GET', 'POST'])]
    public function getProduct(Product $product): JsonResponse
    {
        return $this->json($product->toArray());
    }

    #[Route(path: '/wallbox-products', name: 'ajax_wallbox_products', methods: ['GET', 'POST'])]
    public function getWallboxProducts(Request $request): JsonResponse
    {
        $products = $this->em->getRepository(Product::class)->findByNameField($request->query->get('term'));
        $ep = [];
        /** @var Product $p */
        foreach ($products as $p) {
            $name = $p->getName().' ('.$p->getProductSubCategory()->getName().') '.$p->getKw();

            $ep[] = [
                'label' => trim($name),
                'value' => $p->getId(),
            ];
        }

        return new JsonResponse(json_encode($ep));
    }

    #[Route(path: '/{id}/upload-document', name: 'ajax_upload_document', methods: ['GET', 'POST'])]
    public function uploadDocument(Request $request, FileUploader $fileUploader, Offer $offer): Response
    {
        $file = $request->files->all()['file'];
        if ($file instanceof UploadedFile) {
            $name = $fileUploader->upload($file, 'doc', $offer->getId().'_doc_'.count($offer->getDocuments()), $offer);
            if (false === $name) {
                $this->addFlash('danger', 'Datei kann nicht geladen werden');

                return $this->json('Die Datei konnte nicht geladen werden!');
            }
            $document = new Document();
            $document->setOffer($offer);
            $document->setOriginalName($file->getClientOriginalName());
            $document->setMimeType($file->getClientMimeType());
            $document->setFilename($name);
            $document->setUpdated(new \DateTime());
            $offer->addDocument($document);
            $this->em->persist($offer);
            $this->em->persist($document);
            $this->em->flush();
            $this->em->refresh($document);
        }

        return $this->render('offer/montage/components/'.(!empty($query['type']) && 'request' == $query['type'] ? 'request' : 'doc').'_box.html.twig', [
            'doc' => $document,
            'offer' => $offer,
            'docsCount' => count($offer->getDocuments()),
        ]);
    }

    #[Route(path: '/{id}/upload-request-document', name: 'ajax_upload_request_document', methods: ['POST'])]
    public function uploadRequestDocument(Request $request, FileUploader $fileUploader, Offer $offer, string $hdDir): Response
    {
        $query = $request->query->all();
        $file = $request->files->all()['file'];
        if ($file instanceof UploadedFile) {
            if (!empty($query['type']) && 'request' == $query['type']) {
                $fileUploader->setUniqueId($offer->getSubCategory()->getId().'');
                $name = $fileUploader->upload($file, 'request', $offer->getId().'_request_'.$query['typeId'], $offer);

                $context = $offer->getContext();
                $context['request'][$query['typeId']]['name'] = $name;
                $context['request'][$query['typeId']]['offerId'] = $offer->getId();
                $context['request'][$query['typeId']]['typeId'] = $query['typeId'];
                $offer->setContext($context);
            } else {
                $name = $fileUploader->upload($file, 'doc', $offer->getId().'_doc_'.count($offer->getDocuments()), $offer);
            }
            $document = new Document();
            $document->setOffer($offer);
            $document->setOriginalName($file->getClientOriginalName());
            $document->setMimeType($file->getClientMimeType());
            $document->setFilename($name.'');
            $document->setUpdated(new \DateTime());
            $offer->addDocument($document);
            $this->em->persist($offer);
            $this->em->persist($document);
            $this->em->flush();
            $this->em->refresh($document);
            if (!empty($query['type']) && 'request' == $query['type']) {
                return $this->render('offer/montage/components/request_box.html.twig', [
                    'doc' => $document,
                    'offer' => $offer,
                    'docsCount' => count($offer->getDocuments()),
                ]);
            }
        }

        return $this->render('offer/montage/components/doc_box.html.twig', [
            'doc' => $document,
            'offer' => $offer,
            'docsCount' => count($offer->getDocuments()),
        ]);
    }

    #[Route(path: '/{id}/upload-antrags-document/{count}', name: 'ajax_upload_antrag', methods: ['GET', 'POST'])]
    public function uploadAntragsDocument(Request $request, FileUploader $fileUploader, Offer $offer, int $count): Response
    {
        if (empty($offer->getSubCategory()->getContext())) {
            return $this->json('');
        }
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile) {
            if (!empty($offer->getSubCategory()->getContext())) {
                $name = $fileUploader->upload($file, 'doc', $offer->getSubCategory()->getContext()[$count].'_'.$offer->getId().'_'.$count, $offer);
            }
            $document = new Document();
            $document->setOffer($offer);
            $document->setOriginalName($file->getClientOriginalName());
            $document->setMimeType($file->getClientMimeType());
            $document->setFilename($name);
            $document->setType('antrag');
            $document->setTypeId($count);
            $document->setUpdated(new \DateTime());
            $offer->addDocument($document);
            $this->em->persist($offer);
            $this->em->persist($document);
            $this->em->flush();
            $this->em->refresh($document);
        }

        return $this->render('offer/montage/components/doc_box.html.twig', [
            'doc' => $document,
            'offer' => $offer,
            'docsCount' => count($offer->getDocuments()),
        ]);
    }

    #[Route(path: '/{id}/upload-image', name: 'ajax_upload_image', methods: ['GET', 'POST'])]
    public function uploadImage(Request $request, FileUploader $fileUploader, Offer $offer): Response
    {
        $temp = 'offer/montage/components/im_box.html.twig';
        $files = $request->files->all();
        $file = $files['file'];
        if (!$file instanceof UploadedFile) {
            throw new \Symfony\Component\HttpFoundation\Exception\BadRequestException('Fehlerhafter Upload');
        }
        if ($file instanceof UploadedFile && empty($request->query->get('name'))) {
            $name = $fileUploader->upload($file, 'protocol', $offer->getId().'_img_'.count($offer->getImages()), $offer);
        } elseif ($file instanceof UploadedFile) {
            $temp = 'offer/montage/components/im_box_check.html.twig';
            $fileName = $request->query->get('name');
            $name = $fileUploader->upload($file, 'protocol', $fileName, $offer);
        }
        $document = new Image();
        $document->setOffer($offer);
        $document->setOriginalName($file->getClientOriginalName());
        $document->setMimeType($file->getClientMimeType());
        $document->setFilename($name);
        $document->setUpdated(new \DateTime());
        $offer->addImage($document);
        $this->em->persist($offer);
        $this->em->persist($document);
        $this->em->flush();
        $this->em->refresh($document);

        return $this->render($temp, [
            'img' => $document,
            'offer' => $offer,
            'imagesCount' => count($offer->getImages()),
        ]);
    }

    #[Route(path: '/{id}/upload/{question}', name: 'ajax_upload_images', methods: ['GET', 'POST'])]
    public function uploadImages(Request $request, FileUploader $fileUploader, Offer $offer, OfferQuestion $question): Response
    {
        $file = $request->files->get('file');
        if ($file instanceof UploadedFile) {
            $name = $fileUploader->upload($file, 'question', $offer->getId().'_'.$question->getId().'-'.count($question->getImages()), $offer);
            $image = new Image();
            $image->setOffer($offer);
            $image->setQuestion($question);
            $image->setOriginalName($file->getClientOriginalName());
            $image->setMimeType($file->getClientMimeType());
            $image->setFilename($name);
            $image->setUpdated(new \DateTime());
            $offer->addImage($image);
            $question->addImage($image);
            $this->em->persist($offer);
            $this->em->persist($image);
            $this->em->persist($question);
            $this->em->flush();
        }

        return $this->render('offer/montage/components/img_box.html.twig', [
            'img' => $image,
            'question' => $question,
            'offer' => $offer,
            'imagesCount' => count($question->getImages()),
        ]);
    }

    #[Route(path: '/{id}/update/subCategory/{subCategory}', name: 'ajax_offer_update_subCategory', methods: ['POST', 'GET'])]
    public function updateSubCategory(Offer $offer, OfferSubCategory $subCategory): Response
    {
        $offer->setSubCategory($subCategory);
        $option = $offer->getOption();
        $context = $option ? $offer->getOption()->getContext() : [];
        if (isset($context['kv'])) {
            unset($context['kv']);
            $option->setContext($context);
            $this->em->persist($option);
        }
        $this->em->persist($offer);
        $this->em->flush();

        return $this->redirectToRoute('offer_show', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route(path: '/{id}/update/category/{category}', name: 'ajax_offer_update_category', methods: ['POST', 'GET'])]
    public function updateCategory(Offer $offer, OfferCategory $category): Response
    {
        $offer->setCategory($category);
        $option = $offer->getOption();
        $context = $option ? $offer->getOption()->getContext() : [];
        if (isset($context['kv'])) {
            unset($context['kv']);
            $option->setContext($context);
            $this->em->persist($option);
        }
        $this->em->persist($offer);
        $this->em->flush();

        return $this->redirectToRoute('offer_show', ['id' => $offer->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route(path: '/{id}/update/option-context', name: 'ajax_update_offer_option_context', methods: ['POST'])]
    public function updateOfferOptionContext(Request $request, Offer $offer): JsonResponse
    {
        $post = $request->request->all();
        $option = $offer->getOption();
        $postContext = $post['offer_option']['context'];
        $offerContext = $option->getContext();
        $this->addOptionContext($postContext ?? [], $offerContext);
        $option->setContext($offerContext);
        $this->em->persist($option);
        $this->em->flush();

        return $this->json($offerContext);
    }

    #[Route(path: '/{id}/update/context', name: 'ajax_update_context', methods: ['POST'])]
    public function updateOfferContext(Request $request, Offer $offer): Response
    {
        $post = $request->request->all();
        $postContext = $post['offer_context']['invoice_pay'];
        $offerContext = $offer->getContext();
        foreach ($offerContext as $key => $pc) {
            switch ($key) {
                case 'invoice_pay':
                    unset($offerContext['invoice_pay']);
                    foreach ($postContext as $ki => $invoicePercent) {
                        if (isset($postContext[$ki]['name'])) {
                            $offerContext[$key][$ki]['name'] = $postContext[$ki]['name'];
                        }
                        if (isset($postContext[$ki]['value'])) {
                            if (0 == $ki) {
                                $offer->getOption()->setInvoicePercent((float) $postContext[$ki]['value']);
                                $this->em->persist($offer->getOption());
                            }
                            $offerContext[$key][$ki]['value'] = $postContext[$ki]['value'];
                        }
                    }
                    break;
            }
        }
        $offer->setContext($offerContext);
        $this->em->persist($offer);
        $this->em->flush();

        return $this->render('app/offer_new/zahlungsplan.html.twig', ['offer' => $offer]);
    }

    #[Route(path: '/{id}/update/montage_context', name: 'ajax_update_montage_context', methods: ['POST'])]
    public function updateMontageContext(Request $request, Offer $offer): Response
    {
        $postContext = $request->request->all();
        $offerContext = $offer->getInquiry()->getContext();
        foreach ($offerContext as $k => $context) {
            if (!empty($postContext['ok'])) {
                $offerContext[$k]['questions'][$postContext['question']]['monteur']['ok'] = 1;
                $offerContext[$k]['questions'][$postContext['question']]['monteur']['text'] = $postContext['text'] ?? '';
            } else {
                $offerContext[$k]['questions'][$postContext['question']]['monteur']['ok'] = 0;
                $offerContext[$k]['questions'][$postContext['question']]['monteur']['text'] = $postContext['text'] ?? '';
            }
        }

        $iq = $offer->getInquiry();
        $iq->setContext($offerContext);
        $this->em->persist($iq);
        $this->em->flush();

        return $this->json('success');
    }

    #[Route(path: '/{id}/update/note', name: 'ajax_update_offer_note', methods: ['POST'])]
    public function updateOfferNote(Request $request, Offer $offer): JsonResponse
    {
        $offers = $offer->getCustomer()->getOffers();

        /** @var User $user */
        $user = $this->getUser();
        foreach ($offers as $offered) {
            if ($offered->getId() === $offer->getId()) {
                $offer->setNote(empty($request->request->get('note')) ? null : $request->request->get('note'));
                $offer->setStatusDate(new \DateTime());
                $this->em->persist($offer);
                $this->deleteOfferDependCache($offer, $user);
            } else {
                $date = new \DateTime();
                $line = $date->format('d.m H:i').'-'.$user->getUsername().'. Notiz in '.$offer->getNumber()."̛̉\n";
                $note = $offered->getNote();
                $offered->setNote($line.$note);
                $this->em->persist($offered);
                $this->deleteOfferDependCache($offered, $offered->getUser());
            }

            /** @var User $user */
            $user = $this->getUser();
            $this->deleteOfferDependCache($offer, $user);
        }
        $this->em->flush();

        return $this->json($request->request->get('note'));
    }

    #[Route(path: '/{id}/update/product_box', name: 'update_offer_box', methods: ['POST'])]
    public function updateOfferProduct(Request $request, Offer $offer): Response
    {
        $wb = $request->request->all();
        $wb = $wb['offer_wallbox'] ?? [];
        if (!empty($wb)) {
            $product = $this->em->getRepository(Product::class)->find($wb['wallboxProduct']);
            if ($product instanceof Product) {
                $offer->setWallboxProduct($product);
                $offer->setAmount((int) $wb['amount']);
                $offer->setWallboxPrice((float) str_replace(',', '.', $wb['wallboxPrice']));
                $offer->setStatusDate(new \DateTime());
                $this->em->persist($offer);
                $this->em->flush();
                /** @var User $user */
                $user = $this->getUser();
                $this->deleteOfferDependCache($offer, $user);
            }
        }
        $form = $this->createForm(OfferEmptyWallboxType::class, $offer);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->em;
            $em->persist($offer);
            /** @var User $user */
            $user = $this->getUser();
            $this->deleteOfferDependCache($offer, $user);
            $em->flush();
        }

        return $this->redirectToRoute('offer_show', ['id' => $offer->getId()]);
    }

    #[Route(path: '/{id}/delete/orderItem', name: 'ajax_product_delete_offerItem', methods: ['POST'])]
    public function deleteOrderItem(OfferItem $offerItem): JsonResponse
    {
        try {
            $offerItem->setOffer(null);
            $offerItem->setItem(null);
            $this->em->remove($offerItem);
            $this->em->flush();
        } catch (\Exception $exception) {
            return $this->json(false);
        }

        return $this->json(true);
    }

    #[Route(path: '/{id}/update/orderItem', name: 'ajax_product_update_offerItem', methods: ['POST'])]
    public function updateOrderItem(Request $request, OfferItem $offerItem): JsonResponse
    {
        $postItem = $request->request->all();
        $postItem = $postItem['offer_item'] ?? null;
        if (isset($postItem['price']) && isset($postItem['amount'])) {
            $offerItem->setAmount((int) $postItem['amount']);
            $offerItem->setDescription($postItem['description'] ?? '');
            $offerItem->setName($postItem['name'] ?? '');
            $offerItem->setPrice((float) str_replace(',', '.', (string) $postItem['price']));
            $this->em->persist($offerItem);
            $this->em->flush();

            return $this->json(true);
        }

        return $this->json(false);
    }

    #[Route(path: '/{offer}/update/offer/user/{user}', name: 'ajax_update_offer_user', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE_SERVICE')]
    public function updateOfferUser(Offer $offer, User $user): JsonResponse
    {
        try {
            $offer->setUser($user);
            $in = $offer->getInquiry();
            $in->setUser($user);
            $this->em->persist($in);
            $this->em->persist($offer);
            $this->em->flush();
            $this->deleteOfferDependCache($offer, $user);
        } catch (\Exception $exception) {
            // dump($exception);die;
        }

        return $this->json(true);
    }

    #[Route(path: '/{offer}/update/offer/team/{team?}', name: 'ajax_update_offer_team', defaults: ['team' => 0], methods: ['POST'])]
    public function updateOfferTeam(Request $request, Offer $offer, int $team = 0): JsonResponse
    {
        if (0 !== $team) {
            $montageTeam = $this->em->getRepository(ProjectTeam::class)->find($team);
            if ($montageTeam instanceof ProjectTeam) {
                $offer->addProjectTeam($montageTeam);
                $this->em->persist($offer);
                $this->em->flush();
            }
        } elseif ($request->query->get('tc')) {
            $montageTeams = $this->em->getRepository(ProjectTeam::class)->findBy([
                'offer' => $offer,
                'category' => $this->em->getRepository(ProjectTeamCategory::class)->find($request->query->get('tc')),
            ]);
            if ($montageTeams) {
                foreach ($montageTeams as $montageTeam) {
                    $offer->removeProjectTeam($montageTeam);
                }
                $this->em->persist($offer);
                $this->em->flush();
            }
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->deleteOfferDependCache($offer, $user);

        return $this->json(true);
    }

    #[Route(path: '/{offer}/update/offer/monteur/{user}', name: 'ajax_update_offer_monteur', defaults: ['user' => 0], methods: ['POST'])]
    public function updateOfferMonteur(Offer $offer, int $user): JsonResponse
    {
        if (0 === $user) {
            $offer->setMonteur(null);
        } else {
            $u = $this->em->getRepository(User::class)->find($user);
            $offer->setMonteur($u);
        }
        $this->em->persist($offer);
        $this->em->flush();

        return $this->json(true);
    }

    #[Route(path: '/{offer}/update/offer/tax-{tax}', name: 'ajax_update_offer_tax', methods: ['POST'])]
    public function updateOfferTax(Offer $offer, int $tax): JsonResponse
    {
        $offer->setTax($tax);
        $this->em->persist($offer);
        $this->em->flush();

        return $this->json(true);
    }

    #[Route(path: '/{offer}/update/offer-image', name: 'ajax_update_offer_image', methods: ['POST'])]
    public function updateOfferimage(Request $request, FileUploader $fileUploader, Offer $offer): JsonResponse
    {
        try {
            $offerImage = $request->files->get('offerImage');
            if ($offerImage instanceof UploadedFile) {
                if ($offer->getImage()) {
                    @unlink($this->offersDirectory.'/'.$offer->getId().'/image/'.$offer->getImage());
                }
                $fileName = 'offerimage-'.$offer->getId();
                $offerImageFileName = $fileUploader->upload($offerImage, 'offerImage', $fileName, $offer);
                $offer->setImage($offerImageFileName);
                $this->em->persist($offer);
                $this->em->flush();
            }
        } catch (\Exception $exception) {
            return $this->json(false);
        }

        return $this->json(true);
    }

    #[Route(path: '/{offer}/delete/offer-image', name: 'ajax_delete_offer_image', methods: ['POST'])]
    public function deleteOfferimage(Offer $offer): JsonResponse
    {
        try {
            if ($offer->getImage()) {
                @unlink($this->offersDirectory.'/'.$offer->getId().'/image/'.$offer->getImage());
            }
            $offer->setImage(null);
            $this->em->persist($offer);
            $this->em->flush();
        } catch (\Exception $exception) {
        }

        return $this->json(true);
    }

    #[Route(path: '/get/offer-stats/3', name: 'ajax_offer_stats', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYEE_SERVICE')]
    public function getDashOffer(PriceService $priceService): JsonResponse
    {
        $date = new \DateTime();
        $s = clone $date;
        $date->setTime(0, 0);
        $date->modify('-12 month');
        $e = new \DateTime();
        /** @var InvoiceRepository $repo */
        $repo = $this->em->getRepository(Invoice::class);
        $offerValues = [];
        while ($date < $e) {
            $time = $date->format('U') * 1000;
            $invoicesPartPayed = $repo->findInvoice($date, 'part');
            $invoicesRestPayed = $repo->findInvoice($date, 'rest');

            $part = 0;
            $rest = 0;

            $offers = [];
            foreach ($invoicesPartPayed as $b) {
                $id = $b->getInvoiceOrder()->getId();
                $offers[$id] = $priceService->calculate70Brutto($b->getInvoiceOrder()->getOffer());
                $part += $offers[$id];
            }
            foreach ($invoicesRestPayed as $c) {
                $id = $c->getInvoiceOrder()->getId();
                $rd = $priceService->calculatePrice($c->getInvoiceOrder()->getOffer());
                $rest += $rd;
                if (isset($offers[$id])) {
                    $rest -= $offers[$id];
                } else {
                    $pd = $repo->findOneBy([
                        'type' => 'part',
                        'invoiceOrder' => $c->getInvoiceOrder()->getId(),
                    ]);
                    if ($pd instanceof Invoice && null !== $pd->getBezahlt()) {
                        $rest -= $priceService->calculate70Brutto($pd->getInvoiceOrder()->getOffer());
                    }
                }
            }

            $offerValues[] = [
                $time,
                round($part, 2),
                round($rest, 2),
                count($invoicesPartPayed) + count($invoicesRestPayed),
            ];
            $date->modify('+1 day');
        }
        $dataTo = $s->format('U');
        $dataLimitFrom = $date->format('U');
        $offerArray = [
            'unit' => 'M',
            'dataLimitFrom' => $dataLimitFrom * 1000,
            'dataLimitTo' => $dataTo * 1000,
            'from' => $dataLimitFrom * 1000,
            'to' => $dataTo * 1000,
            'info' => [
                'Datum',
                'Abschlag-Bezahlt',
                'Abschluss-Bezahlt',
                'Anzahl Aufträge',
            ],
            'values' => $offerValues,
        ];

        return $this->json($offerArray);
    }

    #[Route(path: '/{id}/update/status', name: 'ajax_update_offer_status', methods: ['POST'])]
    public function updateOfferStatus(Request $request, Offer $offer): JsonResponse
    {
        $status = $request->request->get('status');
        if (empty($status) && !empty($request->getContent())) {
            $content = json_decode($request->getContent());
            $status = $content->status;
        }

        if (!empty($status)) {
            $order = $offer->getOrder();
            $allow = match ($offer->getStatus()) {
                'bestaetigt', 'open', 'call', 'call-plus', 'storno' => ['storno'],
                'estimate' => ['besichtigung', 'open', 'storno'],
                'besichtigung' => ['open', 'storno'],
                'gesendet' => ['bestaetigt', 'storno'],
                'rechnungPartSend' => ['rechnungPartEingang', 'storno'],
                'rechnungPartEingang' => ['work', 'storno'],
                'work' => ['done', 'storno'],
                'done' => ['rechnungEingangSend', 'storno'],
                'rechnungEingangSend' => ['rechnungEingang', 'storno'],
                'rechnungEingang' => ['archive'],
                default => ['storno'],
            };
            /** @var Order $order */
            if ($order instanceof Order) {
                if (in_array($status, $allow)) {
                    $date = new \DateTime();
                    if ('kostenvoranschlag' === $status) {
                        // $order->setSendPreOfferAt($date);
                    } elseif ('bestaetigt' === $status) {
                        $order->setBestaetigt(true);
                    } elseif ('rechnungEingang' === $status || 'rechnungPartEingang' == $status) {
                        $invoices = $order->getInvoices();
                        $datum = new \DateTime();
                        /** @var Invoice $invoice */
                        foreach ($invoices as $invoice) {
                            if ('part' === $invoice->getType() && 'rechnungPartEingang' == $status) {
                                $invoice->setBezahlt($datum);
                                $this->em->persist($invoice);
                            } elseif ('rest' === $invoice->getType() && 'rechnungEingang' == $status) {
                                $invoice->setBezahlt($datum);
                                $this->em->persist($invoice);
                            }
                        }
                    }
                    $offer->setStatus($status);
                    if (empty($offer->getProjectTeams())) {
                        $team = $this->em->getRepository(ProjectTeam::class)->findOneBy([
                            'isDefault' => true,
                        ]);
                        $offer->addProjectTeam($team);
                    }
                    $this->em->persist($offer);
                    $this->em->persist($order);
                    $offer->setStatusDate($date);
                    $this->em->flush();

                    return $this->json(true);
                }
            } elseif (in_array($status, $allow)) {
                $offer->setStatus($status);
                $offer->setStatusDate(new \DateTime());
                $this->em->persist($offer);
                $this->em->flush();

                return $this->json(true);
            }
        }

        return $this->json(false);
    }

    #[Route(path: '/{id}/add/orderItem', name: 'ajax_product_add_offerItem', methods: ['POST'])]
    public function addOrderItem(Request $request, Offer $offer): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $postItem = $request->request->all();
        $postItem = $postItem['offer_item'] ?? null;
        if (!empty($postItem['item']) && isset($postItem['amount'])) {
            $this->deleteOfferDependCache($offer, $user);
            /** @var Product $item */
            $item = $this->em->getRepository(Product::class)->find($postItem['item']);

            $offerItem = new OfferItem();
            $offerItem->setItem($item);
            $offerItem->setOffer($offer);
            $offerItem->setAmount((int) $postItem['amount']);
            $offerItem->setDescription($item->getDescription());
            $offerItem->setTax(19);
            $offerItem->setPrice($item->getPrice());
            $offer->addOfferItem($offerItem);
            $this->em->persist($offerItem);
            $offer->setStatusDate(new \DateTime());

            $this->em->persist($offer);
            $this->em->flush();

            return $this->json(true);
        }

        return $this->json(false);
    }

    #[Route(path: '/{offer}/update/{type}', name: 'ajax_update_tp', methods: ['POST'])]
    public function updateSolar(Request $request, Offer $offer, string $type): JsonResponse
    {
        try {
            $option = $offer->getOption();
            switch ($type) {
                case 'rabatt':
                    $rabatt = is_numeric($request->request->get('rabat')) ? (float) $request->request->get('rabat') : 0;
                    $offer->setRabat($rabatt);
                    break;
                case 'percent': // not in use
                    $per = $request->request->get('percent', 0);
                    $percent = empty($per) || !is_numeric($per) ? 0 : (int) $per;
                    $option->setInvoicePercent($percent);
                    $this->em->persist($option);
                    break;
                case 'notice':
                    $offer->setNotice($request->request->get('notice'));
                    break;
                case 'call':
                    $option->setCalled(!empty($request->query->get('called')));
                    $this->em->persist($option);
                    break;
                case 'solar':
                    $option->setSolar(!empty($request->query->get('solar')));
                    $this->em->persist($option);
                    break;
                case 'pay':
                    $tis = $offer->getOrder()->getTeilInvoice();
                    $invoice = $tis[0];
                    $invoice->setBezahlt(new \DateTime());
                    $this->em->persist($invoice);
                    $this->em->flush();

                    return $this->json(true);
                    break;
                case 'urgent':
                    $offer->setUrgent(!empty($request->query->get('urgent')));
                    break;
                case 'delete':
                    $offer->setDeleteIt(!empty($request->query->get('deleteIt')));
                    break;
                case 'view-status':
                    $status = $request->request->get('status');
                    $options = $offer->getOption();
                    if (!empty($status)) {
                        $options->setBlendOut(true);
                    } else {
                        $options->setBlendOut(false);
                    }
                    $this->em->persist($options);
                    break;
                default:
                    return $this->json(false);
            }
            $offer->setStatusDate(new \DateTime());
            $this->em->persist($offer);
            $this->em->flush();
        } catch (\Exception $exception) {
            return $this->json(false);
        }

        return $this->json(true);
    }

    #[Route(path: '/{invoice}/update-invoice/', name: 'ajax_update_offer_invoice', methods: ['POST'])]
    public function updateInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        $invoice->setBezahlt(new \DateTime());
        $this->em->persist($invoice);
        $this->em->flush();

        return $this->json(true);
    }
}
