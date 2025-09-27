COMPOSE=docker compose

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

migrate:
	$(COMPOSE) exec -T db psql -U $${DB_USER:-app} -d $${DB_NAME:-app} -f /docker-entrypoint-initdb.d/001_init.sql

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec php sh

db-shell:
	$(COMPOSE) exec db psql -U $${DB_USER:-app} -d $${DB_NAME:-app}

.PHONY: up down migrate logs shell db-shell

