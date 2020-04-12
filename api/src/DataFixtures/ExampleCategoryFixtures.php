<?php

namespace App\DataFixtures;

use Faker\Factory;

use App\Entity\ExampleCategory;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\DataFixtures\ExampleCategoryFixtures;
use App\Repository\ExampleCategoryRepository;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ExampleCategoryFixtures extends Fixture
{
    const AMOUNT = 5;

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        for ($i = 0; $i < self::AMOUNT; $i++) {
            $example = new ExampleCategory();

            $example
                ->setName($faker->jobTitle())
                ->setCreatedAt($faker->dateTimeThisYear())
            ;

            $manager->persist($example);
        }

        $manager->flush();
    }
}
