<?php

namespace App\UI\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'service' => 'Fintech DDD API',
            'version' => '1.0.0',
            'timestamp' => (new \DateTime())->format('c'),
        ]);
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to Fintech DDD API',
            'documentation' => '/api/docs',
            'health' => '/health',
            'api' => '/api',
        ]);
    }
}
