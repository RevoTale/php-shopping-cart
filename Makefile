install:
	docker run --rm --interactive --tty \
      --volume $(PWD):/app \
      composer install
update:
	docker run --rm --interactive --tty \
      --volume $(PWD):/app \
      composer update
phpunit:
	docker run --rm --interactive --tty \
      	  --volume $(PWD):/app \
          composer run phpunit

phpspec:
	docker run --rm --interactive --tty \
      	  --volume $(PWD):/app \
          composer run phpspec