<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\PushSubscription;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Service\GoogleCalendarService;
use App\Service\GoogleService;
use App\Service\PHPMailerService;
use App\Service\PushNotificationService;
use App\Service\SlackService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: [
    'en' => '/bookings',
    'de' => '/termine',
])]
class BookingController extends BaseController
{
    use TargetPathTrait;

    /** @var GoogleService */
    private $googleService;
    private $pushNotificationService;

    public function __construct(private SlackService $slackService, PushNotificationService $pushNotificationService, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
        $this->pushNotificationService = $pushNotificationService;

        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/', name: 'booking_index', methods: ['GET'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function index(Request $request, BookingRepository $bookingRepository): Response
    {
        if (!$this->isGranted('ROLE_MONTAGE')) {
            return $this->redirectToRoute('public_index');
        }

        return $this->render('booking/index.html.twig', [
            'users' => $this->em->getRepository(User::class)->findBy(['status' => 1]),
        ]);
    }

    #[Route(['de' => '/news', 'en' => '/news-data'], name: 'shotstock', methods: ['GET', 'POST'])]
    public function shotstock(): Response
    {
        return $this->render('shotstock/index.html.twig');
    }

    #[Route(path: [
        'en' => '/all',
        'de' => '/alle',
    ], name: 'booking_ajax', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MONTAGE')]
    public function termineAjax(Request $request, BookingRepository $bookingRepository): Response
    {
        $range_start = new \DateTime();
        $range_start->modify('-1 year');
        $range_end = new \DateTime();
        $range_end->modify('+3 month');
        if ($this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            if ($request->query->get('call')) {
                $termine = $bookingRepository->findTermine($range_start, $range_end, true);
            } else {
                $termine = $bookingRepository->findTermine($range_start, $range_end);
            }
        } else {
            if ($request->query->get('call')) {
                $termine = $bookingRepository->findCallTermine($this->getUser());
            } else {
                $termine = $bookingRepository->findServiceTermine($this->getUser());
            }
        }
        $n = [];
        // $googleClient = $this->googleService->createClient();
        // $calendarService = new \Google_Service_Calendar($googleClient);
        // dd($calendarService);
        //        $listEntry = new Google\Service\Calendar\CalendarListEntry();
        //        $listEntry->setDescription('Simone test');
        //        $event = new Google\Service\Calendar\Event();
        //        $event->setDescription('Hallo Welt test');
        //        $event->setCreated(new \DateTime());
        //        $events = $this->caledarService->events->insert('kundenservice@sf-elektro.info', $event, [
        //            'supportsAttachments' => true
        //        ]);
        //        $calendarId = 'kundenservice@sf-elektro.info';
        //        $optParams = array(
        //            'maxResults' => 10,
        //            'orderBy' => 'startTime',
        //            'singleEvents' => true,
        //            'timeMin' => date('c'),
        //        );
        //        $results = $this->caledarService->events->listEvents($calendarId, $optParams);
        //        $events = $results->getItems();
        //        $calendarId = 'kundenservice@sf-elektro.info';
        //        $optParams = array(
        //            'maxResults' => 10,
        //            'orderBy' => 'startTime',
        //            'singleEvents' => true,
        //            'timeMin' => date('c'),
        //        );
        $class = 'get_call';
        // $icon = 'call';
        $date = new \DateTime();
        foreach ($termine as $termin) {
            switch ($termin->getTitle()) {
                case 'Anrufen':
                    $class = 'get_call';
                    $icon = 'phone';
                    break;
                case 'Besichtigung':
                    $class = 'get_view';
                    $icon = 'camera_outdoor';
                    break;
                case 'Montage/Installation':
                    $class = 'get_install';
                    $icon = 'local_gas_station';
                    break;
                case 'Terminvorschlag':
                    $class = 'get_appointment';
                    $icon = 'event';
                    break;
                case 'Aufgabe':
                    $class = 'get_task';
                    $icon = 'keep_public';
                    break;
                case 'Sonstiges':
                    $class = 'get_else';
                    $icon = 'alt_route';
                    break;
                default:
                    $class = 'get_hollyday';
                    $icon = 'alt_route';
                    break;
            }
            if ($termin->getOffer() instanceof Offer) {
                $offer = $termin->getOffer();
                //                if (empty($offer->getStationLat() || empty($offer->getStationLng()))) {
                //                    $lat = $this->getAddresCoordinates($offer->getStationAddress(), $offer->getStationZip().', Deutschland');
                //                    $offer->setStationLat($lat['data'][0]['latitude'].'');
                //                    $offer->setStationLng($lat['data'][0]['longitude'].'');
                //                    $this->em->persist($offer);
                //                    $this->em->flush();
                //                }
                $customer = $termin->getCustomer();
                $n[] = [
                    'id' => $termin->getId(),
                    'title' => $customer->getFullName().' - '.$termin->getTitle(),
                    'startU' => $termin->getBeginAt()->format(\DateTimeInterface::ATOM),
                    'start' => $termin->getBeginAt()->format('c'),
                    'end' => $termin->getEndAt()->format('c'),
                    'data-termin' => $termin->getId(),
                    'data-offer' => $offer->getId(),
                    'data-number' => $offer->getNumber(),
                    //                    'data-lat' => $offer->getStationLat(),
                    //                    'data-lng' => $offer->getStationLng(),
                    'data-city' => $offer->getStationZip(),
                    'data-customer' => $termin->getCustomer()->getId(),
                    'data-title' => $termin->getTitle(),
                    'classNames' => $class,
                    'description' => $termin->getNotice(),
                    'allDay' => false,
                    'icon' => $icon,
                    'url' => $this->generateUrl('sf_montage_offer', ['id' => $offer->getId()]),
                ];
            } else {
                $n[] = [
                    'id' => $termin->getId(),
                    'title' => $customer->getFullName().' - '.$termin->getTitle(),
                    'startU' => $termin->getBeginAt()->format(\DateTimeInterface::ATOM),
                    'start' => $termin->getBeginAt()->format('c'),
                    'end' => $termin->getEndAt()->format('c'),
                    'data-termin' => $termin->getId(),
                    'data-customer' => $termin->getCustomer()->getId(),
                    'data-title' => $termin->getTitle(),
                    'classNames' => $class,
                    'description' => $termin->getNotice(),
                    'allDay' => false,
                    'icon' => $icon,
                    'url' => '#',
                ];
            }
        }
        $this->em->flush();
        try {
            $responseHollydays = $this->client->request(
                'GET',
                'https://feiertage-api.de/api', [
                    'query' => [
                        'nur_land' => $this->getParameter('app_holidays'),
                        'jahr' => $date->format('Y'),
                    ],
                ],
            );
            $re = $responseHollydays->toArray();
            foreach ($re as $title => $termine) {
                $n[] = [
                    'title' => $title,
                    // 'start' => $termin->getBeginAt()->format('d.m.Y H:i'),
                    'start' => $termine['datum'],
                    'end' => $termine['datum'],
                    'display' => 'background',
                    'data-title' => $title,
                    'classNames' => $class,
                    'description' => $termine['hinweis'],
                    'allDay' => true,
                ];
            }
        } catch (\Exception $e) {
        }

        return $this->json($n);
    }

