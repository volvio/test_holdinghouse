<?php

namespace App\Controller;

use App\Service\ProcessRedisServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use \Redis;
 

class ProcessController extends AbstractController
{
    private  $redis;
    

    public function __construct( ProcessRedisServiceInterface $processRedisService)
    {
        $this->redis = $processRedisService;
    }
    
    #[Route('/process-huge-dataset', name: 'process_huge_dataset', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/process-huge-dataset",
     *     summary="Обработка большого датасета с кэшированием",
     *     description="Возвращает массив JSON объектов. Использует Redis для кэширования на 1 минуту и управление параллельными запросами.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с данными (актуальные или устаревшие)",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Запрос принят, обновление кэша выполняется другим процессом"
     *     )
     * )
     */    
    public function index(): JsonResponse
    {
        
        $freshKey = 'huge_dataset:fresh';
        $staleKey = 'huge_dataset:stale';
        $lockKey  = 'huge_dataset:lock';

        //получение из кеша сохраненного результата
        $freshItem = $this->redis->get($freshKey);
        if ($freshItem) {
            return $this->json(json_decode($freshItem, true), 200, ['X-Cache-Status' => 'HIT']);
        }

        // Блоктировка последующих запросов lock и сгенерировать заново
        $lock = $this->redis->get($lockKey);
        if ($lock != "locked") {
            $this->redis->set($lockKey,"locked", 60 );
            $this->redis->save();
            $data = $this->generateHugeDataset();
            $response = $this->json($data, 200, ['X-Cache-Status' => 'REFRESHED']);
            $this->redis->set($freshKey, json_encode($data), 60 );
            $this->redis->set($staleKey, json_encode($data), 86400 );//сохраняем на сутки
            $this->redis->set($lockKey,"0", 60 );
            $this->redis->save();

            return $response;
        }

        //  Если кто-то уже обновляет — отдаём устаревшие данные (если есть), иначе 202
        $staleItem= $this->redis->get($staleKey);
        if ($staleItem) {
            return $this->json(json_decode($staleItem, true), 200, ['X-Cache-Status' => 'STALE']);
        }

        return new JsonResponse(['message' => 'Processing in progress, please retry later'], 202);
    }
    
     /**
     * Имитация долгой операции + генерация датасета
     */
    private function generateHugeDataset(): array
    {
        // Имитируем задержку
        sleep(10);

        $now = new \DateTimeImmutable();

        return [
            [
                'type' => 'response_time',
                'datetime' => $now->format('Y-m-d H:i:s'),
                'timestamp' => $now->getTimestamp(),
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
    }
}

