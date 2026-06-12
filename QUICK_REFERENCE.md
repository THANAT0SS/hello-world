# Quick Reference Guide

## 🚀 Quick Start (Choose One)

### Local Testing (5 min)
```bash
# 1. Install Ollama
curl https://ollama.ai/install.sh | sh
ollama pull neural-chat
ollama serve &

# 2. Clone & setup
git clone https://github.com/THANAT0SS/whatsapp-salon-booking.git
cd whatsapp-salon-booking
composer install
php setup_database.php
cp config.example.php config.php

# 3. Start server
php -S localhost:8000

# 4. Open admin panel
# http://localhost:8000/admin.php
```

### Production Deploy (30 min)
See [DEPLOYMENT.md](DEPLOYMENT.md) for step-by-step Cloudways setup

---

## 📋 Configuration Checklist

### Before Testing
- [ ] Copy `config.example.php` → `config.php`
- [ ] Set `VERIFY_TOKEN` to any random string
- [ ] Database created: `php setup_database.php`
- [ ] Ollama running: `ollama serve`
- [ ] PHP server started: `php -S localhost:8000`

### Before Production
- [ ] WhatsApp Business Account created
- [ ] Meta App created with WhatsApp product
- [ ] Phone number registered in WhatsApp
- [ ] `WHATSAPP_TOKEN` added to config.php
- [ ] `PHONE_NUMBER_ID` added to config.php
- [ ] Google Calendar API key downloaded
- [ ] Calendar shared with service account email
- [ ] SSL certificate installed
- [ ] Domain pointing to server
- [ ] Webhook URL configured in Meta Dashboard

---

## 🧪 Testing Quick Commands

### Test Ollama
```bash
curl http://localhost:11434/api/tags
```

### Test Database
```bash
mysql -u root salon_booking -e "SELECT COUNT(*) FROM bookings;"
```

### Test Webhook
```bash
curl -X GET "https://yourdomain.com/webhook.php?hub.mode=subscribe&hub.verify_token=test_token&hub.challenge=test123"
```

### View Logs
```bash
tail -f logs/bot.log
```

### View Bookings
```bash
mysql -u root salon_booking -e "SELECT * FROM bookings WHERE date >= CURDATE();"
```

---

## 📱 Test Messages

Send these to your WhatsApp test number:

| Test | Message | Expected Response |
|------|---------|-------------------|
| Simple Booking | "Book me tomorrow at 2pm" | ✅ Booking confirmed |
| With Name | "I'm John, haircut on Monday at 3pm" | ✅ Booked for John |
| Price Check | "How much for coloring?" | 💶 €50 (90 min) |
| Availability | "What times today?" | ⏰ List of times |
| French | "Je veux réserver lundi" | ✅ Response in French |

---

## 🛠️ Common Commands

### Start Services
```bash
# Ollama
ollama serve

# PHP Server
php -S localhost:8000

# MySQL (if not auto-running)
sudo systemctl start mysql
```

### Monitor
```bash
# Real-time logs
tail -f logs/bot.log

# System resources
top
free -h
df -h
```

### Database
```bash
# Connect
mysql -u root -p salon_booking

# Common queries
SHOW TABLES;
SELECT * FROM bookings;
SELECT * FROM messages ORDER BY created_at DESC;
DELETE FROM reminders WHERE sent = TRUE;
```

### Deploy
```bash
# SSH to server
ssh user@your-server-ip

# Upload files
rsync -avz ./ user@your-server-ip:/home/user/public_html/

# View logs
tail -f /home/user/public_html/logs/bot.log
```

---

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Ollama not responding | `ollama serve` in new terminal |
| Database error | `php setup_database.php` |
| No bookings showing | Check logs: `tail -f logs/bot.log` |
| Webhook not verifying | Compare token in config.php with Meta Dashboard |
| Bot too slow | Use smaller Ollama model: `ollama pull orca-mini` |
| Reminders not sending | Check cron: `sudo crontab -l` |
| Google Calendar not syncing | Verify service account email has calendar access |

---

## 📊 File Locations

```
Local Development:
~/whatsapp-salon-booking/
├── config.php              # Your credentials
├── logs/bot.log           # Debug logs
└── sqlite.db              # Local database

Cloudways Production:
/home/user/applications/your-app/public_html/
├── config.php
├── logs/bot.log
├── google-service-account-key.json
└── vendor/                # Composer packages
```

---

## 🔧 Environment Setup

### config.php Template
```php
<?php
// WhatsApp
define('VERIFY_TOKEN', 'your_random_token_123');
define('WHATSAPP_TOKEN', 'your_token_from_meta');
define('PHONE_NUMBER_ID', 'your_phone_id');

// Google Calendar
define('GOOGLE_CALENDAR_ID', 'your-email@gmail.com');
define('GOOGLE_SERVICE_ACCOUNT_PATH', 'google-service-account-key.json');

// Ollama AI
define('OLLAMA_URL', 'http://localhost:11434/api/generate');
define('OLLAMA_MODEL', 'neural-chat');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'salon_booking');
?>
```

---

## 📞 Support Resources

- **Documentation**: [README.md](README.md)
- **Local Setup**: [LOCAL_TESTING.md](LOCAL_TESTING.md)
- **Production Deployment**: [DEPLOYMENT.md](DEPLOYMENT.md)
- **Troubleshooting**: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **Test Scenarios**: [TESTING.md](TESTING.md)
- **Architecture**: [ARCHITECTURE.md](ARCHITECTURE.md)

---

## 💰 Cost Summary

| Service | Cost | Notes |
|---------|------|-------|
| Cloudways | $10-15/mo | Min plan |
| WhatsApp | Free-$0.002/msg | 100 msg/day free |
| Ollama | Free | Runs on your server |
| Google Calendar | Free | Free API tier |
| **TOTAL** | **$10-15/mo** | No AI API fees! |

---

## ✅ Deployment Checklist

### Pre-Launch
- [ ] Tested all features locally
- [ ] Ollama autostart configured
- [ ] Database backed up
- [ ] config.php production credentials ready
- [ ] SSL certificate installed
- [ ] Cron job for reminders set
- [ ] Logs folder permissions (777)
- [ ] vendor/ directory uploaded

### Post-Launch
- [ ] Monitor logs for 24 hours
- [ ] Send test bookings
- [ ] Verify Google Calendar syncing
- [ ] Test reminders 24h before
- [ ] Check admin panel
- [ ] Alert team on any issues

---

## 🎯 Next Steps

1. **Read**: [README.md](README.md) - Project overview
2. **Setup**: [LOCAL_TESTING.md](LOCAL_TESTING.md) - Local development
3. **Test**: Send WhatsApp messages, verify bookings appear
4. **Deploy**: [DEPLOYMENT.md](DEPLOYMENT.md) - Move to production
5. **Monitor**: [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Handle issues

---

**Last Updated**: 2024  
**Status**: ✅ Ready for Production  
**Tested on**: PHP 7.4-8.1, MySQL 5.7+, Ollama 0.1+
