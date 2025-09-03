Соборка и запуск контейнеров:

docker-compose up -d --build

или

make build

make up


Установка зависимостей:

docker exec -it symfony_app composer install

или

make composer-install


Открывается в браузере:

http://localhost:8080/process-huge-dataset


Выполнение тестов:

make test

или

docker exec -it $(APP_CONTAINER) ./vendor/bin/phpunit --testdox
