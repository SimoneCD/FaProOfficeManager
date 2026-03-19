<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Service\GoogleCalendarService;
use App\Service\GoogleService;
use App\Service\PHPMailerService;
use App\Service\SlackService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/extern-partner')]
class ExternApiController extends BaseController
{
    use TargetPathTrait;
    private GoogleService $googleService;
    private array $ips;
    private string $stringVars;

    public function __construct(private SlackService $slackService, EntityManagerInterface $em, HttpClientInterface $client, PHPMailerService $mailerService, TranslatorInterface $translator, string $subdomain, GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
        $this->ips = [
            '90.162.228.131',
            '34.254.1.9',
            '52.31.156.93',
            '52.50.32.186',
            '127.0.0.1',
        ];
        $this->stringVars = '
%s
Datum und Uhrzeit: %s
Inhalt: %s
Zusätzliche Daten:
- Name: %s
- Nachname: %s
- Email: %s
- Telefon: %s
';
        parent::__construct($em, $client, $mailerService, $translator, $subdomain);
    }

    #[Route(path: '/booking/new', name: 'extern_booking_new', methods: ['POST'])]
    public function newApiBookingFromExternal(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp();
        $bookingType = 5; // Anrufe

        if (!in_array($clientIp, $this->ips)) {
            return new JsonResponse(['error' => 'Access Denied '.$clientIp]);
        }
        $data = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON data']);
        }

        if ((empty($data['customer_id']) && empty($data['begin_at'])) && (empty($data['begin_at']) || empty($data['additional_data']) || empty($data['additional_data']['phone']))) {
            return new JsonResponse(['success' => false, 'message' => 'Es fehlen Daten zum bearbeiten der Anfrage.']);
        }

        try {
            $booking = new Booking();
            if (!empty($data['customer_id'])) {
                $customer = $this->em->getRepository(Customer::class)->find($data['customer_id']);
                if (empty($customer)) {
                    return new JsonResponse(['success' => false, 'message' => 'Kunde mit der Id nicht gefunden']);
                }
                $booking->setCustomer($customer);
            }
            $formattedDate = date('d.m.Y H:i', strtotime($data['begin_at']));

            if (empty($customer)) {
                $formattedString = sprintf($this->stringVars,
                    'Rückruftermin für neuen Kunden . Bitte Anrufen!',
                    $formattedDate,
                    $data['content'] ?? '',
                    $data['additional_data']['name'],
                    $data['additional_data']['surname'],
                    $data['additional_data']['email'],
                    $data['additional_data']['phone'],
                );
                $this->slackService->addSlackLogToChannel('slack_kicall', $formattedString);

                return new JsonResponse(['success' => true]);
            } else {
                $formattedString = sprintf($this->stringVars,
                    'Rückruftermin für Bestandskunde ('.$customer->getFullNormalName().'). Bitte Anrufen!',
                    $formattedDate,
                    $data['content'] ?? '',
                    $customer->getName(),
                    $customer->getSurName(),
                    $customer->getEmail(),
                    $customer->getPhone(),
                );
                $offers = $customer->getOffers();
                if (empty($offers)) {
                    $this->slackService->addSlackLogToChannel('slack_kicall',
                        'Nachricht von  Bestandskunden ohne Angebote: ('.$customer->getFullName().') \n'.$formattedString);

                    return new JsonResponse(['success' => true]);
                }
                $offer = null;
                if (!empty($offers)) {
                    $offer = $offers[count($offers) - 1];
                    $date = new \DateTime($data['begin_at']);
                    $dateE = clone $date;
                    $booking->setOffer($offer);
                    $booking->setCustomer($customer);
                    $booking->setTitle(Booking::BOOKING_TYPE_RIGHTS[$bookingType]['name']);
                    $booking->setBeginAt($date);
                    $booking->setEndAt($dateE->modify('+15 min'));
                    $booking->setNotice('voiceOne-KI Anruf: '.$data['content'] ?? '');
                    $booking->setType($bookingType);

                    $this->em->persist($booking);
                    $this->em->flush();
                }
                $formattedString = sprintf($this->stringVars,
                    'Rückruftermin vereinbart '.(!empty($offer) ? 'und im Angebot *'.$offer->getNumber().'* als Termin eingetragen.' : 'Kunde hat kein Angebot, daher wurde kein Termin im Kalender eingetragen!'),
                    $formattedDate,
                    $data['content'],
                    $customer->getName(),
                    $customer->getSurName(),
                    $customer->getEmail(),
                    $customer->getPhone(),
                );
                $this->slackService->addSlackLogToChannel('slack_kicall', 'Neuer Rückruftermin für '.$this->generateUrl('customer_edit', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL).': '.$customer->getFullName().' '.$formattedString);

                return new JsonResponse(['success' => true]);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Konnte den Termin nicht erstellen: '.$e->getMessage()]);
        }
    }

