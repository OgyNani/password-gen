.PHONY: up down init test

up:
	docker compose up -d --build

down:
	docker compose down

init: up
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate

test: up
	docker compose exec app php artisan test
