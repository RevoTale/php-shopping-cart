install:
	docker run --rm --interactive --tty \
      --volume $PWD:/app \
      composer install
phpstan:
	docker run --rm --interactive --tty \
          --volume $PWD:/app \
          composer run phpstan
phpspec:
	docker run --rm --interactive --tty \
              --volume $PWD:/app \
              composer run phpspec