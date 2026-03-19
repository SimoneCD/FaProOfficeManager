<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuestionAreaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionAreaRepository::class)]
class QuestionArea
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\OfferQuestion>|\App\Entity\OfferQuestion[]
     */
    #[ORM\OneToMany(mappedBy: 'questionArea', targetEntity: OfferQuestion::class, cascade: ['persist']),]
    private Collection $questions;

    #[ORM\ManyToOne(inversedBy: 'questionAreas')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OfferSubCategory $subCategory = null;

    #[ORM\Column]
    private ?float $sort = 0;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
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
     * @return Collection<int, OfferQuestion>
     */
    public function getQuestions(): Collection
    {
        $q = [];
        foreach ($this->questions as $i => $question) {
            $q[$question->getSort().$i] = $question;
        }

        return new ArrayCollection($q);
    }

    public function addQuestion(OfferQuestion $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuestionArea($this);
        }

        return $this;
    }

    public function removeQuestion(OfferQuestion $question): self
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getQuestionArea() === $this) {
                $question->setQuestionArea(null);
            }
        }

        return $this;
    }

    public function getSubCategory(): ?OfferSubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?OfferSubCategory $subCategory): self
    {
        $this->subCategory = $subCategory;

        return $this;
    }

    public function getSort(): float|int
    {
        return $this->sort ?? 0;
    }

    public function setSort(null|int|float $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }

        $questions = $this->getQuestions();
    }
}
