# Docker Setup Guide

Complete guide for running the project through Docker on different platforms.

## Quick Start

### Linux / Windows WSL2
```bash
# 1. Clone the repository
git clone <your-repo-url>
cd fn_method

# 2. Copy env file
cp .env.docker .env.docker.local

# 3. Start containers
docker compose up -d

# 4. Install dependencies
docker compose exec php composer install

# 5. Run migrations
docker compose exec php bin/console doctrine:migrations:migrate -n

# 6. Done!
# API: http://localhost:8028
# Adminer: http://localhost:8080
# Mailpit: http://localhost:8025
```

### macOS (Intel)
```bash
# 1-2. Same as above

# 3. Edit .env.docker.local
# Uncomment for macOS Intel:
MYSQL_PLATFORM=linux/amd64
MOUNT_OPTIONS=:cached

# 4. Start with macOS optimizations
docker compose -f compose.yaml -f compose.macos.yaml up -d

# 5-6. Same as above
```

### macOS (Apple Silicon M1/M2/M3)
```bash
# 1-2. Same as above

# 3. Edit .env.docker.local
# Uncomment for Apple Silicon:
MYSQL_PLATFORM=linux/arm64
MOUNT_OPTIONS=:delegated

# 4. Start with macOS optimizations
docker compose -f compose.yaml -f compose.macos.yaml up -d

# 5-6. Same as above
```

## File Structure

```
├── compose.yaml              # Main file (base configuration)
├── compose.override.yaml     # Development overrides (automatically loaded)
├── compose.macos.yaml        # macOS optimizations
├── compose.prod.yaml         # Production configuration
├── .env.docker               # Environment variables (template)
├── .env.docker.local         # Your local settings (git ignored)
└── docker/
    ├── nginx/
    │   └── default.conf      # Nginx configuration
    ├── mysql/
    │   └── my.cnf            # MySQL configuration
    └── php/
        └── php.ini           # PHP configuration
```

## Available Services

| Service  | Port  | URL                        | Description                   |
|----------|-------|----------------------------|-------------------------------|
| Nginx    | 8028  | http://localhost:8028      | Web server                    |
| MySQL    | 3327  | localhost:3327             | Database                      |
| Redis    | 6379  | localhost:6379             | Cache/sessions                |
| Adminer  | 8080  | http://localhost:8080      | Database management           |
| Mailpit  | 8025  | http://localhost:8025      | Email testing                 |
| Xdebug   | 9003  | localhost:9003             | Debugging                     |

## Useful Commands

### Container Management
```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# Restart specific service
docker compose restart php

# View logs
docker compose logs -f php

# View status
docker compose ps

# Complete cleanup (including volumes)
docker compose down -v
```

### Working with PHP
```bash
# Execute command in PHP container
docker compose exec php bash

# Composer
docker compose exec php composer install
docker compose exec php composer update
docker compose exec php composer require vendor/package

# Symfony Console
docker compose exec php bin/console list
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console cache:clear

# Tests
docker compose exec php bin/phpunit
```

### Working with Database
```bash
# Connect to MySQL
docker compose exec mysql mysql -u fintech_user -pfintech_pass fintech_db

# Export database
docker compose exec mysql mysqldump -u root -proot fintech_db > backup.sql

# Import database
docker compose exec -T mysql mysql -u root -proot fintech_db < backup.sql

# Via Adminer
# Open http://localhost:8080
# Server: mysql
# Username: fintech_user
# Password: fintech_pass
# Database: fintech_db
```

### Console Commands
```bash
# Create user
docker compose exec php bin/console app:create-user user@example.com password

# Account operations
docker compose exec php bin/console app:deposit-money <account-id> 100.00 USD
docker compose exec php bin/console app:withdraw-money <account-id> 50.00 USD
docker compose exec php bin/console app:get-account-balance <account-id>
docker compose exec php bin/console app:transfer-money <from-id> <to-id> 50.00 USD

# Rebuild projections
docker compose exec php bin/console app:rebuild-account-projections
```

## Different Environments

### Development (default)
```bash
docker compose up -d
# Includes: Xdebug, Adminer, Mailpit, verbose logging
```

