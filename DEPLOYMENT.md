# Deployment Guide - Cloudways

## Prerequisites

- Cloudways account
- WhatsApp Business Account
- Google Calendar API credentials
- Domain name with SSL (required by WhatsApp)

## Step 1: Set Up Server on Cloudways

### 1.1 Create Application

1. Log in to Cloudways Dashboard
2. Click "Create Application"
3. Select:
   - **Server**: PHP
   - **Version**: PHP 7.4+ (recommended 8.0+)
   - **Server Size**: Small (sufficient for salon)
   - **Region**: Closest to you

### 1.2 SSH into Server

```bash
ssh user@your-server-ip -p 22
```

## Step 2: Install Ollama

```bash
# Install Ollama
curl https://ollama.ai/install.sh | sh

# Download lightweight model
ollama pull neural-chat

# Verify installation
curl http://localhost:11434/api/tags
```

### Make Ollama Autostart

```bash
# Create systemd service
sudo nano /etc/systemd/system/ollama.service
```

Paste:
```ini
[Unit]
Description=Ollama Service
After=network-online.target

[Service]
Type=simple
User=ubuntu
ExecStart=/usr/local/bin/ollama serve
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable ollama
sudo systemctl start ollama
sudo systemctl status ollama
```

## Step 3: Deploy PHP Files

### 3.1 Upload via SFTP

Using Cloudways SFTP manager or command line:

```bash
# Connect via SFTP
sftp -P 22 user@your-server-ip

# Navigate to public folder
cd public_html

# Upload all files
put webhook.php
put admin.php
put send_reminders.php
put setup_database.php
put setup.sh
put config.example.php
put composer.json
put lib/helpers.php
```

Or use Git:

```bash
cd /home/user/applications/your-app/public_html
git clone https://github.com/THANAT0SS/whatsapp-salon-booking.git .
```

### 3.2 Install Dependencies

```bash
cd /home/user/applications/your-app/public_html
composer install
```

### 3.3 Set Up Database

```bash
# Access via SSH and run
php setup_database.php
```

### 3.4 Configure Files

```bash
# Copy config
cp config.example.php config.php

# Edit with your credentials
nano config.php
```

**Fill in:**
- `WHATSAPP_TOKEN` - From Meta App
- `PHONE_NUMBER_ID` - From Meta App
- `VERIFY_TOKEN` - Any random string
- `GOOGLE_CALENDAR_ID` - Your salon email
- `GOOGLE_SERVICE_ACCOUNT_PATH` - Path to JSON key

### 3.5 Upload Google Service Account Key

```bash
# Upload JSON key to secure location
scp -P 22 google-service-account-key.json user@your-server-ip:/home/user/applications/your-app/public_html/
```

## Step 4: Configure Webhook

### 4.1 Set Up SSL (If not already done)

Cloudways provides free SSL. Go to:
- Dashboard → Application → SSL
- Generate free SSL certificate

### 4.2 Update Meta App Dashboard

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Select your app
3. Go to WhatsApp → Configuration
4. Set Webhook:
   - **Callback URL**: `https://yourdomain.com/webhook.php`
   - **Verify Token**: Same as in `config.php`
   - **Webhook Fields**: Select `messages`

5. Verify webhook (Cloudways will test it)

### 4.3 Test Webhook

```bash
# Check logs
ssh user@your-server-ip
cd /home/user/applications/your-app/public_html
tail -f logs/bot.log
```

Send a WhatsApp message to test.

## Step 5: Set Up Cron for Reminders

```bash
# Access Cloudways cron panel or via SSH
crontab -e

# Add line (runs every hour)
0 * * * * /usr/bin/php /home/user/applications/your-app/public_html/send_reminders.php >> /tmp/reminders.log 2>&1

# Save (Ctrl+X, then Y)
```

## Step 6: Enable File Access

```bash
# Make sure files are readable
chmod 644 webhook.php admin.php send_reminders.php
chmod 755 lib/
chmod 777 logs/
```

## Step 7: Monitor

### View Logs

```bash
ssh user@your-server-ip
tail -f /home/user/applications/your-app/public_html/logs/bot.log
```

### Check Ollama Status

```bash
curl http://localhost:11434/api/tags
```

### View Bookings

Visit: `https://yourdomain.com/admin.php`

## Troubleshooting

### Bot Not Responding

1. Check webhook verification:
   ```bash
   curl -X GET "https://yourdomain.com/webhook.php?hub.mode=subscribe&hub.verify_token=your_verify_token_123&hub.challenge=test"
   ```

2. Check Ollama:
   ```bash
   ssh user@your-server-ip
   curl http://localhost:11434/api/generate -d '{"model":"neural-chat","prompt":"test"}'
   ```

3. Check database:
   ```bash
   # Via Cloudways database manager
   SELECT * FROM messages LIMIT 10;
   ```

### Ollama Memory Issues

If Ollama crashes with out-of-memory:

```bash
# Use smaller model
ollama pull orca-mini  # Smaller model

# Update config.php
define('OLLAMA_MODEL', 'orca-mini');
```

### SSL Certificate Issues

1. Renew SSL in Cloudways Dashboard
2. Clear browser cache
3. Test: https://yourdomain.com/webhook.php

## Maintenance

### Daily
- Monitor logs: `tail -f logs/bot.log`
- Check admin panel: `https://yourdomain.com/admin.php`

### Weekly
- Backup database via Cloudways
- Review booking statistics

### Monthly
- Clean old logs: `rm logs/bot.log.1 logs/bot.log.2`
- Update PHP dependencies: `composer update`

## Cost Optimization

- **Cloudways**: $10-15/month (minimum)
- **WhatsApp API**: Free tier covers ~100 messages/day
- **Ollama**: No additional cost (runs on your server)
- **Google Calendar**: Free

**Total Monthly Cost: $10-15/month**

## Support

For issues:
1. Check logs: `tail -f logs/bot.log`
2. Verify webhook: Meta App Dashboard → Logs
3. Test locally first before deploying
4. Open GitHub issue: [Issues](https://github.com/THANAT0SS/whatsapp-salon-booking/issues)

