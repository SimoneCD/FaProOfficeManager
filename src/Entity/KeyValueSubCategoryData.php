<?php

namespace App\Entity;

use App\Repository\KeyValueSubCategoryDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeyValueSubCategoryDataRepository::class)]
class KeyValueSubCategoryData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'keyValueSubCategoryData')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?OfferSubCategory $subCategory = null;

    #[ORM\Column(length: 100)]
    private ?string $keyName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $keyValue = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $keySort = 0.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubCategory(): OfferSubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?OfferSubCategory $subCategory): self
    {
        $this->subCategory = $subCategory;

        return $this;
    }

    public function getKeyName(): ?string
    {
        return $this->keyName;
    }

    public function setKeyName(string $keyName): self
    {
        $this->keyName = $keyName;

        return $this;
    }

    public function getKeyValue(): ?string
    {
        return $this->keyValue ?? '';
    }

    public function setKeyValue(?string $keyValue): self
    {
        $this->keyValue = $keyValue;

        return $this;
    }

    public function getKeySort(): float|int
    {
        return $this->keySort ?? 0;
    }

    public function setKeySort(null|int|float $keySort): self
    {
        $this->keySort = $keySort ?? 0;

        return $this;
    }
}
