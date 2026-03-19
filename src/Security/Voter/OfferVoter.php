<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Offer;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * votes.
 */
class OfferVoter extends Voter
{
    final public const EDIT = 'POST_EDIT';
    final public const VIEW = 'POST_VIEW';

    private readonly Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Offer::class, true);
    }

    /**
     * {@inheritDoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW]) && $subject instanceof Offer;
    }

    /**
     * {@inheritDoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$subject instanceof Offer) {
            return false;
        }

        if ($this->security->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            return true;
        }

        return match ($attribute) {
            self::EDIT, self::VIEW => $subject->getUser() === $user,
            default => false,
        };
    }
}
