# 📌 Creditall-Challenge

Repositório para a resoluçao do desafio técnico fullstack da creditall-challenge

## 🚀 Tecnologias Utilizadas

- **Frontend**: React 1.9 + TypeScript + Vite
- **Backend**: PHP 8.3 + Laravel 12
- **Banco de Dados**: MySQL
- **Containerização**: Docker + Docker Compose

## 📂 Estrutura do Repositório

```bash
├── frontend/      # Código-fonte do frontend
├── backend/       # Código-fonte do backend
├── docker-compose.yml  # Configuração do Docker Compose
├── Makefile       # Comandos personalizados
└── README.md      # Documentação do projeto

```

## ⚙️ Pré-requisitos

Antes de começar, certifique-se de ter instalado em sua máquina:

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)

## 🛠️ Configuração e Execução

### 1️⃣ Clonar o Repositório
```bash
git clone https://github.com/djonathanassis/creditall-challenge/tree/desafio
cd creditall-challenge
```
### 2️⃣ Iniciar a Aplicação com Docker

Para subir os containers do frontend, backend e banco de dados, execute:

```bash
make setup
```

Aguarde os containers iniciarem e acesse:
- **Frontend**: [http://localhost:5173](http://localhost:3000)
- **Backend**: [http://localhost:8080](http://localhost:8080)

### 4️⃣ Parar os Containers

Para parar e remover os containers:
```bash
make down
```