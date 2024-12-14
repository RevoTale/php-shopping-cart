
build:
	docker build --tag "revotale/php-shopping-cart:latest" .
composer_install:
	docker run --rm --interactive --tty \
	  	--volume $(PWD):/app \
		"revotale/php-shopping-cart:latest" composer install
composer_update:
	docker run --rm --interactive --tty \
	  	--volume $(PWD):/app \
		"revotale/php-shopping-cart:latest" composer update
phpunit:
	docker run --rm --interactive --tty \
	  	--volume $(PWD):/app \
		"revotale/php-shopping-cart:latest" phpunit
rector_fix:
	docker run --rm --interactive --tty \
	  	--volume $(PWD):/app \
		"revotale/php-shopping-cart:latest" vendor/bin/rector process
phpstan:
	docker run --rm --interactive --tty \
	  	--volume $(PWD):/app \
		"revotale/php-shopping-cart:latest" phpstan