<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Index(columns: ['date'], name: 'create_date_idx')]
#[ORM\Index(columns: ['type'], name: 'type_idx')]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    private \DateTime $date;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $sendDate = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Order $invoiceOrder;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $context = [];

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $type;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $number;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $bauvorhaben;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $bauherr;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Customer $customer;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $text;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $lv;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $ladestation;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $leistungsdatum;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos0Text;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos0Date;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    private ?float $pos0Price;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos1Text;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos1Date;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    private ?float $pos1Price;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos2Text;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos2Date;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    private ?float $pos2Price;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos3Text;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $pos3Date;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    private ?float $pos3Price;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $leistung;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $zusatz;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $bezahlt;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Reminder>|\App\Entity\Reminder[]
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: Reminder::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Collection $reminder;

    public function __construct()
    {
        $this->reminder = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getReminder(): ?Collection
    {
        return $this->reminder ?? new ArrayCollection();
    }

    public function getReminderByNumber(int $reminder): ?Reminder
    {
        return count($this->reminder) ? $this->reminder[$reminder] : null;
    }

    public function setReminder(?Collection $reminder): void
    {
        $this->reminder = $reminder;
    }

    public function addReminder(Reminder $reminder): self
    {
        if (!$this->reminder->contains($reminder)) {
            $this->reminder[] = $reminder;
            $reminder->setInvoice($this);
        }

        return $this;
    }

    public function removeReminder(Reminder $reminder): self
    {
        // set the owning side to null (unless already changed)
        if ($this->reminder->removeElement($reminder) && $reminder->getInvoice() === $this) {
            $reminder->setInvoice(null);
        }

        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getSendDate(): ?\DateTime
    {
        return $this->sendDate;
    }

    public function setSendDate(?\DateTime $sendDate): void
    {
        $this->sendDate = $sendDate;
    }

    public function getInvoiceOrder(): ?Order
    {
        if ($this->invoiceOrder instanceof Order
            && $this->invoiceOrder->getOffer() instanceof Offer
            && $this->invoiceOrder->getOffer()->getStatus() === 'deleted') {
            return null;
        }

        return $this->invoiceOrder;
    }

    public function setInvoiceOrder(?Order $invoiceOrder): void
    {
        $this->invoiceOrder = $invoiceOrder;
    }

    public function getType(): mixed
    {
        return $this->type;
    }

    public function getTypeName(): mixed
    {
        $tpes = [
            'part' => 'Abschlags-Rechnung',
            'part-plus' => 'Abschlags-Rechnung '.$this->getNumber(),
            'rest' => 'Abschluss-Rechnung ',
        ];

        return $tpes[$this->type];
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getUser(): mixed
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function getText(): string
    {
        return $this->text ?? '';
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getLv(): ?string
    {
        return $this->lv ?? '';
    }

    public function setLv(?string $lv): void
    {
        $this->lv = $lv;
    }

    public function getLadestation(): ?string
    {
        return $this->ladestation ?? '';
    }

    public function setLadestation(?string $ladestation): void
    {
        $this->ladestation = $ladestation;
    }

    public function getLeistungsdatum(): ?string
    {
        if (empty($this->leistungsdatum) && $this->getInvoiceOrder() instanceof Order) {
            $of = $this->getInvoiceOrder()->getOffer();
            if ($of->getStatus() !== 'deleted') {
                $bookings = $this->getInvoiceOrder()->getOffer()->getBookings();
                if (!empty($bookings)) {
                    /** @var Booking $booking */
                    foreach ($bookings as $booking) {
                        if ($booking->getTitle() === 'Montage/Installation') {
                            return $this->leistungsdatum = $booking->getBeginAt()->format('d.m.Y');
                        }
                    }
                }
            }
        }

        return $this->leistungsdatum ?? '';
    }

    public function setLeistungsdatum(?string $leistungsdatum): void
    {
        $this->leistungsdatum = $leistungsdatum;
    }

    public function getPos0Text(): ?string
    {
        return $this->pos0Text ?? '';
    }

    public function setPos0Text(?string $pos0Text): void
    {
        $this->pos0Text = $pos0Text;
    }

    public function getPos0Date(): ?string
    {
        return $this->pos0Date ?? '';
    }

    public function setPos0Date(?string $pos0Date): void
    {
        $this->pos0Date = $pos0Date;
    }

    public function getPos0Price(): ?float
    {
        return $this->pos0Price ?? 0;
    }

    public function setPos0Price(?float $pos0Price): void
    {
        $this->pos0Price = $pos0Price;
    }

    public function getPos1Text(): ?string
    {
        return $this->pos1Text ?? '';
    }

    public function setPos1Text(?string $pos1Text): void
    {
        $this->pos1Text = $pos1Text;
    }

    public function getPos1Date(): ?string
    {
        return $this->pos1Date ?? '';
    }

    public function setPos1Date(?string $pos1Date): void
    {
        $this->pos1Date = $pos1Date;
    }

    public function getPos1Price(): ?float
    {
        return $this->pos1Price ?? 0;
    }

    public function setPos1Price(?float $pos1Price): void
    {
        $this->pos1Price = $pos1Price;
    }

    public function getPos2Text(): ?string
    {
        return $this->pos2Text ?? '';
    }

    public function setPos2Text(?string $pos2Text): void
    {
        $this->pos2Text = $pos2Text;
    }

    public function getPos2Date(): ?string
    {
        return $this->pos2Date ?? '';
    }

    public function setPos2Date(?string $pos2Date): void
    {
        $this->pos2Date = $pos2Date;
    }

    public function getPos2Price(): ?float
    {
        return $this->pos2Price ?? 0;
    }

    public function setPos2Price(?float $pos2Price): void
    {
        $this->pos2Price = $pos2Price;
    }

    public function getPos3Text(): ?string
    {
        return $this->pos3Text ?? '';
    }

    public function setPos3Text(?string $pos3Text): void
    {
        $this->pos3Text = $pos3Text;
    }

    public function getPos3Date(): ?string
    {
        return $this->pos3Date ?? '';
    }

    public function setPos3Date(?string $pos3Date): void
    {
        $this->pos3Date = $pos3Date;
    }

    public function getPos3Price(): ?float
    {
        return $this->pos3Price ?? 0;
    }

    public function setPos3Price(?float $pos3Price): void
    {
        $this->pos3Price = $pos3Price;
    }

    public function getLeistung(): ?string
    {
        return $this->leistung ?? '';
    }

    public function setLeistung(?string $leistung): void
    {
        $this->leistung = $leistung;
    }

    public function getZusatz(): ?string
    {
        return $this->zusatz ?? '';
    }

    public function setZusatz(?string $zusatz): void
    {
        $this->zusatz = $zusatz;
    }

    public function getBezahlt(): ?\DateTime
    {
        return $this->bezahlt;
    }

    public function setBezahlt(?\DateTime $bezahlt): void
    {
        $this->bezahlt = $bezahlt;
    }

    public function getNumber(): ?string
    {
        return $this->number ?? null;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getBauvorhaben(): ?string
    {
        return $this->bauvorhaben;
    }

    public function setBauvorhaben(?string $bauvorhaben): void
    {
        $this->bauvorhaben = $bauvorhaben;
    }

    public function getBauherr(): mixed
    {
        return $this->bauherr;
    }

    /**
     * @param mixed $bauherr
     */
    public function setBauherr(?string $bauherr): void
    {
        $this->bauherr = $bauherr;
    }

    /**
     * @return string|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @return array|null
     */
    public function getJsonOptions()
    {
        return $this->context;
    }

    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function addContext(array $context): void
    {
        if (count($context)) {
            foreach ($context as $k => $con) {
                $this->context[$k] = $con;
            }
        }
    }

    public function hasContext(string $context): bool
    {
        return $this->context !== null && in_array($context, $this->context);
    }
}
