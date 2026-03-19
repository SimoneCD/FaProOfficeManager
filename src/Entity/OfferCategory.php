<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

/**
 *
 */
#[ORM\Entity(repositoryClass: OfferCategoryRepository::class)]
class OfferCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\OfferSubCategory>|\App\Entity\OfferSubCategory[]
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: OfferSubCategory::class, cascade: ['persist', 'remove'])]
    private Collection $offerSubCategories;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductCategory $productCategory = null;

    public function __construct()
    {
        $this->offerSubCategories = new ArrayCollection();
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
            $offerSubCategory->setCategory($this);
        }

        return $this;
    }

    public function removeOfferSubCategory(OfferSubCategory $offerSubCategory): self
    {
        if ($this->offerSubCategories->removeElement($offerSubCategory)) {
            // set the owning side to null (unless already changed)
            if ($offerSubCategory->getCategory() === $this) {
                $offerSubCategory->setCategory(null);
            }
        }

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    #[Pure]
    public function __toString(): string
    {
        return $this->getName();
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
}
