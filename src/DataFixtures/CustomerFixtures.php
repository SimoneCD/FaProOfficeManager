<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * php bin/console --env=test doctrine:fixtures:load.
 */
class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $customer = new Customer();
        $customer->setName('Winni');
        $customer->setEmail('si.schulze8@gmail.com');
        $customer->setAddress('Weg 6');
        $customer->setPhone('+49 723 44 56');
        $customer->setCountry('Deutschland');
        $customer->setCity('Berlin');

        $manager->persist($customer);

        $manager->flush();
    }
}
