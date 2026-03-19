<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferSubCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferSubCategoryRepository::class)]
class OfferSubCategory
{
    public const FUNNEL_TYPES = [
        'pv_normal' => 'PV - Kontaktfunnel',
        'pv_hauptproduct' => 'PV - Kostenvoranschlag',
        // 'pv_solarcalculator' => 'Solarrechner',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceTitle = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceText = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $estimateMailText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mailText = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $invoiceMailText = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $partMailText = '';

    #[ORM\Column(nullable: true)]
    private ?bool $status = true;

    #[ORM\Column(nullable: true)]
    private ?bool $topFunnel = true;

    #[ORM\Column(nullable: true)]
    private ?string $type = 'pv_normal';

    #[ORM\ManyToOne(inversedBy: 'offerSubCategories')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?OfferCategory $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductSubCategory $productSubCategory = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\OfferQuestion>|\App\Entity\OfferQuestion[]
     */
    #[ORM\OneToMany(mappedBy: 'subCategory', targetEntity: OfferQuestion::class, cascade: ['persist', 'remove'])]
    private Collection $offerQuestions;

    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'offerSubCategories')]
    private Collection $products;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $estimateText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $offerText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $partInvoiceText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $invoiceText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $specifications = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\KeyValueSubCategoryData>|\App\Entity\KeyValueSubCategoryData[]
     */
    #[ORM\OneToMany(mappedBy: 'subCategory', targetEntity: KeyValueSubCategoryData::class, cascade: ['persist', 'remove'])]
    private Collection $keyValueSubCategoryData;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $image;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Offer>|\App\Entity\Offer[]
     */
    #[ORM\OneToMany(mappedBy: 'subCategory', targetEntity: Offer::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Collection $offers;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\QuestionArea>|\App\Entity\QuestionArea[]
     */
    #[ORM\OneToMany(mappedBy: 'subCategory', targetEntity: QuestionArea::class, cascade: ['persist'])]
    private Collection $questionAreas;

    #[ORM\ManyToMany(targetEntity: ProjectTeamCategory::class, inversedBy: 'subCategories')]
    private Collection $teamCategories;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = [];

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Document>|\App\Entity\Document[]
     */
    #[ORM\OneToMany(mappedBy: 'offerSubCategory', targetEntity: Document::class)]
    private Collection $requests;

    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(Document $request): self
    {
        if (!$this->requests->contains($request)) {
            $this->requests->add($request);
            $request->setOfferSubCategory($this);
        }

        return $this;
    }

    public function removeRequest(Document $request): self
    {
        if ($this->requests->removeElement($request)) {
            // set the owning side to null (unless already changed)
            if ($request->getOfferSubCategory() === $this) {
                $request->setOfferSubCategory(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image ?? '';
    }

    public function setImage(?string $image = ''): self
    {
        $this->image = $image;

        return $this;
    }

    public function __construct()
    {
        $this->offerQuestions = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->keyValueSubCategoryData = new ArrayCollection();
        $this->keyValueSubCategoryData = new ArrayCollection();
        $this->questionAreas = new ArrayCollection();
        $this->teamCategories = new ArrayCollection();
        $this->requests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name ?? '';
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?OfferCategory
    {
        return $this->category;
    }

    public function setCategory(?OfferCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, OfferQuestion>
     */
    public function getOfferQuestions(): Collection|array
    {
        $questions = [];
        if ($this->offerQuestions->count()) {
            /** @var OfferQuestion $question */
            $i = 0;
            foreach ($this->offerQuestions as $k => $question) {
                $questions[$question->getSort().$i++] = $this->offerQuestions->get($k);
            }
        } else {
            $questions = $this->offerQuestions;
        }

        return $questions;
    }

    public function setOfferQuestions(ArrayCollection|Collection $offerQuestions): void
    {
        $this->offerQuestions = $offerQuestions;
    }

    public function getOfferQuestionsSortByAnswerType(): Collection|array
    {
        $questions = [];
        if ($this->offerQuestions->count()) {
            /** @var OfferQuestion $question */
            $i = 1;
            foreach ($this->offerQuestions as $k => $question) {
                switch ($question->getAnswerType()) {
                    case 'length':
                        $questions[0] = $this->offerQuestions->get($k);
                        ++$i;
                        break;
                    case 'install_amount':
                        $questions[1] = $this->offerQuestions->get($k);
                        ++$i;
                        break;
                    case 'amount':
                        $questions[2] = $this->offerQuestions->get($k);
                        ++$i;
                        break;
                    default:
                        $questions[$i + 3] = $this->offerQuestions->get($k);
                        ++$i;
                        break;
                }
            }
        } else {
            $questions = $this->offerQuestions;
        }

        return $questions;
    }

    public function addOfferQuestion(OfferQuestion $offerQuestion): self
    {
        if (!$this->offerQuestions->contains($offerQuestion)) {
            $this->offerQuestions->add($offerQuestion);
            $offerQuestion->setSubCategory($this);
        }

        return $this;
    }

    public function removeOfferQuestion(OfferQuestion $offerQuestion): self
    {
        if ($this->offerQuestions->removeElement($offerQuestion)) {
            // set the owning side to null (unless already changed)
            if ($offerQuestion->getSubCategory() === $this) {
                $offerQuestion->setSubCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
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
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        $this->products->removeElement($product);

        return $this;
    }

    /**
     * @return Collection<int, KeyValueSubCategoryData>
     */
    public function getKeyValueSubCategoryData(): Collection
    {
        return $this->keyValueSubCategoryData;
    }

    public function addKeyValueSubCategoryData(KeyValueSubCategoryData $keyValueSubCategoryData): self
    {
        if (!$this->keyValueSubCategoryData->contains($keyValueSubCategoryData)) {
            $this->keyValueSubCategoryData->add($keyValueSubCategoryData);
            $keyValueSubCategoryData->setSubCategory($this);
        }

        return $this;
    }

    public function removeKeyValueSubCategoryData(KeyValueSubCategoryData $keyValueSubCategoryData): self
    {
        if ($this->keyValueSubCategoryData->removeElement($keyValueSubCategoryData)) {
            if ($keyValueSubCategoryData->getSubCategory() === $this) {
                $keyValueSubCategoryData->setSubCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSpecifications(): string
    {
        return $this->specifications ?? '';
    }

    public function setSpecifications(?string $specifications): void
    {
        $this->specifications = $specifications;
    }

    public function getServiceTitle(): ?string
    {
        return $this->serviceTitle;
    }

    public function setServiceTitle(?string $serviceTitle): void
    {
        $this->serviceTitle = $serviceTitle;
    }

    public function getServiceText(): ?string
    {
        return $this->serviceText;
    }

    public function setServiceText(?string $serviceText): void
    {
        $this->serviceText = $serviceText;
    }

    public function getProductSubCategory(): ?ProductSubCategory
    {
        return $this->productSubCategory;
    }

    public function setProductSubCategory(?ProductSubCategory $productSubCategory): void
    {
        $this->productSubCategory = $productSubCategory;
    }

    public function getStatus(): ?bool
    {
        return !empty($this->status);
    }

    public function setStatus(?bool $status): void
    {
        $this->status = $status;
    }

    public function getOffers(): ?Collection
    {
        return $this->offers;
    }

    public function setOffers(?Collection $offers): void
    {
        $this->offers = $offers;
    }

    public function addOffer(Offer $offer): self
    {
        if (!$this->offers->contains($offer)) {
            $this->offers[] = $offer;
            $offer->setSubCategory($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): self
    {
        // set the owning side to null (unless already changed)
        if ($this->offers->removeElement($offer) && $offer->getSubCategory() === $this) {
            $offer->setSubCategory(null);
        }

        return $this;
    }

    public function getEstimateMailText(): ?string
    {
        return $this->estimateMailText;
    }

    public function setEstimateMailText(?string $estimateMailText): void
    {
        $this->estimateMailText = $estimateMailText;
    }

    public function getEstimateText(): ?string
    {
        return $this->estimateText;
    }

    public function setEstimateText(?string $estimateText): void
    {
        $this->estimateText = $estimateText;
    }

    public function getMailText(): ?string
    {
        return $this->mailText;
    }

    public function setMailText(?string $mailText): void
    {
        $this->mailText = $mailText;
    }

    public function getInvoiceMailText(): ?string
    {
        return $this->invoiceMailText;
    }

    public function setInvoiceMailText(?string $invoiceMailText): void
    {
        $this->invoiceMailText = $invoiceMailText;
    }

    public function getPartMailText(): ?string
    {
        return $this->partMailText;
    }

    public function setPartMailText(?string $partMailText): void
    {
        $this->partMailText = $partMailText;
    }

    public function getOfferText(): ?string
    {
        return $this->offerText;
    }

    public function setOfferText(?string $offerText): void
    {
        $this->offerText = $offerText;
    }

    public function getPartInvoiceText(): ?string
    {
        return $this->partInvoiceText;
    }

    public function setPartInvoiceText(?string $partInvoiceText): void
    {
        $this->partInvoiceText = $partInvoiceText;
    }

    public function getInvoiceText(): ?string
    {
        return $this->invoiceText;
    }

    public function setInvoiceText(?string $invoiceText): void
    {
        $this->invoiceText = $invoiceText;
    }

    /**
     * @return Collection<int, QuestionArea>
     */
    public function getQuestionAreas(): Collection
    {
        return $this->questionAreas;
    }

    public function setQuestionAreas(Collection $areas): void
    {
        $this->questionAreas = $areas;
    }

    public function addQuestionArea(QuestionArea $questionArea): self
    {
        if (!$this->questionAreas->contains($questionArea)) {
            $this->questionAreas->add($questionArea);
            $questionArea->setSubCategory($this);
        }

        return $this;
    }

    public function removeQuestionArea(QuestionArea $questionArea): self
    {
        if ($this->questionAreas->removeElement($questionArea)) {
            // set the owning side to null (unless already changed)
            if ($questionArea->getSubCategory() === $this) {
                $questionArea->setSubCategory(null);
            }
        }

        return $this;
    }

    public function __clone()
    {
        $questionAreas = $this->getQuestionAreas();
        if ($this->id) {
            $this->id = null;
            $this->setQuestionAreas(new ArrayCollection());
            $this->setOfferQuestions(new ArrayCollection());
            $this->setOffers(null);
            $this->setImage(null);
            $questionAreasClone = new ArrayCollection();
            /** @var QuestionArea $questionArea */
            foreach ($questionAreas as $questionArea) {
                $questionAreaClone = clone $questionArea;
            }
        }
    }

    /**
     * @return Collection<int, ProjectTeamCategory>
     */
    public function getTeamCategories(): Collection
    {
        return $this->teamCategories;
    }

    public function addTeamCategory(ProjectTeamCategory $teamCategory): self
    {
        if (!$this->teamCategories->contains($teamCategory)) {
            $this->teamCategories->add($teamCategory);
        }

        return $this;
    }

    public function removeTeamCategory(ProjectTeamCategory $teamCategory): self
    {
        $this->teamCategories->removeElement($teamCategory);

        return $this;
    }

    public function getContext(): array
    {
        return $this->context ?? [];
    }

    public function setContext(?array $context): void
    {
        $this->context = $context;
    }

    public function addContext(array $context): void
    {
        foreach ($context as $k => $c) {
            $this->context[$k] = $c;
        }
    }

    public function getTopFunnel(): ?bool
    {
        return $this->topFunnel;
    }

    public function setTopFunnel(?bool $topFunnel): void
    {
        $this->topFunnel = $topFunnel;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        if (isset(self::FUNNEL_TYPES[$this->type])) {
            return self::FUNNEL_TYPES[$this->type];
        }
        return '';
    }

    public function setType(?string $type): void
    {
        if (isset(self::FUNNEL_TYPES[$type])) {
            $this->type = $type;
        }
    }
}
