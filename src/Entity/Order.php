<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: '`order`')]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id;

    #[ORM\OneToOne(targetEntity: Offer::class)]
    ## [ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT)]
    private float $price;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private int $tax;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    private string $text;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $title;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $createdAt = null;
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $sendAt = null;
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $sendPreOfferAt = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    private string $status;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private bool $bestaetigt = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: false)]
    private string $accessKey;

    /**
     * @var Collection
     */
    #[ORM\OneToMany(mappedBy: 'invoiceOrder', targetEntity: Invoice::class, cascade: ['persist', 'remove'])]
    private Collection $invoices;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartInvoice(): ?Invoice
    {
        if ($this->invoices->count() === 0) {
            return null;
        }
        /** @var Invoice $invoice */
        foreach ($this->invoices as $invoice) {
            if ($invoice->getType() === 'part') {
                return $invoice;
            }
        }

        return null;
    }

    public function getTeilInvoice(): array
    {
        $a = [];
        if ($this->invoices->count() === 0) {
            return $a;
        }
        $invoices = [];
        /** @var Invoice $invoice */
        foreach ($this->invoices as $invoice) {
            if ($invoice->getType() === 'part-plus') {
                $invoices[] = $invoice;
            }
        }

        return $invoices;
    }

    public function getRestInvoice(): ?Invoice
    {
        if ($this->invoices->count() < 2) {
            return null;
        }
        /** @var Invoice $invoice */
        foreach ($this->invoices as $invoice) {
            if ($invoice->getType() === 'rest') {
                return $invoice;
            }
        }

        return null;
    }

    public function isBestaetigt(): bool
    {
        return $this->bestaetigt ?? false;
    }

    /**
     * @param bool $bestaetigt
     */
    public function setBestaetigt(?bool $bestaetigt): void
    {
        $this->bestaetigt = $bestaetigt ?? false;
    }

    public function getOffer(): ?Offer
    {
        if ($this->offer instanceof Offer) {
            if ($this->offer->getStatus() !== 'deleted') {
                return $this->offer;
            }
        }

        return null;
    }

    public function setOffer(?Offer $offer): void
    {
        $this->offer = $offer;
    }

    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    public function setAccessKey(string $accessKey): void
    {
        $this->accessKey = $accessKey;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getSendAt(): ?\DateTime
    {
        return $this->sendAt;
    }

    /**
     * @param \DateTime|null $sendAt
     */
    public function setSendAt(?\DateTime $sendAt): void
    {
        $this->sendAt = $sendAt;
    }

    public function getSendPreOfferAt(): ?\DateTime
    {
        return $this->sendPreOfferAt;
    }

    public function setSendPreOfferAt(?\DateTime $sendPreOfferAt): void
    {
        $this->sendPreOfferAt = $sendPreOfferAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getTax(): ?int
    {
        return $this->tax;
    }

    public function setTax(int $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getTitle(): string
    {
        if ($this->title) {
            return $this->title;
        } elseif (!empty($this->getOffer()->getSubTitle())) {
            return $this->getOffer()->getSubTitle().'';
        } else {
            return $this->getOffer()->getWallboxProduct()->getName().' | '.$this->getOffer()->getKw();
        }
    }

    /**
     * @param string $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function setInvoices(?Collection $ins)
    {
        $this->invoices = $ins;
    }

    public function addInvoice(Invoice $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setInvoiceOrder($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        // set the owning side to null (unless already changed)
        if ($this->invoices->removeElement($invoice) && $invoice->getInvoiceOrder() === $this) {
            $invoice->setInvoiceOrder(null);
        }

        return $this;
    }
}
