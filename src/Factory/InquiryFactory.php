<?php

namespace App\Factory;

use App\Entity\Inquiry;
use App\Repository\InquiryRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Inquiry>
 *
 * @method static Inquiry|Proxy createOne(array $attributes = [])
 * @method static Inquiry[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Inquiry[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Inquiry|Proxy find(object|array|mixed $criteria)
 * @method static Inquiry|Proxy findOrCreate(array $attributes)
 * @method static Inquiry|Proxy first(string $sortedField = 'id')
 * @method static Inquiry|Proxy last(string $sortedField = 'id')
 * @method static Inquiry|Proxy random(array $attributes = [])
 * @method static Inquiry|Proxy randomOrCreate(array $attributes = [])
 * @method static Inquiry[]|Proxy[] all()
 * @method static Inquiry[]|Proxy[] findBy(array $attributes)
 * @method static Inquiry[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Inquiry[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static InquiryRepository|RepositoryProxy repository()
 * @method Inquiry|Proxy create(array|callable $attributes = [])
 */
final class InquiryFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'date' => self::faker()->date('Y-m-d H:i:s'), // TODO add DATETIME ORM type manually
            'status' => 'open',
            'customer' => UserFactory::createOne(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Inquiry $inquiry): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Inquiry::class;
    }
}
