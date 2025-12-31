<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    #[Route('/test-upload', name: 'app_test_upload', methods: ['POST'])]
    public function test(): JsonResponse
    {
        return new JsonResponse(['test' => 'ok']);
    }
}
