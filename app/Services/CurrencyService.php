<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    public static function getRates($baseCurrency = 'PKR')
    {
        // 12 Ghante ke liye rates cache karenge taake bar bar third-party API hit na ho
        return Cache::remember("exchange_rates_{$baseCurrency}", 43200, function () use ($baseCurrency) {
            try {
                $response = Http::get("https://open.er-api.com/v6/latest/{$baseCurrency}");
                if ($response->successful()) {
                    return $response->json()['rates'];
                }
            } catch (\Exception $e) {
                Log::error("Currency API Error: " . $e->getMessage());
            }

            // Fallback rates agar internet/API down ho
            return [
                'PKR' => 1,
                'USD' => 0.0036,
                'EUR' => 0.0033,
                'AED' => 0.0132
            ];
        });
    }

    public static function convert($amount, $from, $to)
    {
        if (!$from) $from = 'PKR';
        if ($from === $to) return $amount;

        $rates = self::getRates($from);

        if (isset($rates[$to])) {
            return $amount * $rates[$to];
        }

        return $amount;
    }
}
