<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReminderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReminderRepository::class)]
#[ORM\Index(columns: ['send_date'], name: 'create_date_idx')]
class Reminder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $sendDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $paid = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'reminder')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $type;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $number;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'reminder')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Customer $customer;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text;


    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getSendDate(): ?\DateTime
    {
        return $this->sendDate;
    }

    public function setSendDate(?\DateTime $sendDate): void
    {
        $this->sendDate = $sendDate;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getType(): mixed
    {
        return $this->type;
    }

    public function getTypeName(): mixed
    {
        $tpes = [
            'first' => '1. Zahlungserinnerung',
            'second' => '2. Mahnung ',
            'last' => '3. Letzte Mahnung',
        ];

        return $tpes[$this->type];
    }

    public function getTypeTime($type): mixed
    {
        /* in Days */
        $tpes = [
            'first' => '7',
            'second' => '14',
            'last' => '30',
        ];

        return $tpes[$type];
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getUser(): User
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

    public function getNumber(): ?string
    {
        return $this->number ?? null;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getPaid(): ?\DateTime
    {
        return $this->paid;
    }

    public function setPaid(?\DateTime $paid): void
    {
        $this->paid = $paid;
    }

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

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser()->getUsername(),
            'type' => $this->getType(),
            'paid' => $this->getType(),
            'invoice_id' => $this->getInvoice()->getId(),
            'invoice_type' => $this->getInvoice()->getType(),
            'invoice_number' => $this->getInvoice()->getNumber(),
            // 'title' => $this->getTypeName(),
        ];
    }
}
