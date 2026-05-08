<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();
$success = '';
$error = '';

// 头像上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'avatar' && isset($_FILES['avatar'])) {
        $path = uploadImage($_FILES['avatar'], 'avatars');
        if ($path) {
            $old = $user['avatar'];
            if ($old) deleteImage($old);
            $stmt = $db->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
            $stmt->bindValue(':avatar', $path, SQLITE3_TEXT);
            $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            $user['avatar'] = $path;
            $success = '头像更新成功！';
        } else {
            $error = '上传失败，仅支持 jpg/png/gif/webp 格式，最大 2MB';
        }
    }
    
    if ($_POST['action'] === 'profile') {
        $nickname = trim($_POST['nickname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        
        if (empty($nickname)) {
            $error = '昵称不能为空';
        } elseif (empty($username)) {
            $error = '用户名不能为空';
        } else {
            // 检查用户名是否已被其他用户使用
            $check = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $check->bindValue(':username', $username, SQLITE3_TEXT);
            $check->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $exists = $check->execute()->fetchArray();
            if ($exists) {
                $error = '用户名已被占用';
            } else {
                $stmt = $db->prepare("UPDATE users SET nickname = :nickname, username = :username WHERE id = :id");
                $stmt->bindValue(':nickname', $nickname, SQLITE3_TEXT);
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $stmt->execute();
                $user['nickname'] = $nickname;
                $user['username'] = $username;
                $success = '个人资料更新成功！';
            }
        }
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料 - 后台管理</title>
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
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 25px; }
        .card h3 { color: #fff; font-size: 18px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #222; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 8px; font-size: 14px; }
        .form-group input { width: 100%; padding: 10px 14px; background: #252525; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 15px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #F48FB1; }
        .btn { padding: 10px 24px; border-radius: 6px; font-size: 14px; cursor: pointer; border: none; color: #fff; }
        .btn-primary { background: #F48FB1; }
        .btn-primary:hover { background: #e91e63; }
        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .message-success { background: rgba(244,143,177,0.1); border: 1px solid rgba(244,143,177,0.3); color: #F48FB1; }
        .message-error { background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.3); color: #f44336; }

        /* 头像上传区 */
        .avatar-section { text-align: center; margin-bottom: 20px; }
        .avatar-section .avatar-preview { width: 100px; height: 100px; border-radius: 50%; margin: 0 auto 12px; overflow: hidden; }
        .avatar-section .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-section .avatar-upload-label { display: inline-block; padding: 8px 20px; background: #333; color: #ccc; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .avatar-section .avatar-upload-label:hover { background: #444; color: #fff; }
        .avatar-section .avatar-upload-label input { display: none; }
        .avatar-section .hint { color: #666; font-size: 12px; margin-top: 8px; }
        .full-width { grid-column: 1 / -1; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">⛏ 后台管理</div>
        <div class="sidebar-user">👤 <?= h($user['nickname']) ?> <span style="color:#F48FB1;">(<?= $user['role'] === 'admin' ? '管理员' : '编辑' ?>)</span></div>
        <ul class="admin-nav">
            <li><a href="index.php"><span class="nav-icon">📊</span>仪表盘</a></li>
            <li><a href="quick_settings.php"><span class="nav-icon">⚡</span>常用设置</a></li>
            <li><a href="logs.php"><span class="nav-icon">📝</span>日志管理</a></li>
            <li><a href="add_log.php"><span class="nav-icon">➕</span>写日志</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="users.php"><span class="nav-icon">👥</span>用户管理</a></li>
            <li><a href="settings.php"><span class="nav-icon">⚙️</span>高级设置</a></li>
            <?php endif; ?>
            <li><a href="profile.php" class="active"><span class="nav-icon">👤</span>个人资料</a></li>
            <li><a href="change_password.php"><span class="nav-icon">🔑</span>修改密码</a></li>
            <li><a href="../index.php"><span class="nav-icon">🏠</span>返回首页</a></li>
            <li><a href="logout.php"><span class="nav-icon">🚪</span>退出登录</a></li>
        </ul>
    </aside>
    <main class="admin-main">
        <div class="admin-header">
            <h2>👤 个人资料</h2>
        </div>
        
        <?php if ($success): ?><div class="message message-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message message-error"><?= h($error) ?></div><?php endif; ?>
        
        <div class="grid-2">
            <div class="card">
                <h3>📷 头像</h3>
                <div class="avatar-section">
                    <div class="avatar-preview">
                        <?php
                        if ($user['avatar']) {
                            echo '<img src="../' . h($user['avatar']) . '" alt="头像">';
                        } else {
                            $first = mb_substr($user['nickname'], 0, 1, 'UTF-8') ?: '?';
                            echo '<div style="width:100%;height:100%;background:linear-gradient(135deg,#F48FB1,#e91e63);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;">' . h($first) . '</div>';
                        }
                        ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <input type="hidden" name="action" value="avatar">
                        <label class="avatar-upload-label">
                            📷 上传新头像
                            <input type="file" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                        </label>
                    </form>
                    <div class="hint">支持 JPG、PNG、GIF、WebP 格式，建议 200×200px</div>
                </div>
            </div>
            
            <div class="card full-width">
                <h3>📋 基本信息</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="profile">
                    <div class="form-group">
                        <label>用户名</label>
                        <input type="text" name="username" value="<?= h($user['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>昵称</label>
                        <input type="text" name="nickname" value="<?= h($user['nickname']) ?>" required>
                        <div style="color:#666;font-size:12px;margin-top:4px;">昵称将显示在日志的发布者信息中</div>
                    </div>
                    <button type="submit" class="btn btn-primary">保存修改</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
