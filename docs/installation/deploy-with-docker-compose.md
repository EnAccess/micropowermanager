---
order: 2
---

# Deployment with Docker Compose

This guide walks you through deploying MicroPowerManager using Docker Compose on a standalone Linux server.
This approach is ideal for production deployments where you need full control over your infrastructure.

> [!INFO]
> For production deployments, additional configuration steps are required including:
>
> - Installing a web server like [Nginx](https://nginx.org/) as a reverse proxy
> - Managing TLS certificates with [Let's Encrypt](https://letsencrypt.org/)
> - General Linux server maintenance (system updates, security patches, performance monitoring)
>
> There are plenty of great resources available online that cover these topics in detail.

## Prerequisites

Before you begin, ensure your server meets the following requirements.

### Server Access

- **SSH access** to the server
- A user with **`sudo` privileges**, required for installing Docker, Nginx and configuring the firewall

All commands in this guide are run on the server over SSH.

### System Requirements

- **Operating System**: 64-bit Linux, for example Ubuntu 24.04 LTS or Debian 12
- **CPU Architecture**: `x86_64` (`amd64`) or `arm64`
- **RAM**: Minimum 4GB, 8GB recommended for production
- **Storage**: 20GB free disk space minimum
- **CPU**: 2 cores minimum, 4 cores recommended for production
- **Network**: Outbound internet access to pull images from Docker Hub

> [!WARNING]
> macOS and Windows (including WSL2) are not supported as server operating systems.
> MicroPowerManager images are only published for `linux/amd64` and `linux/arm64`.
> To try MicroPowerManager on a laptop, follow the [Development Environment](../development/development-environment.md) guide instead.

### Software Requirements

- **Docker Engine**: Version 24 or higher, installed following the [official instructions for your distribution](https://docs.docker.com/engine/install/).
  Docker Desktop is not required and not recommended on servers.
- **Docker Compose plugin**: Version 2 or higher (`docker compose`, not the legacy `docker-compose`)
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

Create three `.env` files to separate configuration for backend, frontend, and MySQL.

```bash
# From your micro-powermanager directory
touch .env.backend .env.frontend .env.mysql
```

Add the following configuration to each file, updating values for your environment.
`DB_PASSWORD` in `.env.backend` must match `MYSQL_ROOT_PASSWORD` in `.env.mysql`.

::: code-group

```dotenv [.env.backend]
# Application Configuration
APP_ENV=production
APP_KEY=base64:your-generated-app-key-here
APP_DEBUG=false
APP_URL=https://api.your-domain.com
MPM_FRONTEND_URL=https://your-domain.com
JWT_SECRET=your-generated-jwt-secret-here

# Database Configuration (host is the "mysql" service from compose)
DB_CONNECTION=micro_power_manager
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
```

```dotenv [.env.frontend]
MPM_ENV=production
MPM_BACKEND_URL=https://api.your-domain.com
```

```dotenv [.env.mysql]
MYSQL_ROOT_PASSWORD=your-secure-database-password
MYSQL_DATABASE=micro_power_manager
```

:::

See [Environment Variables](environment-variables.md) for the full list of supported settings.

## 3. Generate Secrets

Generate the Laravel application key:

```bash
openssl rand -base64 32
```

Paste the output into `APP_KEY` in `.env.backend`, keeping the `base64:` prefix.

Generate the JWT secret:

```bash
openssl rand -base64 48
```

Paste the output into `JWT_SECRET` in `.env.backend`.

## 4. Create Docker Compose File

Create a `docker-compose.yml` file with the following configuration:

::: code-group

```yaml [docker-compose.yml]
services:
  backend:
    image: enaccess/micropowermanager-backend:latest
    env_file:
      - .env.backend
    ports:
      # Only reachable from the server itself, expose it via the reverse proxy
      - 127.0.0.1:8000:80
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_started
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
    env_file:
      - .env.frontend
    ports:
      # Only reachable from the server itself, expose it via the reverse proxy
      - 127.0.0.1:8001:80
    depends_on:
      - backend
    restart: unless-stopped

  scheduler:
    image: enaccess/micropowermanager-scheduler:latest
    env_file:
      - .env.backend
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_started
    restart: unless-stopped
    volumes:
      - storage_data:/var/www/html/storage

  worker:
    image: enaccess/micropowermanager-queue-worker:latest
    env_file:
      - .env.backend
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_started
    restart: unless-stopped
    volumes:
      - storage_data:/var/www/html/storage
    healthcheck:
      test:
        [
          CMD,
          php,
          /var/www/html/artisan,
          mpm-system-checks:horizon,
          --local,
          --quiet,
        ]
      start_period: 60s
      interval: 30s
      timeout: 10s
      retries: 5

  mysql:
    image: mysql:8.4
    env_file:
      - .env.mysql
    restart: unless-stopped
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: [CMD, mysqladmin, ping, -h, localhost]
      start_period: 10s
      interval: 10s
      timeout: 5s
      retries: 3

  redis:
    image: redis:7
    restart: unless-stopped
    volumes:
      - redis_data:/data

volumes:
  mysql_data:
  redis_data:
  storage_data:
```

:::

MySQL and Redis are intentionally not published on the host.
The other services reach them over the internal Compose network.

> [!WARNING]
> Ports published by Docker bypass host firewalls such as `ufw`.
> Never publish MySQL or Redis ports, and bind the backend and frontend to `127.0.0.1` as shown above.

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

On first start, the backend runs all database migrations before it becomes healthy.

## 6. Verify Deployment

Check that all services are running and healthy:

```bash
# Check service status
docker compose ps

# View logs if needed
docker compose logs -f
```

Test the backend health endpoint:

```bash
curl -fsS -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/up
```

Expected response: `200`

## 7. Access Your Application

At this point MicroPowerManager is only reachable from the server itself:

- **Frontend**: <http://127.0.0.1:8001>
- **Backend API**: <http://127.0.0.1:8000>

Continue with [Production Configuration](#production-configuration) to expose it on your domain.

> [!WARNING]
> The application is not yet fully configured for production use.
> You'll need to complete additional setup steps including the reverse proxy, TLS certificates, user creation and company configuration.

## Alternative: Quick Start with Provided Compose Files

> [!WARNING]
> This approach is provided for quick testing and development.
> It uses the development configuration from `dev/` and publishes MySQL and Redis on the host.
> For production deployments, use the [Custom Docker Compose Setup](#4-create-docker-compose-file) above.

If you want to get started quickly without customizing the configuration, you can use the provided compose files as a starting point.

### Option 1: DockerHub Images

```bash
# Clone the repository
git clone https://github.com/EnAccess/micropowermanager.git
cd micropowermanager

# Start all services
docker compose -f docker-compose-dockerhub.yml up -d
```

### Option 2: Build Locally

```bash
# Clone the repository
git clone https://github.com/EnAccess/micropowermanager.git
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

### Update to a New Release

```bash
docker compose pull
docker compose up -d
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
   sudo ss -tulpn | grep -E ':(8000|8001)\b'
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

4. **Check the passwords match**: `DB_PASSWORD` in `.env.backend` must equal `MYSQL_ROOT_PASSWORD` in `.env.mysql`.
   `MYSQL_ROOT_PASSWORD` is only applied when the `mysql_data` volume is first created.

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

| Service  | Port | Reachable from      | Description   |
| -------- | ---- | ------------------- | ------------- |
| Backend  | 8000 | Server only (local) | HTTP API      |
| Frontend | 8001 | Server only (local) | Web Interface |
| MySQL    | 3306 | Compose network     | Database      |
| Redis    | 6379 | Compose network     | Cache         |

### Data Persistence

The following data is persisted using Docker volumes:

- **mysql_data**: Database files
- **redis_data**: Cache data
- **storage_data**: Application files and uploads

To backup your data:

```bash
# Backup database
docker compose exec mysql mysqldump -u root -p micro_power_manager > backup.sql

# Backup application storage
docker run --rm -v micro-powermanager_storage_data:/data -v $(pwd):/backup alpine tar czf /backup/storage_backup.tar.gz /data
```

## Production Configuration

For production deployment, you'll need to configure the following.

### DNS Configuration

Point your domains to your server's IP address:

| Record Type | Name                | Value          |
| ----------- | ------------------- | -------------- |
| A           | your-domain.com     | your-server-ip |
| A           | api.your-domain.com | your-server-ip |

### Web Server Setup

Install Nginx as a reverse proxy, serving the frontend and backend on separate domains to match `APP_URL`, `MPM_FRONTEND_URL` and `MPM_BACKEND_URL`:

```bash
sudo apt install nginx
```

> [!WARNING]
> This Nginx is managed by your operating system, not by the MicroPowerManager images.
> Keep it patched with your distribution's security updates, especially if you enable HTTP/2.

Create `/etc/nginx/sites-available/micropowermanager`:

::: code-group

```nginx [/etc/nginx/sites-available/micropowermanager]
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

server {
    listen 80;
    server_name api.your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

:::

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/micropowermanager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Configure the backend to trust the proxy headers, see [Configuring Trusted Proxies](configuration-production.md#configuring-trusted-proxies).

### SSL Certificate Setup

Use Let's Encrypt for free SSL certificates:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificates for both domains
sudo certbot --nginx -d your-domain.com -d api.your-domain.com
```

### Firewall Configuration

Configure the firewall to allow only SSH, HTTP and HTTPS:

```bash
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

## Next Steps

After successful deployment, your MicroPowerManager instance will be accessible at your configured domain.
However, additional configuration is required for production use:

1. **User Management**: Create admin users and configure authentication
2. **Company Setup**: Configure your company information and settings
3. **Security**: Review and implement security best practices
4. **Monitoring**: Set up monitoring and alerting
5. **Backups**: Implement regular backup procedures

Please proceed to [Configuration for Production](configuration-production.md) to complete your setup.
