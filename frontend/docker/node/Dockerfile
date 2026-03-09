# Multi-stage Dockerfile for React + Vite Development
FROM node:20-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    wget \
    && rm -rf /var/cache/apk/*

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install ALL dependencies (includes devDependencies needed for Vite)
RUN npm ci && \
    npm cache clean --force

# Copy source code
COPY . .

# Create non-root user with CONSISTENT naming (nodejs:nodejs)
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nodejs -u 1001 -G nodejs && \
    chown -R nodejs:nodejs /app

USER nodejs

# Expose Vite dev server port
EXPOSE 5173

# Health check for container status
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:5173 || exit 1

# Development command with host binding
CMD ["npm", "run", "dev"]