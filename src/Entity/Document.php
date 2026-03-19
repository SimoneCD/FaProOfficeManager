<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'offer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $filename;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $mimeType;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $type;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $typeId;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $originalName;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $updated;

    /**
     * Unmapped property to handle file uploads.
     */
    private $file;

    #[ORM\ManyToOne(inversedBy: 'certificats')]
    private ?Product $product = null;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'requests')]
    private ?OfferSubCategory $offerSubCategory = null;

    public function __construct()
    {
        $this->states = new ArrayCollection();
    }

    public function setFile(?UploadedFile $file = null): void
    {
        $this->file = $file;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * Manages the copying of the file to the relevant place on the server.
     */
    public function upload(?Offer $offer = null, string $path = ''): void
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }
        /** @var UploadedFile $file */
        $file = $this->getFile();
        $date = new \DateTime();
        $name = str_replace([' ', 'ö', 'ä', 'ü'], ['_', 'oe', 'ae', 'ue'], $file->getClientOriginalName());
        if ($offer instanceof Offer) {
            $filename = $offer->getNumber().'_'.$date->format('d.m.y_H.i').'_'.$name;
        } else {
            $filename = $date->format('d.m.y_H.i').'_'.$name;
        }
        $path = $offer ? $path.'/'.$offer->getId() : $path;

        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }
        $file->move(
            $path,
            'doc_'.$filename
        );
        $this->filename = 'doc_'.$filename;
        $this->setOffer($offer);
        $this->setUpdated($date);
        $this->setOriginalName($file->getClientOriginalName());
        // dump($file->getType());die;
        // $this->setMimeType($file->getMimeType());
        if ($offer instanceof Offer) {
            $offer->addDocument($this);
        }
        // clean up the file property as you won't need it anymore
        $this->setFile(null);
    }

    /**
     * Lifecycle callback to upload the file to the server.
     */
    public function lifecycleFileUpload(): void
    {
        $this->upload();
    }

    /**
     * Updates the hash value to force the preUpdate and postUpdate events to fire.
     */
    public function refreshUpdated(): void
    {
        $this->setUpdated(new \DateTime());
    }

    public function getFilename(): ?string
    {
        return $this->filename ?? '';
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTime $updated): void
    {
        $this->updated = $updated;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName ?? '';
    }

    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function __toString(): string
    {
        return $this->filename;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function setTypeId(?int $typeId): void
    {
        $this->typeId = $typeId;
    }

    /**
     * @return $this
     */
    public function __clone()
    {
        $this->id = null;
        $this->offer = null;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? '';
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description ?? '';
    }

    public function getOfferSubCategory(): ?OfferSubCategory
    {
        return $this->offerSubCategory;
    }

    public function setOfferSubCategory(?OfferSubCategory $offerSubCategory): self
    {
        $this->offerSubCategory = $offerSubCategory;

        return $this;
    }
}
