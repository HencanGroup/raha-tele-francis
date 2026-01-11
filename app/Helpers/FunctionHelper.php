<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[\s()-]/', '', $phoneNumber);

        if (preg_match('/^0[17]\d{8}$/', $phoneNumber)) {
            return '254' . substr($phoneNumber, 1);
        }

        if (str_starts_with($phoneNumber, '+254')) {
            return substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }
}

if (!function_exists('obfuscatePhone')) {
    function obfuscatePhone(string $phone): string
    {
        return substr(
            $phone,
            0,
            3
        ) . str_repeat('*', strlen($phone) - 5) . substr($phone, -2);
    }
}

if (!function_exists('formatAmount')) {
    function formatAmount($amount): string
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Invalid amount provided.');
        }

        return number_format(
            (float) $amount,
            2,
            '.',
            ''
        );
    }
}

if (!function_exists('generate_reference')) {
    function generate_reference(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}

if (!function_exists('storeLog')) {
    function storeLog(string $type, $data): void
    {
        $filePath = "logs/{$type}.json";
        $disk = Storage::disk('public');
        $existingData = [];

        if ($disk->exists($filePath)) {
            $content = $disk->get($filePath);
            $existingData = json_decode($content, true) ?: [];
        }

        $existingData[] = $data;

        $disk->put($filePath, json_encode(
            $existingData,
            JSON_PRETTY_PRINT
        ));
    }
}