<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
#[ORM\Index(columns: ['status'], name: 'status_idx')]
#[ORM\Index(columns: ['number'], name: 'number_idx')]
#[ORM\Index(columns: ['status_date'], name: 'status_date_idx')]
#[ORM\Index(columns: ['offer_date'], name: 'offer_date_idx')]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $number;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $urgent = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $deleteIt = false;

    /**
     * Mit welcher Ladeleistung (kW) soll geladen werden? *.
     */
    #[ORM\Column(type: Types::FLOAT, scale: 2, nullable: true)]
    private ?float $kw = 11;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = [];

    /**
     * Vorraussichtlicher Baubeginn: *.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $serviceDateFrom = null;

    /**
     * Vorraussichtlicher Bauende: *.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $serviceDateTo = null;

    public function getKw(): float
    {
        return $this->kw ?? 0.0;
    }

    public function setKw(?float $kw = 11): void
    {
        $this->kw = $kw;
    }

    #[ORM\OneToOne(targetEntity: OfferOption::class, inversedBy: 'offer', cascade: ['persist', 'remove'])]
    // # [ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OfferOption $option;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Customer $customer;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\ManyToMany(targetEntity: ProjectTeam::class, mappedBy: 'offers')]
    private ?Collection $projectTeams;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: ActionLog::class, cascade: ['persist', 'remove'])]
    private ?Collection $logs;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $monteur;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $wallboxProduct;

    /**
     * Mit welcher Ladeleistung (kW) soll geladen werden? *.
     */
    #[ORM\Column(type: Types::FLOAT, scale: 2, nullable: true)]
    private ?float $wallboxPrice;

    #[ORM\OneToOne(targetEntity: Order::class, cascade: ['persist', 'remove'])]
    // # [ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $order;

    /**
     * Ladenutzung/Zugänglichkeit *
     * Privat = für Zuhause (auch für Mieter) | Gewerbe = für Kunden & Mitarbeiter | Öffentlich = für alle.
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $useCase = 'privat';

    /**
     * Wie viele Ladestationen werden benötigt? *.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $amount = 0;

    /**
     * Wieviele Ladestationen sollen installiert werden? *.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $installAmount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $appointmentDate;

    /**
     * Ladestation Aufstellort (Straße und Hausnummer).
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $stationAddress;

    /**
     * Ladestation Aufstellort (Straße und Hausnummer).
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $stationLat;

    /**
     * Ladestation Aufstellort (Straße und Hausnummer).
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $stationLng;

    /**
     * Ladestation Aufstellort (Postleitzahl und Ort).
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $stationZip;

    /**
     * infotext von daa.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notice;

    /**
     * Bemerkungen an Kollegen.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note;

    /**
     * Bearbeitungsstatus.
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private string $status = 'open';

    /**
     * Status änderungsdatum.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $statusDate = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $coupon;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $rabat;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private float $price = 0.0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $tax = 19;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $offerDate;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\OfferItem>|\App\Entity\OfferItem[]
     */
    #[ORM\OneToMany(targetEntity: OfferItem::class, mappedBy: 'offer', cascade: ['persist', 'remove'])]
    private ?Collection $offerItems = null;

    #[ORM\OneToOne(targetEntity: Inquiry::class, inversedBy: 'offer', cascade: ['persist', 'remove'])]
    // # [ORM\JoinColumn(nullable: true)]
    private ?Inquiry $inquiry;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Booking>|\App\Entity\Booking[]
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'offer', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $bookings;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Image>|\App\Entity\Image[]
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'offer', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $images;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Document>|\App\Entity\Document[]
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'offer', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $documents;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OfferSubCategory $subCategory = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OfferCategory $category = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\CustomerNotes>|\App\Entity\CustomerNotes[]
     */
    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: CustomerNotes::class)]
    private Collection $customerNotes;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProductOrder>|\App\Entity\ProductOrder[]
     */
    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: ProductOrder::class)]
    private Collection $productOrders;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $image;

    public function __construct()
    {
        $this->offerDate = new \DateTime();
        $this->offerItems = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->customerNotes = new ArrayCollection();
        $this->projectTeams = new ArrayCollection();
        $this->productOrders = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    /**
     * @return Collection<int, ActionLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(ActionLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setOffer($this);
        }

        return $this;
    }

    public function removeLog(ActionLog $actionLog): self
    {
        // set the owning side to null (unless already changed)
        if ($this->logs->removeElement($actionLog) && $actionLog->getUser() === $this) {
            $actionLog->setOffer(null);
        }

        return $this;
    }
    public function isUrgent(): bool
    {
        return $this->urgent;
    }

    public function setUrgent(bool $urgent): void
    {
        $this->urgent = $urgent;
    }

    public function isDeleteIt(): bool
    {
        return $this->deleteIt;
    }

    public function setDeleteIt(bool $deleteIt): void
    {
        $this->deleteIt = $deleteIt;
    }

    public function getImage(): ?string
    {
        return $this->image ?? '';
    }

    public function setImage(?string $image = ''): self
    {
        $this->image = $image;

        return $this;
    }

    public function getOption(): ?OfferOption
    {
        return $this->option ?? null;
    }

    public function setOption(?OfferOption $option): void
    {
        $this->option = $option;
    }

    public function getStatusDate(): ?\DateTime
    {
        return $this->statusDate;
    }

    public function setStatusDate(?\DateTime $statusDate): void
    {
        $this->statusDate = $statusDate;
    }

    public function getInquiry(): ?Inquiry
    {
        return $this->inquiry ?? null;
    }

    public function setInquiry(?Inquiry $inquiry): void
    {
        $this->inquiry = $inquiry;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getRabat(): ?float
    {
        return $this->rabat ?? 0;
    }

    public function setRabat(?float $rabat): void
    {
        $this->rabat = $rabat;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    public function getMonteur(): ?User
    {
        return $this->monteur;
    }

    public function setMonteur(?User $monteur): void
    {
        $this->monteur = $monteur;
    }

    public function getWallboxProduct(): ?Product
    {
        return $this->wallboxProduct ?? null;
    }

    public function setWallboxProduct(?Product $wallboxProduct): Offer
    {
        $this->wallboxProduct = $wallboxProduct;

        return $this;
    }

    public function getWallboxPrice(): float|int|null
    {
        return $this->wallboxPrice;
    }

    public function setWallboxPrice(float|int|null $wallboxPrice): void
    {
        $this->wallboxPrice = $wallboxPrice;
    }

    public function getPrice(): ?float
    {
        return $this->price ?? 0;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getNotice(): ?string
    {
        return $this->notice ?? '';
    }

    public function setNotice(?string $notice): self
    {
        $this->notice = $notice;

        return $this;
    }

    public function getOfferDate(): ?\DateTime
    {
        return $this->offerDate ?? null;
    }

    public function setOfferDate(\DateTime $offerDate): Offer
    {
        $this->offerDate = $offerDate;

        return $this;
    }

    public function getUseCase(): string
    {
        return $this->useCase ?? 'private';
    }

    public function setUseCase(?string $useCase = 'private'): void
    {
        $this->useCase = $useCase;
    }

    public function getAmount(): int
    {
        return $this->amount ?? 0;
    }

    public function setAmount(?int $amount): void
    {
        $this->amount = $amount;
    }

    public function getInstallAmount(): int
    {
        return $this->installAmount ?? 0;
    }

    public function setInstallAmount(?int $installAmount): void
    {
        $this->installAmount = $installAmount;
    }

    public function getSubTitle(): ?string
    {
        if ($this->getOption() === null) {
            $op = new OfferOption();
            $op->setUser($this->getUser());
            $op->setOffer($this);
            $this->setOption($op);
        }
        $context = $this->getOption()->getContext();
        $product = $this->getWallboxProduct();
        if (isset($context['header']['text'])) {
            return $context['header']['text'];
        } elseif ($product !== null && $product->getProductSubCategory() !== null && $product->getProductSubCategory()->isMainProduct()) {
            return $product->getName();
        }

        return !empty($this->getSubCategory()) ? $this->getSubCategory()->getName() : '';
    }

    public function getAppointmentDate(): ?\DateTime
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTime $appointmentDate): void
    {
        $this->appointmentDate = $appointmentDate;
    }

    public function getStationAddress(): ?string
    {
        return $this->stationAddress;
    }

    public function setStationAddress(?string $stationAddress): void
    {
        $this->stationAddress = $stationAddress;
    }

    public function getStationZip(): ?string
    {
        return $this->stationZip;
    }

    public function setStationZip(?string $stationZip): void
    {
        $this->stationZip = $stationZip;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCoupon(): ?string
    {
        return $this->coupon;
    }

    public function setCoupon(?string $coupon): void
    {
        $this->coupon = $coupon;
    }

    public function getTax(): int
    {
        return $this->tax;
    }

    public function setTax(int $tax): void
    {
        $this->tax = $tax;
    }

    public function getServiceDateFrom(): ?\DateTime
    {
        return $this->serviceDateFrom ?? new \DateTime();
    }

    public function setServiceDateFrom(?\DateTime $serviceDateFrom): void
    {
        $this->serviceDateFrom = $serviceDateFrom;
    }

    public function getServiceDateTo(): ?\DateTime
    {
        return $this->serviceDateTo ?? new \DateTime();
    }

    public function setServiceDateTo(?\DateTime $serviceDateTo): void
    {
        $this->serviceDateTo = $serviceDateTo;
    }

    /**
     * @return ?Collection|OfferItem[]
     */
    public function getOfferItems(): ?Collection
    {
        return $this->offerItems ?? new ArrayCollection();
    }

    public function setImages(?Collection $images): void
    {
        $this->images = $images;
    }

    public function setDocuments(?Collection $documents): void
    {
        $this->documents = $documents;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images instanceof Collection) {
            $this->images = new ArrayCollection();
        }
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setOffer($this);
        }

        return $this;
    }

    public function addDocument(Document $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setOffer($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        // set the owning side to null (unless already changed)
        if ($this->images->removeElement($image) && $image->getOffer() === $this) {
            $image->setOffer(null);
        }

        return $this;
    }

    public function removeDocument(Document $document): self
    {
        // set the owning side to null (unless already changed)
        if ($this->documents->removeElement($document) && $document->getOffer() === $this) {
            $document->setOffer(null);
        }

        return $this;
    }

    public function setOfferItems(?Collection $offerItems): void
    {
        $this->offerItems = $offerItems;
    }

    public function getProjectTeams(): ?Collection
    {
        return $this->projectTeams ?? new ArrayCollection();
    }

    /**
     * @return Collection|OfferItem[]
     */
    public function setProjectTeams(?Collection $projectTeams): void
    {
        $this->projectTeams = $projectTeams;
    }

    public function addProjectTeam(ProjectTeam $projectTeam): self
    {
        if (!$this->projectTeams->contains($projectTeam)) {
            $this->projectTeams[] = $projectTeam;
            $projectTeam->addOffer($this);
        }

        return $this;
    }

    public function removeProjectTeam(ProjectTeam $projectTeam): self
    {
        // set the owning side to null (unless already changed)
        if ($this->projectTeams->removeElement($projectTeam) and $projectTeam->getOffers()->contains($projectTeam)) {
            $projectTeam->removeOffer($this);
        }

        return $this;
    }

    public function addOfferItem(OfferItem $offerItem): self
    {
        if (!$this->offerItems->contains($offerItem)) {
            $this->offerItems[] = $offerItem;
            $offerItem->setOffer($this);
        }

        return $this;
    }

    public function removeOfferItem(OfferItem $offerItem): self
    {
        // set the owning side to null (unless already changed)
        if ($this->offerItems->removeElement($offerItem) && $offerItem->getOffer() === $this) {
            $offerItem->setOffer(null);
        }

        return $this;
    }

    /**
     * @return Collection|Booking[]
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings[] = $booking;
            $booking->setOffer($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        // set the owning side to null (unless already changed)
        if ($this->bookings->removeElement($booking) && $booking->getOffer() === $this) {
            $booking->setOffer(null);
        }

        return $this;
    }

    public function __clone()
    {
        $items = $this->getOfferItems();
        $option = $this->getOption();
        if ($this->id) {
            $this->id = null;

            $date = new \DateTime();

            $this->setNotice('');
            $this->setInquiry(null);
            $this->setOfferItems(new ArrayCollection());
            $this->setNote('Kopie von '.$this->getNumber());
            if (empty($this->getOrder()) && $this->getStatus() !== 'estimate') {
                $this->setStatus('call-plus');
            } else {
                $this->setStatus('estimate');
            }
            $this->setAppointmentDate(null);
            $this->setMonteur(null);

            $this->setStatusDate($date);
            $this->setOfferDate($date);
            $this->setImages(null);

            // cloning the relation which is a OneToMany
            $itemsClone = new ArrayCollection();
            foreach ($items as $item) {
                $itemClone = clone $item;
                $itemClone->setOffer($this);
                $itemsClone->add($itemClone);
            }
            $this->setOfferItems($itemsClone);

            $this->setOrder(null);

            $optionsClone = clone $option;
            $optionsClone->setOffer($this);
            $this->setOption($optionsClone);
        }
    }

    public function getSubCategory(): ?OfferSubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?OfferSubCategory $subCategory): self
    {
        $this->subCategory = $subCategory;

        return $this;
    }

    public function getCategory(): ?OfferCategory
    {
        return $this->category;
    }

    public function setCategory(?OfferCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context ?? [];
    }

    public function getJsonOptions(): ?array
    {
        return $this->context;
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

    /**
     * @return Collection<int, CustomerNotes>
     */
    public function getCustomerNotes(): Collection
    {
        return $this->customerNotes;
    }

    public function addCustomerNote(CustomerNotes $customerNote): self
    {
        if (!$this->customerNotes->contains($customerNote)) {
            $this->customerNotes->add($customerNote);
            $customerNote->setOffer($this);
        }

        return $this;
    }

    public function removeCustomerNote(CustomerNotes $customerNote): self
    {
        if ($this->customerNotes->removeElement($customerNote)) {
            // set the owning side to null (unless already changed)
            if ($customerNote->getOffer() === $this) {
                $customerNote->setOffer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductOrder>
     */
    public function getProductOrders(): Collection
    {
        return $this->productOrders;
    }

    public function addProductOrder(ProductOrder $productOrder): self
    {
        if (!$this->productOrders->contains($productOrder)) {
            $this->productOrders->add($productOrder);
            $productOrder->setOffer($this);
        }

        return $this;
    }

    public function removeProductOrder(ProductOrder $productOrder): self
    {
        if ($this->productOrders->removeElement($productOrder)) {
            // set the owning side to null (unless already changed)
            if ($productOrder->getOffer() === $this) {
                $productOrder->setOffer(null);
            }
        }

        return $this;
    }

    public function getStationLat(): ?string
    {
        return $this->stationLat;
    }

    public function setStationLat(?string $stationLat): void
    {
        $this->stationLat = $stationLat;
    }

    public function getStationLng(): ?string
    {
        return $this->stationLng;
    }

    public function setStationLng(?string $stationLng): void
    {
        $this->stationLng = $stationLng;
    }
}
