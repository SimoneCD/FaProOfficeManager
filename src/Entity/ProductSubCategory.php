<?php

namespace App\Entity;

use App\Repository\ProductSubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductSubCategoryRepository::class)]
#[ORM\Index(columns: ['sort'], name: 'psc_sort_idx')]
class ProductSubCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'productSubCategories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProductCategory $category = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Product>|\App\Entity\Product[]
     */
    #[ORM\OneToMany(mappedBy: 'productSubCategory', targetEntity: Product::class, cascade: ['persist'])]
    private Collection $products;

    #[ORM\Column]
    private ?bool $mainProduct = false;

    #[ORM\Column(nullable: true)]
    private ?bool $global = false;

    #[ORM\Column(nullable: true)]
    private ?float $sort = 0;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

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

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setProductSubCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProductSubCategory() === $this) {
                $product->setProductSubCategory(null);
            }
        }

        return $this;
    }

    public function isMainProduct(): bool
    {
        return $this->mainProduct ?? false;
    }

    public function setMainProduct(?bool $mainProduct): void
    {
        $this->mainProduct = $mainProduct;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function isGlobal(): bool
    {
        return $this->global ?? false;
    }

    public function setGlobal(?bool $global): self
    {
        $this->global = $global ?? false;

        return $this;
    }

    public function getSort(): float
    {
        return $this->sort ?? 0.0;
    }

    public function setSort(null|int|float $sort): void
    {
        $this->sort = $sort;
    }
}
