# Munimail

A modern, lightweight, and fast **SMTP server** built with **Laravel** and **ReactPHP**, designed for reliable email transmission, real-time processing, and seamless integration into your applications.

> Built with love for reliable message delivery — streaming, secure, and scalable.

---

##  Current Features

###  **Core SMTP Server (Fully Implemented)**
-  **Complete SMTP Protocol**: EHLO/HELO, MAIL FROM, RCPT TO, DATA, QUIT, RSET, NOOP
-  **Memory-Safe Streaming**: Handles emails of any size with constant ~8KB memory usage
-  **ReactPHP Async Processing**: Non-blocking, high-performance connection handling
-  **Maildir Storage**: Industry-standard email storage format
-  **TLS/SSL Support**: Secure connections with certificate-based encryption

###  **Advanced Processing (Implemented)**
-  **Background Job Queue**: Asynchronous email processing with Laravel queues
-  **Size Limits**: Configurable email size limits (default: 10MB)
-  **Email Validation**: Sender/recipient address validation and duplicate checking(more validation comming soon)
-  **Resource Management**: Automatic cleanup of temporary files and connections
-  **Real-time Logging**: Detailed connection and processing logs
-  **Error Handling**: Proper SMTP error codes and graceful failure handling

---

##  **Important: What This Server Does**

### **Email Reception & Storage (Current)**
This server currently **receives and stores** incoming emails:
- Accepts SMTP connections from email clients
-  Validates and processes incoming emails  
-  Stores emails in Maildir format (`storage/app/maildir/`)
-  Queues emails for processing via Laravel jobs
-  **Use Case**: Internal email collection, development, testing, webhook processing

###  **Email Delivery (Future - Phase 6)**
** This server does NOT yet deliver emails to external inboxes (Gmail, Yahoo, etc.)**

To become a full email service, Phase 6 will add:
- [ ] **SMTP Relay**: Forward emails to external SMTP servers
- [ ] **MX Record Handling**: Direct delivery to recipient mail servers  
- [ ] **Delivery Queue**: Retry logic for failed deliveries
- [ ] **Bounce Handling**: Process delivery failures and bounces
- [ ] **Reputation Management**: IP warming and reputation monitoring

---

##  **Installation & Setup**

### **Prerequisites**
- PHP 8.2+
- Composer
- Laravel 10+
- SQLite/MySQL/PostgreSQL

### **Basic Installation**

```bash
# 1. Clone the repository
git clone https://github.com/yourusername/munimail.git
cd munimail

# 2. Install dependencies
composer install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Database setup
php artisan migrate

# 5. Create storage directories
mkdir -p storage/app/maildir/{tmp,new,cur}
chmod -R 755 storage/app/maildir
```

### **Configuration**

#### **1. Basic SMTP Settings**
```bash
# .env file
SMTP_SERVER_PORT=25
SMTP_TLS_PORT=587
SMTP_MAX_EMAIL_SIZE=10485760  # 10MB

# For TLS support (optional)
SMTP_TLS_CERT_PATH=/path/to/cert.pem
SMTP_TLS_KEY_PATH=/path/to/key.pem
```

#### **2. TLS Certificate Setup**

**Option A: Self-Signed Certificate (Development/Testing)**
```bash
# Generate self-signed certificate for testing
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes

# Or with more specific parameters
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"
```

**Option B: Let's Encrypt Certificate (Production)**
```bash
# Install certbot
sudo apt-get install certbot

# Generate certificate for your domain
sudo certbot certonly --standalone -d yourdomain.com

# Copy certificates to your project
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem ./cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem ./key.pem

# Set proper permissions
sudo chown www-data:www-data cert.pem key.pem
sudo chmod 600 key.pem
sudo chmod 644 cert.pem
```

**Option C: Custom Certificate Authority (Enterprise)**
```bash
# Generate CA private key
openssl genrsa -out ca-key.pem 4096

# Generate CA certificate
openssl req -new -x509 -days 365 -key ca-key.pem -out ca-cert.pem \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=MyCA"

# Generate server private key
openssl genrsa -out server-key.pem 4096

# Create server certificate signing request
openssl req -new -key server-key.pem -out server.csr \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=yourdomain.com"

# Sign server certificate with CA
openssl x509 -req -in server.csr -CA ca-cert.pem -CAkey ca-key.pem \
  -CAcreateserial -out cert.pem -days 365

# Clean up CSR
rm server.csr
```

**Certificate File Structure:**
```
munimail/
├── certificates/
│   ├── cert.pem          # Certificate file
│   ├── key.pem           # Private key file
│   └── ca-cert.pem       # CA certificate (if using custom CA)
├── .env
└── ...
```

#### **3. Queue Configuration**
```bash
# .env file - Choose your queue driver
QUEUE_CONNECTION=database  # or redis, sync

# For database queues
php artisan queue:table
php artisan migrate
```

