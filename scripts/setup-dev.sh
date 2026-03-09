#!/bin/bash
set -e

# ==============================================
# Creditall Challenge - Development Setup
# ==============================================
# Automated setup for Docker development environment
# ==============================================

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo -e "${CYAN}🐳 Creditall Challenge - Docker Setup${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker não está rodando. Por favor, inicie o Docker e tente novamente.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker está rodando${NC}"

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ docker-compose não encontrado. Por favor, instale docker-compose.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ docker-compose encontrado${NC}"
echo ""

# Create backend/.env.local if it doesn't exist
if [ ! -f backend/.env.local ]; then
    echo -e "${YELLOW}📝 Criando backend/.env.local com secrets gerados...${NC}"
    
    # Generate random passwords
    MYSQL_ROOT_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    DB_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    
    cat > backend/.env.local << EOF
# ==============================================
# Auto-generated secrets - $(date)
# ==============================================

# Database Passwords
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASS}
DB_PASSWORD=${DB_PASS}

# Application Key (will be generated after containers start)
APP_KEY=

# Mail Credentials
# TODO: Add your Mailtrap credentials from https://mailtrap.io
MAILTRAP_USERNAME=
MAILTRAP_PASSWORD=
EOF
    
    echo -e "${GREEN}✓ backend/.env.local criado com passwords seguros${NC}"
else
    echo -e "${GREEN}✓ backend/.env.local já existe${NC}"
fi

echo ""

# Create Docker network if it doesn't exist
echo -e "${YELLOW}🌐 Criando rede Docker compartilhada...${NC}"
docker network create creditall_dev_network 2>/dev/null && echo -e "${GREEN}✓ Rede creditall_dev_network criada${NC}" || echo -e "${GREEN}✓ Rede creditall_dev_network já existe${NC}"

echo ""

# Stop and remove existing containers
echo -e "${YELLOW}🛑 Parando containers existentes (se houver)...${NC}"
docker-compose down 2>/dev/null || true

echo ""

# Build containers
echo -e "${YELLOW}🔨 Construindo imagens Docker...${NC}"
echo -e "${CYAN}   Isso pode levar alguns minutos na primeira vez...${NC}"
docker-compose build --no-cache

echo ""

# Start containers
echo -e "${YELLOW}🚀 Iniciando containers...${NC}"
docker-compose up -d

echo ""

# Wait for MySQL to be ready
echo -e "${YELLOW}⏳ Aguardando MySQL estar pronto...${NC}"
MAX_TRIES=30
COUNT=0
until docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo -e "${RED}❌ MySQL não iniciou no tempo esperado${NC}"
        echo -e "${YELLOW}Verifique os logs: docker-compose logs mysql${NC}"
        exit 1
    fi
    echo -e "${CYAN}   Tentativa $COUNT/$MAX_TRIES...${NC}"
    sleep 2
done

echo -e "${GREEN}✓ MySQL está pronto${NC}"
echo ""

# Generate Laravel app key if not exists
echo -e "${YELLOW}🔑 Gerando chave da aplicação Laravel...${NC}"
if ! grep -q "^APP_KEY=base64:" backend/.env.local 2>/dev/null; then
    APP_KEY=$(docker-compose exec -T app php artisan key:generate --show 2>/dev/null || echo "")
    if [ -n "$APP_KEY" ]; then
        # Update APP_KEY in .env.local
        if grep -q "^APP_KEY=" backend/.env.local; then
            sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" backend/.env.local
        else
            echo "APP_KEY=$APP_KEY" >> backend/.env.local
        fi
        echo -e "${GREEN}✓ Chave da aplicação gerada${NC}"
    else
        echo -e "${YELLOW}⚠️  Não foi possível gerar chave automaticamente${NC}"
        echo -e "${YELLOW}   Execute manualmente: docker-compose exec app php artisan key:generate${NC}"
    fi
else
    echo -e "${GREEN}✓ Chave da aplicação já existe${NC}"
fi

echo ""

# Install backend dependencies
echo -e "${YELLOW}📦 Instalando dependências do backend...${NC}"
docker-compose exec -T app composer install --no-interaction --optimize-autoloader

echo ""

# Run migrations
echo -e "${YELLOW}🔄 Executando migrations do banco de dados...${NC}"
docker-compose exec -T app php artisan migrate --force

echo ""

# Ask to seed database
read -p "Deseja popular o banco com dados de exemplo? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}🌱 Populando banco de dados...${NC}"
    docker-compose exec -T app php artisan db:seed --force
    echo -e "${GREEN}✓ Banco populado com sucesso${NC}"
fi

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Setup concluído com sucesso!${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📍 Serviços disponíveis:${NC}"
echo -e "   Backend API:  ${CYAN}http://localhost:8080${NC}"
echo -e "   Frontend:     ${CYAN}http://localhost:5173${NC}"
echo -e "   MySQL:        ${CYAN}Internal only (use: make db)${NC}"
echo -e "   Redis:        ${CYAN}Internal only (use: make redis-cli)${NC}"
echo ""
echo -e "${YELLOW}🔧 Comandos úteis:${NC}"
echo -e "   ${GREEN}make help${NC}         - Ver todos os comandos disponíveis"
echo -e "   ${GREEN}make logs${NC}         - Ver logs dos serviços"
echo -e "   ${GREEN}make test${NC}         - Executar testes"
echo -e "   ${GREEN}make shell-app${NC}    - Acessar shell do container PHP"
echo -e "   ${GREEN}make down${NC}         - Parar containers"
echo -e "   ${GREEN}make reset${NC}        - Reset completo do ambiente"
echo ""
echo -e "${CYAN}Happy coding! 🚀${NC}"
echo ""
