<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferQuestionsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;

#[ORM\Entity(repositoryClass: OfferQuestionsRepository::class)]
#[ORM\Index(columns: ['sort'], name: 'sort_idx')]
class OfferQuestion
{
    public const ANSWER_HTML_TYPES_FORM = [
        'radio' => 'osc.quest.q.answerTypes.radio.name',
        'radio-plus' => 'osc.quest.q.answerTypes.radio.name',
        'select' => 'osc.quest.q.answerTypes.select.name',
        'checkbox' => 'osc.quest.q.answerTypes.checkbox.name',
        'hauptproduct' => 'osc.quest.q.answerTypes.mainProduct.name',
        'selectproduct' => 'osc.quest.q.answerTypes.productSelect.name',
        'text' => 'osc.quest.q.answerTypes.text.name',
        'number' => 'osc.quest.q.answerTypes.number.name',
        'textarea' => 'osc.quest.q.answerTypes.text.area',
        'amount' => 'osc.quest.q.answerTypes.amount.name',
        'install_amount' => 'osc.quest.q.answerTypes.installAmount.name',
        'length' => 'osc.quest.q.answerTypes.length.name',
    ];
    public const ANSWER_HTML_TYPES = [
        'radio' => [
            'help' => 'osc.quest.q.answerTypes.help.onePossible',
            'icon' => 'text_increase', ],
        'radio-plus' => [
            'help' => 'osc.quest.q.answerTypes.help.onePossiblePlus',
            'icon' => 'text_increase', ],
        'select' => [
            'help' => 'osc.quest.q.answerTypes.help.onePossible',
            'icon' => 'text_increase', ],
        'hauptproduct' => [
            'help' => 'osc.quest.q.answerTypes.mainProduct.help',
            'icon' => null, ],
        'selectproduct' => [
            'help' => null,
            'icon' => 'exposure_plus_1', ],
        'checkbox' => [
            'help' => 'osc.quest.q.answerTypes.help.morePossible',
            'icon' => 'text_increase', ],
        'text' => [
            'help' => 'osc.quest.q.answerTypes.text.help',
            'icon' => null, ],
        'number' => [
            'label' => 'osc.quest.q.answerTypes.number.name',
            'help' => '',
            'icon' => null, ],
        'textarea' => [
            'help' => 'osc.quest.q.answerTypes.text.help',
            'icon' => null, ],
        'amount' => [
            'label' => 'osc.quest.q.answerTypes.amount.name',
            'help' => 'osc.quest.q.answerTypes.help.useOneTime',
            'icon' => null, ],
        'install_amount' => [
            'help' => 'osc.quest.q.answerTypes.help.useOneTime',
            'icon' => null, ],
        'length' => [
            'help' => 'osc.quest.q.answerTypes.help.useOneTime',
            'icon' => null, ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT)]
    private ?string $description = '';

    #[ORM\Column]
    private ?float $sort = 0;

    #[ORM\Column]
    private ?bool $view = true;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $needImage = false;

    #[ORM\Column(length: 20)]
    private ?string $answerType = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\OfferAnswers>|\App\Entity\OfferAnswers[]
     */
    #[ORM\OneToMany(mappedBy: 'question', targetEntity: OfferAnswers::class, cascade: ['persist', 'remove'])]
    private Collection $offerAnswers;

    #[ORM\ManyToOne(inversedBy: 'offerQuestions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?OfferSubCategory $subCategory = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductSubCategory $productSelectSubCategory = null;

    #[ORM\Column(length: 20)]
    private ?string $productSelectSubCategoryAnz = '1';

    #[ORM\Column(nullable: true)]
    private ?bool $funnelEnd = false;
    /**
     * p = form in protocol.
     * pq = default = as form in questionnaire and only as view in the protocol.
     * q = only form in questionnaire.
     */
    #[ORM\Column(length: 3)]
    private ?string $protocol = 'pq';

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?QuestionArea $questionArea = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Image>|\App\Entity\Image[]
     */
    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Image::class)]
    private Collection $images;

    public function __construct()
    {
        $this->offerAnswers = new ArrayCollection();
        $this->images = new ArrayCollection();
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

    public function getSort(): ?float
    {
        return $this->sort;
    }

    /**
     * @param $sort
     */
    public function setSort($sort): void
    {
        $this->sort = $sort ? floatval(str_replace(',', '.', $sort.'')) : 0;
    }

    public function isView(): bool
    {
        return $this->view ?? false;
    }

    public function setView(?bool $view): self
    {
        $this->view = $view ?? false;

        return $this;
    }

    public function getAnswerType(): ?string
    {
        return $this->answerType;
    }

    public function setAnswerType(string $answerType): self
    {
        $this->answerType = $answerType;

        return $this;
    }

    /**
     * @return Collection<int, OfferAnswers>
     */
    public function getOfferAnswers(): Collection
    {
        return $this->offerAnswers ?? new ArrayCollection();
    }

    public function addOfferAnswer(OfferAnswers $offerAnswer): self
    {
        if (!$this->offerAnswers->contains($offerAnswer)) {
            $this->offerAnswers->add($offerAnswer);
            $offerAnswer->setQuestion($this);
        }

        return $this;
    }

    public function removeOfferAnswer(OfferAnswers $offerAnswer): self
    {
        if ($this->offerAnswers->removeElement($offerAnswer)) {
            // set the owning side to null (unless already changed)
            if ($offerAnswer->getQuestion() === $this) {
                $offerAnswer->setQuestion(null);
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

    #[Pure]
    public function __toString(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getProductSelectSubCategory(): ?ProductSubCategory
    {
        return $this->productSelectSubCategory;
    }

    public function setProductSelectSubCategory(?ProductSubCategory $productSelectSubCategory): self
    {
        $this->productSelectSubCategory = $productSelectSubCategory;

        return $this;
    }

    public function getProductSelectSubCategoryAnz(): string
    {
        return $this->productSelectSubCategoryAnz ?? '1';
    }

    public function setProductSelectSubCategoryAnz(?string $productSelectSubCategoryAnz = '1'): void
    {
        $this->productSelectSubCategoryAnz = $productSelectSubCategoryAnz;
    }

    public function getQuestionArea(): ?QuestionArea
    {
        return $this->questionArea;
    }

    public function setQuestionArea(?QuestionArea $questionArea): self
    {
        $this->questionArea = $questionArea;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    public function setProtocol(?string $protocol): void
    {
        $this->protocol = $protocol ?? 'pq';
    }

    public function isNeedimage(): bool
    {
        return $this->needImage ?? false;
    }

    public function setNeedimage(?bool $needImage): void
    {
        $this->needImage = $needImage;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
        $answers = $this->getOfferAnswers();
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setQuestion($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getQuestion() === $this) {
                $image->setQuestion(null);
            }
        }

        return $this;
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
