include make/docker.mk
include make/symfony.mk

.DEFAULT_GOAL := help

help:
	@echo "========================================"
	@echo "Makefile for school project"
	@echo "========================================"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo "========================================"