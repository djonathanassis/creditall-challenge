# ==============================================
# Creditall Challenge - Development Makefile
# ==============================================
# Quick commands for Docker development
# Usage: make <command>
# ==============================================

.PHONY: help setup dev dev-backend dev-frontend up down restart logs logs-backend logs-frontend ps clean reset shell-app shell-frontend db redis test migrate seed format quality

# Colors for output
CYAN := \033[0;36m
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m # No Color

help:  ## 📖 Mostra esta ajuda
	@echo "$(CYAN)Creditall Challenge - Comandos Docker$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""

setup:  ## 🚀 Setup inicial completo do ambiente
	@echo "$(CYAN)🚀 Configurando ambiente de desenvolvimento...$(NC)"
	@./scripts/setup-dev.sh

dev:  ## 🏃 Inicia ambiente completo (backend + frontend)
	@echo "$(CYAN)🏃 Iniciando ambiente de desenvolvimento...$(NC)"
	@docker network create creditall_dev_network 2>/dev/null || true
	@docker-compose up -d
	@echo "$(GREEN)✅ Ambiente rodando!$(NC)"
	@echo "$(YELLOW)📍 Backend:  http://localhost:8080$(NC)"
	@echo "$(YELLOW)📍 Frontend: http://localhost:5173$(NC)"

dev-backend:  ## 🔧 Inicia apenas backend
	@echo "$(CYAN)🔧 Iniciando backend...$(NC)"
	@docker network create creditall_dev_network 2>/dev/null || true
	@cd backend && docker-compose up -d
	@echo "$(GREEN)✅ Backend rodando em http://localhost:8080$(NC)"

dev-frontend:  ## 💻 Inicia apenas frontend
	@echo "$(CYAN)💻 Iniciando frontend...$(NC)"
	@docker network create creditall_dev_network 2>/dev/null || true
	@cd frontend && docker-compose up -d
	@echo "$(GREEN)✅ Frontend rodando em http://localhost:5173$(NC)"

up:  ## ⬆️  Sobe todos os containers
	@docker-compose up -d

down:  ## ⬇️  Para todos os containers
	@docker-compose down

restart:  ## 🔄 Reinicia todos os containers
	@docker-compose restart

logs:  ## 📋 Visualiza logs de todos os serviços
	@docker-compose logs -f

logs-backend:  ## 📋 Visualiza logs do backend
	@cd backend && docker-compose logs -f

logs-frontend:  ## 📋 Visualiza logs do frontend
	@cd frontend && docker-compose logs -f

ps:  ## 📊 Status dos containers
	@docker-compose ps

clean:  ## 🧹 Remove containers e volumes (mantém dados)
	@echo "$(YELLOW)🧹 Limpando containers...$(NC)"
	@docker-compose down
	@echo "$(GREEN)✅ Limpeza concluída$(NC)"

reset:  ## ⚠️  Reset completo (APAGA TODOS OS DADOS!)
	@echo "$(RED)⚠️  ATENÇÃO: Isso vai APAGAR TODOS OS DADOS!$(NC)"
	@echo "$(YELLOW)Containers, volumes e dados do banco serão removidos.$(NC)"
	@read -p "Continuar? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		./scripts/cleanup.sh; \
	else \
		echo "$(GREEN)Cancelado.$(NC)"; \
	fi

# Container access commands
shell-app:  ## 🐚 Acessa shell do container PHP
	@cd backend && docker-compose exec app sh

shell-frontend:  ## 🐚 Acessa shell do container frontend
	@cd frontend && docker-compose exec frontend sh

db:  ## 🗄️  Acessa MySQL CLI
	@cd backend && docker-compose exec mysql mysql -u root -p

redis-cli:  ## 🔴 Acessa Redis CLI
	@cd backend && docker-compose exec redis redis-cli

# Laravel commands
test:  ## 🧪 Executa testes do backend
	@echo "$(CYAN)🧪 Executando testes...$(NC)"
	@cd backend && docker-compose exec app php artisan test --compact

migrate:  ## 🔄 Executa migrations
	@echo "$(CYAN)🔄 Executando migrations...$(NC)"
	@cd backend && docker-compose exec app php artisan migrate

migrate-fresh:  ## ⚠️  Recria banco de dados (APAGA DADOS!)
	@echo "$(RED)⚠️  Isso vai APAGAR todos os dados do banco!$(NC)"
	@read -p "Continuar? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		cd backend && docker-compose exec app php artisan migrate:fresh --seed; \
	fi

seed:  ## 🌱 Executa seeders
	@echo "$(CYAN)🌱 Executando seeders...$(NC)"
	@cd backend && docker-compose exec app php artisan db:seed

# Code quality commands
format:  ## ✨ Formata código (Pint + Prettier)
	@echo "$(CYAN)✨ Formatando código backend...$(NC)"
	@cd backend && docker-compose exec app vendor/bin/pint --format agent
	@echo "$(CYAN)✨ Formatando código frontend...$(NC)"
	@cd frontend && npm run format

quality:  ## 🎯 Executa pipeline de qualidade completo
	@echo "$(CYAN)🎯 Executando pipeline de qualidade...$(NC)"
	@cd backend && composer run quality
	@cd frontend && npm run quality

# Build commands
build:  ## 🔨 Rebuild containers (sem cache)
	@echo "$(CYAN)🔨 Rebuilding containers...$(NC)"
	@docker-compose build --no-cache

# Status and health
health:  ## 🏥 Verifica saúde dos containers
	@echo "$(CYAN)🏥 Verificando saúde dos containers...$(NC)"
	@docker-compose ps --format json | jq -r '.[] | "\(.Service): \(.Health)"'

# Default target
.DEFAULT_GOAL := help
