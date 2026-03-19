<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Index(columns: ['begin_at'], name: 'begin_at_i_idx')]
#[ORM\Index(columns: ['google_event_id'], name: 'google_event_idx')]
class Booking
{
    public const BOOKING_TYPES = [
        'Montage/Installation' => 1,
        'Besichtigung' => 2,
        'Anrufen' => 5,
        'Aufgabe' => 7,
        'Terminvorschlag' => 8,
        'Sonstiges' => 6,
    ];

    public const BOOKING_TYPE_RIGHTS = [
        1 => [
            'range' => ['montage', 'extern', 'service', 'admin'],
            'name' => 'Vorort Arbeiten',
        ],
        2 => [
            'range' => ['montage', 'extern', 'service', 'admin'],
            'name' => 'Besichtigung',
        ],
        3 => [
            'range' => ['montage', 'extern', 'service', 'admin'],
            'name' => 'DC',
        ],
        4 => [
            'range' => ['montage', 'extern', 'service', 'admin'],
            'name' => 'AC',
        ],
        5 => [
            'range' => ['extern', 'service', 'admin'],
            'name' => 'Anrufen',
        ],
        6 => [
            'range' => ['extern', 'service', 'admin'],
            'name' => 'Sonstiges',
        ],
        7 => [
            'range' => ['montage', 'extern', 'service', 'admin'],
            'name' => 'Aufgabe',
        ],
        8 => [
            'range' => ['montage', 'extern', 'service', 'admin'],
            'name' => 'Terminvorschlag',
        ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private \DateTimeInterface $beginAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private \DateTimeInterface $endAt;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $type;
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $done;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user;
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_task_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $userTask;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Customer $customer;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'offer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notice;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $googleEventId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeginAt(): ?\DateTimeInterface
    {
        return $this->beginAt;
    }

    public function setBeginAt(\DateTimeInterface $beginAt): self
    {
        $this->beginAt = $beginAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt ?? $this->beginAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUserTask(): ?User
    {
        return $this->userTask;
    }

    public function setUserTask(?User $userTask): void
    {
        $this->userTask = $userTask;
    }

    public function getDone(): ?int
    {
        return $this->done;
    }

    public function setDone(?int $done): void
    {
        $this->done = $done;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getOffer(): ?Offer
    {
        if (!$this->offer instanceof Offer) {
            return null;
        }

        if ('deleted' !== $this->offer->getStatus()) {
            return $this->offer;
        }

        return null;
    }

    public function setOffer(?Offer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function getNotice(): ?string
    {
        return $this->notice;
    }

    public function setNotice(?string $notice): self
    {
        $this->notice = $notice;

        return $this;
    }

    public function getGoogleEventId(): ?string
    {
        return $this->googleEventId;
    }

    public function setGoogleEventId(?string $googleEventId): void
    {
        $this->googleEventId = $googleEventId;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeResult(?int $type): array
    {
        if (null !== $type && isset(self::BOOKING_TYPE_RIGHTS[$type])) {
            return self::BOOKING_TYPE_RIGHTS[$type];
        }

        return [];
    }

    public function setType(?int $type): void
    {
        $this->type = $type;
    }
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'beginAt' => $this->getBeginAt(),
            'endAt' => $this->getEndAt(),
            'notice' => $this->getNotice(),
        ];
    }
}
