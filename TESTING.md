# Test WhatsApp Messages

These are example messages you can send to test the bot:

## Booking Tests

**Test 1: Simple booking**
```
I want to book a haircut tomorrow at 2pm
```

Expected response:
```
✅ Booking Confirmed!
📝 Name: Guest
📅 Date: [tomorrow's date]
⏰ Time: 2:00 PM
💇 Service: Haircut
💶 Price: €25

🔔 We'll send you a reminder 24 hours before.
Thanks for choosing us!
```

**Test 2: With name**
```
My name is John, I'd like to book a massage on Monday at 3pm
```

Expected response:
```
✅ Booking Confirmed!
📝 Name: John
📅 Date: [next Monday]
⏰ Time: 3:00 PM
💇 Service: Massage
💶 Price: €40
...
```

**Test 3: Check availability**
```
What times do you have available?
```

Expected response:
```
Available times:
10:00 AM, 11:00 AM, 2:00 PM, 3:00 PM, 4:00 PM, 5:00 PM
```

**Test 4: Ask about service**
```
How much does a haircut cost?
```

Expected response:
```
Haircut: €25 (60 minutes)
```

**Test 5: Multi-language (French)**
```
Je veux réserver une coloration lundi à 15h
```

Expected response:
```
✅ Réservation confirmée!
📝 Nom: Guest
📅 Date: [next Monday]
...
```

## Troubleshooting

If bot doesn't respond:

1. **Check logs**:
   ```bash
   tail -f logs/bot.log
   ```

2. **Check Ollama is running**:
   ```bash
   curl http://localhost:11434/api/tags
   ```

3. **Check webhook URL**:
   - Verify ngrok URL in Meta App Dashboard
   - Check webhook is verified

4. **Check database**:
   ```bash
   php -a
   # Then in PHP:
   $db = new mysqli('localhost', 'root', '', 'salon_booking');
   $result = $db->query('SELECT * FROM messages');
   ```

## Database Query Examples

**View all messages:**
```sql
SELECT * FROM messages ORDER BY created_at DESC LIMIT 20;
```

**View all bookings:**
```sql
SELECT * FROM bookings ORDER BY date ASC;
```

**View upcoming bookings:**
```sql
SELECT * FROM bookings WHERE date >= CURDATE();
```

**View reminders:**
```sql
SELECT * FROM reminders WHERE sent = FALSE;
```
