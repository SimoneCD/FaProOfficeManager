<?php

namespace App\Entity;

use App\Repository\IndexStatesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;

#[ORM\Entity(repositoryClass: IndexStatesRepository::class)]
class IndexStates
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $state = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $sendCostEstimate = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $sendOffer = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $sendPartInvoice = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $sendInvoice = null;

    #[ORM\Column(length: 255)]
    private ?string $actionFirst = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actionLast = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $help = null;

    #[ORM\ManyToOne(inversedBy: 'states')]
    private ?Document $document = null;

    #[ORM\Column(nullable: true)]
    private ?bool $autoMoveByTime = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getActionFirst(): ?string
    {
        return $this->actionFirst;
    }

    public function setActionFirst(string $actionFirst): self
    {
        $this->actionFirst = $actionFirst;

        return $this;
    }

    public function getActionLast(): ?string
    {
        return $this->actionLast;
    }

    public function setActionLast(?string $actionLast): self
    {
        $this->actionLast = $actionLast;

        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(?string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function isAutoMoveByTime(): ?bool
    {
        return $this->autoMoveByTime;
    }

    public function setAutoMoveByTime(?bool $autoMoveByTime): self
    {
        $this->autoMoveByTime = $autoMoveByTime;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSendCostEstimate(): bool
    {
        return $this->sendCostEstimate ?? false;
    }

    /**
     * @param bool|null $sendCostEstimate
     */
    public function setSendCostEstimate(?bool $sendCostEstimate): void
    {
        $this->sendCostEstimate = $sendCostEstimate;
    }

    /**
     * @return bool|null
     */
    public function getSendOffer(): bool
    {
        return $this->sendOffer ?? false;
    }

    /**
     * @param bool|null $sendOffer
     */
    public function setSendOffer(?bool $sendOffer): void
    {
        $this->sendOffer = $sendOffer;
    }

    /**
     * @return bool|null
     */
    public function getSendPartInvoice(): bool
    {
        return $this->sendPartInvoice ?? false;
    }

    /**
     * @param bool|null $sendPartInvoice
     */
    public function setSendPartInvoice(?bool $sendPartInvoice): void
    {
        $this->sendPartInvoice = $sendPartInvoice;
    }

    /**
     * @return bool|null
     */
    public function getSendInvoice(): bool
    {
        return $this->sendInvoice ?? false;
    }

    /**
     * @param bool|null $sendInvoice
     */
    public function setSendInvoice(?bool $sendInvoice): void
    {
        $this->sendInvoice = $sendInvoice;
    }
}
