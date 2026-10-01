### Variables

# Applications
COMPOSER ?= /usr/bin/env composer
PHP ?= /usr/bin/env php

# Box builds the phar. It can not be a dependency of this project (it conflicts with PHPUnit), so the release is
# downloaded and verified.
BOX_VERSION ?= 4.7.0
BOX_SHA256 ?= 3d390eeaec33288098fe83f8a54c60cc575cb6be295f38ff4482b4b4f26f8d52
BOX_URL ?= https://github.com/box-project/box/releases/download/$(BOX_VERSION)/box.phar

PHAR = dist/east-website.phar

### Helpers
all: depend

.PHONY: all

### Dependencies
depend:
	${COMPOSER} update --prefer-dist --no-interaction

.PHONY: depend

### QA
qa: lint phpstan phpcs audit
qa-offline: lint phpstan phpcs

lint:
	find ./src ./tests -name "*.php" -exec ${PHP} -l {} \; | grep "Parse error" > /dev/null && exit 1 || exit 0
	${PHP} -l bin/east-website

phpstan:
	${PHP} -d memory_limit=256M vendor/bin/phpstan analyse

phpcs:
	${PHP} vendor/bin/phpcs --standard=PSR12 --extensions=php src/ bin/east-website

audit:
	${COMPOSER} audit

.PHONY: qa qa-offline lint phpstan phpcs audit

### Testing
# When Xdebug is not already enabled in the PHP configuration, it is loaded from the ini file of the library, also
# scanned by the PHP processes started by the end to end tests.
ifeq ($(shell ${PHP} -r 'echo (int) extension_loaded("xdebug");'),1)
PHP_WITH_XDEBUG = XDEBUG_MODE=coverage ${PHP}
else
PHP_WITH_XDEBUG = XDEBUG_MODE=coverage PHP_INI_SCAN_DIR=":$(CURDIR)/../tests/support/xdebug" ${PHP}
endif

test:
	${PHP_WITH_XDEBUG} -dxdebug.mode=coverage vendor/bin/phpunit -c phpunit.xml --testsuite unit,e2e --colors --coverage-text

.PHONY: test

### Phar
build/box.phar:
	mkdir -p build
	curl -fsSL -o build/box.phar ${BOX_URL}
	echo "${BOX_SHA256}  build/box.phar" | sha256sum -c -
	chmod +x build/box.phar

# The phar is built from a staging directory, where only the production dependencies are installed (from the lock
# file, for a reproducible build): the development dependencies can not be embedded.
phar: build/box.phar
	rm -rf build/stage
	mkdir -p build/stage dist
	cp -R bin src composer.json composer.lock box.json build/stage/
	cd build/stage && ${COMPOSER} install --no-dev --prefer-dist --no-interaction --optimize-autoloader --classmap-authoritative --no-scripts
	cd build/stage && ${PHP} -d phar.readonly=0 ../box.phar compile -c box.json
	${PHP} ${PHAR} --version

phar-test:
	vendor/bin/phpunit -c phpunit.xml --testsuite phar --colors

.PHONY: phar phar-test

### Cleaning
clean:
	rm -rf vendor build/stage .phpunit.cache dist/*.phar

.PHONY: clean
