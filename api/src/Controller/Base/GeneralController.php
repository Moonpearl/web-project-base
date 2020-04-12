<?php

namespace App\Controller\Base;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class GeneralController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home(): JsonResponse
    {
        return new JsonResponse([ 'message' => 'API up and running!']);
    }
}
