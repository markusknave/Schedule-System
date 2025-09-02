<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "sched_load_system";

$conn = new mysqli($host, $username, $password, $database);

$projectRoot = str_replace('\\', '/', realpath(__DIR__));

$relativePath = str_replace(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '', $projectRoot);

define('IMAGE_BASE', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
    . "://{$_SERVER['HTTP_HOST']}{$relativePath}");
define('UPLOAD_REL_PATH','/uploads/images/');
define('IMAGE_DIR', __DIR__ . UPLOAD_REL_PATH);

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
