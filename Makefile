# Run `make` (no arguments) to get a short description of what is available
# within this `Makefile`.

SHELL=/bin/bash
MKDOCS_IMAGE_ID := $(shell docker images -q laminas/mkdocs | xargs)
DOCKER_PHP=-it -w /app -v ${PWD}:/app --rm php:8.2-alpine
MDLINT_FILE = https://raw.githubusercontent.com/laminas/laminas-continuous-integration-action/e321dbdcc74e665512b5d2e8fd9012b3432df897/setup/markdownlint/markdownlint.json

MK_BLUE = echo -e "\033[34m"$(1)"\033[0m"
MK_GREEN = echo -e "\033[32m"$(1)"\033[0m"

MK_INFO = @$(call MK_BLUE,$1)
MK_SUCCESS = @$(call MK_GREEN,$1)

help: ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.PHONY: help

docs-lint: .markdownlint.json ## Lint documentation
	@$(call MK_INFO,"Linting documentation files")
	@docker run -it -w /app -v ${PWD}:/app --rm davidanson/markdownlint-cli2 "docs/**/*.md" README.md
.PHONY: docs-lint

check-links: ## Check documentation links
	@$(call MK_INFO,"Checking links in documentation files")
	@docker run -it -w /app -v ${PWD}:/app --rm lycheeverse/lychee "docs/**/*.md" README.md
.PHONY: check-links

.markdownlint.json: ## Fetch the most recent settings for Markdown lint
	@$(call MK_INFO,"Fetching markdown lint configuration")
	@curl -o .markdownlint.json ${MDLINT_FILE}

install: ## Install composer dependencies
	@$(call MK_INFO,"Installing composer dependencies")
	@composer install
.PHONY: install

documentation-theme: ## fetch the documentation theme repo
	@$(call MK_INFO,"Fetching documentation theme resources")
	@git clone git@github.com:laminas/documentation-theme.git

build-mkdocs-image: documentation-theme ## Build the mkdocs image with necessary dependencies for building the docs
	@$(if ${MKDOCS_IMAGE_ID}, @$(call MK_INFO,"mkdocs image already built"), cd documentation-theme/builder && docker build -t laminas/mkdocs .)
.PHONY: build-mkdocs-image

docs: build-mkdocs-image ## build the docs using a Docker container
	@$(call MK_INFO,"Building documentation")
	@docker run -it -w /app -v ${PWD}:/app --rm laminas/mkdocs ./documentation-theme/build.sh -u https://www.example.com
	@$(call MK_SUCCESS,"file://${PWD}/docs/html/index.html")
.PHONY: docs

set-baseline: ## Expand the Psalm baseline with current issues
	@$(call MK_INFO,"Resetting the Psalm baseline")
	@docker run $(DOCKER_PHP) vendor/bin/psalm --no-cache --set-baseline=psalm-baseline.xml
.PHONY: set-baseline

update-baseline: ## Remove resolved issues from the baseline
	@$(call MK_INFO,"Updating the Psalm baseline")
	@docker run $(DOCKER_PHP) vendor/bin/psalm --no-cache --update-baseline
.PHONY: update-baseline

sa: ## Run static analysis
	@$(call MK_INFO,"Running static analysis")
	@docker run $(DOCKER_PHP) vendor/bin/psalm --no-cache
.PHONY: sa

cs: ## Run coding standards checks
	@$(call MK_INFO,"Checking coding standards")
	@docker run $(DOCKER_PHP) vendor/bin/phpcs
.PHONY: cs

test: ## Run tests
	@$(call MK_INFO,"Running Tests")
	@docker run $(DOCKER_PHP) vendor/bin/phpunit
.PHONY: test

composer-checks: ## Dump the composer autoloader
	@$(call MK_INFO,"Validating composer.json and dumping the autoloader")
	@composer validate --strict
	@composer dump-autoload --strict-psr --optimize
.PHONY: composer-checks

qa: composer-checks cs test sa docs-lint check-links ## Run all QA checks

bump: ## Update dependencies and bump development dependency versions
	@$(call MK_INFO,"Bumping development dependencies and refreshing composer lock")
	@composer update
	@composer bump -D
	@composer update
.PHONY: bump

clean: ## Delete caches and docs-build assets
	@$(call MK_INFO,"Cleaning up")
	@rm -rf documentation-theme
	@rm -rf docs/html
	@rm -f .phpcs-cache
	@rm -f .phpunit.result.cache
	@rm -rf .phpunit.cache
