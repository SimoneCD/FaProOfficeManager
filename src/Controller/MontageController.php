<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ActionLog;
use App\Entity\Document;
use App\Entity\Image;
use App\Entity\Offer;
use App\Entity\Protocol;
use App\Entity\User;
use App\Form\ActionLogType;
use App\Form\OfferOrderType;
use App\Repository\OfferRepository;
use App\Service\PHPMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * auftragsblätter.
 */
#[Route(path: ['de' => '/vor-ort', 'en' => '/on-site'])]
class MontageController extends BaseController
{
    use TargetPathTrait;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, private string $docDirectory)
    {
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/', name: 'sf_montage', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function index(Request $request, string $protocolDirectory): Response
    {
        //        if(!$this->isGranted('ROLE_ADMIN') && ($url = $this->checkUserTime($request)) !== false) {
        //            return new RedirectResponse($url);
        //        }
        if (!$this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            $auftrag = $request->request->get('_offerNr') ?? '';
            // dd(0);
            $auftrage = $this->em->getRepository(Offer::class)->findProtocolByMonteur($this->getUser());
        } else {
            $auftrag = $request->request->get('_offerNr') ?? '';
            $post = $request->query->get('all');
            if (!empty($post) && 'all' === $post) {
                $auftrage = $this->em->getRepository(Offer::class)->findProtocolByAll();
            } elseif (empty($post)) {
                $auftrage = $this->em->getRepository(Offer::class)->findProtocolByMonteur($this->getUser());
            } else {
                if ($request->query->get('all')) {
                    $user = $this->em->getRepository(User::class)->find($request->query->get('all'));
                } else {
                    $user = $this->em->getRepository(User::class)->find($this->getUser());
                }
                $auftrage = $this->em->getRepository(Offer::class)->findProtocolByMonteur($user);
            }
        }

        $offer = null;

        if (!empty($auftrag)) {
            $offer = $this->em->getRepository(Offer::class)->findOneBy([
                'number' => $auftrag,
            ]);
            if ($offer instanceof Offer) {
                $form = $this->createForm(OfferOrderType::class, $offer);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    /** @var Offer $data */
                    $data = $form->getData();
                    foreach ($data->getImages() as $file) {
                        $file->upload($offer, $protocolDirectory);
                        $this->em->persist($file);
                        $offer->addImage($file);
                    }
                    $this->em->persist($offer);
                    $this->em->flush();
                }
            }
        }

        return $this->render('offer/montage/index.html.twig', [
            'users' => $this->getThisUser(),
            'offer' => $offer,
            'form' => isset($form) ? $form->createView() : null,
            'montage' => true,
            'auftraege' => $auftrage,
            'notes' => empty($offer) ? [] : $offer->getLogs(),
        ]);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: ['de' => '/suche', 'en' => '/search'], name: 'sf_montage_search', methods: ['GET'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function montageSearch(Request $request, OfferRepository $offerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('offer/montage/index.html.twig', [
            'offer' => null,
            'form' => isset($form) ? $form->createView() : null,
            'montage' => true,
            'auftraege' => $offerRepository->findMontageBySearch($request->query->get('search'), $user),
            'users' => $this->getServiceUsers(),
            'user' => $user,
            'search' => true,
        ]);
    }

    #[Route(path: ['de' => '/protokoll/{id}', 'en' => '/protocol/{id}'], name: 'offer_auftrags_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function auftragsBlatt(Request $request, Offer $offer, string $protocolDirectory): Response
    {
        //        if (empty($offer->getStationLat())) {
        //            $lat = $this->getAddresCoordinates($offer->getStationAddress(), $offer->getStationZip());
        //            if (!empty($lat['data'])) {
        //                $offer->setStationLat($lat['data'][0]['latitude'].'');
        //                $offer->setStationLng($lat['data'][0]['longitude'].'');
        //                $this->em->persist($offer);
        //            }
        //        }
        $offers = [];
        if (!empty($offer->getStationLat())) {
            $offers = $this->em->getRepository(Offer::class)->findCustomersNearby($offer->getStationLat(), $offer->getStationLng(), 20);
        }

        $stageAddress = $offer->getStationAddress();
        $stageZip = $offer->getStationZip();

        $form = $this->createForm(OfferOrderType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Offer $data */
            $data = $form->getData();
            //            foreach ($data->getImages() as $image) {
            //                $image->upload($offer, $protocolDirectory);
            //            }
            //            foreach ($data->getDocuments() as $document) {
            //                $document->upload($offer, $protocolDirectory);
            //            }
            if ($data->getStationAddress() != $stageAddress || $data->getStationZip() != $stageZip) {
                $lat = $this->getAddresCoordinates($data->getStationAddress(), $data->getStationZip());
                if (!empty($lat['data'])) {
                    $data->setStationLat($lat['data'][0]['latitude'].'');
                    $data->setStationLng($lat['data'][0]['longitude'].'');
                    $this->em->persist($data);
                }
            }
            $this->addFlash('success', $this->translator->trans('m.actualized'));
            $this->em->persist($data);
            $this->em->flush();
            //
            //            return $this->redirectToRoute('offer_auftrags_index', ['id' => $offer->getId()]);
        }

        return $this->render('offer/montage/index.monteur.html.twig', [
            'users' => $this->getThisUser(),
            'offer' => $offer,
            'nearBys' => $offers,
            'montage' => false,
            'form' => $form->createView(),
            'auftraege' => null,
            'type' => null,
        ]);
    }

    /**
     * Nicht mehr nutzen
     * Montageblatt ohne Menü / kalender Aufruf.
     */
    #[Route(path: '/installation/{id}', name: 'sf_montage_offer', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function indexOffer(Request $request, Offer $offer): Response
    {
        //        $ofs = $this->em->getRepository(Offer::class)->findBy(['stationLat' => null]);
        //        foreach ($ofs as $o) {
        //            try {
        //                if (!empty($o->getStationAddress()) && empty($offer->getStationLat())) {
        //                    dd($o);
        //                    $lat = $this->getAddresCoordinates($o->getStationAddress(), $o->getStationZip());
        //                    if (isset($lat['data'][0]) && 'deleted' !== $o->getStatus() && 'archive' != $o->getStatus() && 'storno' !== $o->getStatus()) {
        //                        $o->setStationLat($lat['data'][0]['latitude'].'');
        //                        $o->setStationLng($lat['data'][0]['longitude'].'');
        //                        $this->em->persist($o);
        //                        $this->em->flush();
        //                    }
        //                }
        //            } catch (\Exception $exception) {
        //                // dd($exception);
        //            }
        //        }
        $form2 = $this->createForm(ActionLogType::class, new ActionLog());
        // $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('sf_montage_offer', ['id' => $offer->getId()]));
        if (empty($offer->getStationLat()) && !empty($offer->getStationAddress()) && !empty($offer->getStationZip())) {
            $lat = $this->getAddresCoordinates($offer->getStationAddress(), $offer->getStationZip());
            if (is_array($lat) && !empty($lat['data'])) {
                $offer->setStationLat($lat['data'][0]['latitude'].'');
                $offer->setStationLng($lat['data'][0]['longitude'].'');
                $this->em->persist($offer);
                $this->em->flush();
                $this->em->refresh($offer);
                $nearBys = $this->em->getRepository(Offer::class)->findCustomersNearby($offer->getStationLat(), $offer->getStationLng(), 12);
            } else {
                $nearBys = null;
            }
        }
        if (!empty($offer->getStationLat())) {
            // dd($offer->getStationLat());
            $nearBys = $this->em->getRepository(Offer::class)->findCustomersNearby($offer->getStationLat(), $offer->getStationLng(), 12);
        }

        $form2s = $this->createForm(ActionLogType::class, new ActionLog());
        $form = $this->createForm(OfferOrderType::class, $offer);
        $form->handleRequest($request);
        // dd($form->createView());
        $filteredChoices = array_filter(ActionLog::TYPE_CHOICES, function ($choice) {
            return $choice['open'] ?? false;
        });
        $notes = $this->em->getRepository(ActionLog::class)->findOfferNotes($offer);
        usort($notes, function ($a, $b) {
            // Wir nutzen die Zeitstempel direkt, um den Vergleich zu machen
            // Der Vergleich wird so gemacht, dass das neuere Datum zuerst kommt (absteigend)
            return $b->getCreatedAt()->getTimestamp() <=> $a->getCreatedAt()->getTimestamp();
        });

        return $this->render('offer/montage/index.monteur.html.twig', [
            'users' => $this->getThisUser(),
            'offer' => $offer,
            'form2' => $form2s->createView(),
            'nearBys' => $nearBys ?? [],
            'type' => null,
            'teams' => $this->isGranted('ROLE_EMPLOYEE_SERVICE') ? $teams = $this->getTeams() : [],
            // 'cords' => $this->getAddresCoordinates($offer->getStationAddress(), $offer->getStationZip()),
            'form' => $form->createView(),
            'montage' => true,
            'auftraege' => null,
            'protocol' => $this->em->getRepository(Protocol::class)->findBy(['customer' => $offer->getCustomer()]),
            'notes' => $notes,
            'ActionFilterLog' => $filteredChoices,
            'ActionLog' => ActionLog::TYPE_CHOICES,
        ]);
    }

    #[Route(path: '/ajax/montage-image/{id}/remove', name: 'montage_image_remove', methods: ['GET', 'POST'])]
    public function massImageRemove(Image $image): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('security_login', ['last_username' => '']);
        }
        $offer = $image->getOffer();
        if ($offer instanceof Offer) {
            $offer->removeImage($image);
            $this->em->persist($offer);
        }
        $image->setOffer(null);
        $this->em->remove($image);
        $this->em->flush();
        $file = $this->docDirectory.'/'.$image->getFilename();
        try {
            if (file_exists($file)) {
                @unlink($file);

                return new JsonResponse('ok');
            } else {
                return new JsonResponse('not_ok');
            }
        } catch (\Exception $exception) {
            return new JsonResponse('error_not_ok '.$exception->getMessage());
        }
    }

    #[Route(path: '/ajax/montage/auftrags-document/{id}/remove', name: 'montage_document_remove', methods: ['GET', 'POST'])]
    public function massDocumentRemove(Document $document): Response
    {
        if (!$this->getUser() instanceof User) {
            return $this->redirectToRoute('security_login', ['last_username' => '']);
        }
        $offer = $document->getOffer();
        $offer->removeDocument($document);
        $document->setOffer(null);
        $this->em->persist($offer);
        $this->em->remove($document);
        $this->em->flush();
        $file = $this->docDirectory.'/'.$offer->getId().'/'.$document->getFilename();
        try {
            if (file_exists($file)) {
                @unlink($file);

                return new JsonResponse('ok');
            } else {
                return new JsonResponse('not_ok');
            }
        } catch (\Exception $exception) {
            return new JsonResponse('not_ok');
        }
    }

    private function getThisUser(): array
    {
        $newUser = [];
        $users = $this->em->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            if (true === $user->getStatus()) {
                $newUser[] = $user;
            }
        }

        return $newUser;
    }
}
