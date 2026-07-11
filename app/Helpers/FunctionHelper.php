<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('formatPhoneNumber')) {
    function formatPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[\s()-]/', '', $phoneNumber);

        if (preg_match('/^0[17]\d{8}$/', $phoneNumber)) {
            return '254'.substr($phoneNumber, 1);
        }

        if (str_starts_with($phoneNumber, '+254')) {
            return substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }
}

if (! function_exists('obfuscatePhone')) {
    function obfuscatePhone(string $phone): string
    {
        return substr(
            $phone,
            0,
            3
        ).str_repeat('*', strlen($phone) - 5).substr($phone, -2);
    }
}

if (! function_exists('formatAmount')) {
    function formatAmount($amount): string
    {
        if (! is_numeric($amount)) {
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

if (! function_exists('generate_reference')) {
    function generate_reference(): string
    {
        return strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }
}

if (! function_exists('storeLog')) {
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

// app/Helpers/ServiceHelper.php
if (! function_exists('getEscortServices')) {
    function getEscortServices(): array
    {
        return [
            'OWO (Oral without condom)',
            'O-Level (Oral sex)',
            'CIM (Come in mouth)',
            'COF (Come on face)',
            'COB (Come on body)',
            'Swallow',
            'DFK (Deep French Kissing)',
            'A-Level (Anal sex)',
            'Anal Rimming (Analingus)',
            '69 (69 sex position) - Reverse oral & face sitting',
            'Striptease - lap dance & spanking',
            'Erotic massage (incl. Nuru)',
            'Golden shower (Watersports)',
            'Couples (Group sex)',
            'GFE (Girlfriend experience)',
            'Threesome',
            'Fetishism',
            'Sex toys & Dildo vibrators',
            'Extraball (Having sex multiple times)',
            'BDSM (Domination & submission)',
            'LT (Long Time; Usually overnight)',
            'Tantric body-to-body massage',
            'Hot stone massage',
            'Thai yoga massage',
            'Tie and tease',
            'Teabagging',
            'Webcam sex (video sex & phone sex)',
            'Fisting (Handballing)',
            'Deepthroat',
            'Fingering',
            'Uniforms & costumes',
            'Hardsports (scat)',
            'Romantic date & Party',
            'Travel companion',
            'Strap-on pegging',
            'Raw sex (bareback)',
            'Squirting',
        ];
    }
}
