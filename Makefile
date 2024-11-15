.PHONY: test
setup:
	docker-compose build
	docker-compose run --rm test composer install

test:
	docker-compose run --rm test ./vendor/bin/phpunit tests/

# Additional helpful commands
coverage:
	docker-compose run --rm test ./vendor/bin/phpunit --coverage-html coverage/

bash:
	docker-compose run --rm test bash

clean:
	docker-compose down
	rm -rf vendor
	rm -rf coverage
