<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferAnswersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 */
#[ORM\Entity(repositoryClass: OfferAnswersRepository::class)]
class OfferAnswers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'offerAnswers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OfferQuestion $question = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    private ?float $sort = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $image;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $customerimage;

    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'offerAnswers')]
    private Collection $products;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $productMultiplicator;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $dependencies;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $helptext = '';

    #[ORM\Column(nullable: true)]
    private ?bool $funnelEnd = false;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?OfferQuestion
    {
        return $this->question;
    }

    public function setQuestion(?OfferQuestion $question): self
    {
        $this->question = $question;

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
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->products->removeElement($product);

        return $this;
    }


    public function __toStrimg(): string
    {
        return $this->getName();
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getDependencies(): ?array
    {
        $dependencies = $this->dependencies ?? [];

        return array_unique($dependencies);
    }

    public function setDependencies(?array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    public function addDependency(string $dependency): void
    {
        if (!in_array($dependency, $this->dependencies)) {
            $this->dependencies[] = $dependency;
        }
    }

    public function hasDependency(string $dependency): bool
    {
        return $this->dependencies !== null && in_array($dependency, $this->dependencies);
    }

    public function getHelptext(): ?string
    {
        return $this->helptext;
    }

    public function setHelptext(?string $helptext): void
    {
        $this->helptext = $helptext;
    }


    public function getProductMultiplicator(): ?string
    {
        return $this->productMultiplicator;
    }

    public function setProductMultiplicator(?string $productMultiplicator): void
    {
        $this->productMultiplicator = $productMultiplicator;
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

    public function getCustomerimage(): ?string
    {
        return $this->customerimage ?? '';
    }

    public function setCustomerimage(?string $image = ''): self
    {
        $this->customerimage = $image;

        return $this;
    }

    public function getSort(): float|int
    {
        return $this->sort ?? 0;
    }

    public function setSort(float|int|null $sort): void
    {
        $this->sort = $sort;
    }

    public function getFunnelEnd(): ?bool
    {
        return $this->funnelEnd;
    }

    public function setFunnelEnd(?bool $funnelEnd): void
    {
        $this->funnelEnd = $funnelEnd;
    }
}
