#!/bin/bash

# Setup script for local development

echo "🚀 WhatsApp Salon Booking Bot - Setup Script"
echo "============================================"

# Check PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed"
    exit 1
fi

echo "✅ PHP found: $(php -v | head -n 1)"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "⚠️  Composer not found. Installing..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

echo "✅ Composer found"

# Install dependencies
echo ""
echo "📦 Installing PHP dependencies..."
composer install

# Create config file
echo ""
echo "⚙️  Creating config file..."
if [ ! -f config.php ]; then
    cp config.example.php config.php
    echo "⚠️  Created config.php - Please edit with your credentials"
else
    echo "✅ config.php already exists"
fi

# Create logs directory
mkdir -p logs
chmod 777 logs

# Setup database
echo ""
echo "🗄️  Setting up database..."
php setup_database.php

# Instructions
echo ""
echo "============================================"
echo "✅ Setup Complete!"
echo "============================================"
echo ""
echo "Next steps:"
echo "1. Edit config.php with your credentials"
echo "2. Start Ollama in another terminal: ollama serve"
echo "3. Run: php -S localhost:8000"
echo "4. Visit: http://localhost:8000/admin.php"
echo ""
echo "To test with ngrok:"
echo "1. Install ngrok: https://ngrok.com"
echo "2. Run: ngrok http 8000"
echo "3. Copy ngrok URL to Meta App Dashboard"
echo ""
