<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'offer_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Offer $offer;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $filename;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $mimeType;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $originalName;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $updated;

    /**
     * Unmapped property to handle file uploads.
     */
    private $file;

    #[ORM\ManyToOne(inversedBy: 'images')]
    private ?OfferQuestion $question = null;

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
        $name = str_replace(' ', '_', $date->format('Ymd-h-i-s').'.'.$file->getClientOriginalExtension());
        if ($offer instanceof Offer) {
            $filename = $offer->getNumber().'_'.$date->format('d.m.y_H.i.s').'_'.$name;
        } else {
            $filename = $date->format('d.m.y_H.i.s').'_'.$name;
        }
        $path = $offer ? $path.'/'.$offer->getId() : $path;
        if (!is_dir($path)) {
            @mkdir($path, 0777);
        }
        $file->move(
            $path,
            $filename
        );
        $this->filename = $filename;
        $this->setOffer($offer);
        $this->setUpdated($date);
        $this->setOriginalName($file->getClientOriginalName());
        if ($offer instanceof Offer) {
            $offer->addImage($this);
        }
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
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function __toString(): string
    {
        return $this->filename;
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

    public function getQuestion(): ?OfferQuestion
    {
        return $this->question;
    }

    public function setQuestion(?OfferQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }
}
