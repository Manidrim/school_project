build: # build the
	docker compose build --no-cache

start: # start the project
	docker compose up --wait

.PHONY: build start