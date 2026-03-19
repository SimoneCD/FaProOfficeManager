<?php

namespace App\Entity;

use App\Repository\FaqRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\Entity(repositoryClass: FaqRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $slackBearer;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private string $emailName;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $customerIdStart;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private string $holidaysBundesland;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $appActiveLog = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = [];

    /* Unmapped property to handle file uploads. */
    private $logoFile;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $logo;

    public function __construct()
    {
        $this->appActiveLog = [
            'firebase_active'  => false,
            'slack_active'  => false,
            'slack_log'  => false,
            'app_chat'  => false,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLogoFile(?UploadedFile $logoFile = null): void
    {
        $this->logoFile = $logoFile;
    }

    public function getLogoFile(): ?UploadedFile
    {
        return $this->logoFile;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getSlackBearer(): ?string
    {
        return $this->slackBearer;
    }

    public function setSlackBearer(?string $slackBearer): void
    {
        $this->slackBearer = $slackBearer;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmailName(): string
    {
        return $this->emailName;
    }

    public function setEmailName(string $emailName): void
    {
        $this->emailName = $emailName;
    }

    public function getCustomerIdStart(): int
    {
        return $this->customerIdStart;
    }

    public function setCustomerIdStart(int $customerIdStart): void
    {
        $this->customerIdStart = $customerIdStart;
    }

    public function getHolidaysBundesland(): string
    {
        return $this->holidaysBundesland;
    }

    public function setHolidaysBundesland(string $holidaysBundesland): void
    {
        $this->holidaysBundesland = $holidaysBundesland;
    }

    public function getAppActiveLog(): ?array
    {
        return $this->appActiveLog;
    }

    public function setAppActiveLog(?array $appActiveLog): void
    {
        $this->appActiveLog = $appActiveLog;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }

    public function addData(string $data): void
    {
        if (!in_array($data, $this->data)) {
            $this->data[] = $data;
        }
    }

    public function hasData(string $data): bool
    {
        return $this->data !== null && in_array($data, $this->data);
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }
//
//    public function upload(string $path = ''): void
//    {
//        // the file property can be empty if the field is not required
//        if (null === $this->getFile()) {
//            return;
//        }
//        /** @var UploadedFile $file */
//        $file = $this->getFile();
//        $date = new \DateTime();
//        $name = $file->getClientOriginalName();
//        $filename = $date->format('d.m.y_H.i').'_'.$name;
//
//        if (!is_dir($path)) {
//            @mkdir($path, 0777);
//        }
//        $file->move(
//            $path,
//            'logo_'.$filename
//        );
//        $this->filename = 'logo_'.$filename;
//        $this->setOffer($offer);
//        $this->setUpdated($date);
//        $this->setOriginalName($file->getClientOriginalName());
//        // dump($file->getType());die;
//        // $this->setMimeType($file->getMimeType());
//        if ($offer instanceof Offer) {
//            $offer->addDocument($this);
//        }
//        // clean up the file property as you won't need it anymore
//        $this->setFile(null);
//    }
//
//    /**
//     * Lifecycle callback to upload the file to the server.
//     */
//    public function lifecycleFileUpload(): void
//    {
//        $this->upload();
//    }
}
