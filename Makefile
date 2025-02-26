rebuild:
	docker-compose build --no-cache
up:
	docker-compose up -d --remove-orphans
	@echo "App is running on http://localhost:8093\n"
shell-php:
	docker-compose exec php bash
clear-cache:
	docker-compose exec php bash -c "php bin/console cache:clear"
deps:
	docker-compose exec php composer install

