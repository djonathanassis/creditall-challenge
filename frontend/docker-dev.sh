#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🐳 Creditall Challenge - Frontend Docker Development Environment${NC}"
echo -e "${BLUE}====================================================================${NC}"
echo ""

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${CYAN}🔍 $1${NC}"
}

# Check if Docker is running
print_info "Verificando se Docker está rodando..."
if ! docker info >/dev/null 2>&1; then
    print_error "Docker não está rodando."
    echo -e "${YELLOW}   Por favor, inicie o Docker Desktop ou o serviço Docker primeiro.${NC}"
    exit 1
fi
print_status "Docker está rodando"

# Check if backend network exists
print_info "Verificando rede do backend..."
if ! docker network ls | grep -q "backend_creditall_network"; then
    print_error "Rede 'backend_creditall_network' não encontrada."
    echo -e "${YELLOW}   Execute primeiro o backend:${NC}"
    echo -e "${CYAN}   cd ../backend && docker-compose up -d${NC}"
    exit 1
fi
print_status "Rede do backend encontrada"

# Check if backend containers are running
print_info "Verificando containers do backend..."
if ! docker ps | grep -q "creditall_localhost_web"; then
    print_error "Container 'creditall_localhost_web' não está rodando."
    echo -e "${YELLOW}   Execute primeiro o backend:${NC}"
    echo -e "${CYAN}   cd ../backend && docker-compose up -d${NC}"
    exit 1
fi
print_status "Backend containers estão rodando"

# Test backend connectivity
print_info "Testando conectividade com o backend..."
if ! curl -sf http://localhost:8080 >/dev/null; then
    print_warning "Backend não está respondendo em localhost:8080"
    echo -e "${YELLOW}   Continuando mesmo assim... será verificado internamente no Docker${NC}"
else
    print_status "Backend está respondendo em localhost:8080"
fi

echo ""
echo -e "${PURPLE}🔨 Building frontend container...${NC}"

# Stop existing containers if running
if docker ps | grep -q "creditall_frontend"; then
    print_info "Parando container frontend existente..."
    docker-compose stop frontend
fi

# Build the container
docker-compose build --no-cache frontend

echo ""
echo -e "${PURPLE}🚀 Starting development environment...${NC}"

# Start containers
docker-compose up -d

# Wait for container to be ready
print_info "Aguardando containers iniciarem..."
sleep 10

# Check if frontend container is running
if docker ps | grep -q "creditall_frontend"; then
    print_status "Frontend container está rodando!"
    
    # Check if Vite dev server is responding
    print_info "Verificando se Vite dev server está respondendo..."
    for i in {1..30}; do
        if curl -sf http://localhost:5173 >/dev/null 2>&1; then
            print_status "Vite dev server está respondendo!"
            break
        fi
        if [ $i -eq 30 ]; then
            print_warning "Vite dev server ainda não está respondendo após 30 tentativas"
            print_info "Verifique os logs com: docker-compose logs -f frontend"
        else
            sleep 2
        fi
    done
    
    echo ""
    echo -e "${GREEN}🎉 Frontend Docker Environment está pronto!${NC}"
    echo ""
    echo -e "${CYAN}🌐 Acessos:${NC}"
    echo -e "   Frontend:  ${BLUE}http://localhost:5173${NC}"
    echo -e "   Backend:   ${BLUE}http://localhost:8080${NC}"
    echo ""
    echo -e "${CYAN}📋 Comandos úteis:${NC}"
    echo -e "   Ver logs:        ${YELLOW}docker-compose logs -f frontend${NC}"
    echo -e "   Shell container: ${YELLOW}docker-compose exec frontend sh${NC}"
    echo -e "   Parar:          ${YELLOW}docker-compose down${NC}"
    echo -e "   Restart:        ${YELLOW}docker-compose restart frontend${NC}"
    echo ""
    echo -e "${CYAN}🔧 NPM Scripts disponíveis:${NC}"
    echo -e "   npm run docker:logs    - Ver logs do container"
    echo -e "   npm run docker:shell   - Shell do container"
    echo -e "   npm run docker:down    - Parar containers"
    echo -e "   npm run docker:restart - Reiniciar frontend"
    echo ""
    
else
    print_error "Erro ao iniciar frontend container"
    echo ""
    echo -e "${YELLOW}📋 Logs do container:${NC}"
    docker-compose logs frontend
    echo ""
    echo -e "${YELLOW}🔧 Para debugar:${NC}"
    echo -e "   docker-compose logs frontend"
    echo -e "   docker-compose exec frontend sh"
    exit 1
fi