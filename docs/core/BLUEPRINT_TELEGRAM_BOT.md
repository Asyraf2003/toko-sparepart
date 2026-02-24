# Technical Blueprint: Implementation Details

## 1. Database Schema (State Management)

~~~sql
CREATE TABLE purchase_due_notification_states (
    id BIGINT PRIMARY KEY,
    invoice_id BIGINT NOT NULL,
    notified_at DATE NOT NULL,
    correlation_id VARCHAR(100),
    UNIQUE(invoice_id, notified_at)
);

CREATE TABLE daily_profit_notification_states (
    id BIGINT PRIMARY KEY,
    business_date DATE NOT NULL UNIQUE,
    sent_at TIMESTAMP NOT NULL
);
~~~

## 2. Domain Policy: DueDatePolicy
Logic penentuan tanggal jatuh tempo tanpa asumsi:

~~~php
public function calculateDueDate(DateTime $deliveryDate): DateTime 
{
    $target = clone $deliveryDate;
    $target->modify('+1 month');
    
    // Check if day overflowed (e.g. Jan 31 -> Mar 3)
    if ($target->format('d') != $deliveryDate->format('d')) {
        $target->modify('last day of last month');
    }
    return $target;
}
~~~

## 3. Infrastructure Config (services.php)
Konfigurasi wajib untuk integrasi Telegram:

~~~php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'allowlist' => explode(',', env('TELEGRAM_ALLOWLIST_IDS')), // chat_id atau user_id
],
~~~

## 4. Audit Log Payload Examples

**Event: TELEGRAM_COMMAND**
~~~json
{
    "user_id": "12345678",
    "chat_id": "87654321",
    "command": "/profit 2026-02-24",
    "status": "SUCCESS"
}
~~~

**Event: NOTIFICATION_SENT**
~~~json
{
    "type": "PURCHASE_DUE_REMINDER",
    "invoice_count": 5,
    "recipient": "Group_Management_Chat"
}
~~~