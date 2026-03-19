<?php

declare(strict_types=1);

namespace App\Filter;

use App\Entity\Invoice;
use App\Entity\Offer;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Remove deleted offers.
 */
class DeletedOfferFilter extends SQLFilter
{
    /**
     * @param string $targetTableAlias
     *
     * @psalm-param ClassMetadata<object> $targetEntity
     *
     * @return string the constraint SQL if there is available, empty string otherwise
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
//        switch ($targetEntity->getReflectionClass()->name) {
//            case Offer::class:
//                return $targetTableAlias . '.status != "deleted"';
//                break;
//            case Invoice::class:
//                return ''.
//                    $targetTableAlias . '.status != "deleted"';
//                break;
//        }
        return '';
    }
}
