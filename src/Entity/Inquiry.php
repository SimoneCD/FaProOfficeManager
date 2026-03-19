<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InquiryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Kunden aufnehmen.
 */
#[ORM\Table(name: 'inquiry')]
#[ORM\Index(columns: ['create_date'], name: 'create_date_idx')]
#[ORM\Index(columns: ['sale_id'], name: 'sale_id_idx')]
#[ORM\Index(columns: ['lead_id'], name: 'lead_id_idx')]
#[ORM\Entity(repositoryClass: InquiryRepository::class)]
class Inquiry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $leadId;
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $saleId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'inquiries')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $date;

    /**
     * Imported sale_date field from DAA.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createDate;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $stornoDate;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $refererFrom = 'DAA';

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'inquiries')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Customer $customer;

    #[ORM\OneToOne(mappedBy: 'inquiry', targetEntity: Offer::class)]
    ## [ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: false)]
    private string $status = 'open';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 20, nullable: true)]
    private ?string $offerType;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $declined;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $notice;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $context = [];

    public function __construct()
    {
        $this->setDate(new \DateTime());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): void
    {
        if ($offer === null && $this->offer instanceof Offer) {
            $this->offer->setInquiry(null);
        }
        $this->offer = $offer;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getStornoDate(): ?\DateTimeInterface
    {
        return $this->stornoDate;
    }

    public function setStornoDate(?\DateTime $date): void
    {
        $this->stornoDate = $date;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(?\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getLeadId(): ?int
    {
        return $this->leadId;
    }

    public function setLeadId(?int $leadId): void
    {
        $this->leadId = $leadId;
    }

    /**
     * @return int|null
     */
    public function getSaleId(): ?int
    {
        return $this->saleId;
    }

    /**
     * @param int|null $saleId
     */
    public function setSaleId(?int $saleId): void
    {
        $this->saleId = $saleId;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getRefererFrom(): ?string
    {
        return $this->refererFrom;
    }

    public function setRefererFrom(?string $refererFrom): self
    {
        $this->refererFrom = $refererFrom;

        return $this;
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

    /**
     * @return string|null
     */
    public function getOfferType(): ?string
    {
        return $this->offerType;
    }

    /**
     * @param string|null $offerType
     */
    public function setOfferType(?string $offerType): void
    {
        $this->offerType = $offerType;
    }


    public function getDeclined(): ?\DateTimeInterface
    {
        return $this->declined;
    }

    public function setDeclined(?\DateTimeInterface $declined): self
    {
        $this->declined = $declined;

        return $this;
    }

    public function getNotice(): string
    {
        return $this->notice ?? '';
    }

    public function setNotice(?string $notice): Inquiry
    {
        $this->notice = $notice;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): Inquiry
    {
        $this->status = $status;

        return $this;
    }

    public function __clone()
    {
        $oldOffer = $this->getOffer();
        if ($this->id) {
            $this->id = null;

            $date = new \DateTime();
            $this->offer = clone $oldOffer;

            $this->setStatus('open');
            $this->setDate($date);
            $this->setSaleId(null);
            $this->setCreateDate($date);
            $this->setStornoDate(null);
        }
    }

    public function getContext(): array
    {
        return $this->context ?? [];
    }

    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function addContext(string $context): void
    {
        if (!in_array($context, $this->context)) {
            $this->context[] = $context;
        }
    }

    public function hasContext(string $context): bool
    {
        return $this->context !== null && in_array($context, $this->context);
    }
}