### Production
```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d
# Excludes: Xdebug, development tools
# Optimized for performance
```

### macOS Optimizations
```bash
docker compose -f compose.yaml -f compose.macos.yaml up -d
# Uses delegated/cached mount options
# Named volumes for vendor and var
```

## Xdebug Configuration

### PhpStorm / IntelliJ IDEA

1. **Settings → PHP → Servers**
   - Name: `fintech`
   - Host: `localhost`
   - Port: `8028`
   - Debugger: `Xdebug`
   - Path mappings: `/path/to/project` → `/var/www/html`

2. **Settings → PHP → Debug**
   - Xdebug port: `9003`

3. **Enable listening**
   - Click on phone icon in toolbar

4. **Start Xdebug**
```bash
# In .env.docker.local set:
XDEBUG_MODE=develop,debug

# Restart container
docker compose restart php
```

### VS Code

Add to `.vscode/launch.json`:
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

## Performance Tips

### macOS
1. Use `compose.macos.yaml`
2. Vendor and var in named volumes (already configured)
3. Consider VirtioFS in Docker Desktop settings

### General
1. Disable Xdebug when not in use: `XDEBUG_MODE=off`
2. Limit resources in Docker Desktop (4GB RAM, 2 CPU usually sufficient)
3. Periodically clean up: `docker system prune -a`

## Troubleshooting

### Ports Already in Use
```bash
# Change ports in .env.docker.local
NGINX_PORT=8029
MYSQL_PORT=3328
```

### Permission Denied (macOS/Linux)
```bash
# Add your user to www-data group (Linux)
sudo usermod -aG www-data $USER

# Or change permissions
docker compose exec php chown -R www-data:www-data /var/www/html/var
```

### MySQL Connection Refused
```bash
# Wait for health check
docker compose ps

# Or view logs
docker compose logs mysql
```

### Slow Performance on macOS
```bash
# Make sure you're using compose.macos.yaml
docker compose -f compose.yaml -f compose.macos.yaml up -d

# Check that vendor is in named volume
docker compose exec php ls -la /var/www/html/vendor
```

### Clean Everything and Start Fresh
```bash
docker compose down -v
docker volume prune
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate -n
```

## API Testing

### Via curl
```bash
# Health check
curl http://localhost:8028/health

# API Docs
curl http://localhost:8028/api/docs

# Create account (requires authentication)
curl -X POST http://localhost:8028/api/accounts \
  -H "Content-Type: application/json" \
  -d '{"userId": "user-123", "currency": "USD"}'
```

### Via API Platform UI
Open in browser: http://localhost:8028/api

## Health Checks

All services have health checks:
```bash
# Check status
docker compose ps

# Detailed information
docker inspect fintech_php | grep -A 10 Health
```

## Backup & Restore

### Backup
```bash
# MySQL
docker compose exec mysql mysqldump -u root -proot fintech_db > backup.sql

# Event Store
docker compose exec mysql mysqldump -u root -proot fintech_db event_store > events_backup.sql

# Volumes
docker run --rm -v fintech_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_data.tar.gz /data
```

### Restore
```bash
# MySQL
docker compose exec -T mysql mysql -u root -proot fintech_db < backup.sql

# Volume
docker run --rm -v fintech_mysql_data:/data -v $(pwd):/backup alpine tar xzf /backup/mysql_data.tar.gz -C /
```

## Production Checklist

- [ ] Change passwords in `.env.docker.local`
- [ ] Use `compose.prod.yaml`
- [ ] Disable Xdebug: `XDEBUG_MODE=off`
- [ ] Configure HTTPS (reverse proxy)
- [ ] Close database ports externally
- [ ] Configure backups
- [ ] Configure monitoring
- [ ] Configure logs (ELK stack)
- [ ] Check security headers
- [ ] Configure rate limiting

## Resources

- [Docker Compose Docs](https://docs.docker.com/compose/)
- [Symfony Docker Best Practices](https://symfony.com/doc/current/setup/docker.html)
- [macOS Docker Performance](https://docs.docker.com/desktop/mac/)
