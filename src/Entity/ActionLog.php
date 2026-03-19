<?php

namespace App\Entity;

use App\Repository\ActionLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ActionLogRepository::class)]
class ActionLog
{
    public const TYPE_CHOICES = [
        'miss' => [
            'open' => true,
            'label' => 'Material fehlt',
            'icon' => 'handyman',
        ],
        'copy' => [
            'open' => false,
            'label' => 'Angebot kopiert',
            'icon' => 'move_group',
        ],
        'ordered' => [
            'open' => true,
            'label' => 'Material bestellt',
            'icon' => 'shopping_cart',
        ],
        'delivered' => [
            'open' => true,
            'label' => 'Material geliefert',
            'icon' => 'local_shipping',
        ],
        'phone' => [
            'open' => true,
            'label' => 'Telefon Notiz',
            'icon' => 'call',
        ],
        'done' => [
            'open' => true,
            'label' => 'Arbeit erledigt',
            'icon' => 'check',
        ],
        'info' => [
            'open' => true,
            'label' => 'Notiz',
            'icon' => 'edit_note',
        ],
        'send' => [
            'open' => false,
            'label' => 'Gesendet',
            'icon' => 'send',
        ],
        'mail' => [
            'open' => false,
            'label' => 'E-MAil geschrieben',
            'icon' => 'mail',
        ],
        'new' => [
            'open' => false,
            'label' => 'Neu',
            'icon' => 'add',
        ],
        'delete' => [
            'open' => false,
            'label' => 'Gelöscht',
            'icon' => 'delete',
        ],
        'answer' => [
            'open' => false,
            'label' => 'Antwort',
            'icon' => 'post_add',
        ],
        'voiceOneKi' => [
            'open' => false,
            'label' => 'VoicePne KI Anruf',
            'icon' => 'api',
        ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(name: 'answer_id', referencedColumnName: 'id')]
    private ?ActionLog $answer = null;

    #[ORM\OneToMany(mappedBy: 'answer', targetEntity: self::class)]
    private Collection $answers;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    private ?Offer $offer = null;

    #[ORM\ManyToOne(inversedBy: 'actionLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
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

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setOffer(?Offer $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
    // Getters und Setters

    public function getAnswer(): ?ActionLog
    {
        return $this->answer;
    }

    public function setAnswer(?ActionLog $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(ActionLog $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setAnswer($this);
        }

        return $this;
    }

    public function removeAnswer(ActionLog $answer): self
    {
        if ($this->answers->contains($answer)) {
            $this->answers->removeElement($answer);
            // set the owning side to null (unless already changed)
            if ($answer->getAnswer() === $this) {
                $answer->setAnswer(null);
            }
        }

        return $this;
    }
}
