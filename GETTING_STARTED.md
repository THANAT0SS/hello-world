# WhatsApp Salon Booking Bot - Complete Setup Guide

## 🎯 What You'll Build

A **WhatsApp AI booking agent for your salon** that:
- ✅ Receives booking requests via WhatsApp
- ✅ Understands natural language ("book me tomorrow at 2pm")
- ✅ Stores bookings in a database
- ✅ Syncs to Google Calendar automatically
- ✅ Sends 24-hour reminders
- ✅ Works in multiple languages
- ✅ **Costs: $10-15/month** (no AI API fees!)

---

## 📋 Prerequisites

### What You Need

1. **A Computer** with:
   - PHP 7.4+
   - MySQL 5.7+ (or use SQLite - no install needed)
   - Terminal/Command Line access

2. **Online Accounts** (all free):
   - Facebook Developer Account
   - WhatsApp Business Account
   - Google Account (for Calendar)
   - Meta App (for WhatsApp API)

3. **Software to Install** (free):
   - Ollama (local AI)
   - Composer (PHP package manager)
   - Ngrok (for local testing)

---

## 🚀 Installation Steps

### Step 1: Install Ollama (Local AI)

**macOS:**
```bash
brew install ollama
```

**Linux:**
```bash
curl https://ollama.ai/install.sh | sh
```

