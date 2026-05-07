<?php
/**
 * 站点配置文件
 */
define('SITE_NAME', 'MC Server 更新日志');
define('SITE_DESC', '我的世界服务器更新日志，记录每一次版本更新与变化');
define('SITE_URL', ''); // 留空自动检测
define('ITEMS_PER_PAGE', 10);

// 自动检测站点URL
if (empty(SITE_URL)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    define('BASE_URL', $protocol . $host . $dir);
} else {
    define('BASE_URL', SITE_URL);
}

// 会话配置
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
