---
order: 2
---

# Deployment with Docker Compose

This guide walks you through deploying MicroPowerManager using Docker Compose on a standalone server. This approach is ideal for production deployments where you need full control over your infrastructure.

> [!INFO]
> For production deployments, additional configuration steps are required including:
>
> - Installing a web server like [Nginx](https://nginx.org/) as a reverse proxy
> - Managing TLS certificates with [Let's Encrypt](https://letsencrypt.org/)
> - General Linux server maintenance (system updates, security patches, performance monitoring)
>
> There are plenty of great resources available online that cover these topics in detail.

## Prerequisites

Before you begin, ensure your system meets the following requirements:

### System Requirements

- **Operating System**: Linux (Ubuntu 20.04+ recommended), macOS, or Windows with WSL2
- **RAM**: Minimum 4GB, 8GB recommended for production
- **Storage**: 20GB free disk space minimum
- **CPU**: 2 cores minimum, 4 cores recommended for production
- **Network**: Stable internet connection for downloading images and updates

### Software Requirements

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **Git**: For cloning the repository (optional)

### Verify Prerequisites

Check your Docker installation:

```bash
docker --version
docker compose version
```

Both commands should return version information without errors.

## 1. Create Project Directory

Create a dedicated directory for your MicroPowerManager deployment:

```bash
mkdir micro-powermanager
cd micro-powermanager
```

## 2. Environment Configuration

Create a `.env` file to store your configuration variables:

```bash
nano .env
```

Add the following configuration, updating the values to match your environment:

```env
# Application Configuration
APP_ENV=production
APP_KEY=base64:your-generated-app-key-here
APP_DEBUG=false
APP_URL=https://api.your-domain.com
MPM_FRONTEND_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=micro_power_manager
DB_USERNAME=root
DB_PASSWORD=your-secure-database-password

# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# MicroPowerManager Configuration
MPM_LOAD_DEMO_DATA=false
MPM_ENV=production

# Frontend Configuration
MPM_BACKEND_URL=https://api.your-domain.com

# Database User Configuration
MYSQL_ROOT_PASSWORD=your-secure-database-password
MYSQL_DATABASE=micro_power_manager
MYSQL_USER=mpm_user
MYSQL_PASSWORD=your-secure-user-password
```

## 3. Generate Application Key

Generate a secure application key for your deployment:

```bash
openssl rand -base64 32
```

Copy the generated key and replace `your-generated-app-key-here` in your `.env` file.

## 4. Create Docker Compose File

Create a `docker-compose.yml` file with the following configuration:

```yaml
version: "3.8"

services:
  backend:
    image: enaccess/micropowermanager-backend:latest
    environment:
      APP_ENV: ${APP_ENV}
      APP_KEY: ${APP_KEY}
      APP_DEBUG: ${APP_DEBUG}
      APP_URL: ${APP_URL}
      DB_CONNECTION: ${DB_CONNECTION}
      DB_HOST: ${DB_HOST}
      DB_PORT: ${DB_PORT}
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      CACHE_DRIVER: ${CACHE_DRIVER}
      REDIS_HOST: ${REDIS_HOST}
      REDIS_PORT: ${REDIS_PORT}
      MPM_LOAD_DEMO_DATA: ${MPM_LOAD_DEMO_DATA}
      MPM_ENV: ${MPM_ENV}
      MPM_FRONTEND_URL: ${MPM_FRONTEND_URL}
    ports:
      - "8000:80"
      - "8443:443"
    depends_on:
      - redis
      - mysql
    restart: unless-stopped
    volumes:
      - storage_data:/var/www/html/storage
    healthcheck:
      test: [CMD, curl, -f, http://localhost/up]
      start_period: 60s
      interval: 30s
      timeout: 10s
      retries: 3

  frontend:
    image: enaccess/micropowermanager-frontend:latest
    environment:
      MPM_ENV: ${MPM_ENV}
      MPM_BACKEND_URL: ${MPM_BACKEND_URL}
    ports:
      - "8001:80"
    depends_on:
      - backend
    restart: unless-stopped

  scheduler:
    image: enaccess/micropowermanager-scheduler:latest
    environment:
      APP_ENV: ${APP_ENV}
      APP_KEY: ${APP_KEY}
      APP_DEBUG: ${APP_DEBUG}
      APP_URL: ${APP_URL}
      DB_CONNECTION: ${DB_CONNECTION}
      DB_HOST: ${DB_HOST}
      DB_PORT: ${DB_PORT}
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      CACHE_DRIVER: ${CACHE_DRIVER}
      REDIS_HOST: ${REDIS_HOST}
      REDIS_PORT: ${REDIS_PORT}
      MPM_LOAD_DEMO_DATA: ${MPM_LOAD_DEMO_DATA}
      MPM_ENV: ${MPM_ENV}
    depends_on:
      - redis
      - mysql
    restart: unless-stopped
    volumes:
      - storage_data:/var/www/html/storage

  worker:
    image: enaccess/micropowermanager-queue-worker:latest
    environment:
      APP_ENV: ${APP_ENV}
      APP_KEY: ${APP_KEY}
      APP_DEBUG: ${APP_DEBUG}
      APP_URL: ${APP_URL}
      DB_CONNECTION: ${DB_CONNECTION}
      DB_HOST: ${DB_HOST}
      DB_PORT: ${DB_PORT}
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      CACHE_DRIVER: ${CACHE_DRIVER}
      REDIS_HOST: ${REDIS_HOST}
      REDIS_PORT: ${REDIS_PORT}
      MPM_LOAD_DEMO_DATA: ${MPM_LOAD_DEMO_DATA}
      MPM_ENV: ${MPM_ENV}
    depends_on:
      - redis
      - mysql
    restart: unless-stopped
    volumes:
      - storage_data:/var/www/html/storage

  mysql:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: [CMD, mysqladmin, ping, -h, localhost]
      start_period: 10s
      interval: 10s
      timeout: 5s
      retries: 3

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    restart: unless-stopped
    volumes:
      - redis_data:/data

volumes:
  mysql_data:
  redis_data:
  storage_data:
```

## 5. Deploy MicroPowerManager

Start all services:

```bash
docker compose up -d
```

This command will:

- Download the required Docker images
- Create and start all containers
- Set up networking between services
- Create persistent volumes for data storage

## 6. Verify Deployment

Check that all services are running:

```bash
# Check service status
docker compose ps

# View logs if needed
docker compose logs -f
```

Test the backend health endpoint:

```bash
# Test backend health
curl http://localhost:8000/up
```

Expected response: `{"status":"ok"}`

## 7. Access Your Application

Once deployed, MicroPowerManager will be accessible at:

- **Frontend**: http://localhost:8001
- **Backend API**: http://localhost:8000

> [!WARNING]
> The application is not yet fully configured for production use. You'll need to complete additional setup steps including user creation, company configuration, and SSL certificate setup.

## Alternative: Quick Start with Provided Compose Files

> [!WARNING]
> This approach is provided for quick testing and development. For production
> deployments, we recommend using the [Custom Docker Compose Setup](#4-create-docker-compose-file) above.

If you want to get started quickly without customizing the configuration,
you can use the provided compose files as a starting point.

### Option 1: DockerHub Images

```bash
# Clone the repository
git clone https://github.com/your-org/micropowermanager.git
cd micropowermanager

# Start all services
docker compose -f docker-compose-dockerhub.yml up -d
```

### Option 2: Build Locally

```bash
# Clone the repository
git clone https://github.com/your-org/micropowermanager.git
cd micropowermanager

# Start all services
docker compose -f docker-compose-prod.yml up -d
```

## Service Management

### Start Services

```bash
# Start all services
docker compose up -d

# Start specific service
docker compose up -d backend
```

### Stop Services

```bash
# Stop all services
docker compose stop

# Stop specific service
docker compose stop backend
```

### View Logs

```bash
# View all logs
docker compose logs -f

# View specific service logs
docker compose logs -f backend

# View last 100 lines
docker compose logs --tail=100 backend
```

### Restart Services

```bash
# Restart all services
docker compose restart

# Restart specific service
docker compose restart backend
```

## Troubleshooting

### Common Issues

#### Services Won't Start

1. **Check Docker is running**:

   ```bash
   docker --version
   docker compose version
   ```

2. **Check port conflicts**:

   ```bash
   # Check if ports are in use
   netstat -tulpn | grep :8000
   netstat -tulpn | grep :8001
   ```

3. **Check logs for errors**:
   ```bash
   docker compose logs backend
   docker compose logs mysql
   ```

#### Database Connection Issues

1. **Verify MySQL is running**:

   ```bash
   docker compose ps mysql
   ```

2. **Check database logs**:

   ```bash
   docker compose logs mysql
   ```

3. **Test database connection**:
   ```bash
   docker compose exec mysql mysql -u root -p
   ```

#### Backend Health Check Fails

1. **Check backend logs**:

   ```bash
   docker compose logs backend
   ```

2. **Verify environment variables**:

   ```bash
   docker compose exec backend env | grep DB_
   ```

3. **Test internal connectivity**:
   ```bash
   docker compose exec backend curl http://localhost/up
   ```

### Service Ports

| Service  | Port | Description   |
| -------- | ---- | ------------- |
| Backend  | 8000 | HTTP API      |
| Frontend | 8001 | Web Interface |
| MySQL    | 3306 | Database      |
| Redis    | 6379 | Cache         |

### Data Persistence

The following data is persisted using Docker volumes:

- **mysql_data**: Database files
- **redis_data**: Cache data
- **storage_data**: Application files and uploads

To backup your data:

```bash
# Backup database
docker compose exec mysql mysqldump -u root -p micro_power_manager > backup.sql

# Backup volumes
docker run --rm -v micro-powermanager_mysql_data:/data -v $(pwd):/backup alpine tar czf /backup/mysql_backup.tar.gz /data
```

## Production Configuration

For production deployment, you'll need to configure:

### Web Server Setup

Install and configure Nginx as a reverse proxy:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### SSL Certificate Setup

Use Let's Encrypt for free SSL certificates:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com
```

### Firewall Configuration

Configure firewall to allow necessary traffic:

```bash
# Allow HTTP and HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Allow SSH (if needed)
sudo ufw allow 22
```

### DNS Configuration

Point your domain to your server's IP address:

| Record Type | Name                | Value          |
| ----------- | ------------------- | -------------- |
| A           | your-domain.com     | your-server-ip |
| A           | api.your-domain.com | your-server-ip |

## Next Steps

After successful deployment, your MicroPowerManager instance will be accessible at your configured domain. However, additional configuration is required for production use:

1. **User Management**: Create admin users and configure authentication
2. **Company Setup**: Configure your company information and settings
3. **Security**: Review and implement security best practices
4. **Monitoring**: Set up monitoring and alerting
5. **Backups**: Implement regular backup procedures

Please proceed to [Configuration for Production](configuration-production.md) to complete your setup.
