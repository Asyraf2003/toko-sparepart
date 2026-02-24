#!/bin/bash

# 1. Load Environment Variables dari .env
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
else
    echo "Error: File .env tidak ditemukan!"
    exit 1
fi

# 2. Ambil Token & Secret (Berdasarkan Audit Data)
TOKEN=$TELEGRAM_OPS_BOT_TOKEN
SECRET=$(php artisan tinker --execute='echo (string) config("services.telegram_ops.webhook_secret");')

# 3. Minta Input URL Ngrok
echo "Masukkan URL Ngrok (contoh: https://abcd-1234.ngrok-free.app):"
read BASE

# 4. Eksekusi Set Webhook
echo "Mengirim request ke Telegram API..."
curl -s "https://api.telegram.org/bot${TOKEN}/setWebhook" \
  -d "url=${BASE}/telegram/webhook" \
  -d "secret_token=${SECRET}" \
  -d "drop_pending_updates=true" \
  | jq . 2>/dev/null || curl -s "https://api.telegram.org/bot${TOKEN}/setWebhook" \
  -d "url=${BASE}/telegram/webhook" \
  -d "secret_token=${SECRET}" \
  -d "drop_pending_updates=true"

echo -e "\nSelesai."