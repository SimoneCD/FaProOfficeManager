<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Kundendaten.
 */
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Index(columns: ['company_name'], name: 'company_idx')]
#[ORM\Index(columns: ['sur_name'], name: 'sure_name_idx')]
#[ORM\Index(columns: ['name'], name: 'name_idx')]
#[ORM\Index(columns: ['email'], name: 'email_idx')]
#[ORM\Index(columns: ['customer_number'], name: 'customer_number_idx')]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    final public const CUSTOMER_START = 4000;

    final public const CUSTOMER_ROLES = [
        'ROLE_CUSTOMER' => 'w.securityRoles.ROLE_CUSTOMER',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $companyName = '';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $title = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $surName = '';

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $sex;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $email;

    #[ORM\Column(type: Types::JSON)]
    private $roles = [];

    private ?string $plainPassword;

    #[ORM\Column(type: Types::STRING)]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, length: 300, nullable: true)]
    private ?string $address;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $zip;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $city;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $country;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $customerNumber;

    /**
     * @var Collection|Offer[]
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Offer::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $offers;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $notice;

    /**
     * @var Collection|Inquiry[]
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Inquiry::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $inquiries;

    /**
     * @var Collection|Invoice[]
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Invoice::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $invoices;

    /**
     * @var Collection|Reminder[]
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Reminder::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $reminder;

    /**
     * @var Collection
     */
    #[ORM\ManyToOne(targetEntity: Link::class, cascade: ['persist', 'remove'], inversedBy: 'customer')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Link $link;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: ActionLog::class, cascade: ['persist', 'remove'])]
    private Collection $logs;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Email::class, cascade: ['persist', 'remove'])]
    private Collection $emails;

    /**
     * @var Collection|CustomerNotes[]
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: CustomerNotes::class, cascade: ['persist', 'remove'])]
    private Collection $customerNotes;

    public function __construct()
    {
        $this->offers = new ArrayCollection();
        $this->inquiries = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->reminder = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->customerNotes = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title = ''): void
    {
        $this->title = $title;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSurName(): ?string
    {
        return $this->surName;
    }

    public function setSurName(string $surName): self
    {
        $this->surName = $surName;

        return $this;
    }

    public function getFullName(): string
    {
        if (!empty($this->getCompanyName())) {
            return $this->getCompanyName();
        }

        return sprintf('%s %s %s', $this->getTitle(), $this->getName(), $this->getSurName());
    }

    public function getFullNormalName(): string
    {
        return sprintf('%s %s %s', $this->getTitle(), $this->getName(), $this->getSurName());
    }

    public function getFullShortNormalName(): string
    {
        return sprintf('%s %s', $this->getTitle(), $this->getSurName());
    }

    public function getNormalName(): string
    {
        return sprintf('%s %s %s', $this->getTitle(), $this->getName(), $this->getSurName());
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getFullAddress(): ?string
    {
        return sprintf('%s, %s %s', $this->address, $this->zip, $this->city);
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function setCustomerNumber(?string $customerNumber): self
    {
        $this->customerNumber = $customerNumber;

        return $this;
    }

    /**
     * @return Collection|Offer[]
     */
    public function getOffers(): Collection
    {
        $offers = new ArrayCollection();
        try {
            foreach ($this->offers as $offer) {
                if ('deleted' !== $offer->getStatus() && 'archive' !== $offer->getStatus() && 'storno' !== $offer->getStatus()) {
                    $offers->add($offer);
                }
            }
        } catch (\Exception $exception) {
        }

        return $offers;
    }

    public function getLastOffer(): ?Offer
    {
        $offers = new ArrayCollection();
        try {
            foreach ($this->offers as $offer) {
                if ('deleted' !== $offer->getStatus() && 'archive' !== $offer->getStatus() && 'storno' !== $offer->getStatus()) {
                    $offers->add($offer);
                }
            }
            return $offers[count($offers)-1];
        } catch (\Exception $exception) {
        }

        return null;
    }

    /**
     * @return Collection|Offer[]
     */
    public function getArchiveOffers(): Collection
    {
        $offers = new ArrayCollection();
        try {
            foreach ($this->offers as $offer) {
                if ('archive' === $offer->getStatus()) {
                    $offers->add($offer);
                }
            }
        } catch (\Exception $exception) {
        }

        return $offers;
    }

    /**
     * @return Collection|Offer[]
     */
    public function getAllOffers(): Collection
    {
        return $this->offers;
    }

    public function setOffers(?Collection $offers)
    {
        $this->offers = $offers;
    }

    public function addOffers(Offer $offers): self
    {
        if (!$this->offers->contains($offers)) {
            $this->offers->add($offers);
            $offers->setCustomer($this);
        }

        return $this;
    }

    public function removeOffers(Offer $offers): self
    {
        if ($this->offers->removeElement($offers)) {
            // set the owning side to null (unless already changed)
            if ($offers->getCustomer() === $this) {
                $offers->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Inquiry[]
     */
    public function getInquiries(): Collection
    {
        return $this->inquiries;
    }

    public function setInquiries(?Collection $ins): void
    {
        $this->inquiries = $ins;
    }

    public function addInquiry(Inquiry $inquiry): self
    {
        if (!$this->inquiries->contains($inquiry)) {
            $this->inquiries[] = $inquiry;
            $inquiry->setCustomer($this);
        }

        return $this;
    }

    public function removeInquiry(Inquiry $inquiry): self
    {
        // set the owning side to null (unless already changed)
        if ($this->inquiries->removeElement($inquiry) && $inquiry->getCustomer() === $this) {
            $inquiry->setCustomer(null);
        }

        return $this;
    }

    public function getNotice(): ?string
    {
        return $this->notice;
    }

    public function setNotice(?string $notice): Customer
    {
        $this->notice = $notice;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function getReminder(): Collection
    {
        return $this->reminder;
    }

    public function getReminderArray(): array
    {
        if (count($this->reminder)) {
            return $this->reminder->toArray();
        }

        return [];
    }

    public function addInvoice(Invoice $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setCustomer($this);
        }

        return $this;
    }

    public function addReminder(Reminder $reminder): self
    {
        if (!$this->reminder->contains($reminder)) {
            $this->reminder[] = $reminder;
            $reminder->setCustomer($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        // set the owning side to null (unless already changed)
        if ($this->invoices->removeElement($invoice) && $invoice->getCustomer() === $this) {
            $invoice->setCustomer(null);
        }

        return $this;
    }

    public function removeReminder(Reminder $reminder): self
    {
        // set the owning side to null (unless already changed)
        if ($this->reminder->removeElement($reminder) && $reminder->getCustomer() === $this) {
            $reminder->setCustomer(null);
        }

        return $this;
    }

    /**
     * @return Collection|Booking[]
     */
    public function getBookings(): Collection
    {
        $b = new ArrayCollection();
        /** @var Booking $booking */
        foreach ($this->bookings as $booking) {
            $offer = $booking->getOffer();
            if ($offer instanceof Offer && 'deleted' !== $offer->getStatus()) {
                $b->add($booking);
            }
        }

        return $b;
    }

    /**
     * @return Collection|Booking[]
     */
    public function getLogs(): Collection
    {
        $b = new ArrayCollection();
        /** @var ActionLog $log */
        foreach ($this->logs as $log) {
            $offer = $log->getOffer();
            if ($offer instanceof Offer && 'deleted' !== $offer->getStatus()) {
                $b->add($log);
            }
        }

        return $b;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings[] = $booking;
            $booking->setCustomer($this);
        }

        return $this;
    }

    public function addLog(ActionLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setCustomer($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        // set the owning side to null (unless already changed)
        if ($this->bookings->removeElement($booking) && $booking->getCustomer() === $this) {
            $booking->setCustomer(null);
        }

        return $this;
    }

    public function removeLog(ActionLog $log): self
    {
        // set the owning side to null (unless already changed)
        if ($this->logs->removeElement($log) && $log->getCustomer() === $this) {
            $log->setCustomer(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addEmail(Email $email): self
    {
        if (!$this->emails->contains($email)) {
            $this->emails[] = $email;
            $email->setCustomer($this);
        }

        return $this;
    }

    public function removeEmail(Email $email): self
    {
        // set the owning side to null (unless already changed)
        if ($this->emails->removeElement($email) && $email->getCustomer() === $this) {
            $email->setCustomer(null);
        }

        return $this;
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
            $customerNote->setCustomer($this);
        }

        return $this;
    }

    public function removeCustomerNote(CustomerNotes $customerNote): self
    {
        if ($this->customerNotes->removeElement($customerNote)) {
            // set the owning side to null (unless already changed)
            if ($customerNote->getCustomer() === $this) {
                $customerNote->setCustomer(null);
            }
        }

        return $this;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword ?? '';
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getRoles(): array
    {
        $roles = $this->roles ?? ['ROLE_CUSTOMER'];

        return array_unique($roles);
    }

    public function getRoleName(string $role): string
    {
        return self::CUSTOMER_ROLES[$role];
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function addRole(string $role): void
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        $rems = [];
        foreach ($this->reminder as $rem) {
            $rems[] = $rem->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getFullName(),
            'customerNumber' => $this->getCustomerNumber(),
            'reminder' => $rems,
        ];
    }
}
