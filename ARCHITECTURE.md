# Project Structure

```
whatsapp-salon-booking/
├── webhook.php                 # Main entry point - receives WhatsApp messages
├── admin.php                   # Dashboard - view bookings
├── send_reminders.php          # Cron job - sends 24h reminders
├── setup_database.php          # Database initialization
├── setup.sh                    # Setup script
├── config.example.php          # Config template
├── config.php                  # Your configuration (git ignored)
├── composer.json               # PHP dependencies
├── composer.lock               # Locked dependencies
├── .gitignore                  # Git ignore rules
│
├── lib/
│   └── helpers.php             # Helper functions
│
├── logs/
│   └── bot.log                 # Application logs
│
└── docs/
    ├── README.md               # This file
    ├── DEPLOYMENT.md           # Cloudways deployment guide
    ├── LOCAL_TESTING.md        # Local development guide
    ├── TESTING.md              # Test scenarios
    └── ARCHITECTURE.md         # System architecture
```

## File Descriptions

### Core Files

#### `webhook.php`
- Receives WhatsApp messages from Meta API
- Verifies webhook authenticity
- Processes messages with Ollama AI
- Extracts booking information
- Syncs with Google Calendar
- Stores bookings in database
- Sends responses back

#### `config.php`
- WhatsApp credentials
- Google Calendar credentials
- Ollama AI settings
- Database connection
- Salon configuration (services, hours)
- Language settings

#### `admin.php`
- Dashboard to view all bookings
- Real-time updates (auto-refresh every 10s)
- Shows customer details, dates, times, services
- Status tracking
- Mobile-responsive UI

#### `send_reminders.php`
- Scheduled job (runs via cron every hour)
- Sends WhatsApp reminders 24 hours before appointment
- Updates reminder status in database

#### `setup_database.php`
- Creates all necessary tables
- Initializes MySQL database
- Run once: `php setup_database.php`

### Helper Files

#### `lib/helpers.php`
Utility functions:
- `detectLanguage()` - Identifies language from text
- `translate()` - Multi-language support
- `parseDate()` - Extracts date from message
- `parseTime()` - Extracts time from message
- `logMessage()` - Application logging

### Configuration

#### `config.example.php`
Template with all required settings. Copy to `config.php` and fill in credentials.

#### `.gitignore`
Prevents sensitive files from being committed:
- `config.php` (has credentials)
- `vendor/` (dependencies)
- `logs/` (application logs)
- `.env` files

### Documentation

#### `README.md`
Project overview, features, quick start

#### `DEPLOYMENT.md`
Step-by-step Cloudways deployment

#### `LOCAL_TESTING.md`
Local development with Ollama and ngrok

#### `TESTING.md`
Test scenarios and example messages

## Database Schema

### `customers` Table
```sql
phone          VARCHAR(20) PRIMARY KEY
name           VARCHAR(255)
status         VARCHAR(50) - idle, booking, selecting_date, etc.
selected_service VARCHAR(100)
created_at     TIMESTAMP
```

### `bookings` Table
```sql
id             INT AUTO_INCREMENT PRIMARY KEY
phone          VARCHAR(20) FOREIGN KEY
name           VARCHAR(255)
service        VARCHAR(100)
date           DATE
time           TIME
status         VARCHAR(50) - confirmed, completed, cancelled
event_id       VARCHAR(255) - Google Calendar event ID
created_at     TIMESTAMP
```

### `messages` Table
```sql
id             INT AUTO_INCREMENT PRIMARY KEY
phone          VARCHAR(20)
text           TEXT
type           VARCHAR(20) - incoming, outgoing
timestamp      INT - Unix timestamp
created_at     TIMESTAMP
```

### `reminders` Table
```sql
id             INT AUTO_INCREMENT PRIMARY KEY
phone          VARCHAR(20) FOREIGN KEY
booking_date   DATE
booking_time   TIME
reminder_time  DATETIME - When to send reminder
sent           BOOLEAN - Whether reminder was sent
created_at     TIMESTAMP
```

## API Integrations

### WhatsApp Cloud API
- **Endpoint**: `https://graph.facebook.com/v18.0/{phone_id}/messages`
- **Method**: POST
- **Authentication**: Bearer token
- **Rate**: 100 messages/day (free tier)

### Ollama Local AI
- **Endpoint**: `http://localhost:11434/api/generate`
- **Method**: POST
- **Model**: neural-chat (4GB)
- **Response Time**: 2-5 seconds

### Google Calendar API
- **Service**: Google Calendar API v3
- **Authentication**: Service Account (JSON key)
- **Syncs**: Bookings to calendar automatically

## Workflow

```
Customer sends WhatsApp message
    ↓
webhook.php receives request
    ↓
Verify webhook authenticity
    ↓
Send to Ollama AI for understanding
    ↓
Extract booking info (date, time, service, name)
    ↓
Check availability in database
    ↓
If available:
    - Add to Google Calendar
    - Save to database
    - Schedule reminder for 24h before
    - Send confirmation to customer
    ↓
If not available:
    - Show available slots
    - Ask customer to choose different time
    ↓
Save all messages to database
```

## Cron Jobs

```bash
# Run every hour to send reminders
0 * * * * /usr/bin/php /path/to/send_reminders.php
```

## Performance

- **Webhook Response**: < 1 second
- **AI Processing**: 2-5 seconds
- **Database**: < 100ms
- **Google Calendar Sync**: 1-2 seconds
- **Total**: ~5-8 seconds end-to-end

## Security

- Webhook verification token
- SSL/HTTPS required by WhatsApp
- Database connection over localhost
- Service account for Google Calendar
- No sensitive data in logs
- Input sanitization

