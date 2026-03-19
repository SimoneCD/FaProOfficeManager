<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User.
 */
#[ORM\Table(name: 'user')]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\Index(columns: ['username'], name: 'username_idx')]
#[ORM\Index(columns: ['full_name'], name: 'full_name_idx')]
#[ORM\Index(columns: ['email', 'password'], name: 'login_idx')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    final public const USER_ROLES = [
        'ROLE_EMPLOYEE_ONE' => 'w.securityRoles.ROLE_EMPLOYEE_ONE',
        'ROLE_CUSTOMER' => 'Kunde',
        'ROLE_MONTAGE' => 'w.securityRoles.ROLE_MONTAGE',
        'ROLE_EMPLOYEE_EXTERN' => 'w.securityRoles.ROLE_EMPLOYEE_EXTERN',
        'ROLE_EMPLOYEE_SERVICE' => 'w.securityRoles.ROLE_EMPLOYEE_SERVICE',
        'ROLE_ADMIN' => 'w.securityRoles.ROLE_ADMIN',
        'ROLE_SUPER_ADMIN' => 'w.securityRoles.ROLE_SUPER_ADMIN',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    private string $salutation;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    #[Assert\NotBlank]
    private string $fullName;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $slackId;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $slackLog = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    private string $username;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, unique: true)]
    #[Assert\Email]
    private string $email;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Email>|\App\Entity\Email[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Email::class, cascade: ['persist', 'remove'])]
    private ?Collection $emails;

    private ?string $plainPassword;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING)]
    private ?string $password;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $status = true;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $phone;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $color;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private array $roles = ['ROLE_CUSTOMER'];

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Invoice>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Invoice::class, cascade: ['persist'])]
    private \Doctrine\Common\Collections\Collection $invoices;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Inquiry>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Inquiry::class, cascade: ['persist'])]
    private \Doctrine\Common\Collections\Collection $inquiries;

    #[ORM\ManyToMany(targetEntity: ProjectTeam::class, mappedBy: 'users')]
    private Collection $projectTeams;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private int $unreadChats = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $notice = '0';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $positionName = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    private ?float $nettoSalary = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\CustomerNotes>|\App\Entity\CustomerNotes[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CustomerNotes::class)]
    private Collection $customerNotes;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ActionLog>|\App\Entity\ActionLog[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ActionLog::class)]
    private Collection $actionLogs;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, nullable: true)]
    private ?string $image;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Timesheet>|\App\Entity\Timesheet[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Timesheet::class)]
    private Collection $timesheets;

    public function __construct()
    {
        $this->inquiries = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->projectTeams = new ArrayCollection();
        $this->customerNotes = new ArrayCollection();
        $this->actionLogs = new ArrayCollection();
        $this->timesheets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvatarUri(int $size = 32): string
    {
        $color = 'fff'; // str_starts_with($this->getFullName(), 'Herr') ? 'fff' : 'fff';
        $name = str_replace(['Herr', 'Frau'], '', $this->getFullName());
        if ($this->getImage() instanceof Image) {
            return '';
        }

        return 'https://ui-avatars.com/api/?'.http_build_query([
            'name' => $name,
            'size' => $size,
            'background' => $this->getColor(),
            'color' => $color,
        ]);
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): void
    {
        $this->status = $status;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getColor(): string
    {
        return empty($this->color) ? ($this->getSalutation() === 'Frau' ? 'e63946' : '1982c4') : (str_replace('#', '', $this->color));
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getInvoices()
    {
        return $this->invoices;
    }

    public function setInvoices($invoices): void
    {
        $this->invoices = $invoices;
    }

    public function getInquiries()
    {
        return $this->inquiries;
    }

    public function setInquiries($inquiries): void
    {
        $this->inquiries = $inquiries;
    }

    public function getShortSalutation(): string
    {
        if ($this->salutation === 'mr') {
            return 'mrShort';
        } elseif ($this->salutation === 'ms') {
            return 'msShort';
        }

        return 'maShort';
    }

    public function getSalutation(): string
    {
        return $this->salutation ?? 'Herr';
    }

    public function setSalutation(string $salutation): void
    {
        $this->salutation = $salutation;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword ?? '';
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function getRoleName(string $role): string
    {
        return self::USER_ROLES[$role];
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function addRole(string $role): void
    {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function getEmails(string $email): Collection
    {
        return $this->emails ?? new ArrayCollection();
    }

    public function setEmails(Collection $emails): void
    {
        $this->emails = $emails;
    }

    public function addEmail(string $email): void
    {
        if (!$this->emails->contains($email)) {
            $this->emails[] = $email;
        }
    }

    public function hasEmail(string $email): bool
    {
        return in_array($email, (array) $this->emails);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        // We're using bcrypt in security.yaml to encode the password, so
        // the salt value is built-in and and you don't have to generate one
        // See https://en.wikipedia.org/wiki/Bcrypt

        return null;
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials(): void
    {
        // if you had a plainPassword property, you'd nullify it here
        $this->plainPassword = null;
    }

    public function __serialize(): array
    {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        return [$this->id, $this->username, $this->password];
    }

    public function __unserialize(array $data): void
    {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        [$this->id, $this->username, $this->password] = $data;
    }

    public function getInvoice(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setUser($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        // set the owning side to null (unless already changed)
        if ($this->invoices->removeElement($invoice) && $invoice->getUser() === $this) {
            $invoice->setUser(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getUsername();
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
            $projectTeam->addUser($this);
        }

        return $this;
    }

    public function removeProjectTeam(ProjectTeam $projectTeam): self
    {
        if ($this->projectTeams->removeElement($projectTeam)) {
            $projectTeam->removeUser($this);
        }

        return $this;
    }

    public function getUnreadChats(): int
    {
        return $this->unreadChats ?? 0;
    }

    public function setUnreadChats(int $unreadChats): void
    {
        $this->unreadChats = $unreadChats;
    }

    public function getNotice(): string
    {
        return $this->notice ?? '';
    }

    public function setNotice(?string $notice): void
    {
        $this->notice = $notice;
    }

    public function getPositionName(): string
    {
        return $this->positionName ?? 'Kundenservice';
    }

    public function setPositionName(?string $positionName): void
    {
        $this->positionName = $positionName;
    }

    /**
     * @return float|int|null
     */
    public function getNettoSalary(): float|int
    {
        return $this->nettoSalary ?? 0;
    }

    public function setNettoSalary(float|int|null $nettoSalary): void
    {
        $this->nettoSalary = $nettoSalary;
    }

    /**
     * @return Collection<int, CustomerNotes>
     */
    public function getCustomerNotes(): Collection
    {
        return $this->customerNotes;
    }

    public function addCustomerNotes(CustomerNotes $customerNotes): self
    {
        if (!$this->customerNotes->contains($customerNotes)) {
            $this->customerNotes->add($customerNotes);
            $customerNotes->setUser($this);
        }

        return $this;
    }

    public function removeCustomerNotes(CustomerNotes $customerNotes): self
    {
        // set the owning side to null (unless already changed)
        if ($this->customerNotes->removeElement($customerNotes) && $customerNotes->getUser() === $this) {
            $customerNotes->setUser(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ActionLog>
     */
    public function getActionLogs(): Collection
    {
        return $this->actionLogs;
    }

    public function addActionLog(ActionLog $actionLog): self
    {
        if (!$this->actionLogs->contains($actionLog)) {
            $this->actionLogs->add($actionLog);
            $actionLog->setUser($this);
        }

        return $this;
    }

    public function removeActionLog(ActionLog $actionLog): self
    {
        // set the owning side to null (unless already changed)
        if ($this->actionLogs->removeElement($actionLog) && $actionLog->getUser() === $this) {
            $actionLog->setUser(null);
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

    /**
     * @return Collection<int, Timesheet>
     */
    public function getTimesheets(): Collection
    {
        return $this->timesheets;
    }

    public function addTimesheet(Timesheet $timesheet): self
    {
        if (!$this->timesheets->contains($timesheet)) {
            $this->timesheets->add($timesheet);
            $timesheet->setUser($this);
        }

        return $this;
    }

    public function removeTimesheet(Timesheet $timesheet): self
    {
        // set the owning side to null (unless already changed)
        if ($this->timesheets->removeElement($timesheet) && $timesheet->getUser() === $this) {
            $timesheet->setUser(null);
        }

        return $this;
    }

    public function getSlackId(): ?string
    {
        return $this->slackId;
    }

    public function setSlackId(?string $slackId): void
    {
        $this->slackId = $slackId;
    }

    public function isSlackLog(): bool
    {
        return $this->slackLog ?? false;
    }

    public function setSlackLog(bool $slackLog = null): void
    {
        $this->slackLog = $slackLog ?? false;
    }
}
