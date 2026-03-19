<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
class Partner
{
    public const PARTNER_TYPES = [
        'Händler' => 'handler',
        'Versicherung' => 'versicherung',
        'Elektriker Mitarbeiter Unternehmen/Personen' => 'employee_el',
        'Dachdecker Mitarbeiter Unternehmen/Personen' => 'employee_d',
        'Sonstige Mitarbeiter Unternehmen/Personen' => 'employee',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProjectTeam>|\App\Entity\ProjectTeam[]
     */
    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: ProjectTeam::class)]
    private Collection $projectTeams;

    public function __construct()
    {
        $this->projectTeams = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTypeName(): ?string
    {
        return array_flip(self::PARTNER_TYPES)[$this->type];
    }

    public function setType(?string $type): void
    {
        $a = array_flip(self::PARTNER_TYPES);
        if (isset($a[$type])) {
            $this->type = $type;
        } else {
            $this->type = 'employee';
        }
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param string|null $link
     */
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
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

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, ProjectTeam>
     */
    public function getProjectTeams(): Collection
    {
        return $this->projectTeams;
    }

    public function addProjectTeam(ProjectTeam $projectTeam): self
    {
        if (!$this->projectTeams->contains($projectTeam)) {
            $this->projectTeams->add($projectTeam);
            $projectTeam->setPartner($this);
        }

        return $this;
    }

    public function removeProjectTeam(ProjectTeam $projectTeam): self
    {
        if ($this->projectTeams->removeElement($projectTeam)) {
            // set the owning side to null (unless already changed)
            if ($projectTeam->getPartner() === $this) {
                $projectTeam->setPartner(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }
}
