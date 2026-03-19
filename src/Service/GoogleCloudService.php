<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Offer;

class GoogleCloudService extends GoogleService
{
    public function createClient(): \Google_Client
    {
        $client = new \Google_Client();
        $client->setApplicationName('FaPro app');
        $client->setSubject($_ENV['GOOGLE_APPLICATION_ACCOUNT']);
        $client->setAuthConfig($_ENV['GOOGLE_APPLICATION_CREDENTIALS']);
        $client->setScopes([\Google_Service_CloudNaturalLanguage_Sentence::class]);
        $client->useApplicationDefaultCredentials();

        return $client;
    }

    private function getAppClient(): \Google_Client
    {
        $client = new \Google_Client();
        $credentialsPath = $_ENV['GOOGLE_APPLICATION_CREDENTIALS'];
        $client->setApplicationName('FaPro');
        $client->setSubject($_ENV['GOOGLE_APPLICATION_ACCOUNT']);
        $client->setScopes([
            \Google_Service_Calendar::CALENDAR,
            \Google_Service_Pubsub::PUBSUB,
        ]);
        $client->setAuthConfig($credentialsPath);
        $client->setAccessType('offline');
        $client->setPrompt('none');
        $this->client = $client;

        return $this->client;
    }

    public function deleteEvent(Booking $booking)
    {
        if ($booking->getGoogleEventId() !== null) {
            $service = new \Google_Service_Calendar($this->getAppClient());

            return $service->events->delete(self::APPLICATION_CALENDAR_ID, $booking->getGoogleEventId());
        }

        return false;
    }

    public function writeEvent(Offer $offer, Booking $booking)
    {
        if ($booking->getTitle() !== 'Anrufen' && $booking->getTitle() !== 'Sonstiges') {
            $start = [
                'dateTime' => $booking->getBeginAt()->format('Y-m-d\TH:i:s+01:00'),
                'timeZone' => 'Europe/Berlin',
            ];
            $end = [
                'dateTime' => $booking->getEndAt()->format('Y-m-d\TH:i:s+01:00'),
                'timeZone' => 'Europe/Berlin',
            ];
        } else {
            $start = [
                'date' => $booking->getBeginAt()->format('Y-m-d'),
                'timeZone' => 'Europe/Berlin',
            ];
            $end = [
                'date' => $booking->getEndAt()->format('Y-m-d'),
                'timeZone' => 'Europe/Berlin',
            ];
        }
        $data = [
            'summary' => $offer->getNumber().' - '.$booking->getTitle(),
            'location' => ($offer->getStationAddress() ?? '').', '.($offer->getStationZip() ?? '').', DE',
            'description' => '<strong>Kunde</strong>
Name: '.$offer->getCustomer()->getFullNormalName().'
Tel: '.$offer->getCustomer()->getPhone().'
Email: '.$offer->getCustomer()->getEmail().'
 
<strong>Projektleiter</strong>
'.(empty($booking->getOffer()->getMonteur()) ? '' : $booking->getOffer()->getMonteur()->getFullName()).'
  
<strong>Montage Teams</strong>
 
<strong>Termin Notizen</strong>
'.(empty($booking->getNotice()) ? '' : $booking->getNotice()).'

<strong>Angebots Notizen</strong>
'.(empty($offer->getNote()) ? '' : $offer->getNote()).'

<strong>Kunden Angaben</strong>
'.(empty($offer->getNotice()) ? '' : $offer->getNotice()).'

<strong>Auftragsblatt:</strong>
<a href="https://admin.zukunftsorientierte-energie.de/montage/auftrags-blatt/'.$offer->getId().'">Auftragsblatt: '.$offer->getNumber().'</a>

',
            'start' => $start,
            'end' => $end,
            'colorId' => $booking->getTitle() === 'Besichtigung' ? '9' : '11',
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 15],
                ],
            ],
        ];
        try {
            $service = new \Google_Service_Calendar($this->getAppClient());
            $event = new \Google_Service_Calendar_Event($data);
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }
            /* Service accounts cannot invite attendees without Domain-Wide Delegation of Authority.
             * 'attendees' => [
                ['email' => 'sschulze35@gmail.com'],
                ['email' => 'sf-elektro@green-management.eu'],
            ],
            * wiederholungen
            'recurrence' => [
                'RRULE:FREQ=DAILY;COUNT=2'
            ],*/

            $event = $service->events->insert(self::APPLICATION_CALENDAR_ID, $event);

        $booking->setGoogleEventId($event->getId());
    }
}
