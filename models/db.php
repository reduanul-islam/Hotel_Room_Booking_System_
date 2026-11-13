<?php
class db {
    public function connection() {
        $config = require __DIR__ . "/../config/config.php";
        $c = $config['db'];

        mysqli_report(MYSQLI_REPORT_OFF);
        $connection = @new mysqli($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);

        if ($connection->connect_error) {
            if (!empty($config['debug'])) {
                die("Database connection failed: " . $connection->connect_error);
            }
            die("Database connection failed. Please try again later.");
        }

        $connection->set_charset("utf8mb4");
        return $connection;
    }
}
