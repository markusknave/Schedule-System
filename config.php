<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "sched_load_system";

$conn = new mysqli($host, $username, $password, $database);

define('IMAGE_BASE', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
define('UPLOAD_REL_PATH', '/myschedule/uploads/images/');
define('IMAGE_DIR', $_SERVER['DOCUMENT_ROOT'] . UPLOAD_REL_PATH);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function imageExists($filename) {
    if (empty($filename)) return false;
    $filePath = IMAGE_DIR . basename($filename);
    return file_exists($filePath);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function getOfficeId() {
    return isAdmin() ? null : (isset($_SESSION['office_id']) ? $_SESSION['office_id'] : null);
}
?>
