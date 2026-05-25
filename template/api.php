<?php
header('Content-Type: application/json');
error_reporting(0);

$config = require 'config.php';
$db = $config['db'];
require_once 'db.php';

$isSQLite = ($db['type'] === 'sqlite');

try {
    $pdo = getPDO($db);

    $action = $_GET['action'] ?? 'chart_data';
    $date   = $_GET['date']   ?? date('Y-m-d');

    // 1. DANE DZIENNE
    if ($action === 'chart_data') {
        $stmt = $pdo->prepare("SELECT * FROM weather_data WHERE date(measurement_datetime) = :selectedDate ORDER BY measurement_datetime ASC");
        $stmt->execute(['selectedDate' => $date]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 2. REKORDY ROCZNE
    elseif ($action === 'year_stats') {
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

        if ($isSQLite) {
            $yearExpr = "strftime('%Y', measurement_datetime)";
        } else {
            $yearExpr = "YEAR(measurement_datetime)";
        }

        $sql = "
            SELECT
                MAX(temperature) as max_temp,
                MIN(temperature) as min_temp,
                (SELECT measurement_datetime FROM weather_data WHERE temperature = (SELECT MAX(temperature) FROM weather_data WHERE $yearExpr = :year) AND $yearExpr = :year LIMIT 1) as max_temp_date,
                (SELECT measurement_datetime FROM weather_data WHERE temperature = (SELECT MIN(temperature) FROM weather_data WHERE $yearExpr = :year) AND $yearExpr = :year LIMIT 1) as min_temp_date,
                MAX(pressure) as max_press,
                MIN(pressure) as min_press,
                (SELECT measurement_datetime FROM weather_data WHERE pressure = (SELECT MAX(pressure) FROM weather_data WHERE $yearExpr = :year) AND $yearExpr = :year LIMIT 1) as max_press_date,
                (SELECT measurement_datetime FROM weather_data WHERE pressure = (SELECT MIN(pressure) FROM weather_data WHERE $yearExpr = :year) AND $yearExpr = :year LIMIT 1) as min_press_date
            FROM weather_data
            WHERE $yearExpr = :year
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['year' => (string)$year]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    // 3. TREND ROCZNY
    elseif ($action === 'yearly_trend') {
        if ($isSQLite) {
            $sql = "SELECT date(measurement_datetime) as date, MAX(temperature) as max_temp, MIN(temperature) as min_temp
                    FROM weather_data
                    WHERE measurement_datetime > datetime('now', '-1 year')
                    GROUP BY date(measurement_datetime)
                    ORDER BY date ASC";
        } else {
            $sql = "SELECT DATE(measurement_datetime) as date, MAX(temperature) as max_temp, MIN(temperature) as min_temp
                    FROM weather_data
                    WHERE measurement_datetime > DATE_SUB(NOW(), INTERVAL 1 YEAR)
                    GROUP BY DATE(measurement_datetime)
                    ORDER BY date ASC";
        }
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 4. ŚREDNIE TEMPERATURY
    elseif ($action === 'avg_stats') {
        $range  = $_GET['range'] ?? '30days';
        $sql    = "";
        $params = [];

        if ($isSQLite) {
            $today     = "date('now')";
            $now       = "datetime('now')";
            $yearExpr  = "strftime('%Y', measurement_datetime)";
            $monthExpr = "strftime('%m', measurement_datetime)";
            $ymExpr    = "strftime('%Y-%m', measurement_datetime)";
            $ymdhExpr  = "strftime('%Y-%m-%d %H:00', measurement_datetime)";
            $dateExpr  = "date(measurement_datetime)";
            $curYear   = "strftime('%Y', 'now')";
            $curMonth  = "strftime('%m', 'now')";
            $interval  = function($n, $unit) { return "datetime('now', '-$n $unit')"; };
        } else {
            $today     = "CURDATE()";
            $now       = "NOW()";
            $yearExpr  = "YEAR(measurement_datetime)";
            $monthExpr = "MONTH(measurement_datetime)";
            $ymExpr    = "DATE_FORMAT(measurement_datetime, '%Y-%m')";
            $ymdhExpr  = "DATE_FORMAT(measurement_datetime, '%Y-%m-%d %H:00')";
            $dateExpr  = "DATE(measurement_datetime)";
            $curYear   = "YEAR(CURDATE())";
            $curMonth  = "MONTH(CURDATE())";
            $interval  = function($n, $unit) { return "DATE_SUB(NOW(), INTERVAL $n $unit)"; };
        }

        if ($range === 'today') {
            $sql = "SELECT $ymdhExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE $dateExpr = $today
                    GROUP BY date_label ORDER BY date_label ASC";
        } elseif ($range === '7days') {
            $sql = "SELECT $dateExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE measurement_datetime > {$interval(7, 'days')}
                    GROUP BY date_label ORDER BY date_label ASC";
        } elseif ($range === '30days') {
            $sql = "SELECT $dateExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE measurement_datetime > {$interval(30, 'days')}
                    GROUP BY date_label ORDER BY date_label ASC";
        } elseif ($range === 'month') {
            $sql = "SELECT $dateExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE $yearExpr = $curYear AND $monthExpr = $curMonth
                    GROUP BY date_label ORDER BY date_label ASC";
        } elseif ($range === 'year' || $range === 'current_year') {
            $sql = "SELECT $ymExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE $yearExpr = $curYear
                    GROUP BY date_label ORDER BY date_label ASC";
        } elseif (is_numeric($range)) {
            $year = intval($range);
            $sql = "SELECT $ymExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE $yearExpr = :year
                    GROUP BY date_label ORDER BY date_label ASC";
            $params['year'] = $isSQLite ? (string)$year : $year;
        } else {
            $sql = "SELECT $dateExpr as date_label, AVG(temperature) as avg_temp
                    FROM weather_data WHERE measurement_datetime > {$interval(30, 'days')}
                    GROUP BY date_label ORDER BY date_label ASC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 5. TABELA MIESIĘCZNA
    elseif ($action === 'monthly_stats') {
        if ($isSQLite) {
            $sql = "SELECT strftime('%Y-%m', measurement_datetime) as month_id,
                           MAX(temperature) as max_temp, MIN(temperature) as min_temp
                    FROM weather_data
                    WHERE measurement_datetime > datetime('now', '-14 months')
                    GROUP BY month_id ORDER BY month_id DESC LIMIT 14";
        } else {
            $sql = "SELECT DATE_FORMAT(measurement_datetime, '%Y-%m') as month_id,
                           MAX(temperature) as max_temp, MIN(temperature) as min_temp
                    FROM weather_data
                    WHERE measurement_datetime > DATE_SUB(NOW(), INTERVAL 14 MONTH)
                    GROUP BY month_id ORDER BY month_id DESC LIMIT 14";
        }
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 6. AKTUALNE WARUNKI
    elseif ($action === 'current') {
        $stmt = $pdo->query("SELECT * FROM weather_data ORDER BY measurement_datetime DESC LIMIT 1");
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($currentData) {
            $currentDate    = new DateTime($currentData['measurement_datetime']);
            $pastDateTarget = (clone $currentDate)->modify('-1 year');
            $pastStart      = (clone $pastDateTarget)->modify('-1 hour')->format('Y-m-d H:i:s');
            $pastEnd        = (clone $pastDateTarget)->modify('+1 hour')->format('Y-m-d H:i:s');
            $pastTarget     = $pastDateTarget->format('Y-m-d H:i:s');

            if ($isSQLite) {
                $orderExpr = "ABS(strftime('%s', measurement_datetime) - strftime('%s', :target))";
            } else {
                $orderExpr = "ABS(TIMESTAMPDIFF(SECOND, measurement_datetime, :target))";
            }

            $stmtPast = $pdo->prepare("
                SELECT temperature, measurement_datetime
                FROM weather_data
                WHERE measurement_datetime BETWEEN :start AND :end
                ORDER BY $orderExpr ASC
                LIMIT 1
            ");
            $stmtPast->execute(['start' => $pastStart, 'end' => $pastEnd, 'target' => $pastTarget]);
            $pastData = $stmtPast->fetch(PDO::FETCH_ASSOC);

            if ($pastData) {
                $diff = floatval($currentData['temperature']) - floatval($pastData['temperature']);
                $currentData['historical_comparison'] = [
                    'available' => true,
                    'diff'      => $diff,
                    'past_temp' => floatval($pastData['temperature']),
                    'past_date' => $pastData['measurement_datetime']
                ];
            } else {
                $currentData['historical_comparison'] = ['available' => false];
            }
        }

        echo json_encode($currentData);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
