<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EmailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailRepository::class)]
class Email
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private Customer $customer;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private string $template;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $date;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $attachment;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $attachmentName;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $attachmentSecond;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $attachmentSecondName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->sendFrom;
    }

    public function setCustomer(Customer $sendFrom): void
    {
        $this->sendFrom = $sendFrom;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): void
    {
        $this->attachment = $attachment;
    }

    public function getAttachmentName(): ?string
    {
        return $this->attachmentName;
    }

    public function setAttachmentName(?string $attachmentName): void
    {
        $this->attachmentName = $attachmentName;
    }

    public function getAttachmentSecond(): ?string
    {
        return $this->attachmentSecond;
    }

    public function setAttachmentSecond(?string $attachmentSecond): void
    {
        $this->attachmentSecond = $attachmentSecond;
    }

    public function getAttachmentSecondName(): ?string
    {
        return $this->attachmentSecondName;
    }

    public function setAttachmentSecondName(?string $attachmentSecondName): void
    {
        $this->attachmentSecondName = $attachmentSecondName;
    }
}
