<?php

namespace App\Controller\Base;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Exception\ClassConfigurationException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class RestController extends AbstractController
{
    protected $logger;
    protected $entityManager;
    protected $serializer;
    protected $router;
    protected $propertyAccessor;

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
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
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

    protected function resourceResponse($data, array $params = null, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        if (is_null($params)) {
            $params = [];
        }

        if (!isset($params['entities'])) {
            $params['entities'] = [];
        }

        $className = static::getEntity();

        if (!in_array($className, $params['entities'])) {
            array_push($params['entities'], $className);
        }

        return new JsonResponse(
            $this->serializer->normalize(
                [
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

    protected function deserializeFromJson(string $data, bool $allPropsRequired = true)
    {
        $className = static::getEntity();
        $object = $this->serializer->deserialize($data, $className, 'json');

        $classMetadata = $this->getClassMetadata();

        if ($allPropsRequired) {
            $mappings = $classMetadata->fieldMappings;
            unset($mappings['id']);
            
            foreach ($mappings as $propName => $mapping) {
                $value = $this->propertyAccessor->getValue($object, $propName);
                if ($mapping['nullable'] === false && is_null($value)) {
                    throw new BadRequestHttpException('Mandatory property "' . $propName . '" missing in new ' . $className . ' object.');
                }
            }
        }

        foreach ($classMetadata->associationMappings as $propName => $mapping) {
            // If relationship is owned by object (ManyToOne, OneToOne)
            if (is_null($mapping['mappedBy'])) {
                $relatedEntity = $this->propertyAccessor->getValue($object, $propName);

                if (!is_null($relatedEntity)) {
                    $this->propertyAccessor->setValue($object, $propName, $this->getReference($relatedEntity));
                }
            // Otherwise (OneToMany, ManyToMany)
            } else {
                $relatedEntities = $this->propertyAccessor->getValue($object, $propName);

                $references = $relatedEntities->map(\Closure::fromCallable(array($this, 'getReference')));

                $this->propertyAccessor->setValue($object, $propName, $references);
            }
        }

        return $object;
    }

    protected function getReference(object $object) {
        $id = $object->getId();
        $reference = $this->entityManager->getReference(get_class($object), $id);
        return $reference;
    }

    protected function find(int $id) {
        $object = $this->getRepository()->find($id);

        if (is_null($object)) {
            throw new NotFoundHttpException('Entity ' . static::getEntity() . ' #' . $id . ' does not exist.');
        }

        return $object;
    }

    /**
     * @Route("/", methods={"GET"}, name="_find_all")
     */
    public function findAll(): JsonResponse
    {
        return $this->resourceResponse(
            $this->getRepository()->findAll()
        );
    }

    /**
     * @Route("/{id<\d+>}", methods={"GET"}, name="_find_by_id")
     */
    public function findById(Request $request): JsonResponse
    {
        $id = $request->get('id');

        $object = $this->find($id);

        return $this->resourceResponse(
            $object
        );
    }

    /**
     * @Route("/", methods={"POST"}, name="_create")
     */
    public function create(Request $request): JsonResponse
    {
        $object = $this->deserializeFromJson($request->getContent());

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $this->resourceResponse(
            $object,
            null,
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * @Route("/{id<\d+>}", methods={"PUT"}, name="_update")
     */
    public function update(Request $request): JsonResponse
    {
        $updatedObject = $this->deserializeFromJson($request->getContent());

        $id = (int)$request->get('id');

        if (is_null($updatedObject->getId())) {
            $updatedObject->setId($id);
        } else if ($updatedObject->getId() !== $id) {
            throw new BadRequestHttpException('Wrong object ID (given: ' . $updatedObject->getId() . ', expected: ' . $id . ')');
        }

        $object = $this->find($id);

        $classMetadata = $this->getClassMetadata();

        $mappings = array_merge($classMetadata->fieldMappings, $classMetadata->associationMappings);

        foreach ($mappings as $propName => $mapping) {
            $value = $this->propertyAccessor->getValue($updatedObject, $propName);
            $this->propertyAccessor->setValue($object, $propName, $value);
        }

        $this->entityManager->persist($object);
        $this->entityManager->flush();
        $this->entityManager->refresh($object);

        return $this->resourceResponse(
            $object
        );
    }

    /**
     * @Route("/{id<\d+>}", methods={"DELETE"}, name="_delete")
     */
    public function delete(Request $request): JsonResponse
    {
        $id = $request->get('id');

        $object = $this->find($id);
        $this->entityManager->remove($object);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
