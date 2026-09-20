# SapinSolidaire - Production Installation Guide

A Laravel 12 + Livewire application for managing Christmas gift donations to families in need.

## System Requirements

### Server

- **OS**: Linux (Ubuntu 22.04+ recommended), or CentOS 8+
- **PHP**: 8.2 or higher (8.3+ recommended)
- **Composer**: 2.2 or higher
- **Node.js**: 18.x or 20.x
- **npm**: 9.x or higher

### Databases

Choose one:
- **PostgreSQL**: 14+ (recommended for production)
- **MySQL**: 8.0+ / MariaDB 10.7+
- **SQLite**: 3.35+ (for small deployments only)

### Optional Services

- **Email Server**: SMTP access or AWS SES / SendGrid (for production emails)
- **SSL Certificate**: Required for HTTPS (highly recommended)

---

## Pre-Installation Checklist

Before starting, ensure you have:

- [ ] SSH access to your server
- [ ] Sudo or root privileges
- [ ] Domain name pointing to server
- [ ] SSL certificate or ability to generate one (Let's Encrypt)
- [ ] Database credentials
- [ ] SMTP credentials (or email service API key)
- [ ] Web server configured (Nginx or Apache)

---

## Production Installation

### Step 1: Install System Dependencies

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Prepare Application Directory

```bash
# Create application directory
sudo mkdir -p /var/www/sapin-solidaire
cd /var/www/sapin-solidaire

# Download/clone the application (example using git)
sudo git clone <repository-url> .
# OR: sudo cp -r /local/path/* .

# Set proper permissions
sudo chown -R www-data:www-data /var/www/sapin-solidaire
sudo chmod -R 775 /var/www/sapin-solidaire/storage
sudo chmod -R 775 /var/www/sapin-solidaire/bootstrap/cache
```

### Step 3: Install PHP Dependencies

```bash
cd /var/www/sapin-solidaire

# Install Composer packages
composer install --no-dev --optimize-autoloader

# Clear cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: Install Frontend Dependencies

```bash
# Install Node dependencies
npm install --production

# Build assets for production
npm run build
```

### Step 5: Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` with production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=sapin_solidaire
DB_USERNAME=sapin_user
DB_PASSWORD=<strong-password>

# Mail Configuration (SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<your-smtp-user>
MAIL_PASSWORD=<your-smtp-password>
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="SapinSolidaire"

# Session (use 'database' for distributed setups)
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Optional: For production email providers
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=
```

### Step 6: Set Up Database

```bash
# Create PostgreSQL user and database (run as postgres user)
sudo -u postgres psql <<EOF
CREATE USER sapin_user WITH PASSWORD '<strong-password>';
CREATE DATABASE sapin_solidaire OWNER sapin_user;
GRANT ALL PRIVILEGES ON DATABASE sapin_solidaire TO sapin_user;
EOF

# Run migrations with seed data
php artisan migrate --force --seed
```

### Step 7: Configure Web Server

#### Nginx Configuration

Create `/etc/nginx/sites-available/sapin-solidaire`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    # SSL Certificates (use Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/sapin-solidaire/public;
    index index.php;

    # Logging
    access_log /var/log/nginx/sapin-solidaire-access.log;
    error_log /var/log/nginx/sapin-solidaire-error.log;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Disable access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
        return 404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/sapin-solidaire /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### Apache Configuration (Alternative)

Create `/etc/apache2/sites-available/sapin-solidaire.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/sapin-solidaire/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem

    ErrorLog ${APACHE_LOG_DIR}/sapin-solidaire-error.log
    CustomLog ${APACHE_LOG_DIR}/sapin-solidaire-access.log combined

    <Directory /var/www/sapin-solidaire/public>
        AllowOverride All
        Require all granted
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite sapin-solidaire
sudo a2enmod rewrite ssl
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### Step 8: Set Up SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### Step 9: Set Up Queue Worker (for emails)

Create `/etc/systemd/system/sapin-queue.service`:

```ini
[Unit]
Description=SapinSolidaire Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sapin-solidaire
ExecStart=/usr/bin/php artisan queue:work --wait=3
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable the service:
```bash
sudo systemctl enable sapin-queue
sudo systemctl start sapin-queue
sudo systemctl status sapin-queue
```

### Step 10: Set Up Cron Jobs

```bash
# Add to crontab (run as www-data user)
sudo -u www-data crontab -e

# Add these lines:
* * * * * /usr/bin/php /var/www/sapin-solidaire/artisan schedule:run >> /dev/null 2>&1
```

### Step 11: Post-Installation Setup

```bash
cd /var/www/sapin-solidaire

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Create first admin user
php artisan tinker
# In Tinker console:
# User::factory()->create(['email' => 'admin@example.com', 'password' => bcrypt('temp-password')]);
# $user = User::first();
# $user->roles()->sync([Role::whereSlug('admin')->first()->id]);
# exit
```

Access the application at `https://your-domain.com/admin` and change the admin password.

---

## Updating Production Deployment

```bash
cd /var/www/sapin-solidaire

# Pull latest changes
sudo git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Rebuild assets
npm install --production && npm run build

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
sudo systemctl restart sapin-queue
```

---

## Monitoring & Maintenance

### Check Application Health

```bash
# View recent logs
tail -f /var/www/sapin-solidaire/storage/logs/laravel.log

# Check queue worker status
sudo systemctl status sapin-queue

# Monitor database
php artisan tinker
# Check counts: GiftRequest::count(), Family::count(), etc.
```

### Backup Database

```bash
# PostgreSQL backup
sudo -u postgres pg_dump sapin_solidaire > backup-$(date +%Y%m%d).sql

# Backup files
tar -czf sapin-files-backup-$(date +%Y%m%d).tar.gz /var/www/sapin-solidaire/storage
```

### Enable Debug Logging (Temporary)

If issues occur, temporarily enable debug mode:
```env
APP_DEBUG=true
```

Then restart PHP-FPM:
```bash
sudo systemctl restart php8.3-fpm
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 500 error on pages | Check `/storage/logs/laravel.log` for errors |
| Email not sending | Verify SMTP credentials in `.env`; check `php artisan queue:work` is running |
| Uploads not working | Ensure `/storage` directory is writable by `www-data` |
| Database connection error | Verify database credentials and PostgreSQL is running |
| Assets not loading (404) | Run `npm run build` and clear browser cache |
| Permission denied errors | Run: `sudo chown -R www-data:www-data /var/www/sapin-solidaire` |

---

## Support

For detailed documentation, see:
- [Architecture Overview](/_doc/implementation.md)
- [Database Schema](/_doc/database.md)
- [Email Templates](/_doc/emails.md)
- [Missing Features](/_doc/missingFeatures.md)
