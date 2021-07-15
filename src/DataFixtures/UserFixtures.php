<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{

    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user
            ->setUsername('bruno')
            ->setPassword('$2y$13$Z4wlgVWRylyMQQzu0bJnn.eDyPhwp7feigflqyYvqoEd8.Ov50rEq');

        $manager->persist($user);
        $manager->flush();
    }

}