    #[Route(path: '/{id}/ajax-delete', name: 'booking_delete_ajax', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function deleteJson(Booking $booking): Response
    {
        if (!empty($booking->getGoogleEventId())) {
            $this->googleService->deleteEvent($booking);
        }
        $booking->setCustomer(null);
        $booking->setOffer(null);
        $this->em->remove($booking);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route(path: [
        'en' => '/{offer}/ajax/{customer}/new-appointment',
        'de' => '/{offer}/ajax/{customer}/neuer-termin',
    ], name: 'booking_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYEE_EXTERN')]
    public function newAjax(Request $request, Offer $offer, Customer $customer): Response
    {
        $booking = new Booking();
        $date = new \DateTime();
        $dateE = clone $date;
        $dateE->modify('+3 hours');
        $booking->setBeginAt($date);
        $booking->setEndAt($dateE);
        $booking->setTitle('Anrufen');
        $booking->setOffer($offer);
        $booking->setCustomer($customer);
        $booking->setNotice('');
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setStatusDate($date);
            $post = $request->request->all();
            $bookingRequest = $post['booking'] ?? [];
            $this->setStartTime($booking, $bookingRequest);
            $startTime = clone $booking->getBeginAt();
            if ('dev' !== $_ENV['APP_ENV']) {
                $reminderTime = SlackService::SLACK_REMINDER_TIME;
                $offerUser = $offer->getUser();
            } else {
                $reminderTime = SlackService::SLACK_REMINDER_TIME_TEST;
                $offerUser = $this->em->getRepository(User::class)->find(17); // Simone developer
            }
            if ('Anrufen' === $booking->getTitle()) {
                $startTime->modify($reminderTime['call']);
                $title = 'Erinnerung Anruftermin';
            } elseif ('Montage/Installation' === $booking->getTitle()) {
                $startTime->modify($reminderTime['work']);
                $title = 'Erinnerung Montagetermin';
            } elseif ('Besichtigung' === $booking->getTitle()) {
                $startTime->modify($reminderTime['view']);
                $title = 'Erinnerung Besichtigung';
            } elseif ('Aufgabe' === $booking->getTitle()) {
                $startTime->modify($reminderTime['task']);
                $title = 'Aufgabe';
            } elseif ('Terminvorschlag' === $booking->getTitle()) {
                $startTime->modify($reminderTime['try']);
                $title = 'Terminvorschlag';
            } else {
                $startTime->modify($reminderTime['else']);
                $title = 'Sonstiger Termin';
            }
            $message = $title.' um '.$booking->getBeginAt()->format('H:i').' Uhr'."\n".
                $customer->getFullName()."\n".
                $customer->getAddress()."\n";
            if ($this->getParameter('app_active_log')['slack_active'] and $offerUser->getSlackId()) {
                $messagex = $message.$customer->getZip().' '.$customer->getCity()."\n".
                    '*'.$customer->getPhone().'*'."\n".
                    '<'.$this->generateUrl('sf_montage_offer', ['id' => $offer->getId()], UrlGeneratorInterface::ABSOLUTE_URL).'>';
                $this->slackService->addSlackReminderForUser($offerUser, $messagex, $startTime);
            }

            $this->em->persist($offer);
            if ('Montage/Installation' !== $booking->getTitle() || 'Besichtigung' === $booking->getTitle()) {
                $this->googleService->writeEvent($offer, $booking);
            }
            $this->em->persist($booking);
            $this->em->flush();

            return $this->render('offer/_offer_block_termin.html.twig', [
                'booking' => $booking,
            ]);
        }