#### **4. Quick Certificate Commands Reference**

**Development (Self-Signed):**
```bash
# Generate certificate
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes

# Start server
php artisan smtp:serve --port=25 --tls-port=587 --cert=cert.pem --key=key.pem
```

**Production (Let's Encrypt):**
```bash
# Generate certificate
sudo certbot certonly --standalone -d yourdomain.com

# Copy certificates
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem ./cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem ./key.pem

# Start server
php artisan smtp:serve --port=25 --tls-port=587 --cert=cert.pem --key=key.pem
```

### **Running the Server**

#### **Method 1: Basic Setup (Development)**
```bash
# Terminal 1: Start queue worker (required for email processing)
php artisan queue:work

# Terminal 2: Start SMTP server
php artisan smtp:serve --port=25

# Optional: Use different port to avoid permission issues
php artisan smtp:serve --port=2525
```

#### **Method 2: With TLS Support**

**Quick Start (Self-Signed Certificate):**
```bash
# Generate self-signed certificate for testing
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes

# Start with TLS
php artisan smtp:serve --port=25 --tls-port=587 --cert=cert.pem --key=key.pem
```

**Production Setup (Let's Encrypt):**
```bash
# After generating certificates with certbot (see Configuration section)
php artisan smtp:serve --port=25 --tls-port=587 --cert=cert.pem --key=key.pem
```

**Testing TLS Connection:**
```bash
# Test with openssl
openssl s_client -connect localhost:587 -starttls smtp

# Test with telnet (then STARTTLS)
telnet localhost 25

# Test with curl (if you have a test endpoint)
curl --insecure -v telnet://localhost:587
```

**Common TLS Issues & Solutions:**

1. **Certificate not found:**
   ```bash
   # Check certificate exists and has correct permissions
   ls -la cert.pem key.pem
   chmod 600 key.pem
   chmod 644 cert.pem
   ```

2. **Permission denied:**
   ```bash
   # Run with proper user permissions
   sudo php artisan smtp:serve --port=25 --tls-port=587 --cert=cert.pem --key=key.pem
   ```

3. **Certificate validation failed:**
   ```bash
   # For development, use self-signed certificates
   # For production, ensure proper CA chain
   openssl verify -CAfile ca-cert.pem cert.pem
   ```

#### **Method 3: Production Setup**
```bash
# Install supervisor for process management
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/munimail.conf
```

**Supervisor Configuration:**
```ini
[program:munimail-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/munimail/artisan queue:work --sleep=3 --tries=3
directory=/path/to/munimail
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/munimail/storage/logs/queue.log

[program:munimail-smtp]
process_name=%(program_name)s
command=php /path/to/munimail/artisan smtp:serve --port=25 --tls-port=587 --cert=/path/to/munimail/cert.pem --key=/path/to/munimail/key.pem
directory=/path/to/munimail
autostart=true
autorestart=true
user=root
redirect_stderr=true
stdout_logfile=/path/to/munimail/storage/logs/smtp.log
```

```bash
# Start services
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

#### **Certificate Renewal (Production)**

**Let's Encrypt Auto-Renewal:**
```bash
# Set up automatic renewal
sudo crontab -e

# Add this line for daily renewal checks
0 12 * * * /usr/bin/certbot renew --quiet && cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem /path/to/munimail/cert.pem && cp /etc/letsencrypt/live/yourdomain.com/privkey.pem /path/to/munimail/key.pem && sudo supervisorctl restart munimail-smtp
```

**Manual Renewal:**
```bash
# Renew certificate
sudo certbot renew

# Copy new certificates
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem /path/to/munimail/cert.pem
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem /path/to/munimail/key.pem

# Restart SMTP server
sudo supervisorctl restart munimail-smtp
```

**Certificate Health Check:**
```bash
# Check certificate expiration
openssl x509 -in cert.pem -text -noout | grep "Not After"

# Verify certificate chain
openssl verify -CAfile /etc/ssl/certs/ca-certificates.crt cert.pem

# Test TLS connection
echo "QUIT" | openssl s_client -connect localhost:587 -starttls smtp
```

---

##  **Production Readiness Roadmap**

###  **Ready Now: Single User/Testing**
-  Core SMTP server fully functional
-  Memory-safe for production workloads
-  TLS encryption support
-  Basic authentication (hardcoded)
-  **Use Case**: Internal applications, development, single organization


**Phase 1 Features:**
- 👥 **Multi-User Management**: Database-driven user accounts
-  **Dynamic Authentication**: Users get individual SMTP credentials
-  **Usage Quotas**: Per-user storage and daily sending limits
-  **Rate Limiting**: Prevent abuse with configurable limits
-  **Usage Tracking**: Monitor per-user email statistics
-  **Admin Commands**: Admin panel for user management
-  **Use Case**: **Ready for customer SMTP credentials!**

**Phase 2 Features:**
-  **Web Dashboard**: User self-service portal for managing accounts
-  **Advanced Analytics**: Detailed reporting and usage insights  
-  **API Management**: RESTful API for user and email management
-  **Use Case**: **Full commercial SMTP service provider**

**Phase 3 Features:**

####  **SMTP Server Metrics**
- **Connection Metrics**: Current connections, connection rate, connection duration
- **Email Metrics**: Emails/second, bytes transferred, queue depth
- **Authentication Metrics**: Login success/failure rates, user activity
- **Error Metrics**: SMTP error codes, failed deliveries, timeouts
- **Performance Metrics**: Memory usage, CPU utilization, disk I/O

#### **Business Intelligence Dashboards**
- **User Analytics**: Active users, top senders, quota utilization
- **Revenue Metrics**: Usage-based billing insights, customer growth
- **SLA Monitoring**: Uptime, response times, service availability
- **Capacity Planning**: Growth trends, resource forecasting

**Grafana Dashboards:**
-  **SMTP Server Overview**: Real-time server health and performance
- 👥 **User Management**: Per-user statistics, quota usage, activity
-  **Business Metrics**: Revenue, customer acquisition, usage trends  
-  **Infrastructure**: System resources, network, storage
-  **Alerts Dashboard**: Active alerts, incident history, SLA status

####  **Custom Metrics Collection**
**Monitoring Endpoints:**
- `/metrics` - Prometheus metrics endpoint
- `/health` - Health check endpoint  
- `/ready` - Readiness probe
- `/stats` - Real-time statistics API

**Phase 4 Features:**
-  **Email Parser**: Extract headers, body, and attachments
-  **DKIM Verification**: Validate email signatures  
-  **SPF Checks**: Sender Policy Framework validation
-  **DMARC Support**: Domain-based message authentication
-  **Advanced Spam Filtering**: Bayesian filters and reputation scoring
-  **Content Analytics**: Email content insights and patterns

---

##  **Future Roadmap**

### **Phase 5: Scale & Enterprise (100% Complete) - ETA: 18+ weeks**
- [ ] **Multi-tenancy**: Support for multiple domains/organizations
- [ ] **Load Balancing**: Horizontal scaling with multiple server instances
- [ ] **Message Routing**: Smart delivery based on rules and policies
- [ ] **Backup & Recovery**: Automated backup and disaster recovery
- [ ] **Webhook Integration**: Custom event handlers and notifications
- [ ] **High Availability**: Cluster mode with automatic failover

---

##  **Business Use Cases by Phase**

| Phase | Ready For | Timeline | Key Features | Monitoring |
|-------|-----------|----------|--------------|------------|
| **Current** | Internal apps, Development |  **Now** | Single user, Full SMTP | Basic logs |
| **Phase 1** |  **SMTP Service Provider** | **3-4 weeks** | **Multi-user credentials** | File-based logs |
| **Phase 2** | Commercial SMTP business | **6-8 weeks** | Web dashboard, Billing | Database analytics |
| **Phase 3** | **Enterprise Production** | **10-12 weeks** | ** Prometheus + Grafana** | **Full observability** |
| **Phase 4** | Secure email platform | **14-16 weeks** | DKIM/SPF, Content filtering | Security metrics |
| **Phase 5** | Large-scale email service | **18+ weeks** | Multi-tenant, HA cluster | Distributed monitoring |

---

##  **Getting Started**

### **Current (Single User)**
```bash
# Basic setup
composer install && cp .env.example .env && php artisan key:generate
php artisan queue:work & php artisan smtp:serve
```

### **Phase 3 (Production with Monitoring)**
```bash
# Start monitoring stack
docker-compose -f docker-compose.monitoring.yml up -d

# Configure Prometheus scraping
# prometheus.yml
scrape_configs:
  - job_name: 'munimail'
    static_configs:
      - targets: ['localhost:8080']
    scrape_interval: 15s
    metrics_path: '/metrics'

# Access dashboards
# Grafana: http://localhost:3000 (admin/admin)
# Prometheus: http://localhost:9090
```

**Sample Grafana Dashboard Queries:**
```promql
# Emails per second
rate(smtp_emails_total[5m])

# Active connections  
smtp_connections_active

# User quota usage
(smtp_user_storage_bytes / smtp_user_quota_bytes) * 100

# Error rate
rate(smtp_errors_total[5m]) / rate(smtp_connections_total[5m])
```

---

##  **Acknowledgments**

Built with passion for reliable email infrastructure. **Phase 3 brings enterprise-grade monitoring that rivals commercial email services!**

*Special thanks to the ReactPHP, Laravel, Prometheus, and Grafana communities.*

