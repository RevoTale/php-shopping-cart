install:
	docker run --rm --interactive --tty \
      --volume $PWD:/app \
      composer install