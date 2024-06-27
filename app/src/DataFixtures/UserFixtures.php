<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordEncoder)
    {
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 100; $i++) {
            $user = new User();
            $user->setUsername('User'.$i);
            $user->setPassword($this->passwordEncoder->hashPassword($user, 'password'));
            $user->setContractStartDate(new \DateTime());
            $user->setContractEndDate((new \DateTime())->modify('+1 year'));
            $user->setType($i % 2 === 0 ? 'normal' : 'premium');
            $user->setVerified($i % 2 === 0);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
