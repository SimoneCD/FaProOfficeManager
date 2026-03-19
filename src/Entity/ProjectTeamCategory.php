<?php

namespace App\Entity;

use App\Repository\ProjectTeamCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectTeamCategoryRepository::class)]
class ProjectTeamCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProjectTeam>|\App\Entity\ProjectTeam[]
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: ProjectTeam::class)]
    private Collection $projectTeams;

    #[ORM\Column]
    private ?bool $intern = null;

    #[ORM\ManyToMany(targetEntity: OfferSubCategory::class, mappedBy: 'teamCategories')]
    private Collection $subCategories;

    public function __construct()
    {
        $this->projectTeams = new ArrayCollection();
        $this->subCategories = new ArrayCollection();
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
            $projectTeam->setCategory($this);
        }

        return $this;
    }

    public function removeProjectTeam(ProjectTeam $projectTeam): self
    {
        if ($this->projectTeams->removeElement($projectTeam)) {
            // set the owning side to null (unless already changed)
            if ($projectTeam->getProjectTeamCategory() === $this) {
                $projectTeam->setProjectTeamCategory(null);
            }
        }

        return $this;
    }

    public function isIntern(): ?bool
    {
        return $this->intern;
    }

    public function setIntern(bool $intern): self
    {
        $this->intern = $intern;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, OfferSubCategory>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(OfferSubCategory $subCategory): self
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
            $subCategory->addTeamCategory($this);
        }

        return $this;
    }

    public function removeSubCategory(OfferSubCategory $subCategory): self
    {
        if ($this->subCategories->removeElement($subCategory)) {
            $subCategory->removeTeamCategory($this);
        }

        return $this;
    }
}