        return $this->render('booking/new.html.twig', [
            'booking' => $booking,
            'offer' => $offer,
            'customer' => $customer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/api/customers/search', name: 'api_customers_search')]
    #[IsGranted('ROLE_MONTAGE')]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = $request->query->get('q', '');
        $customers = $em->getRepository(Customer::class)
            ->createQueryBuilder('c')
            ->where('c.name LIKE :query')
            ->orWhere('c.surName LIKE :query')
            ->orWhere('c.email LIKE :query')
            ->setParameter('query', "%$query%")
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->json(array_map(fn ($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'surName' => $c->SurName(),
        ], $customers));
    }

    #[Route('/api/event/create/{id}', name: 'api_event_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, Customer $customer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        /*
         * [
          "title" => "Aufgabe"
          "customerId" => "12"
          "user" => "2"
          "userTask" => null
          "date" => "2025-03-20"
          "beginAt" => "2025-03-20T10:00"
          "endAt" => "2025-03-20T13:00"
          "notice" => "ergregregreg"
        ]
         */
        if (!$customer instanceof Customer) {
            return $this->json(['success' => false, 'error' => 'Kundenangaben fehlen!'], 400);
        }

        $event = new Booking();
        $event->setTitle($data['title']);
        $event->setNotice($data['notice']);
        $event->setCustomer($customer);
        $event->setUser($this->getUser());
        if (!empty($data['user']) && 'Aufgabe' === $data['title']) {
            $taskUser = $this->em->getRepository(User::class)->find($data['user']);
            $event->setUserTask($taskUser);
        } else {
            $event->setUserTask(null);
        }
        $offers = $customer->getOffers();
        if (!empty($offers)) {
            $event->setOffer($offers[count($offers) - 1]);
        }

        $stime = new \DateTime($data['beginAt']);
        $etime = new \DateTime($data['endAt']);
        $event->setBeginAt($stime);
        $event->setEndAt($etime);
        $em->persist($event);
        $em->flush();
        $em->refresh($event);

        if ($event->getUserTask() instanceof User) {
            $this->sendPushNotification($event);
        }

        return $this->json(['success' => true, 'id' => $event->getId()]);
    }

    #[Route('/{id}', name: 'api_bookings_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $booking = $this->em->getRepository(Booking::class)->find($id);
        if (!$booking) {
            return new JsonResponse(['error' => 'Booking not found'], 404);
        }

