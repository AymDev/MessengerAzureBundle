EXEC = docker run --rm -v $$(pwd):/srv/app aymdev/azure-bundle:latest

.PHONY: build
build:
	DOCKER_BUILDKIT=1 docker build -t aymdev/azure-bundle:latest .

.PHONY: shell
shell:
	docker run --rm -it -v $$(pwd):/srv/app aymdev/azure-bundle:latest sh

.PHONY: tests
tests:
	$(EXEC) ./vendor/bin/simple-phpunit

.PHONY: analysis
analysis:
	$(EXEC) ./vendor/bin/phpstan analyse
	$(EXEC) ./vendor/bin/phpcs