#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🛑 Creditall Frontend - Stopping Docker Environment${NC}"
echo -e "${BLUE}====================================================${NC}"

# Stop containers
echo -e "${YELLOW}Stopping frontend containers...${NC}"
docker-compose down

# Check if containers are stopped
if ! docker ps | grep -q "creditall_frontend"; then
    echo -e "${GREEN}✅ Frontend containers stopped successfully${NC}"
else
    echo -e "${RED}❌ Some containers may still be running${NC}"
    docker-compose ps
fi

echo ""
echo -e "${CYAN}📋 Para reiniciar:${NC}"
echo -e "   ./docker-dev.sh    ou    npm run docker:dev"
echo ""