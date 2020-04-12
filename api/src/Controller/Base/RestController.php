<?php

namespace App\Controller\Base;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Exception\ClassConfigurationException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class RestController extends AbstractController
{
    protected $logger;
    protected $entityManager;
    protected $serializer;
    protected $router;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $router
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->router = $router;
    }

    protected static function getEntity(): string
    {
        if (!defined('static::ENTITY')) {
            throw new ClassConfigurationException(get_called_class() . '::ENTITY constant not set.');
        } else if (!class_exists(static::ENTITY)) {
            throw new ClassConfigurationException('Entity "' . static::ENTITY . '" bound to ' . get_called_class() . ' does not exist.');
        } else if (!preg_match('/^App\\\Entity\\\([\w\\_]+)$/', static::ENTITY)) {
            throw new ClassConfigurationException('Class "' . static::ENTITY . '" bound as entity to ' . get_called_class() . ' doesn\'t belong in App\Entity namespace.');
        }
        return static::ENTITY;
    }

    protected function getRepository(string $className = null): ServiceEntityRepository
    {
        if (is_null($className)) {
            $className = static::getEntity();
        }
        return $this->getDoctrine()->getRepository($className);
    }

    protected function getClassMetadata(string $className = null): ClassMetadata
    {
        if (is_null($className)) {
            $className = static::getEntity();
        }
        return $this->entityManager->getClassMetadata($className);
    }

    protected function resourceResponse($data, array $params = [], int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse(
            $this->serializer->normalize(
                [
                    // 'meta' => [
                    //     'fields' => $this->getClassMetadata()->fieldMappings,
                    // ],
                    'path' => [
                        'baseUri' => $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ],
                    'data' => $data,
                ],
                'json',
                $params
            ),
            $status
        );
    }

    /**
     * @Route("/", name="_find_all")
     */
    public function findAll(): JsonResponse
    {
        return $this->resourceResponse(
            $this->getRepository()->findAll(),
            [
                'entities' => [$this->getEntity()],
            ]
        );
    }

    /**
     * @Route("/{id<\d+>}", name="_find_by_id")
     */
    public function findById(Request $request): JsonResponse
    {
        $id = $request->get('id');

        return $this->resourceResponse(
            $this->getRepository()->find($id),
            [
                'entities' => [$this->getEntity()],
            ]
        );
    }
}