        return new JsonResponse([
            'id' => $booking->getId(),
            'offer' => $booking->getOffer() ? $this->generateUrl('sf_montage_offer', ['id' => $booking->getOffer()->getId()]) : null,
            'customer_id' => $booking->getCustomer()->getId(),
            'customer_link' => $this->generateUrl('customer_edit', ['id' => $booking->getCustomer()->getId()]),
            'customer_name' => $booking->getCustomer()->getFullNormalName(),
            'begin_at' => $booking->getBeginAt()->format('c'),
            'end_at' => $booking->getEndAt()->format('c'),
            'title' => $booking->getTitle(),
            'user_task' => $booking->getUserTask() ? $booking->getUserTask()->getId() : null,
            'notice' => $booking->getNotice(),
        ]);
    }

    #[Route('/{id}', name: 'api_bookings_update', methods: ['POST'])]
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $start = new \DateTime($data['begin_at']);
        $end = new \DateTime($data['end_at']);
        $booking->setBeginAt($start);
        $booking->setEndAt($end);
        $booking->setNotice($data['notice']);
        if ('Aufgabe' === $data['title'] && !empty($data['user'])) {
            $userTask = $this->em->getRepository(User::class)->find($data['user']);
            if ($userTask instanceof User) {
                if ($booking->getUserTask()->getId() !== $userTask->getId()) {
                    $booking->setUserTask($userTask);
                }
                $this->sendPushNotification($booking);
            }
        } else {
            $booking->setUserTask(null);
        }
        if ($data['customerId'] !== ($booking->getCustomer()->getId() ?: 0)) {
            $customer = $this->em->getRepository(Customer::class)->find($data['customerId']);
            $booking->setCustomer($customer);
        }

        $this->em->flush();

        return new JsonResponse($booking->toArray());
    }

    private function sendPushNotification(Booking $event): void
    {
        $subscription = $this->em->getRepository(PushSubscription::class)->findOneBy(['user' => $event->getUserTask()]);
        if ($subscription instanceof PushSubscription) {
            $title = 'WICHTIG: Neue Aufgabe von '.$event->getUser()->getFullName();
            $body = $event->getNotice()."\n";
            $body .= 'Bitte bis zum '.$event->getBeginAt()->format('d.m.Y H:i').' erledigen';
            $this->pushNotificationService->sendNotification($subscription, $title, $body);
        }
    }

    private function setStartTime(Booking $booking, array $bookingRequest)
    {
        $startDateTime = explode(' ', (string) $bookingRequest['beginAt']);
        $startDate = explode('.', $startDateTime[0]);
        $endDateTime = explode(' ', (string) $bookingRequest['endAt']);
        $endDate = explode('.', $endDateTime[0]);
        if ('Anrufen' == $booking->getTitle() || 'Sonstiges' == $booking->getTitle()) {
            $time = explode(':', $startDateTime[1]);
            $start = new \DateTime(sprintf('%s-%s-%sT%02d:%02d:00', $startDate[2], $startDate[1], $startDate[0], $time[0], $time[1]), new \DateTimeZone('Europe/Berlin'));
            $end = clone $start;
            $end->modify('+15 minutes');
        } else {
            $starttime = explode(':', $startDateTime[1]);
            $start = new \DateTime(sprintf('%s-%s-%sT%02d:%02d:00', $startDate[2], $startDate[1], $startDate[0], $starttime[0], $starttime[1]), new \DateTimeZone('Europe/Berlin'));
            $endtime = explode(':', $endDateTime[1]);
            $end = new \DateTime(sprintf('%s-%s-%sT%02d:%02d:00', $endDate[2], $endDate[1], $endDate[0], $endtime[0], $endtime[1]), new \DateTimeZone('Europe/Berlin'));
        }
        $booking->setBeginAt($start);
        $booking->setEndAt($end);
    }

    /*
     * @param Request $request
     * @return JsonResponse
     * {
     * "offer_id": 123,
     * "customer_id": 456,
     * "title": "Rückruf Termin",
     * "begin_at": "2023-12-01T09:00:00+00:00",
     * "end_at": "2023-12-01T10:00:00+00:00",
     * "type": 2,
     * "notice": "Max Mustermann.  Rückrufbitte zu einer PV Anlage 10kWp.",
     * "additional_data": {
     * "contact_person": "Max Mustermann",
     * "phone": "0123456789"
     * }
     * }
     * bookings/api/extern/new
     */
}
