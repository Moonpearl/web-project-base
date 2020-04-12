<?php

namespace App\DataFixtures;

use Faker\Factory;

use App\Entity\Example;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\DataFixtures\ExampleCategoryFixtures;
use App\Repository\ExampleCategoryRepository;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ExampleFixtures extends Fixture implements DependentFixtureInterface
{
    const AMOUNT = 20;

    private $categoryRepository;

    public function __construct(ExampleCategoryRepository $categoryRepository) {
        $this->categoryRepository = $categoryRepository;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $categories = $this->categoryRepository->findAll();
        $categories[] = null;

        for ($i = 0; $i < self::AMOUNT; $i++) {
            $example = new Example();

            $example
                ->setName($faker->firstName())
                ->setCreatedAt($faker->dateTimeThisYear())
                ->setCategory($categories[rand(0, sizeof($categories) - 1)])
            ;

            $manager->persist($example);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            ExampleCategoryFixtures::class,
        );
    }
}
