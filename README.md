Соберите и запустите контейнеры:

docker-compose up -d --build

make build
make up


Установите зависимости:

docker exec -it symfony_app composer install

make composer-install


Откройте в браузере:

http://localhost:8080


Redis доступен по адресу: redis://symfony_redis:6379