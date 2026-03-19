<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'email' => 'simone@example.com',
            'roles' => ['ROLE_ADMIN'],
        ]);
        UserFactory::createOne([
            'email' => 'gina@example.com',
        ]);

        $manager->flush();
    }
}
