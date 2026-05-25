<?php
// config.php

function loadEnv($path) {
    if (!file_exists($path)) {
        die('BŁĄD KRYTYCZNY: Nie znaleziono pliku .env w katalogu: ' . dirname($path));
    }

    $content = file_get_contents($path);
    if (empty($content)) {
        die('BŁĄD KRYTYCZNY: Plik .env istnieje, ale jest PUSTY (0 znaków). Edytuj go i wklej dane konfiguracyjne.');
    }

    $lines = preg_split("/\r\n|\n|\r/", $content);
    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key   = trim($parts[0]);
            $value = trim(trim($parts[1]), "\"'");
            $env[$key] = $value;
        }
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/.env');

$dbType = $env['DB_TYPE'] ?? 'mysql';

if ($dbType === 'sqlite') {
    if (empty($env['DB_PATH'])) {
        die('BŁĄD KONFIGURACJI: Brak klucza DB_PATH w pliku .env dla trybu SQLite.');
    }
    return [
        'db' => [
            'type' => 'sqlite',
            'path' => $env['DB_PATH'],
        ],
        'weather_api' => [
            'key'      => $env['WEATHER_API_KEY'] ?? '',
            'location' => $env['WEATHER_LOCATION'] ?? 'Gliwice',
            'url_base' => $env['WEATHER_API_URL'] ?? 'http://api.openweathermap.org/data/2.5/weather'
        ]
    ];
}

// MySQL
if (empty($env['DB_HOST'])) {
    die('BŁĄD KONFIGURACJI: Plik .env został wczytany, ale nie znaleziono w nim klucza DB_HOST. Sprawdź czy wpisy mają format KLUCZ=WARTOŚĆ.');
}

return [
    'db' => [
        'type'    => 'mysql',
        'host'    => $env['DB_HOST'],
        'name'    => $env['DB_NAME']    ?? '',
        'user'    => $env['DB_USER']    ?? '',
        'pass'    => $env['DB_PASS']    ?? '',
        'charset' => $env['DB_CHARSET'] ?? 'utf8mb4'
    ],
    'weather_api' => [
        'key'      => $env['WEATHER_API_KEY'] ?? '',
        'location' => $env['WEATHER_LOCATION'] ?? 'Gliwice',
        'url_base' => $env['WEATHER_API_URL'] ?? 'http://api.openweathermap.org/data/2.5/weather'
    ]
];
?>