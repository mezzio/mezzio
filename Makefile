# Minimum version of PHP supported by this project
PHP_VERSION ?= 8.3
PHP_EXTENSIONS ?= mbstring json xdebug
DOCKER_IMAGE_NAME = mezzio/mezzio

# Includes _everything_ supplied by the internal tooling lib
include vendor/laminas/internal-tooling/Makefile
