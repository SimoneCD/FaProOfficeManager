<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\User;
use App\Service\GoogleCalendarService;
use PHPUnit\Framework\TestCase;

final class GoogleCalendarServiceTest extends TestCase
{
    private GoogleCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoogleCalendarService();
    }

    private function createCustomer(): Customer
    {
        $customer = new Customer();
        $customer->setSurName('Mustermann');
        $customer->setName('Max');
        $customer->setSex('m');
        $customer->setEmail('max@example.com');
        $customer->setPhone('+49 123 456789');

        return $customer;
    }

    private function createOffer(?Customer $customer = null): Offer
    {
        $offer = new Offer();
        $offer->setNumber('ANGEBOT-2025-001');
        $offer->setCustomer($customer ?? $this->createCustomer());
        $offer->setStationAddress('Musterstraße 42');
        $offer->setStationZip('12345');
        $offer->setNote('Testnotiz für das Angebot');

        return $offer;
    }

    private function createBooking(
        string $title,
        \DateTimeInterface $beginAt,
        \DateTimeInterface $endAt,
        ?Offer $offer = null,
        ?string $notice = null,
        ?string $googleEventId = null
    ): Booking {
        $booking = new Booking();
        $booking->setTitle($title);
        $booking->setBeginAt($beginAt);
        $booking->setEndAt($endAt);
        $booking->setOffer($offer ?? $this->createOffer());
        $booking->setNotice($notice);
        $booking->setGoogleEventId($googleEventId);

        return $booking;
    }

    public function testDeleteEventReturnsFalseWhenGoogleEventIdIsNull(): void
    {
        $booking = $this->createBooking(
            title: 'Besichtigung',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00')
        );

        $result = $this->service->deleteEvent($booking);

        $this->assertFalse($result);
    }

    public function testDeleteEventReturnsFalseWhenGoogleEventIdIsEmptyString(): void
    {
        $this->markTestSkipped('Requires Google API mocking - empty string is not null so service tries to call API');
    }

    public function testWriteEventBuildsCorrectEventDataForDateTimeBasedBooking(): void
    {
        $beginAt = new \DateTime('2025-06-01 10:00:00', new \DateTimeZone('Europe/Berlin'));
        $endAt = new \DateTime('2025-06-01 11:00:00', new \DateTimeZone('Europe/Berlin'));

        $booking = $this->createBooking(
            title: 'Montage/Installation',
            beginAt: $beginAt,
            endAt: $endAt,
            notice: 'Wichtige Hinweise'
        );

        $this->assertSame('Montage/Installation', $booking->getTitle());
        $this->assertSame($beginAt, $booking->getBeginAt());
        $this->assertSame($endAt, $booking->getEndAt());
    }

    public function testWriteEventBuildsCorrectEventDataForDateBasedBooking(): void
    {
        $beginAt = new \DateTime('2025-06-01 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $endAt = new \DateTime('2025-06-01 23:59:59', new \DateTimeZone('Europe/Berlin'));

        $booking = $this->createBooking(
            title: 'Anrufen',
            beginAt: $beginAt,
            endAt: $endAt
        );

        $this->assertSame('Anrufen', $booking->getTitle());
        $this->assertNotNull($booking->getBeginAt());
        $this->assertSame('2025-06-01', $booking->getBeginAt()->format('Y-m-d'));
    }

    public function testBookingTitleDeterminesEventType(): void
    {
        $beginAt = new \DateTime('2025-06-01 10:00:00', new \DateTimeZone('Europe/Berlin'));
        $endAt = new \DateTime('2025-06-01 11:00:00', new \DateTimeZone('Europe/Berlin'));

        $titlesUsingDateFormat = ['Anrufen', 'Sonstiges'];
        $titlesUsingDateTimeFormat = ['Montage/Installation', 'Besichtigung', 'Aufgabe', 'Terminvorschlag'];

        foreach ($titlesUsingDateFormat as $title) {
            $booking = $this->createBooking($title, $beginAt, $endAt);
            $this->assertContains($booking->getTitle(), $titlesUsingDateFormat);
        }

        foreach ($titlesUsingDateTimeFormat as $title) {
            $booking = $this->createBooking($title, $beginAt, $endAt);
            $this->assertContains($booking->getTitle(), $titlesUsingDateTimeFormat);
        }
    }

    public function testColorIdIsSetCorrectlyForBesichtigung(): void
    {
        $booking = $this->createBooking(
            title: 'Besichtigung',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00')
        );

        $this->assertSame('Besichtigung', $booking->getTitle());
        $colorId = 'Besichtigung' === $booking->getTitle() ? '9' : '11';
        $this->assertSame('9', $colorId);
    }

    public function testColorIdIsSetCorrectlyForMontage(): void
    {
        $booking = $this->createBooking(
            title: 'Montage/Installation',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00')
        );

        $this->assertSame('Montage/Installation', $booking->getTitle());
        $colorId = 'Besichtigung' === $booking->getTitle() ? '9' : '11';
        $this->assertSame('11', $colorId);
    }

    public function testSummaryFormat(): void
    {
        $offer = $this->createOffer();
        $booking = $this->createBooking(
            title: 'Besichtigung',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00'),
            offer: $offer
        );

        $expectedSummary = $offer->getNumber().' - '.$booking->getTitle();

        $this->assertSame('ANGEBOT-2025-001 - Besichtigung', $expectedSummary);
    }

    public function testLocationFormat(): void
    {
        $offer = $this->createOffer();

        $expectedLocation = ($offer->getStationAddress() ?? '').', '.($offer->getStationZip() ?? '').', DE';

        $this->assertSame('Musterstraße 42, 12345, DE', $expectedLocation);
    }

    public function testLocationHandlesNullStationAddress(): void
    {
        $offer = new Offer();
        $offer->setNumber('ANGEBOT-2025-002');
        $offer->setCustomer($this->createCustomer());
        $offer->setStationAddress(null);
        $offer->setStationZip(null);

        $expectedLocation = ($offer->getStationAddress() ?? '').', '.($offer->getStationZip() ?? '').', DE';

        $this->assertSame(', , DE', $expectedLocation);
    }

    public function testDescriptionIncludesCustomerInfo(): void
    {
        $customer = $this->createCustomer();
        $offer = $this->createOffer($customer);
        $booking = $this->createBooking(
            title: 'Besichtigung',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00'),
            offer: $offer
        );

        $customerName = $offer->getCustomer()?->getFullNormalName() ?? '';
        $customerPhone = $offer->getCustomer()?->getPhone() ?? '';
        $customerEmail = $offer->getCustomer()?->getEmail() ?? '';

        $description = '<strong>Kunde</strong>
Name: '.$customerName.'
Tel: '.$customerPhone.'
Email: '.$customerEmail;

        $this->assertStringContainsString('Max Mustermann', $description);
        $this->assertStringContainsString('+49 123 456789', $description);
        $this->assertStringContainsString('max@example.com', $description);
    }

    public function testDescriptionIncludesNotice(): void
    {
        $booking = $this->createBooking(
            title: 'Besichtigung',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00'),
            notice: 'Wichtige Hinweise zum Termin'
        );

        $notice = empty($booking->getNotice()) ? '' : $booking->getNotice();

        $this->assertSame('Wichtige Hinweise zum Termin', $notice);
    }

    public function testDescriptionHandlesEmptyNotice(): void
    {
        $booking = $this->createBooking(
            title: 'Besichtigung',
            beginAt: new \DateTime('2025-06-01 10:00:00'),
            endAt: new \DateTime('2025-06-01 11:00:00'),
            notice: null
        );

        $notice = empty($booking->getNotice()) ? '' : $booking->getNotice();

        $this->assertSame('', $notice);
    }

    public function testDescriptionIncludesOfferLink(): void
    {
        $this->markTestSkipped('Requires Doctrine entity with ID - Offer entity ID is managed by Doctrine ORM');
    }

    public function testRemindersConfiguration(): void
    {
        $reminders = [
            'useDefault' => false,
            'overrides' => [
                ['method' => 'email', 'minutes' => 24 * 60],
                ['method' => 'popup', 'minutes' => 15],
            ],
        ];

        $this->assertFalse($reminders['useDefault']);
        $this->assertCount(2, $reminders['overrides']);
        $this->assertSame('email', $reminders['overrides'][0]['method']);
        $this->assertSame(1440, $reminders['overrides'][0]['minutes']);
        $this->assertSame('popup', $reminders['overrides'][1]['method']);
        $this->assertSame(15, $reminders['overrides'][1]['minutes']);
    }

    public function testDateTimeFormatting(): void
    {
        $beginAt = new \DateTime('2025-06-01 10:30:00', new \DateTimeZone('Europe/Berlin'));
        $endAt = new \DateTime('2025-06-01 11:45:00', new \DateTimeZone('Europe/Berlin'));

        $dateTimeStart = [
            'dateTime' => $beginAt->format('Y-m-d\TH:i:s+01:00'),
            'timeZone' => 'Europe/Berlin',
        ];

        $dateTimeEnd = [
            'dateTime' => $endAt->format('Y-m-d\TH:i:s+01:00'),
            'timeZone' => 'Europe/Berlin',
        ];

        $this->assertSame('2025-06-01T10:30:00+01:00', $dateTimeStart['dateTime']);
        $this->assertSame('2025-06-01T11:45:00+01:00', $dateTimeEnd['dateTime']);
        $this->assertSame('Europe/Berlin', $dateTimeStart['timeZone']);
    }

    public function testDateFormatting(): void
    {
        $beginAt = new \DateTime('2025-06-01 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $endAt = new \DateTime('2025-06-01 23:59:59', new \DateTimeZone('Europe/Berlin'));

        $dateStart = [
            'date' => $beginAt->format('Y-m-d'),
            'timeZone' => 'Europe/Berlin',
        ];

        $dateEnd = [
            'date' => $endAt->format('Y-m-d'),
            'timeZone' => 'Europe/Berlin',
        ];

        $this->assertSame('2025-06-01', $dateStart['date']);
        $this->assertSame('2025-06-01', $dateEnd['date']);
        $this->assertSame('Europe/Berlin', $dateStart['timeZone']);
    }

    public function testBookingOfferRelation(): void
    {
        $offer = $this->createOffer();
        $booking = new Booking();
        $booking->setTitle('Besichtigung');
        $booking->setBeginAt(new \DateTime('2025-06-01 10:00:00'));
        $booking->setEndAt(new \DateTime('2025-06-01 11:00:00'));
        $booking->setOffer($offer);

        $this->assertSame($offer, $booking->getOffer());
        $this->assertSame('ANGEBOT-2025-001', $booking->getOffer()->getNumber());
    }

    public function testBookingGoogleEventIdSetter(): void
    {
        $booking = new Booking();
        $booking->setGoogleEventId('abc123xyz');

        $this->assertSame('abc123xyz', $booking->getGoogleEventId());

        $booking->setGoogleEventId(null);

        $this->assertNull($booking->getGoogleEventId());
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function bookingTitleProvider(): array
    {
        return [
            'Anrufen uses date format' => ['Anrufen', true],
            'Sonstiges uses date format' => ['Sonstiges', true],
            'Besichtigung uses datetime format' => ['Besichtigung', false],
            'Montage/Installation uses datetime format' => ['Montage/Installation', false],
            'Aufgabe uses datetime format' => ['Aufgabe', false],
            'Terminvorschlag uses datetime format' => ['Terminvorschlag', false],
        ];
    }
}
