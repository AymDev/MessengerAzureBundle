.PHONY: tests
tests:
	php ./vendor/bin/simple-phpunit

.PHONY: analysis
analysis:
	php ./vendor/bin/phpstan analyse
	php ./vendor/bin/phpcs