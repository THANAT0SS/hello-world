# Troubleshooting Guide

## Common Issues & Solutions

### 1. Webhook Not Verifying

**Error**: "Invalid token" or verification fails

**Solutions**:
```bash
# Check verify token matches
grep VERIFY_TOKEN config.php

# Test webhook manually
curl -X GET "https://yourdomain.com/webhook.php?hub.mode=subscribe&hub.verify_token=your_token&hub.challenge=test123"

# Should return: test123
```

### 2. Bot Not Responding

**Error**: Send message, no reply

**Check in order**:

1. **Ollama running?**
```bash
curl http://localhost:11434/api/tags
```
If connection refused, start Ollama:
```bash
ollama serve
```

2. **Database connected?**
```bash
mysql -u root -p salon_booking -e "SELECT COUNT(*) FROM messages;"
```

3. **Check logs**:
```bash
tail -f logs/bot.log
```

4. **Check Meta App webhook logs**:
   - Dashboard → WhatsApp → Logs
   - Look for delivery status

### 3. "Connection to Ollama Failed"

**Error**: `Connection refused` or timeout

**Solutions**:
```bash
# Verify Ollama is running
ps aux | grep ollama

# If not running, start it
ollama serve &

# Check port 11434
netstat -an | grep 11434

# Check firewall
sudo ufw allow 11434
```

### 4. Database Errors

**Error**: "SQLSTATE[HY000]"

**Solutions**:
```bash
# Restart MySQL
sudo systemctl restart mysql

# Check connection
mysql -u root -p
# Use password from config.php

# Verify database exists
SHOW DATABASES;

# If missing, run setup
php setup_database.php
```

### 5. Google Calendar Sync Not Working

**Error**: Bookings not appearing on calendar

**Solutions**:
```bash
# Check service account file exists
ls -la google-service-account-key.json

# Verify path in config.php
grep GOOGLE_SERVICE_ACCOUNT_PATH config.php

# Check calendar ID
grep GOOGLE_CALENDAR_ID config.php

# Verify calendar is shared with service account email
# In Google Calendar settings → Share calendar
# Add service account email (from JSON file)
```

### 6. Reminders Not Sending

**Error**: No reminders 24 hours before appointment

**Solutions**:
```bash
# Check cron job is running
sudo crontab -l
# Should show: 0 * * * * /usr/bin/php /path/to/send_reminders.php

# Test cron manually
php send_reminders.php

# Check reminders table
mysql -u root -p salon_booking -e "SELECT * FROM reminders WHERE sent = FALSE;"

# Check logs
tail -f /tmp/reminders.log
```

### 7. "Timeout waiting for response"

**Error**: Message processing takes too long

**Solutions**:
```bash
# Use faster Ollama model
ollama pull neural-chat-7b-v3

# Update config.php
define('OLLAMA_MODEL', 'neural-chat-7b-v3');

# Check server resources
free -h  # RAM usage
df -h    # Disk usage

# Monitor Ollama
ps aux | grep ollama
```

### 8. "No bookings appearing in admin panel"

**Error**: Visit admin.php but no data shown

**Solutions**:
```bash
# Check database has data
mysql -u root -p salon_booking -e "SELECT * FROM bookings;"

# Check PHP errors
php -l admin.php

# Check database path in config
grep DB_ config.php

# Verify admin.php has permissions
ls -la admin.php
```

### 9. WhatsApp API Rate Limited

**Error**: "Rate limit exceeded"

**Solutions**:
```bash
# Check your plan
# Free tier: 100 messages/day
# Paid: $0.002 per message

# Optimize to send fewer messages:
# - Combine confirmation + reminder
# - Remove debug messages
# - Use shorter responses
```

### 10. AI Responses are Slow

**Error**: Takes 10+ seconds for AI response

**Solutions**:
```bash
# Use smaller model
ollama pull neural-chat-7b-v3-q4_K_M

# Or even smaller
ollama pull orca-mini

# Check system resources
top  # Monitor CPU/RAM

# Reduce timeout in config if acceptable
define('OLLAMA_TIMEOUT', 15); # seconds
```

## Debug Mode

### Enable Detailed Logging

```php
// In config.php
define('DEBUG_MODE', true);
```

Then check logs:
```bash
tail -f logs/bot.log
```

### Test Specific Functions

```bash
php -a

# Test language detection
$text = "Je veux réserver lundi";
echo detectLanguage($text); // Should print: fr

# Test date parsing
$date = parseDate("tomorrow at 2pm");
echo $date; // Should print: Y-m-d format

# Test time parsing
$time = parseTime("3:30pm");
echo $time; // Should print: 15:30
```

## Performance Optimization

### Reduce AI Processing Time

```bash
# Faster model
ollama pull neural-chat-7b-v3-q4_K_M  # Quantized, 4GB

# Or ultra-fast
ollama pull orca-mini  # 2.7GB
```

### Cache AI Responses

Add to `config.php`:
```php
$cache_file = 'cache/' . md5($userMessage) . '.json';
if (file_exists($cache_file)) {
    $cached = json_decode(file_get_contents($cache_file), true);
    if (time() - $cached['time'] < 86400) { // 24 hours
        $aiResponse = $cached['response'];
        goto send_message;
    }
}
```

## Monitoring

### Set Up Alerts

```bash
# Check for errors in real-time
watch -n 5 'tail -n 5 logs/bot.log'

# Count messages per hour
grep $(date +%Y-%m-%d\ %H:) logs/bot.log | wc -l
```

### Database Backups

```bash
# Daily backup
mysqldump -u root -p salon_booking > backup_$(date +%Y%m%d).sql

# Restore
mysql -u root -p salon_booking < backup_20240101.sql
```

## Emergency Procedures

### Service Down - Quick Recovery

```bash
# 1. Check Ollama
ps aux | grep ollama || ollama serve &

# 2. Check MySQL
sudo systemctl restart mysql

# 3. Check webhook
curl https://yourdomain.com/webhook.php

# 4. View recent errors
tail -n 50 logs/bot.log | grep ERROR
```

### Rollback to Previous Version

```bash
# If latest version broke something
git log --oneline  # See history
git checkout <commit-hash>  # Go back to working version

# Restart services
php -S localhost:8000
```

## Getting Help

1. **Check logs first**:
   ```bash
   tail -f logs/bot.log
   ```

2. **Test each component**:
   - Ollama: `curl http://localhost:11434/api/tags`
   - MySQL: `mysql -u root -p`
   - WhatsApp: Send test message

3. **Check GitHub Issues**: https://github.com/THANAT0SS/whatsapp-salon-booking/issues

4. **Enable DEBUG_MODE** for detailed logs

5. **Save and share**:
   - Last 50 lines of logs
   - Error message from dashboard
   - What you were trying to do

