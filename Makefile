# Названия сервисов из docker-compose
APP_CONTAINER = symfony_app
WEB_CONTAINER = symfony_web

# ------------------------------
# Основные команды
# ------------------------------

## Собрать контейнеры
build:
	docker-compose build

## Пересобрать контейнеры без кеша
rebuild:
	docker-compose build --no-cache

## Запустить контейнеры
up:
	docker-compose up -d

## Остановить контейнеры
down:
	docker-compose down

## Перезапустить контейнеры
restart: down up

## Зайти внутрь PHP контейнера
bash:
	docker exec -it $(APP_CONTAINER) bash

## Установить зависимости через Composer
composer-install:
	docker exec -it $(APP_CONTAINER) composer install

## Установить Symfony skeleton (webapp)
symfony-new:
	docker exec -it $(APP_CONTAINER) symfony new . --version=6.0 --webapp

## Проверить версию Symfony CLI
symfony-version:
	docker exec -it $(APP_CONTAINER) symfony -v

## Очистить кэш Symfony
cache-clear:
	docker exec -it $(APP_CONTAINER) php bin/console cache:clear

## Запустить миграции Doctrine
migrate:
	docker exec -it $(APP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction

