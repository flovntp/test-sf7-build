<?php

namespace App\DataFixtures;

use App\Entity\BigFootSighting;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /** @var ObjectManager */
    private $objectManager;

    /** @var Generator */
    private $faker;

    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private array $users = [],
        private array $sightings = []
    ) {
    }

    public function load(ObjectManager $manager)
    {
        $this->objectManager = $manager;
        $this->faker = Factory::create();

        $this->createUsers();
        $this->createSightings();
        $this->createComments();

        $manager->flush();
    }

    private function createUsers()
    {
        $this->users = $this->createMany(100, function () {
            $user = new User();
            $user->setUsername($this->faker->userName);
            $user->setEmail($user->getUsername() . '@example.com');
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, 'believe')
            );
            $user->setAgreedToTermsAt($this->faker->dateTimeBetween('-6 months', 'now'));

            return $user;
        });
    }

    private function createSightings()
    {
        $this->sightings = $this->createMany(200, function () {
            $sighting = new BigFootSighting();
            $sighting->setOwner($this->users[array_rand($this->users)]);
            $sighting->setTitle($this->faker->realText(80));
            $sighting->setDescription($this->faker->paragraph(5));
            $sighting->setConfidenceIndex(rand(1, 10));
            $sighting->setLatitude($this->faker->latitude);
            $sighting->setLongitude($this->faker->longitude);
            $sighting->setCreatedAt($this->faker->dateTimeBetween('-6 months', 'now'));

            return $sighting;
        });
    }

    private function createComments()
    {
        $this->createMany(4000, function (int $i) {
            $comment = new Comment();
            if ($i % 5 === 0) {
                // make every 5th comment done by a small set of users
                // Wow! They must *love* Bigfoot!
                $rangeMax = floor(count($this->users) / 10);
                $comment->setOwner($this->users[rand(0, $rangeMax)]);
            } else {
                $comment->setOwner($this->users[array_rand($this->users)]);
            }
            $comment->setBigFootSighting($this->sightings[array_rand($this->sightings)]);
            $comment->setContent($this->faker->paragraph);
            $comment->setCreatedAt($this->faker->dateTimeBetween(
                $comment->getBigFootSighting()->getCreatedAt(),
                'now'
            ));

            return $comment;
        });
    }

    private function createMany(int $amount, callable $callback)
    {
        $objects = [];
        for ($i = 0; $i < $amount; $i++) {
            $object = $callback($i);
            $this->objectManager->persist($object);

            $objects[] = $object;
        }
        $this->objectManager->flush();

        return $objects;
    }
}
