<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPwd = $_POST['old_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    if (empty($oldPwd) || empty($newPwd) || empty($confirmPwd)) {
        $error = '请填写所有密码字段';
    } elseif ($newPwd !== $confirmPwd) {
        $error = '两次输入的新密码不一致';
    } elseif (strlen($newPwd) < 6) {
        $error = '新密码至少需要6个字符';
    } elseif (!password_verify($oldPwd, $user['password'])) {
        $error = '当前密码不正确';
    } else {
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = :pwd, updated_at = datetime('now','localtime') WHERE id = :id");
        $stmt->bindValue(':pwd', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
        $stmt->execute();
        $success = '密码修改成功！';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 240px; background: #111; padding: 20px 0; border-right: 1px solid #222; flex-shrink: 0; }
        .admin-sidebar .sidebar-title { padding: 0 20px; font-size: 18px; color: #F48FB1; margin-bottom: 20px; }
        .admin-sidebar .sidebar-user { padding: 0 20px 20px; border-bottom: 1px solid #222; margin-bottom: 20px; color: #aaa; font-size: 14px; }
        .admin-nav { list-style: none; padding: 0; margin: 0; }
        .admin-nav li a { display: block; padding: 12px 20px; color: #ccc; text-decoration: none; transition: all 0.2s; font-size: 15px; }
        .admin-nav li a:hover, .admin-nav li a.active { background: rgba(244,143,177,0.1); color: #F48FB1; border-left: 3px solid #F48FB1; }
        .admin-nav li a .nav-icon { margin-right: 10px; }
        .admin-main { flex: 1; padding: 30px; background: #0a0a0a; }
        .admin-header { margin-bottom: 30px; }
        .admin-header h2 { color: #fff; font-size: 24px; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 30px; max-width: 480px; }
        .card h3 { color: #fff; font-size: 18px; margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 6px; font-size: 13px; font-weight: 500; }
        .form-group input { width: 100%; padding: 10px 14px; background: #252525; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #F48FB1; }
        .btn { padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; border: none; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #F48FB1; }
        .btn-primary:hover { background: #e91e63; }
        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .message-success { background: rgba(244,143,177,0.1); border: 1px solid rgba(244,143,177,0.3); color: #F48FB1; }
        .message-error { background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.3); color: #f44336; }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">⛏ 后台管理</div>
        <div class="sidebar-user">👤 <?= h($user['nickname']) ?> <span style="color:#F48FB1;">(<?= $user['role'] === 'admin' ? '管理员' : '编辑' ?>)</span></div>
        <ul class="admin-nav">
            <li><a href="index.php"><span class="nav-icon">📊</span>仪表盘</a></li>
            <li><a href="quick_settings.php" class="<?= basename($_SERVER['SCRIPT_NAME']) == 'quick_settings.php' ? 'active' : '' ?>"><span class="nav-icon">⚡</span>常用设置</a></li>
            <li><a href="logs.php"><span class="nav-icon">📝</span>日志管理</a></li>
            <li><a href="add_log.php"><span class="nav-icon">➕</span>写日志</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="users.php"><span class="nav-icon">👥</span>用户管理</a></li>
            <li><a href="settings.php"><span class="nav-icon">⚙️</span>高级设置</a></li>
            <?php endif; ?>
            <li><a href="change_password.php" class="active"><span class="nav-icon">🔑</span>修改密码</a></li>
            <li><a href="../index.php"><span class="nav-icon">🏠</span>返回首页</a></li>
            <li><a href="logout.php"><span class="nav-icon">🚪</span>退出登录</a></li>
        </ul>
    </aside>
    <main class="admin-main">
        <div class="admin-header">
            <h2>🔑 修改密码</h2>
        </div>
        <?php if ($success): ?><div class="message message-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message message-error"><?= h($error) ?></div><?php endif; ?>
        <div class="card">
            <h3>用户：<?= h($user['username']) ?></h3>
            <form method="POST">
                <div class="form-group">
                    <label>当前密码</label>
                    <input type="password" name="old_password" required>
                </div>
                <div class="form-group">
                    <label>新密码</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>确认新密码</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">💾 保存新密码</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
