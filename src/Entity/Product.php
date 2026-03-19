<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'product')]
#[ORM\Index(columns: ['name'], name: 'create_name_idx')]
#[ORM\Index(columns: ['product_type'], name: 'create_type_id')]
#[ORM\Index(columns: ['product_number'], name: 'create_number_id')]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    final public const COLORS = [
        '#ffffff' => 'w.colors.white',
        '#000000' => 'w.colors.black',
        '#8b8b8b' => 'w.colors.grey',
        '#dee2e6' => 'w.colors.silver',
        '#666666' => 'w.colors.anthrazit',
        '#aa51b9' => 'w.colors.collorfull',
        '#2196f3' => 'w.colors.color',
    ];

    public function getTypeByName(string $name): string
    {
        return $name;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $valueName;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $einheit;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $image;

    #[ORM\Column(type: Types::STRING, length: 105, nullable: true)]
    private string $productNumber;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $salesInfo = '';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $shop = '';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $inStock = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $manufacturerName = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $manufacturerInfo = '';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $manufacturerWarranty = '';

    /**
     * Mit welcher Ladeleistung (kW) soll geladen werden? *.
     */
    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\Type(type: 'float', message: 'Der Wert {{ value }} ist kein gültiger Nummerischer Wert.')]
    private ?float $kw = 0;

    /**
     * Mit welcher Ladeleistung (kW) soll geladen werden? *.
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $color = '#fff';

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
    private bool $funnel = true;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private bool $partner = true;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $kfw = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $ibb = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private bool $bmvi = false;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true, options: ['default' => 1])]
    private ?string $deliveryTime = '';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['default' => ''])]
    private ?string $description = '';

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['default' => ''])]
    private ?string $shopLink = '';

    /** @var string|null GTIN(Global Trade Item Number) */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $ean = '';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $productType = '';

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['default' => 0])]
    private float $stock = 0.0;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['default' => 0])]
    private float $price = 0.0;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['default' => 0])]
    private ?float $ekPrice = 0.0;

    /**
     * Ladestation/en an eine PV-Anlage (Solaranlage) koppeln? *.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $solar = false;

    #[ORM\ManyToMany(targetEntity: OfferAnswers::class, mappedBy: 'products')]
    private Collection $offerAnswers;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductManufactura $productManufactura = null;

    #[ORM\ManyToMany(targetEntity: OfferSubCategory::class, mappedBy: 'products')]
    private Collection $offerSubCategories;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductCategory $productCategory = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductSubCategory $productSubCategory = null;

    #[ORM\ManyToMany(targetEntity: self::class)]
    private ?Collection $products;

    #[ORM\Column(nullable: true)]
    private ?bool $workerProduct = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Document>|\App\Entity\Document[]
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Document::class)]
    private Collection $certificats;

    public function __construct()
    {
        $this->offerAnswers = new ArrayCollection();
        $this->offerSubCategories = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->certificats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getName(): ?string
    {
        return $this->name ?? '';
    }

    public function getFullName(): ?string
    {
        return ($this->name ?? '').' | '.$this->getKw().' '.$this->getValueName();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
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

    public function getDeliveryTime(): string
    {
        return $this->deliveryTime ?? '';
    }

    public function setDeliveryTime(?string $deliveryTime = ''): Product
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    public function getValueName(): ?string
    {
        return $this->valueName ?? '';
    }

    public function setValueName(?string $valueName): void
    {
        $this->valueName = $valueName;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(string $type = ''): self
    {
        $this->productType = $type;

        return $this;
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

    public function getShopLink(): ?string
    {
        return $this->shopLink;
    }

    public function setShopLink(?string $shopLink): void
    {
        $this->shopLink = $shopLink;
    }

    public function getStock(): float
    {
        return $this->stock;
    }

    public function setStock(float $stock): void
    {
        $this->stock = $stock;
    }

    public function getEkPrice(): float
    {
        return $this->ekPrice;
    }

    public function setEkPrice(float $ekPrice): void
    {
        $this->ekPrice = $ekPrice;
    }

    public function getProductNumber(): string
    {
        return $this->productNumber ?? '';
    }

    public function setProductNumber(string $productNumber): void
    {
        $this->productNumber = $productNumber;
    }

    public function getKw(): float
    {
        return $this->kw ?? 0;
    }

    public function setKw(?float $kw = 0): void
    {
        $this->kw = $kw;
    }

    public function isKfw(): bool
    {
        return $this->kfw;
    }

    public function setKfw(bool $kfw): void
    {
        $this->kfw = $kfw;
    }

    public function isIbb(): bool
    {
        return $this->ibb;
    }

    public function setIbb(bool $ibb): void
    {
        $this->ibb = $ibb;
    }

    public function isBmvi(): bool
    {
        return $this->bmvi;
    }

    public function setBmvi(bool $bmvi): void
    {
        $this->bmvi = $bmvi;
    }

    public function isPartner(): bool
    {
        return $this->partner;
    }

    public function setPartner(bool $partner): void
    {
        $this->partner = $partner;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isSolar(): bool
    {
        return $this->solar;
    }

    public function setSolar(bool $solar = false): void
    {
        $this->solar = $solar;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getEinheit(): string
    {
        return $this->einheit ?? '';
    }

    /**
     * @param string $einheit
     */
    public function setEinheit(?string $einheit): void
    {
        $this->einheit = $einheit;
    }

    public function __toString(): string
    {
        $color = empty($this->getColor()) ? '#fff' : $this->getColor();

        return $this->getName().' '.$color.' ('.$this->getKw().') '.$this->getValueName().'';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'color' => $this->getColor(),
            'name' => $this->getName(),
            'kw' => $this->getKw(),
            'solar' => $this->isSolar(),
            'deliveryTime' => $this->getDeliveryTime(),
            'description' => $this->getDescription(),
            'productType' => $this->getProductType(),
            'price' => number_format($this->getPrice(), 2, ',', '.'),
            'einheit' => $this->getEinheit() ?? '',
            'valueName' => $this->getValueName() ?? '',
        ];
    }

    /**
     * @return Collection<int, OfferAnswers>
     */
    public function getOfferAnswers(): Collection
    {
        return $this->offerAnswers;
    }

    public function addOfferAnswer(OfferAnswers $offerAnswer): self
    {
        if (!$this->offerAnswers->contains($offerAnswer)) {
            $this->offerAnswers->add($offerAnswer);
            $offerAnswer->addProduct($this);
        }

        return $this;
    }

    public function removeOfferAnswer(OfferAnswers $offerAnswer): self
    {
        if ($this->offerAnswers->removeElement($offerAnswer)) {
            $offerAnswer->removeProduct($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, OfferSubCategory>
     */
    public function getOfferSubCategories(): Collection
    {
        return $this->offerSubCategories;
    }

    public function addOfferSubCategory(OfferSubCategory $offerSubCategory): self
    {
        if (!$this->offerSubCategories->contains($offerSubCategory)) {
            $this->offerSubCategories->add($offerSubCategory);
            $offerSubCategory->addProduct($this);
        }

        return $this;
    }

    public function removeOfferSubCategory(OfferSubCategory $offerSubCategory): self
    {
        if ($this->offerSubCategories->removeElement($offerSubCategory)) {
            $offerSubCategory->removeProduct($this);
        }

        return $this;
    }

    public function getProductCategory(): ?ProductCategory
    {
        return $this->productCategory;
    }

    public function setProductCategory(?ProductCategory $productCategory): self
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    public function getProductManufactura(): ?ProductManufactura
    {
        return $this->productManufactura;
    }

    public function setProductManufactura(?ProductManufactura $productManufactura): self
    {
        $this->productManufactura = $productManufactura;

        return $this;
    }

    public function getProductSubCategory(): ?ProductSubCategory
    {
        return $this->productSubCategory;
    }

    public function setProductSubCategory(?ProductSubCategory $productSubCategory): self
    {
        $this->productSubCategory = $productSubCategory;

        return $this;
    }

    public function getSalesInfo(): ?string
    {
        return $this->salesInfo;
    }

    public function setSalesInfo(?string $salesInfo): void
    {
        $this->salesInfo = $salesInfo;
    }

    public function getInStock(): ?int
    {
        return $this->inStock;
    }

    public function setInStock(?int $inStock): void
    {
        $this->inStock = $inStock;
    }

    public function getManufacturerName(): string
    {
        return $this->manufacturerName ?? '';
    }

    public function setManufacturerName(?string $manufacturerName): void
    {
        $this->manufacturerName = $manufacturerName;
    }

    public function getManufacturerInfo(): string
    {
        return $this->manufacturerInfo ?? '';
    }

    public function setManufacturerInfo(?string $manufacturerInfo): void
    {
        $this->manufacturerInfo = $manufacturerInfo;
    }

    public function getManufacturerWarranty(): ?string
    {
        return $this->manufacturerWarranty;
    }

    public function setManufacturerWarranty(?string $manufacturerWarranty): void
    {
        $this->manufacturerWarranty = $manufacturerWarranty;
    }

    public function getShop(): ?string
    {
        return $this->shop;
    }

    public function setShop(?string $shop): void
    {
        $this->shop = $shop;
    }

    public function getProducts(): Collection
    {
        return $this->products ?? new ArrayCollection();
    }

    public function setProducts(?ArrayCollection $products): self
    {
        $this->products = $products;

        return $this;
    }

    public function addProduct(self $product): self
    {
        $this->products = $this->getProducts();
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }


        return $this;
    }

    public function removeProduct(self $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProducts() === $this) {
                $product->setProducts(null);
            }
        }

        return $this;
    }

    public function isWorkerProduct(): ?bool
    {
        return $this->workerProduct;
    }

    public function setWorkerProduct(?bool $workerProduct): self
    {
        $this->workerProduct = $workerProduct;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getCertificats(): Collection
    {
        return $this->certificats;
    }

    public function addCertificat(Document $certificat): self
    {
        if (!$this->certificats->contains($certificat)) {
            $this->certificats->add($certificat);
            $certificat->setProduct($this);
        }

        return $this;
    }

    public function removeCertificat(Document $certificat): self
    {
        if ($this->certificats->removeElement($certificat)) {
            // set the owning side to null (unless already changed)
            if ($certificat->getProduct() === $this) {
                $certificat->setProduct(null);
            }
        }

        return $this;
    }

    public function getEan(): string
    {
        return $this->ean ?? '';
    }

    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    public function isFunnel(): bool
    {
        return $this->funnel;
    }

    public function setFunnel(bool $funnel): void
    {
        $this->funnel = $funnel;
    }
}
