<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\CustomerNotes;
use App\Entity\User;
use App\Service\PriceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserTwigExtension extends AbstractExtension
{
    protected $storage = [];

    protected $entityManager;

    protected $priceService;

    protected $fa;

    public function __construct(
        EntityManagerInterface $entityManager,
        PriceService $priceService
    ) {
        $this->entityManager = $entityManager;
        $this->priceService = $priceService;
        $this->fa = new FilesystemAdapter('offer_admin', 0, __DIR__.'../../var/tmp');
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAvatar', $this->getAvatar(...)),
            new TwigFunction('hasCustomerChats', $this->hasCustomerChats(...)),
        ];
    }

    public function getAvatar(int $id): string
    {
        /* @var User $user */
        $user = $this->fa->get('user-avatar-'.$id, function () use ($id) {
            return $this->entityManager->getRepository(User::class)->find($id);
        });

        return $user->getAvatarUri();
    }

    public function hasCustomerChats(): int
    {
        /* @var User $user */
        $chats = $this->entityManager->getRepository(CustomerNotes::class)->findNewCustomerChats();

        return count($chats);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }
}
