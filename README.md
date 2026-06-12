# 🎭 WhatsApp Salon Booking Bot

A completely **free, self-hosted WhatsApp booking agent** for salons. No external AI APIs, no monthly costs.

## Features

✅ **AI Chat** - Understands natural language (Ollama - free & local)
✅ **Google Calendar Sync** - Auto-syncs all bookings
✅ **Availability Check** - Auto-detects conflicts
✅ **Price List** - Bot quotes prices automatically
✅ **SMS Reminders** - 24 hours before appointment
✅ **Multi-language** - English, French, Spanish, German
✅ **Team Collaboration** - Everyone sees the calendar

## Cost

| Item | Cost |
|------|------|
| Server (Cloudways/VPS) | $10-15/month |
| WhatsApp API | Free (100 msg/day) or $0.002/msg |
| AI (Ollama) | FREE - runs on your server |
| Google Calendar | FREE |
| **TOTAL** | **$10-15/month** |

## Quick Start

### Prerequisites
- PHP 7.4+
- MySQL or SQLite
- Ollama (free local AI)
- WhatsApp Business Account
- Google Calendar API (free)

### Installation

1. **Clone repository**
```bash
git clone https://github.com/THANAT0SS/whatsapp-salon-booking.git
cd whatsapp-salon-booking
```

2. **Install dependencies**
```bash
composer install
```

3. **Set up database**
```bash
php setup_database.php
```

4. **Configure settings**
```bash
cp config.example.php config.php
# Edit config.php with your credentials
```

5. **Start Ollama** (in separate terminal)
```bash
ollama pull neural-chat
ollama serve
```

6. **Run local server**
```bash
php -S localhost:8000
```

7. **Test the bot**
Visit: http://localhost:8000/admin.php

## Files Overview

- `webhook.php` - Main entry point (receives WhatsApp messages)
- `config.php` - Configuration (credentials, salon settings)
- `setup_database.php` - Database initialization
- `send_reminders.php` - Sends reminders 24h before
- `admin.php` - View bookings dashboard
- `lib/` - Helper functions and classes

## Testing Locally

1. Use [Ngrok](https://ngrok.com) to expose local server:
```bash
ngrok http 8000
```

2. Set webhook in Meta App Dashboard:
- URL: `https://your-ngrok-url.ngrok.io/webhook.php`
- Verify Token: `test_verify_token_123`

3. Send WhatsApp message to test number

## Deployment to Cloudways

See `DEPLOYMENT.md`

## Support

Issues? Questions? Open an issue on GitHub!

---

**Built with ❤️ for salon owners who want to save money**