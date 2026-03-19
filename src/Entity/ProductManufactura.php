<?php

namespace App\Entity;

use App\Repository\ProductManufacturaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductManufacturaRepository::class)]
class ProductManufactura
{
    public const MANUFACTURA_TYPES = [
        'Sehr guter Hersteller' => 'handler_sgut',
        'Guter Hersteller' => 'handler_gut',
        'Hersteller' => 'handler',
        'Veralteter Hersteller' => 'handler_old',
        'Nur in Ausnahmen Hersteller' => 'handler_no',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $logo;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 105, nullable: true)]
    private ?string $userName = null;

    #[ORM\Column(length: 105, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Product>|\App\Entity\Product[]
     */
    #[ORM\OneToMany(mappedBy: 'productManufactura', targetEntity: Product::class, cascade: ['persist'])]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogo(): ?string
    {
        return $this->logo ?? '';
    }

    public function setLogo(?string $logo = ''): self
    {
        $this->logo = $logo;

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
            $product->setProductManufactura($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getProductManufactura() === $this) {
                $product->setProductManufactura(null);
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTypeName(): ?string
    {
        return array_flip(self::MANUFACTURA_TYPES)[$this->type];
    }

    public function setType(?string $type): void
    {
        $a = array_flip(self::MANUFACTURA_TYPES);
        if (isset($a[$type])) {
            $this->type = $type;
        } else {
            $this->type = 'handler';
        }
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * @param string|null $userName
     */
    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function __toString()
    {
        return $this->name;
    }
}
