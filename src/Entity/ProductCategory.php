<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductCategoryRepository::class)]
class ProductCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Product>|\App\Entity\Product[]
     */
    #[ORM\OneToMany(mappedBy: 'productCategory', targetEntity: Product::class, cascade: ['persist'])]
    private Collection $products;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProductSubCategory>|\App\Entity\ProductSubCategory[]
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: ProductSubCategory::class, cascade: ['persist', 'remove'])]
    private Collection $productSubCategories;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->productSubCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $name): self
    {
        if (!$this->products->contains($name)) {
            $this->products->add($name);
            $name->setProduktCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProduktCategory() === $this) {
                $product->setProduktCategory(null);
            }
        }

        return $this;
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

    /**
     * @return Collection<int, ProductSubCategory>
     */
    public function getProductSubCategories(): Collection
    {
        return $this->productSubCategories;
    }

    public function addProductSubCategory(ProductSubCategory $productSubCategory): self
    {
        if (!$this->productSubCategories->contains($productSubCategory)) {
            $this->productSubCategories->add($productSubCategory);
            $productSubCategory->setCategory($this);
        }

        return $this;
    }

    public function removeProductSubCategory(ProductSubCategory $productSubCategory): self
    {
        if ($this->productSubCategories->removeElement($productSubCategory)) {
            // set the owning side to null (unless already changed)
            if ($productSubCategory->getCategory() === $this) {
                $productSubCategory->setCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
