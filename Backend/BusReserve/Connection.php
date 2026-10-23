<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
class Connection {
    function connect() {
        require_once __DIR__.'/Constants.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
        $conn->query("SET time_zone = '+02:00'");
        return $conn;
    }
}
