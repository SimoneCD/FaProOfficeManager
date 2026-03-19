<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectTeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTeamRepository::class)]
class ProjectTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Ladestation/en an eine PV-Anlage (Solaranlage) koppeln? *.
     */
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isDefault = false;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'projectTeams')]
    private Collection $users;

    #[ORM\ManyToMany(targetEntity: Offer::class, inversedBy: 'projectTeams', cascade: ['persist'])]
    private Collection $offers;

    #[ORM\ManyToOne(targetEntity: ProjectTeamCategory::class, inversedBy: 'projectTeams')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?ProjectTeamCategory $category = null;

    #[ORM\ManyToOne(inversedBy: 'projectTeams')]
    private ?Partner $partner = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->offers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsDefault(): bool
    {
        return $this->isDefault ?? false;
    }

    public function setIsDefault(bool $default): void
    {
        $this->isDefault = $default;
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
     * @return Collection<int, User>
     */
    public function getUsers(): ArrayCollection|Collection
    {
        return $this->users;
    }

    public function setUsers(ArrayCollection|Collection $users): void
    {
        $this->users = $users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->addProjectTeam($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        if ($this->offers->removeElement($offer)) {
            if ($offer->getProjectTeams()->contains($this)) {
                $offer->removeProjectTeam($this);
            }
        }

        return $this;
    }

    public function getCategory(): ?ProjectTeamCategory
    {
        return $this->category;
    }

    public function setCategory(?ProjectTeamCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): self
    {
        $this->partner = $partner;

        return $this;
    }
}
