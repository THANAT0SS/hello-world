# Local Development & Testing Guide

## Prerequisites

- PHP 7.4+
- MySQL 5.7+ or SQLite
- Ollama
- Ngrok (for WhatsApp testing)
- Composer

## Quick Start (5 minutes)

### 1. Install Ollama

**macOS/Linux:**
```bash
curl https://ollama.ai/install.sh | sh
```

**Windows:**
Download from [ollama.ai](https://ollama.ai)

### 2. Download Model

```bash
# First time only
ollama pull neural-chat

# Start server (in background)
ollama serve &
```

### 3. Clone & Setup

```bash
# Clone repo
git clone https://github.com/THANAT0SS/whatsapp-salon-booking.git
cd whatsapp-salon-booking

# Install dependencies
composer install

# Setup database
php setup_database.php

# Copy config
cp config.example.php config.php
```

### 4. Edit Config

```bash
nano config.php
```

Just need to change:
```php
define('VERIFY_TOKEN', 'test_token_123');
```

### 5. Start Server

```bash
php -S localhost:8000
```

Visit: http://localhost:8000/admin.php

## Testing with Ngrok

### 1. Install Ngrok

```bash
# Download from https://ngrok.com
# Or via homebrew (macOS)
brew install ngrok
```

### 2. Expose Local Server

```bash
# In separate terminal
ngrok http 8000
```

You'll see:
```
Forwarding  https://xxxx-xxx-xxx.ngrok.io -> http://localhost:8000
```

### 3. Configure Meta App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Select your app → WhatsApp → Configuration
3. Set Webhook:
   - **Callback URL**: `https://xxxx-xxx-xxx.ngrok.io/webhook.php`
   - **Verify Token**: `test_token_123` (from config.php)

4. Test webhook - it should verify automatically

### 4. Send Test Message

Send WhatsApp message to your test number:
```
I want to book a haircut tomorrow at 2pm
```

You should get response:
```
✅ Booking Confirmed!
📝 Name: Guest
📅 Date: [tomorrow]
⏰ Time: 2:00 PM
💇 Service: Haircut
💶 Price: €25
```

### 5. Check Logs

```bash
# Terminal 1: Local server logs
php -S localhost:8000

# Terminal 2: Check log file
tail -f logs/bot.log

# Terminal 3: View admin panel
# http://localhost:8000/admin.php
```

## Testing Different Scenarios

### Test 1: Simple Booking

**Message:**
```
book me for saturday at 3pm
```

**Expected:** Booking confirmed for next Saturday at 3:00 PM

### Test 2: With Name

**Message:**
```
my name is john and i want a massage tomorrow at 2pm
```

**Expected:** Booking confirmed for John, Massage, Tomorrow at 2:00 PM

### Test 3: Availability Check

**Message:**
```
what times are available tomorrow?
```

**Expected:** List of available times

### Test 4: Price Check

**Message:**
```
how much for coloring?
```

**Expected:** Coloring costs €50

### Test 5: Multi-language (French)

**Message:**
```
bonjour je veux réserver lundi à 15h
```

**Expected:** Response in French

## Database Queries

### View Messages

```bash
# Via PHP
php -a
```

Then:
```php
$db = new mysqli('localhost', 'root', '', 'salon_booking');
$result = $db->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 10');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
```

### View Bookings

```bash
mysql -u root salon_booking -e "SELECT * FROM bookings;"
```

### View Reminders

```bash
mysql -u root salon_booking -e "SELECT * FROM reminders WHERE sent = FALSE;"
```

## Troubleshooting

### "Connection refused" Error

Ollama not running:
```bash
# Start Ollama
ollama serve
```

### "Database connection error"

MySQL not running:
```bash
# macOS
brew services start mysql@5.7

# Linux
sudo systemctl start mysql
```

### "Webhook verification failed"

1. Check ngrok is still running
2. Verify token matches in config.php
3. Check logs: `tail -f logs/bot.log`

### Empty admin.php

No bookings appear:
1. Database might not be set up: `php setup_database.php`
2. Check logs for errors
3. Verify webhook is receiving messages

## Performance Tips

### Speed Up Ollama

```bash
# Use faster model
ollama pull neural-chat-7b-v3-q5_K_M

# Update config.php
define('OLLAMA_MODEL', 'neural-chat-7b-v3-q5_K_M');
```

### Monitor Resources

```bash
# Watch Ollama memory usage
watch -n 1 'ps aux | grep ollama'
```

## Deployment Checklist

Before pushing to production:

- [ ] All test messages work
- [ ] Admin panel shows bookings
- [ ] Reminders queue working
- [ ] Google Calendar syncing
- [ ] Logs are clean (no errors)
- [ ] config.php has production credentials
- [ ] Database backed up
- [ ] Ollama autostart configured
- [ ] Cron job for reminders set
- [ ] SSL certificate installed

## Next Steps

1. **Test locally** using ngrok
2. **Deploy to Cloudways** (see DEPLOYMENT.md)
3. **Monitor** first 24 hours
4. **Collect feedback** from team
5. **Optimize** based on usage

