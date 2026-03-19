<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferItemRepository::class)]
class OfferItem
{
    final public const PRODUCT_KEYS = [
        'Vorarbeiten' => 22,
        'Regieleistung' => 223,
        'Kleinmaterial' => 222,
        'Angebotserstellung' => 15,
        'An- und Abfahrt' => 225,
        'Kabelschutzrohr M25' => 175,
        'Kabelschutzrohr M32' => 174,
        'Kabelschutzrohr M50' => 215,
        'Datenleitung - Cat 7' => 149,
        'Hager Aufputz-Verteiler 36' => 126,
        'Hager Aufputz-Verteiler 24' => 125,
        'USB-WiFi 4G/ Europe' => 224,
        'Wetterschutzdach' => 87,
        'Pflasterarbeiten' => 24,
        'Erdarbeiten' => 23,
        'Kabelhalterung Typ 2 schwarz' => 74,
        'Kabelhalterung Typ 2 weiss' => 73,
        'Blitzschutz - Überspannungsableiter 3P' => 30,
        'Lastmanagement - Master/Slave' => 39,
        'Zaehleranschlusssaeule' => 217,
        'Zaehlerschrank' => 218,
        'Fundament Herstellung' => 184,
        'Pruefung vorab-Installations-Check' => 16,
        'Bauschaum (Brandschutzschaum Klasse 1)' => 170,
        'Durchbruch' => 185,
        'Ladestation Installation und Inbetriebnahme' => 17,
        'HPC Booster Montage und Installation' => 191,
        'HPC-Batteriemodule Installation' => 192,
        'Schnell-Ladestation Installation und Inbetriebnahme' => 141,
        'PV Installation und Inbetriebnahme' => 327,
        'NYM-J Leitung 100kw' => 147,
        'NYY-J Leitung 100kw' => 135,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $name;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'offerItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $item;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT)]
    private float $amount = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $einheit = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT)]
    private float $price;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescription(string $description = ''): void
    {
        $this->description = $description;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function getItem(): ?Product
    {
        return $this->item;
    }

    public function setItem(?Product $item): self
    {
        $this->item = $item;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount ?? 0;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount ?? 0;

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

    public function getPrice(): ?float
    {
        return $this->price ?? 0;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function __clone()
    {
        $this->id = null;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEinheit(): ?string
    {
        return $this->einheit;
    }

    public function setEinheit(?string $einheit): void
    {
        $this->einheit = $einheit;
    }
}
