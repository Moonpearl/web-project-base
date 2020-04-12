<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Generator;
use Doctrine\Common\Annotations\Annotation;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use App\Exception\ClassConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Input\InputInterface;

final class MakeController extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:rest';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new custom REST controller class for Doctrine entity class')
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('What entity should that controller be bound to?', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeRestController.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClass = $input->getArgument('entity-class');

        if (!class_exists('App\\Entity\\' . $entityClass)) {
            throw new \RunTimeException('Entity "' . $entityClass . '" does not exist.');
        }

        $fullEntityClass = 'App\\Entity\\' . $entityClass;

        if (!defined($fullEntityClass . '::ROUTE')) {
            throw new ClassConfigurationException('ROUTE constant not set in ' . $fullEntityClass . '.');
        } else if (!preg_match('/^[\w-]+$/', $fullEntityClass::ROUTE)) {
            throw new ClassConfigurationException('Route name "' . $fullEntityClass::ROUTE . '" can only include letters, digits, underscores and hyphens.');
        }

        $controllerClassNameDetails = $generator->createClassNameDetails(
            $entityClass,
            'Controller\\',
            'Controller'
        );

        $controllerPath = $generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            'src/Resources/skeleton/RestController.tpl.php',
            [
                'route_path' => '/' . $fullEntityClass::ROUTE,
                'route_name' => $fullEntityClass::ROUTE,
                'entity_class' => $entityClass,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text('Next: Open your new controller class and add some pages!');
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Annotation::class,
            'doctrine/annotations'
        );
    }
}
