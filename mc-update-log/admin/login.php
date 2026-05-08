<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND status = 1");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    } else {
        $error = '请输入用户名和密码';
    }
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - <?= h(getSetting('site_name', 'MC 服务器更新日志')) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans SC", sans-serif; background: #0a0a0a; color: #e0e0e0; }
        .login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0a0a0a; padding: 20px; }
        .login-box { width: 380px; padding: 40px; background: #1a1a1a; border-radius: 16px; border: 1px solid #333; }
        .login-title { text-align: center; color: #fff; font-size: 24px; margin-bottom: 8px; }
        .login-sub { text-align: center; color: #888; margin-bottom: 30px; font-size: 14px; }
        .login-error { background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.3); color: #f44336; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 8px; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px 15px; background: #252525; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 15px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #4CAF50; }
        .login-btn { width: 100%; padding: 12px; background: #4CAF50; color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.2s; }
        .login-btn:hover { background: #45a049; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #4CAF50; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <h1 class="login-title">⛏ 后台管理</h1>
        <p class="login-sub"><?= h(getSetting('site_name', 'MC 服务器更新日志')) ?></p>
        
        <?php if ($error): ?>
        <div class="login-error"><?= h($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required placeholder="请输入密码">
            </div>
            <button type="submit" class="login-btn">登 录</button>
        </form>
        <a href="../index.php" class="back-link">← 返回首页</a>
    </div>
</div>
</body>
</html>
