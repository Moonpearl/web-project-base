<?php

namespace App\Controller;

use App\Entity\ExampleCategory;
use App\Controller\Base\RestController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/example-categories", name="example-categories")
 */
class ExampleCategoryController extends RestController
{
    const ENTITY = ExampleCategory::class;
}
