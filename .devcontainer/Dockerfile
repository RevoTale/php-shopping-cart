FROM mcr.microsoft.com/devcontainers/php:8.4
RUN docker-php-ext-configure bcmath \
	&& docker-php-ext-install -j$(nproc) bcmath