<?php

namespace App\Tests\Controller;

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

    /**
     * Создаём клиента и подменяем Redis в контроллере
     */
    private function createClientWithStub(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $container = $client->getContainer();

        // получаем контроллер и подменяем redis на stub
        $controller = $container->get(\App\Controller\ProcessController::class);
        $ref = new \ReflectionObject($controller);
        $prop = $ref->getProperty('redis');
        $prop->setAccessible(true);
        $prop->setValue($controller, $this->stubRedis);

        return $client;
    }
/**
 * @runInSeparateProcess
 */
    public function testFirstRequestGeneratesFreshData(): void
    {
        $client = $this->createClientWithStub();

        $client->request('GET', '/process-huge-dataset');
        $response = $client->getResponse();

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
        $client2 = $this->createClientWithStub();

        //$client2 = $this->createClientWithStub();

        // первый запрос генерирует кэш
        //$client->request('GET', '/process-huge-dataset');
        //$this->assertSame('REFRESHED', $client->getResponse()->headers->get('X-Cache-Status'));
        
        $this->stubRedis->set('huge_dataset:fresh',json_encode([['test' => true, 'test2' => true ]], 60) );
        $this->stubRedis->set('huge_dataset:stale',json_encode([['test' => true, 'test2' => true ]], 20060) );
        $this->stubRedis->set('huge_dataset:lock', '0', 0);
        
        // второй запрос
        $client2->request('GET', '/process-huge-dataset');
        $response = $client2->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('HIT', $response->headers->get('X-Cache-Status'));
    }
/**
 * @runInSeparateProcess
 */
    public function testStaleServedWhenRefreshing(): void
    {
        $client = $this->createClientWithStub();

        // подготавливаем устаревший кэш и блокировку
        $this->stubRedis->set('huge_dataset:stale', json_encode([['dummy' => true]]));
        $this->stubRedis->set('huge_dataset:lock', 'locked', 60);

        $client->request('GET', '/process-huge-dataset');
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('STALE', $response->headers->get('X-Cache-Status'));
    }
/**
 * @runInSeparateProcess
 */
    public function testReturns202WhenLockedAndNoStale(): void
    {
        $client = $this->createClientWithStub();

        // блокировка без устаревшего кэша
        $this->stubRedis->set('huge_dataset:lock', 'locked', 60);

        $client->request('GET', '/process-huge-dataset');
        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // сбросить пользовательские обработчики ошибок
        restore_error_handler();
        restore_exception_handler();
    }
}
