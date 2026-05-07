<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$user = getCurrentUser();
$db = db();
$success = '';
$error = '';

// 头像上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'avatar' && isset($_FILES['avatar_file'])) {
        $uid = intval($_POST['uid'] ?? 0);
        $path = uploadImage($_FILES['avatar_file'], 'avatars');
        if ($path) {
            // 删除旧头像
            $old = getUserAvatar($uid);
            if ($old) deleteImage($old);
            $stmt = $db->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
            $stmt->bindValue(':avatar', $path, SQLITE3_TEXT);
            $stmt->bindValue(':id', $uid, SQLITE3_INTEGER);
            $stmt->execute();
            $success = '头像更新成功！';
        } else {
            $error = '头像上传失败，仅支持 jpg/png/gif/webp 格式';
        }
    }
}

// 添加用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $role = $_POST['role'] ?? 'editor';
        
        if (empty($username) || empty($password) || empty($nickname)) {
            $error = '请填写所有必填项';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $db->prepare("INSERT INTO users (username, password, nickname, role) VALUES (:username, :password, :nickname, :role)");
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':password', $hashed, SQLITE3_TEXT);
                $stmt->bindValue(':nickname', $nickname, SQLITE3_TEXT);
                $stmt->bindValue(':role', $role, SQLITE3_TEXT);
                $stmt->execute();
                $success = '用户添加成功！';
            } catch (Exception $e) {
                $error = '用户名已存在';
            }
        }
    }
    
    // 修改密码
    if ($_POST['action'] === 'password') {
        $uid = intval($_POST['uid'] ?? 0);
        $newpass = trim($_POST['new_password'] ?? '');
        if ($newpass) {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindValue(':password', $hashed, SQLITE3_TEXT);
            $stmt->bindValue(':id', $uid, SQLITE3_INTEGER);
            $stmt->execute();
            $success = '密码修改成功！';
        }
    }
    
    // 删除用户
    if ($_POST['action'] === 'delete') {
        $uid = intval($_POST['uid'] ?? 0);
        if ($uid == $user['id']) {
            $error = '不能删除自己';
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindValue(':id', $uid, SQLITE3_INTEGER);
            $stmt->execute();
            $success = '用户已删除';
        }
    }
}

$users = getUsers();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 后台管理</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 240px; background: #111; padding: 20px 0; border-right: 1px solid #222; flex-shrink: 0; }
        .admin-sidebar .sidebar-title { padding: 0 20px; font-size: 18px; color: #4CAF50; margin-bottom: 20px; }
        .admin-sidebar .sidebar-user { padding: 0 20px 20px; border-bottom: 1px solid #222; margin-bottom: 20px; color: #aaa; font-size: 14px; }
        .admin-nav { list-style: none; padding: 0; margin: 0; }
        .admin-nav li a { display: block; padding: 12px 20px; color: #ccc; text-decoration: none; transition: all 0.2s; font-size: 15px; }
        .admin-nav li a:hover, .admin-nav li a.active { background: rgba(76,175,80,0.1); color: #4CAF50; border-left: 3px solid #4CAF50; }
        .admin-nav li a .nav-icon { margin-right: 10px; }
        .admin-main { flex: 1; padding: 30px; background: #0a0a0a; }
        .admin-header { margin-bottom: 30px; }
        .admin-header h2 { color: #fff; font-size: 24px; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 25px; }
        .card h3 { color: #fff; font-size: 18px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #222; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 6px; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; background: #252525; border: 1px solid #333; border-radius: 6px; color: #fff; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #4CAF50; }
        .btn { padding: 10px 24px; border-radius: 6px; font-size: 14px; cursor: pointer; border: none; color: #fff; }
        .btn-primary { background: #4CAF50; }
        .btn-primary:hover { background: #45a049; }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 4px; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #d32f2f; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #222; }
        th { color: #888; font-size: 13px; font-weight: normal; }
        td { color: #ccc; font-size: 14px; }
        .badge-admin { background: rgba(76,175,80,0.15); color: #4CAF50; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .badge-editor { background: rgba(33,150,243,0.15); color: #2196F3; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .message-success { background: rgba(76,175,80,0.1); border: 1px solid rgba(76,175,80,0.3); color: #4CAF50; }
        .message-error { background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.3); color: #f44336; }
        .full-width { grid-column: 1 / -1; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">⛏ 后台管理</div>
        <div class="sidebar-user">👤 <?= h($user['nickname']) ?> <span style="color:#4CAF50;">(管理员)</span></div>
        <ul class="admin-nav">
            <li><a href="index.php"><span class="nav-icon">📊</span>仪表盘</a></li>
            <li><a href="quick_settings.php"><span class="nav-icon">⚡</span>常用设置</a></li>
            <li><a href="logs.php"><span class="nav-icon">📝</span>日志管理</a></li>
            <li><a href="add_log.php"><span class="nav-icon">➕</span>写日志</a></li>
            <li><a href="users.php" class="active"><span class="nav-icon">👥</span>用户管理</a></li>
            <li><a href="settings.php"><span class="nav-icon">⚙️</span>高级设置</a></li>
            <li><a href="change_password.php"><span class="nav-icon">🔑</span>修改密码</a></li>
            <li><a href="../index.php"><span class="nav-icon">🏠</span>返回首页</a></li>
            <li><a href="logout.php"><span class="nav-icon">🚪</span>退出登录</a></li>
        </ul>
    </aside>
    <main class="admin-main">
        <div class="admin-header">
            <h2>👥 用户管理</h2>
        </div>
        
        <?php if ($success): ?><div class="message message-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message message-error"><?= h($error) ?></div><?php endif; ?>
        
        <div class="grid-2">
            <div class="card">
                <h3>添加新用户</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>用户名 *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>昵称 *</label>
                        <input type="text" name="nickname" required>
                    </div>
                    <div class="form-group">
                        <label>密码 *</label>
                        <input type="text" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>角色</label>
                        <select name="role">
                            <option value="editor">编辑</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">添加用户</button>
                </form>
            </div>
            
            <div class="card full-width">
                <h3>用户列表 (<?= count($users) ?>)</h3>
                <table>
                    <thead><tr><th>ID</th><th>用户名</th><th>昵称 / 头像</th><th>角色</th><th>状态</th><th>注册时间</th><th>操作</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td><?= h($u['username']) ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <?php
                                    $uData = ['avatar' => $u['avatar'], 'nickname' => $u['nickname']];
                                    echo '<div style="flex-shrink:0;">' . getAvatarHtml($uData, 28) . '</div>';
                                    ?>
                                    <span><?= h($u['nickname']) ?></span>
                                </div>
                            </td>
                            <td><span class="<?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-editor' ?>"><?= $u['role'] === 'admin' ? '管理员' : '编辑' ?></span></td>
                            <td><?= $u['status'] ? '正常' : '禁用' ?></td>
                            <td><?= $u['created_at'] ?></td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                <form method="POST" enctype="multipart/form-data" style="display:inline;">
                                    <input type="hidden" name="action" value="avatar">
                                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                    <label class="btn btn-sm btn-primary" style="cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                                        📷 头像
                                        <input type="file" name="avatar_file" accept="image/*" style="display:none;" onchange="this.form.submit()">
                                    </label>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="password">
                                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                    <input type="text" name="new_password" placeholder="新密码" style="width:90px;padding:4px 8px;background:#252525;border:1px solid #333;border-radius:4px;color:#fff;font-size:12px;" required>
                                    <button type="submit" class="btn btn-sm btn-primary">改密</button>
                                </form>
                                <?php if ($u['id'] != $user['id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('确定删除此用户吗？')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                </form>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
