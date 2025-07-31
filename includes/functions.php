<?php
// Utility functions for Interior Project Management System

if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize input data
     */
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('generateRandomPassword')) {
    /**
     * Generate secure random password
     */
    function generateRandomPassword($length = 12) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
        $password = '';
        $maxIndex = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $maxIndex)];
        }
        return $password;
    }
}

if (!function_exists('formatBytes')) {
    /**
     * Format file size in human-readable format
     */
    function formatBytes($size, $precision = 2) {
        if ($size <= 0) return '0 B';
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Time ago function
     */
    function timeAgo($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

if (!function_exists('generateSlug')) {
    /**
     * Generate unique slug
     */
    function generateSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}

// ... Keep adding other functions similarly protected by function_exists() check

// For example: getStatusBadge, getUserAvatar, etc.

if (!function_exists('getStatusBadge')) {
    /**
     * Returns status badge html
     */
    function getStatusBadge($status, $type = 'project') {
        $badges = [
            'project' => [
                'planning' => 'secondary',
                'active' => 'primary',
                'in_progress' => 'warning',
                'completed' => 'success',
                'on_hold' => 'info',
                'cancelled' => 'danger',
            ],
            'task' => [
                'pending' => 'secondary',
                'in_progress' => 'warning',
                'completed' => 'success',
                'cancelled' => 'danger',
            ],
            'user' => [
                'active' => 'success',
                'inactive' => 'secondary',
                'suspended' => 'danger',
            ],
        ];

        $class = $badges[$type][$status] ?? 'secondary';

        return '<span class="badge bg-' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }
}



// ... and so on for your other functions ...


?>