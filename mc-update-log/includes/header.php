<?php
require_once __DIR__ . '/functions.php';

// 主题设置 - 默认值统一为淡粉浅色
$siteName = getSetting('site_name', 'MC 服务器更新日志');
$siteIcon = getSetting('site_icon', '');
$siteDesc = getSetting('site_desc', '我的世界服务器更新日志网站');
$themePrimary = getSetting('theme_primary', '#F48FB1');
$themeSecondary = getSetting('theme_secondary', '#F8BBD0');
$themeBg = getSetting('theme_bg', '#f0f2f5');
$themeCard = getSetting('theme_card', 'rgba(255,255,255,0.6)');
$blurAmount = getSetting('blur_amount', '16');
$bgImage = getSetting('bg_image', '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= h($siteDesc) ?>">
    <title><?= h(isset($pageTitle) ? $pageTitle . ' - ' . $siteName : $siteName) ?></title>
    <?php if ($siteIcon): ?>
    <link rel="icon" type="image/x-icon" href="../<?= h($siteIcon) ?>">
    <link rel="shortcut icon" type="image/x-icon" href="../<?= h($siteIcon) ?>">
    <?php else: ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⛏</text></svg>">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        :root {
            --primary: <?= h($themePrimary) ?>;
            --secondary: <?= h($themeSecondary) ?>;
            --bg-color: <?= h($themeBg) ?>;
            --card-bg: <?= h($themeCard) ?>;
            --blur-amount: <?= h($blurAmount) ?>px;
        }
    </style>
</head>
<body<?= $bgImage ? ' style="background-image:url(\''.h($bgImage).'\')"' : '' ?>>
<div class="layout">
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="index.php" class="logo">
                    <span class="logo-icon">⛏</span>
                    <span class="logo-text"><?= h($siteName) ?></span>
                </a>
                <nav class="nav">
                    <a href="index.php" class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) == 'index.php' ? 'active' : '' ?>">首页</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="admin/index.php" class="nav-link">后台管理</a>
                        <a href="admin/logout.php" class="nav-link">退出</a>
                    <?php else: ?>
                        <a href="admin/login.php" class="nav-link nav-login">登录后台</a>
                    <?php endif; ?>
                </nav>
                <button class="menu-toggle" onclick="toggleMenu()">☰</button>
            </div>
        </div>
    </header>
    <main class="main">
