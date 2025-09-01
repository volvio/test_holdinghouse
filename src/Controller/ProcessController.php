<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProcessController extends AbstractController
{
    #[Route('/process-huge-dataset', name: 'process_huge_dataset', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $responseTime = new \DateTimeImmutable();

        $data = [
            [
                'type' => 'response_time',
                'datetime' => $responseTime->format('Y-m-d H:i:s'),
                'timestamp' => $responseTime->getTimestamp(),
            ],
            [
                'type' => 'exchange_rate',
                'currency_code' => 'EUR/USD',
                'rate' => 1.10, 
            ],
            [
                'type' => 'retail_price',
                'currency' => 'USD',
                'quantity' => 10,
                'price' => 25.50, 
            ],
            [
                'type' => 'wholesale_price',
                'currency' => 'USD',
                'quantity' => '>10',
                'price' => 20.00, 
            ],
            [
                'type' => 'limits',
                'min' => 1,
                'max' => 100,
            ],
        ];

        return $this->json($data);
    }
}

