CREATE TABLE IF NOT EXISTS weather_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    location TEXT NOT NULL DEFAULT 'Gliwice',
    measurement_datetime TEXT NOT NULL,
    temperature REAL NOT NULL,
    pressure REAL NOT NULL,
    humidity INTEGER NOT NULL,
    wind_speed REAL NOT NULL,
    wind_direction INTEGER DEFAULT NULL,
    rainfall REAL DEFAULT 0.0,
    snowfall REAL DEFAULT 0.0,
    visibility INTEGER DEFAULT NULL,
    uv_index REAL DEFAULT NULL,
    weather_main TEXT DEFAULT NULL,
    weather_description TEXT DEFAULT NULL,
    weather_icon TEXT DEFAULT NULL,
    cloudiness INTEGER DEFAULT NULL,
    feels_like REAL DEFAULT NULL,
    sea_level_pressure REAL DEFAULT NULL,
    ground_level_pressure REAL DEFAULT NULL,
    data_source TEXT DEFAULT 'OpenWeatherMap',
    raw_json TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (location, measurement_datetime)
);

CREATE INDEX IF NOT EXISTS idx_datetime    ON weather_data (measurement_datetime);
CREATE INDEX IF NOT EXISTS idx_temperature ON weather_data (temperature);
CREATE INDEX IF NOT EXISTS idx_created     ON weather_data (created_at);