**Windows:**
- Download from [ollama.ai](https://ollama.ai)
- Run installer

**Verify Installation:**
```bash
ollama --version
```

### Step 2: Download Project Files

**Option A: Using Git**
```bash
git clone https://github.com/THANAT0SS/whatsapp-salon-booking.git
cd whatsapp-salon-booking
```

**Option B: Manual Download**
- Go to https://github.com/THANAT0SS/whatsapp-salon-booking
- Click "Code" → "Download ZIP"
- Extract to folder
- Open terminal in that folder

### Step 3: Install Dependencies

```bash
# Install Composer if you don't have it
curl -sS https://getcomposer.org/installer | php

# Install PHP packages
composer install
```

### Step 4: Create Configuration

```bash
# Copy example config
cp config.example.php config.php

# Edit it
nano config.php  # or use your favorite editor
```

**For now, just change the verify token:**
```php
define('VERIFY_TOKEN', 'my_test_token_123');
```

### Step 5: Setup Database

```bash
# Create tables
php setup_database.php
```

You should see:
```
✅ Customers table created
✅ Bookings table created
✅ Messages table created
✅ Reminders table created
✅ DATABASE SETUP COMPLETE!
```

### Step 6: Start Ollama

**In a new terminal:**
```bash
# Download AI model (first time, ~4GB)
ollama pull neural-chat

# Start server (keeps running)
ollama serve
```

You should see:
```
Listening on 127.0.0.1:11434 (HTTP)
```

### Step 7: Start PHP Server

**In another new terminal:**
```bash
# Start PHP development server
php -S localhost:8000
```

You should see:
```
Listening on http://127.0.0.1:8000
```

### Step 8: View Admin Panel

Open your browser:
```
http://localhost:8000/admin.php
```

You should see a dashboard (empty for now).

---

## 🧪 Test Locally with Ngrok

### Step 1: Install Ngrok

**macOS:**
```bash
brew install ngrok
```

**Linux/Windows:**
- Download from [ngrok.com](https://ngrok.com)
- Extract and add to PATH

### Step 2: Create Ngrok Tunnel

**In a new terminal:**
```bash
ngrok http 8000
```

You'll see:
```
Forwarding  https://xxxx-xxxx-xxxx.ngrok.io -> http://localhost:8000
```

**Copy that HTTPS URL** - you'll need it next.

### Step 3: Configure Meta App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create an app or select existing
3. Add WhatsApp product
4. Go to **Configuration**
5. Set Webhook:
   - **Callback URL**: `https://xxxx-xxxx-xxxx.ngrok.io/webhook.php`
   - **Verify Token**: `my_test_token_123` (from your config.php)
   - **Webhook Fields**: Check `messages`

6. Click **Verify and Save**

**If it fails**, check:
   - Ngrok URL is correct (starts with https://)
   - Verify token matches exactly
   - Check PHP server is running

### Step 4: Send Test Message

1. Get your **WhatsApp test number** from Meta App Dashboard
2. Send test message:
   ```
   I want to book a haircut tomorrow at 2pm
   ```
3. You should get response:
   ```
   ✅ Booking Confirmed!
   📝 Name: Guest
   📅 Date: [tomorrow]
   ⏰ Time: 2:00 PM
   💇 Service: Haircut
   💶 Price: €25
   ```

### Step 5: Check Admin Panel

Refresh: `http://localhost:8000/admin.php`

You should see your booking in the table!

---

## 🌍 Deploy to Production (Cloudways)

When you're ready to go live:

1. **Read**: [DEPLOYMENT.md](DEPLOYMENT.md)
2. **Follow steps 1-7** in that guide
3. **Your bot will be live!**

---

## 📊 Test Different Scenarios

### Test 1: Simple Booking
```
Message: "Book a massage on Monday at 3pm"
Expected: ✅ Booking confirmed for Monday at 3:00 PM
```

### Test 2: With Your Name
```
Message: "Hi, I'm Sarah. I want a haircut tomorrow at 10am"
Expected: ✅ Booking confirmed for Sarah, Haircut, Tomorrow at 10:00 AM
```

### Test 3: Ask About Prices
```
Message: "How much for coloring?"
Expected: 💶 Coloring: €50 (90 minutes)
```

### Test 4: Check Availability
```
Message: "What times are available today?"
Expected: ⏰ Available times: 10am, 11am, 2pm, 3pm, 4pm, 5pm
```

### Test 5: Multi-language (French)
```
Message: "Bonjour, je veux une coupe lundi à 14h"
Expected: ✅ Réservation confirmée! (Response in French)
```

---

## 🔧 Troubleshooting

### "Ollama connection refused"
```bash
# Make sure Ollama terminal is still running
# If not, start it
ollama serve
```

### "Database connection error"
```bash
# Make sure MySQL is running
# On macOS
brew services start mysql@5.7

# On Linux
sudo systemctl start mysql

# Or setup database again
php setup_database.php
```

### "Webhook verification failed"
1. Check ngrok is still running
2. Check verify token matches config.php
3. Check PHP server is running
4. Look at logs: `tail -f logs/bot.log`

### "No bookings appear in admin panel"
```bash
# Check database has data
mysql -u root salon_booking -e "SELECT * FROM bookings;"

# Check logs for errors
tail -f logs/bot.log
```

### "Bot responses are slow (10+ seconds)"
```bash
# Use faster AI model
ollama pull orca-mini

# Update config.php
define('OLLAMA_MODEL', 'orca-mini');
```

**For more help**: See [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

## 📱 Files Explanation

| File | Purpose |
|------|----------|
| `webhook.php` | Receives WhatsApp messages |
| `admin.php` | Dashboard to view bookings |
| `config.php` | Your settings & credentials |
| `send_reminders.php` | Sends reminders 24h before |
| `lib/helpers.php` | Helper functions |
| `logs/bot.log` | Debug logs |

---

## 💰 Costs

| Item | Cost | Notes |
|------|------|-------|
| Server (Cloudways) | $10-15/month | Minimum plan |
| WhatsApp API | Free-$0.002/msg | 100 free/day |
| AI (Ollama) | Free | Runs on YOUR server |
| Google Calendar | Free | Free API tier |
| **TOTAL** | **$10-15/month** | **NO AI API FEES!** |

---

## 🎯 What's Next?

✅ **You've successfully:**
- Set up local development
- Tested WhatsApp integration
- Stored bookings in database
- Viewed bookings in admin panel

📚 **Next steps:**
1. Send more test messages
2. Test all languages
3. Verify Google Calendar syncing
4. Deploy to Cloudways (see DEPLOYMENT.md)
5. Monitor in production

---

## 🆘 Need Help?

1. **Check logs**: `tail -f logs/bot.log`
2. **Read docs**: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
3. **Test each part**: Ollama, MySQL, Webhook separately
4. **Open GitHub issue**: [Issues](https://github.com/THANAT0SS/whatsapp-salon-booking/issues)

---

**Happy Booking! 🎉**

You now have a free WhatsApp booking bot for your salon.