    #[Route(path: '/notice/new', name: 'extern_notice_new', methods: ['POST'])]
    public function createNote(Request $request): JsonResponse
    {
        $bookingType = 5;
        $clientIp = $request->getClientIp();
        if (!in_array($clientIp, $this->ips)) {
            return new JsonResponse(['error' => 'Access Denied '.$clientIp], JsonResponse::HTTP_UNAUTHORIZED);
        }
        $data = json_decode($request->getContent(), true);
        if ((empty($data['customer_id']) && empty($data['content']))
            || (empty($data['content']) && (empty($data['additional_data']) || empty($data['additional_data']['phone'])))) {
            return new JsonResponse(['success' => false, 'message' => 'Es fehlen Daten zum bearbeiten der Anfrage.']);
        }

        try {
            $booking = new Booking();
            if (!empty($data['customer_id'])) {
                $customer = $this->em->getRepository(Customer::class)->find($data['customer_id']);
                if (empty($customer)) {
                    return new JsonResponse(['success' => false, 'message' => 'Kunde mit der Id nicht gefunden']);
                }
                $booking->setCustomer($customer);
            }
            $formattedDate = new \DateTime();

            if (empty($customer)) {
                $formattedString = sprintf($this->stringVars,
                    'Hallo Team ZOE, Unbekannter/Neuer Kunde hat folgende Nachricht hinterlassen:',
                    $formattedDate->modify('+1 hour')->format('d.m H:i'),
                    $data['content'] ?? '',
                    $data['additional_data']['name'],
                    $data['additional_data']['surname'],
                    $data['additional_data']['email'],
                    $data['additional_data']['phone'],
                );
                $this->slackService->addSlackLogToChannel('slack_kicall', $formattedString);

                return new JsonResponse(['success' => true]);
            } else {
                $formattedString = sprintf($this->stringVars,
                    'Hallo Team ZOE, ein Kunde hat folgende Nachricht hinterlassen:',
                    $formattedDate->modify('+1 hour')->format('d.m H:i'),
                    $data['content'] ?? '',
                    $customer->getName(),
                    $customer->getSurName(),
                    $customer->getEmail(),
                    $customer->getPhone(),
                );
                $offers = $customer->getOffers();
                if (empty($offers)) {
                    $offer = null;
                } else {
                    $offer = $offers[count($offers) - 1];
                }
                $formattedString = sprintf($this->stringVars,
                    'Ein Kunde hat eine Nachricht hinterlassen. '.(!empty($offer) ? ' Angebot *'.$offer->getNumber().'* .' : 'Neue Nachricht von einem Kunden:!'),
                    $formattedDate->modify('+1 hour')->format('d.m H:i'),
                    $data['content'],
                    $customer->getName(),
                    $customer->getSurName(),
                    $customer->getEmail(),
                    $customer->getPhone(),
                );
                $this->slackService->addSlackLogToChannel('slack_kicall', 'Nachricht von '.$customer->getFullNormalName().' ('.$this->generateUrl('customer_edit', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL).') : '.$customer->getFullName().' '.$formattedString);

                return new JsonResponse(['success' => true]);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Konnte den Termin nicht erstellen: '.$e->getMessage()]);
        }
    }

    #[Route(path: '/customer', name: 'extern_customer', methods: ['POST'])]
    public function getApiCustomer(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp();
        if (!in_array($clientIp, $this->ips)) {
            return new JsonResponse(['success' => false, 'message' => 'Access Denied '.$clientIp]);
        }
        $data = json_decode($request->getContent(), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON data']);
        }
        if (empty($data['phone']) && empty($data['customer_id'])) {
            return new JsonResponse(['success' => false, 'message' => 'Es fehlen Daten']);
        }
        try {
            $phoneNumber = $data['phone'];

            // Normalisieren der Telefonnummer
            $normalizedPhoneNumber = $this->normalizePhoneNumber($phoneNumber);
            $customer = $this->em->getRepository(Customer::class)->findCustomersByPhoneFormat($normalizedPhoneNumber);

            if (empty($customer) and empty($customer[0])) {
                // Kein Kunde gefunden slack ->
                $this->slackService->addSlackLogToChannel(
                    'slack_kicall',
                    'Konnte keinen Kunden anhand der Anruf-KI übergebenen Nummer (+'.$normalizedPhoneNumber.') finden (Neukunde)');

                return new JsonResponse(['success' => false, 'message' => 'Kunde nicht gefunden.']);
            }
            /** @var Customer $customer */
            $customer = $customer[0];
            $this->slackService->addSlackLogToChannel('slack_kicall', 'Kunde gefunden und zurückgegeben - '.$customer->getFullNormalName());

            $date = new \DateTime();
            $hour = $date->format('G'); // Format to get hours in 24-hour format
            $minute = $date->format('i'); // Format to get minutes
            $anrede = $this->getGreeting((int) $hour, (int) $minute).' '.('ma' != $customer->getSex() && '' != $customer->getSex() ? (str_replace(['mr', 'ms'], ['Herr', 'Frau'], $customer->getSex()).' '.$customer->getTitle().' '.$customer->getSurName()) : '');

            return new JsonResponse([
                'id' => $customer->getId(),
                'employeeStatus' => $this->getEmployeeStatus((int) $hour, (int) $minute),
                'salutation' => trim(str_replace('  ', ' ', $anrede)).',',
                'sex' => str_replace(['mr', 'ms', '', 'ma'], ['Herr', 'Frau', '', ''], $customer->getSex()),
                'firstname' => $customer->getName(),
                'lastname' => $customer->getSurName(),
                'fullName' => trim($customer->getFullNormalName()),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
            ]);
        } catch (\Exception $e) {
            $this->slackService->addSlackLogToChannel('slack_kicall', 'Fehler im ExternApiController->getApiCustomer(), bitte Admin Informieren! ('.$normalizedPhoneNumber.') nicht finden (Neukunde)');

            return new JsonResponse(['success' => false, 'message' => 'Fehler beim Respons, unser Admin wurde informiert.']);
        }
    }

    private function getGreeting(int $hour, int $minute): string
    {
        if ($hour >= 1 && ($hour < 10 || (10 == $hour && $minute <= 30))) {
            return 'Guten Morgen';
        } elseif ($hour >= 11 && ($hour < 17 || (17 == $hour && $minute > 30))) {
            return 'Guten Tag';
        } else {
            return 'Guten Abend';
        }
    }

    private function getEmployeeStatus(int $hour, int $minute): string
    {
        if ($hour >= 16 && ($hour < 7 || (16 == $hour && $minute <= 30))) {
            return 'Sie rufen ausserhalb der Geschäftszeiten an.';
        } else {
            return 'Alle Mitarbeiter befinden sich derzeit in einem Gespräch.';
        }
    }

    private function normalizePhoneNumber($phoneNumber)
    {
        $normalized = str_replace(['+', ' ', '-', '/'], '', $phoneNumber);
        if (str_starts_with($normalized, '0') && strlen($normalized) > 10) {
            $normalized = '+49'.substr($normalized, 1);
        }

        return $normalized;
    }
}
