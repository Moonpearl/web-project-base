<?php

namespace App\Controller;

use App\Entity\Example;
use App\Entity\ExampleCategory;
use App\Controller\Base\RestController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @Route("examples", name="examples")
 */
class ExampleController extends RestController
{
    const ENTITY = Example::class;

    /**
     * @Route("/details"), name="_find_all_details"
     */
    public function findAllDetails(): JsonResponse
    {
        return $this->resourceResponse(
            $this->getRepository()->findAll(),
            [
                'entities' => [
                    $this->getEntity(),
                    ExampleCategory::class,
                ],
                ObjectNormalizer::IGNORED_ATTRIBUTES => ['examples'],
            ]
        );
    }
}
