<?php

namespace App\Tests\Controller;

use App\Controller\ProcessController;
use App\Service\ProcessRedisServiceInterface;
use App\Service\ProcessRedisService;
use App\Tests\Stub\StubRedis;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProcessControllerTest extends WebTestCase
{
    private StubRedis $stubRedis;

    protected function setUp(): void
    {
        parent::setUp();

        // создаём in-memory Redis stub
        $this->stubRedis = new StubRedis();
    }
        
    private function createTestController(): ProcessController
    {
        //  Создаем клиента для HTTP запросов
        $client = static::createClient();

        // Получаем контейнер сервисов и заменяем реальный сервис
        $container = $client->getContainer();
        $container->set(ProcessRedisService::class, $this->stubRedis);

        // Создаем экземпляр контроллера
        $controller = new ProcessController($this->stubRedis); 
        $controller->setContainer($container); 

        return $controller;
    }
    /**
     * @runInSeparateProcess
     */
    public function testFirstRequestGeneratesFreshData(): void
    {
        $this->stubRedis->set('huge_dataset:fresh', '', -1);
        $this->stubRedis->set('huge_dataset:stale', '', -1);
        $this->stubRedis->set('huge_dataset:lock', '0', 0);

        $controller = $this->createTestController();
        $response = $controller->index();
        
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('REFRESHED', $response->headers->get('X-Cache-Status'));
        
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(5, count($data));

        foreach ($data as $item) {
            $this->assertGreaterThanOrEqual(2, count($item));
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testSecondRequestGetsCacheHit(): void
    {

        // вручную кладём данные в fake redis
        $this->stubRedis->set('huge_dataset:fresh', json_encode([['test' => true, 'test2' => true]]), 60);
        $this->stubRedis->set('huge_dataset:stale', json_encode([['test' => true, 'test2' => true]]), 20060);
        $this->stubRedis->set('huge_dataset:lock', '0', 0);

        $controller = $this->createTestController();
        $response = $controller->index();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('HIT', $response->headers->get('X-Cache-Status'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testStaleServedWhenRefreshing(): void
    {
        // подготавливаем устаревший кэш и блокировку
        $this->stubRedis->set('huge_dataset:stale', json_encode([['dummy' => true]]));
        $this->stubRedis->set('huge_dataset:lock', 'locked', 60);

        $controller = $this->createTestController();
        $response = $controller->index();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('STALE', $response->headers->get('X-Cache-Status'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testReturns202WhenLockedAndNoStale(): void
    {
        // блокировка без устаревшего кэша
        $this->stubRedis->set('huge_dataset:fresh', '', -1);
        $this->stubRedis->set('huge_dataset:stale', '', -1);
        $this->stubRedis->set('huge_dataset:lock', 'locked', 60);
        
        $controller = $this->createTestController();
        $response = $controller->index();

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }
}
