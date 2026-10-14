<?php

namespace App\Services;

use App\Models\Orders;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends the "your order is complete" message to a customer.
 *
 * Drivers (chosen in Settings → Notifications):
 *   - off      : disabled
 *   - log      : write to storage/logs/notify.log (safe default for testing)
 *   - whatsapp : WhatsApp Cloud API (graph.facebook.com) — needs token + phone id
 *   - sms      : generic HTTP SMS gateway (url + key + sender)
 *
 * A zero-config wa.me deep link is always available via whatsappLink().
 */
class OrderNotifier
{
    public function sendCompletion(Orders $order): array
    {
        $order->loadMissing('customer');
        $message = $this->message($order);
        $driver = setting('notify_driver', 'log');

        if (! $this->enabled()) {
            return ['sent' => false, 'driver' => $driver, 'message' => $message, 'reason' => 'Notifications are turned off.'];
        }

        $phone = $this->digits($order->customer->phone ?? '');

        if ($phone === '') {
            return ['sent' => false, 'driver' => $driver, 'message' => $message, 'reason' => 'Customer has no phone number.'];
        }

        try {
            return match ($driver) {
                'whatsapp' => $this->sendWhatsApp($phone, $message),
                'sms' => $this->sendSms($phone, $message),
                default => $this->sendLog($phone, $message, $order),
            };
        } catch (\Throwable $e) {
            Log::error('Order notification failed', ['order' => $order->order_no, 'error' => $e->getMessage()]);

            return ['sent' => false, 'driver' => $driver, 'message' => $message, 'reason' => $e->getMessage()];
        }
    }

    public function enabled(): bool
    {
        return (bool) setting('notify_enabled', false) && setting('notify_driver', 'off') !== 'off';
    }

    public function message(Orders $order): string
    {
        $template = (string) setting(
            'notify_message',
            'Hello {customer}, your order {order_no} is complete. Total {amount}, balance {balance}. Please collect it. Thank you - {shop}'
        );

        return str_replace(
            ['{customer}', '{order_no}', '{amount}', '{balance}', '{shop}'],
            [
                $order->customer->fullname ?? 'customer',
                $order->order_no,
                $this->money($order->amount_charged),
                $this->money($order->balance),
                setting('app_name', config('app.name')),
            ],
            $template
        );
    }

    /**
     * Deep link that opens WhatsApp with the message pre-filled. Works with no API key.
     */
    public function whatsappLink(Orders $order): string
    {
        $order->loadMissing('customer');
        $phone = $this->digits($order->customer->phone ?? '');

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($this->message($order));
    }

    protected function sendLog(string $phone, string $message, Orders $order): array
    {
        Log::channel('notify')->info('Outbound order message', [
            'to' => $phone,
            'order' => $order->order_no,
            'message' => $message,
        ]);

        return ['sent' => true, 'driver' => 'log', 'message' => $message, 'reason' => null];
    }

    protected function sendWhatsApp(string $phone, string $message): array
    {
        $token = setting('notify_whatsapp_token');
        $phoneId = setting('notify_whatsapp_phone_id');

        if (! $token || ! $phoneId) {
            return ['sent' => false, 'driver' => 'whatsapp', 'message' => $message, 'reason' => 'WhatsApp token / phone id not configured.'];
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->failed()) {
            return ['sent' => false, 'driver' => 'whatsapp', 'message' => $message, 'reason' => $response->body()];
        }

        return ['sent' => true, 'driver' => 'whatsapp', 'message' => $message, 'reason' => null];
    }

    protected function sendSms(string $phone, string $message): array
    {
        $url = setting('notify_gateway_url');

        if (! $url) {
            return ['sent' => false, 'driver' => 'sms', 'message' => $message, 'reason' => 'SMS gateway URL not configured.'];
        }

        $response = Http::get($url, [
            'to' => $phone,
            'message' => $message,
            'sender' => setting('notify_from', setting('app_name', config('app.name'))),
            'apikey' => setting('notify_gateway_key'),
        ]);

        if ($response->failed()) {
            return ['sent' => false, 'driver' => 'sms', 'message' => $message, 'reason' => $response->body()];
        }

        return ['sent' => true, 'driver' => 'sms', 'message' => $message, 'reason' => null];
    }

    protected function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    protected function money($amount): string
    {
        return setting('app_currency', '$') . number_format((float) $amount, 2);
    }
}
