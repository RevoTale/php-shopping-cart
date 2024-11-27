
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

phpstan:
	docker run --rm --interactive --tty \
	  	--volume $(PWD):/app \
		"revotale/php-shopping-cart:latest" phpstan