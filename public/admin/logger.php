<?php
if (!function_exists('log_action')) {
    function log_action($action, $summary = '') {
        $log_dir = __DIR__;
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $user_type = isset($_SESSION['role']) ? $_SESSION['role'] : (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 'admin' : 'unknown');
        $entry = "[$timestamp] [$user_type] $action: $summary\n";
        @file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
    }
}
