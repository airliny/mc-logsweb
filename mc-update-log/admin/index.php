<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();

// 统计信息
$totalLogs = $db->querySingle("SELECT COUNT(*) FROM update_logs");
$publishedLogs = $db->querySingle("SELECT COUNT(*) FROM update_logs WHERE status='published'");
$draftLogs = $db->querySingle("SELECT COUNT(*) FROM update_logs WHERE status='draft'");
$totalUsers = $db->querySingle("SELECT COUNT(*) FROM users");
$totalViews = $db->querySingle("SELECT SUM(views) FROM update_logs") ?? 0;

// 最新日志
$recentLogs = $db->query("SELECT l.*, u.nickname as author_name FROM update_logs l LEFT JOIN users u ON l.author_id = u.id ORDER BY l.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - <?= h(getSetting('site_name', 'MC 服务器更新日志')) ?></title>
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
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .admin-header h2 { color: #fff; font-size: 24px; }
        .admin-header .admin-actions { display: flex; gap: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 20px; }
        .stat-card .stat-number { font-size: 32px; font-weight: bold; color: #4CAF50; }
        .stat-card .stat-label { color: #888; font-size: 14px; margin-top: 5px; }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 10px; }
        
        .section-title { color: #fff; font-size: 18px; margin-bottom: 15px; }
        .recent-table { width: 100%; border-collapse: collapse; background: #1a1a1a; border-radius: 12px; overflow: hidden; }
        .recent-table th, .recent-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #222; }
        .recent-table th { color: #888; font-size: 13px; font-weight: normal; }
        .recent-table td { color: #ccc; font-size: 14px; }
        .recent-table tr:hover td { background: rgba(255,255,255,0.02); }
        
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .badge-success { background: rgba(76,175,80,0.15); color: #4CAF50; }
        .badge-warning { background: rgba(255,152,0,0.15); color: #ff9800; }
        
        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 10px 20px; border-radius: 8px; font-size: 14px; text-decoration: none; cursor: pointer; border: none; }
        .btn-primary { background: #4CAF50; color: #fff; }
        .btn-primary:hover { background: #45a049; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        @media (max-width: 768px) {
            .admin-sidebar { display: none; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">⛏ 后台管理</div>
        <div class="sidebar-user">
            👤 <?= h($user['nickname']) ?> 
            <span style="color:#4CAF50;">(<?= $user['role'] === 'admin' ? '管理员' : '编辑' ?>)</span>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php" class="active"><span class="nav-icon">📊</span>仪表盘</a></li>
            <li><a href="quick_settings.php"><span class="nav-icon">⚡</span>常用设置</a></li>
            <li><a href="logs.php"><span class="nav-icon">📝</span>日志管理</a></li>
            <li><a href="add_log.php"><span class="nav-icon">➕</span>写日志</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="users.php"><span class="nav-icon">👥</span>用户管理</a></li>
            <li><a href="settings.php"><span class="nav-icon">⚙️</span>高级设置</a></li>
            <?php endif; ?>
            <li><a href="profile.php"><span class="nav-icon">👤</span>个人资料</a></li>
            <li><a href="change_password.php"><span class="nav-icon">🔑</span>修改密码</a></li>
            <li><a href="../index.php"><span class="nav-icon">🏠</span>返回首页</a></li>
            <li><a href="logout.php"><span class="nav-icon">🚪</span>退出登录</a></li>
        </ul>
    </aside>
    
    <main class="admin-main">
        <div class="admin-header">
            <h2>仪表盘</h2>
            <div class="admin-actions">
                <a href="add_log.php" class="btn btn-primary">✍️ 写新日志</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number"><?= $totalLogs ?></div>
                <div class="stat-label">全部日志</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= $publishedLogs ?></div>
                <div class="stat-label">已发布</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-number"><?= $draftLogs ?></div>
                <div class="stat-label">草稿</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👁</div>
                <div class="stat-number"><?= $totalViews ?></div>
                <div class="stat-label">总浏览量</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?= $totalUsers ?></div>
                <div class="stat-label">用户数</div>
            </div>
        </div>
        
        <h3 class="section-title">📌 最近更新</h3>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>作者</th>
                    <th>分类</th>
                    <th>状态</th>
                    <th>浏览量</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $recentLogs->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td><a href="edit_log.php?id=<?= $log['id'] ?>" style="color:#4CAF50;text-decoration:none;"><?= h(mb_substr($log['title'], 0, 30)) ?></a></td>
                    <td><?= h($log['author_name']) ?></td>
                    <td><?= h($log['category']) ?></td>
                    <td><span class="badge <?= $log['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>"><?= $log['status'] === 'published' ? '已发布' : '草稿' ?></span></td>
                    <td><?= $log['views'] ?></td>
                    <td><?= date('m-d H:i', strtotime($log['created_at'])) ?></td>
                    <td>
                        <a href="edit_log.php?id=<?= $log['id'] ?>" class="btn btn-sm btn-primary">编辑</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($totalLogs == 0): ?>
                <tr><td colspan="7" style="text-align:center;padding:30px;color:#666;">还没有日志，<a href="add_log.php" style="color:#4CAF50;">写一篇吧</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
