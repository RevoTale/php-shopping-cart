FROM composer:2.10@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040
COPY --from=mlocati/php-extension-installer@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions bcmath