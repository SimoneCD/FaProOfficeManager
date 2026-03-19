<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferOptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: OfferOptionRepository::class)]
class OfferOption
{
    /**
     * @var mixed|\Symfony\Component\Security\Core\User\UserInterface|null
     */
    public $user;
    /**
     * @var mixed|\App\Entity\User|null
     */
    public $monteur;
    /**
     * @var mixed|string
     */
    public $color;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id;

    #[ORM\OneToOne(mappedBy: 'option', targetEntity: Offer::class)]
    ## [ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $context = [];

    /**
     * Wie viele Ladestationen werden benötigt? *.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true, options: ['default' => 50])]
    private float $invoicePercent = 50;

    /**
     * Ladestation/en an eine PV-Anlage (Solaranlage) koppeln? *.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $solar = false;

    /**
     * Bis zum Termin Ausblenden *.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, scale: 2, nullable: true, options: ['default' => 0])]
    private ?bool $blendOut = false;

    /**
     * Angerufen? *.
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, scale: 2, nullable: true, options: ['default' => 0])]
    private ?bool $called = false;

    public function getCalled(): ?bool
    {
        return $this->called ?? false;
    }

    public function setCalled(?bool $called): void
    {
        $this->called = $called;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoicePercent(): float
    {
        return $this->invoicePercent ?? 50;
    }

    public function setInvoicePercent(?float $invoicePercent): void
    {
        $this->invoicePercent = $invoicePercent;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

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

    public function isSolar(): bool
    {
        return $this->solar ?? false;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): void
    {
        $this->offer = $offer;
    }

    public function setSolar(?bool $solar = false): void
    {
        $this->solar = $solar;
    }

    public function getColor(): ?string
    {
        return $this->color ?? '#ffffff';
    }

    public function setColor(?string $color): void
    {
        $this->color = $color ?? '#ffffff';
    }

    public function getOutletFuseMeter(): int
    {
        return $this->outletFuseMeter ?? 0;
    }

    public function setOutletFuseMeter(?int $outletFuseMeter): void
    {
        $this->outletFuseMeter = $outletFuseMeter;
    }

    public function getBlendOut(): ?bool
    {
        return $this->blendOut ?? false;
    }

    public function setBlendOut(?bool $blendOut): void
    {
        $this->blendOut = $blendOut;
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

    public function __clone()
    {
        $this->id = null;
    }
}
