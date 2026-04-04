# Docker Quick Start 🚀

## Quick Setup in 3 Minutes

### 1️⃣ Choose Your Platform:

#### 🐧 Linux / Windows WSL2:
```bash
make setup
```

#### 🍎 macOS (Intel or Apple Silicon):
```bash
make setup-macos
```

### 2️⃣ Wait for Completion:
```bash
# Dependencies will be installed and migrations will run
# Wait approximately 2-3 minutes
```

### 3️⃣ Done! Open in Browser:
- **API**: http://localhost:8028/api
- **Adminer** (DB): http://localhost:8080
- **Mailpit** (Email): http://localhost:8025

---

## Useful Commands

```bash
make help          # Show all commands
make ps            # Container status
make logs          # View logs
make bash          # Enter PHP container
make test          # Run tests
```

## Database Connection

### Via Adminer (browser):
1. Open http://localhost:8080
2. Enter:
   - **Server**: `mysql`
   - **Username**: `fintech_user`
   - **Password**: `fintech_pass`
   - **Database**: `fintech_db`

### Via CLI:
```bash
make mysql
# Or:
docker compose exec mysql mysql -u fintech_user -pfintech_pass fintech_db
```

### Via TablePlus/DBeaver/etc:
- **Host**: `localhost`
- **Port**: `3327`
- **User**: `fintech_user`
- **Password**: `fintech_pass`
- **Database**: `fintech_db`

## API Testing

### Via Swagger UI:
Open: http://localhost:8028/api

### Via curl:
```bash
# Health check
curl http://localhost:8028/health

# API documentation
curl http://localhost:8028/api/docs.json
```

## Troubleshooting

### Ports Already in Use?
```bash
# Change ports
echo "NGINX_PORT=8029" >> .env.docker.local
echo "MYSQL_PORT=3328" >> .env.docker.local
make restart
```

### Slow on macOS?
```bash
# Make sure you're using macOS version:
make setup-macos
```

### Start from Scratch?
```bash
make down
make clean  # WARNING: deletes data!
make setup
```

## Next Steps

📖 **Detailed Documentation**: [DOCKER.md](DOCKER.md)
🔄 **What's New**: [DOCKER_CHANGELOG.md](DOCKER_CHANGELOG.md)
🏗️ **Architecture**: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

## Development Commands

```bash
# Run tests
make test

# Clear cache
make cache-clear

# Create migration
make migration-create

# Run migrations
make migrate

# Backup database
make db-backup

# Check code style
make cs-check

# Fix code style
make cs-fix
```

## macOS Specific

If you're on macOS, the Makefile automatically:
- Detects your architecture (Intel vs Apple Silicon)
- Uses optimal mount options
- Creates named volumes for vendor/var
- Configures correct MySQL image

## Production

```bash
# Start in production mode
make prod-up

# Or manually:
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

---

**Everything Working?** Great! Start developing 🎉

**Questions?** Check [DOCKER.md](DOCKER.md) or `make help`
