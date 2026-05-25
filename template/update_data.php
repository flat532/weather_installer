<?php
chdir(__DIR__);

// Wczytanie konfiguracji
$config = require 'config.php';
$apiConfig = $config['weather_api'];
$db = $config['db'];

$apiKey = $apiConfig['key'];
$location = $apiConfig['location'];

// Format daty
$date = date('Y-m-d-H');
$datetime = date('Y-m-d H:00:00');

// Budowanie URL z konfiguracji
$url = "{$apiConfig['url_base']}?q={$location}&appid={$apiKey}&units=metric";
$data = file_get_contents($url);

if (!empty($data) && $data !== 'null') {
    // Upewnij się, że katalog istnieje
    if (!is_dir('archive')) { mkdir('archive', 0755, true); }
    
    $filename = "archive/{$location}-{$date}.json";
    file_put_contents($filename, $data);
    echo "✅ Saved data to file: {$filename}<br>";

    $decoded = json_decode($data, true);

    if ($decoded && isset($decoded['main']['temp'])) {
        try {
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $sql = "INSERT INTO weather_data (
                        location, measurement_datetime, temperature, pressure, humidity,
                        wind_speed, wind_direction, rainfall, snowfall, visibility,
                        weather_main, weather_description, weather_icon, cloudiness,
                        feels_like, sea_level_pressure, ground_level_pressure, raw_json
                    ) VALUES (
                        :location, :measurement_datetime, :temperature, :pressure, :humidity,
                        :wind_speed, :wind_direction, :rainfall, :snowfall, :visibility,
                        :weather_main, :weather_description, :weather_icon, :cloudiness,
                        :feels_like, :sea_level_pressure, :ground_level_pressure, :raw_json
                    ) ON DUPLICATE KEY UPDATE
                        temperature = VALUES(temperature),
                        pressure = VALUES(pressure),
                        humidity = VALUES(humidity),
                        wind_speed = VALUES(wind_speed),
                        wind_direction = VALUES(wind_direction),
                        rainfall = VALUES(rainfall),
                        snowfall = VALUES(snowfall),
                        visibility = VALUES(visibility),
                        weather_main = VALUES(weather_main),
                        weather_description = VALUES(weather_description),
                        weather_icon = VALUES(weather_icon),
                        cloudiness = VALUES(cloudiness),
                        feels_like = VALUES(feels_like),
                        sea_level_pressure = VALUES(sea_level_pressure),
                        ground_level_pressure = VALUES(ground_level_pressure),
                        raw_json = VALUES(raw_json),
                        updated_at = CURRENT_TIMESTAMP";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'location' => $location,
                'measurement_datetime' => $datetime,
                'temperature' => $decoded['main']['temp'] ?? null,
                'pressure' => $decoded['main']['pressure'] ?? null,
                'humidity' => $decoded['main']['humidity'] ?? null,
                'wind_speed' => $decoded['wind']['speed'] ?? 0,
                'wind_direction' => $decoded['wind']['deg'] ?? null,
                'rainfall' => $decoded['rain']['1h'] ?? 0,
                'snowfall' => $decoded['snow']['1h'] ?? 0,
                'visibility' => $decoded['visibility'] ?? null,
                'weather_main' => $decoded['weather'][0]['main'] ?? null,
                'weather_description' => $decoded['weather'][0]['description'] ?? null,
                'weather_icon' => $decoded['weather'][0]['icon'] ?? null,
                'cloudiness' => $decoded['clouds']['all'] ?? null,
                'feels_like' => $decoded['main']['feels_like'] ?? null,
                'sea_level_pressure' => $decoded['main']['sea_level'] ?? null,
                'ground_level_pressure' => $decoded['main']['grnd_level'] ?? null,
                'raw_json' => json_encode($decoded)
            ]);

            echo "✅ Data inserted/updated in DB for {$datetime}<br>";

        } catch (PDOException $e) {
            echo "❌ DB Error: " . $e->getMessage() . "<br>";
        }

    } else {
        echo "❌ Invalid JSON format<br>";
    }

} else {
    echo "❌ No data for {$location} on {$date}<br>";
}
?>