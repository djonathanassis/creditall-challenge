#!/bin/bash
set -e

# ==============================================
# Creditall Challenge - Environment Cleanup
# ==============================================
# Complete cleanup of Docker environment
# WARNING: This will DELETE ALL DATA!
# ==============================================

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${RED}═══════════════════════════════════════════════${NC}"
echo -e "${RED}⚠️  Docker Environment Reset${NC}"
echo -e "${RED}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Isso irá:${NC}"
echo -e "  ${RED}✗${NC} Parar todos os containers"
echo -e "  ${RED}✗${NC} Remover todos os containers"
echo -e "  ${RED}✗${NC} Remover todos os volumes (${RED}DADOS SERÃO PERDIDOS!${NC})"
echo -e "  ${RED}✗${NC} Limpar sistema Docker"
echo -e "  ${RED}✗${NC} Remover rede compartilhada"
echo ""
echo -e "${RED}ATENÇÃO: Todos os dados do banco de dados serão PERMANENTEMENTE DELETADOS!${NC}"
echo ""

# Confirmation
read -p "Tem certeza que deseja continuar? Digite 'yes' para confirmar: " -r
echo

if [[ ! $REPLY == "yes" ]]; then
    echo -e "${GREEN}✓ Operação cancelada.${NC}"
    exit 0
fi

echo ""
echo -e "${YELLOW}🛑 Parando e removendo containers...${NC}"

# Stop and remove all compose services
cd "$(dirname "$0")/.."
docker-compose down -v 2>/dev/null || true
cd backend && docker-compose down -v 2>/dev/null || true
cd ../frontend && docker-compose down -v 2>/dev/null || true
cd ..

echo -e "${GREEN}✓ Containers removidos${NC}"
echo ""

# Remove named volumes
echo -e "${YELLOW}🗑️  Removendo volumes nomeados...${NC}"

VOLUMES=(
    "creditall_mysql_data"
    "creditall_redis_data"
    "creditall_backend_vendor"
    "creditall_backend_node"
    "creditall_frontend_node_modules"
    "creditall_frontend_vite_cache"
    "creditall_frontend_npm_cache"
)

for volume in "${VOLUMES[@]}"; do
    if docker volume ls -q | grep -q "^${volume}$"; then
        docker volume rm "$volume" 2>/dev/null && echo -e "${GREEN}  ✓ Removido: $volume${NC}" || echo -e "${YELLOW}  ⚠ Falha ao remover: $volume${NC}"
    else
        echo -e "${CYAN}  - Volume não existe: $volume${NC}"
    fi
done

echo ""

# Clean Docker system
echo -e "${YELLOW}🧹 Limpando sistema Docker...${NC}"
docker system prune -f --volumes

echo -e "${GREEN}✓ Sistema Docker limpo${NC}"
echo ""

# Remove network
echo -e "${YELLOW}🌐 Removendo rede compartilhada...${NC}"
docker network rm creditall_dev_network 2>/dev/null && echo -e "${GREEN}✓ Rede removida${NC}" || echo -e "${CYAN}Rede não existe ou ainda está em uso${NC}"

echo ""

# Optional: Remove .env.local
read -p "Deseja também remover backend/.env.local (secrets)? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    rm -f backend/.env.local
    echo -e "${GREEN}✓ backend/.env.local removido${NC}"
fi

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Reset completo!${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Para iniciar novamente:${NC}"
echo -e "  ${GREEN}make setup${NC}   - Setup completo"
echo -e "  ${GREEN}make dev${NC}     - Iniciar ambiente"
echo ""
