<?php
// db.php — zwraca PDO na podstawie konfiguracji (MySQL lub SQLite)

function getPDO(array $db): PDO {
    if ($db['type'] === 'sqlite') {
        $pdo = new PDO('sqlite:' . $db['path'], null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        return $pdo;
    }

    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
?>
