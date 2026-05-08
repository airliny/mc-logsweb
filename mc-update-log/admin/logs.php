<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// 筛选
$where = "WHERE 1=1";
$params = [];
if (isset($_GET['status']) && $_GET['status']) {
    $where .= " AND l.status = :status";
    $params[':status'] = $_GET['status'];
}
if (isset($_GET['search']) && $_GET['search']) {
    $where .= " AND (l.title LIKE :search OR l.content LIKE :search2)";
    $params[':search'] = "%{$_GET['search']}%";
    $params[':search2'] = "%{$_GET['search']}%";
}
// 非管理员只能看自己的
if ($user['role'] !== 'admin') {
    $where .= " AND l.author_id = :author_id";
    $params[':author_id'] = $user['id'];
}

// 总数
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM update_logs l $where");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$total = $countStmt->execute()->fetchArray(SQLITE3_ASSOC)['total'];
$pages = ceil($total / $perPage);

// 列表
$sql = "SELECT l.*, u.nickname as author_name, u.avatar as author_avatar FROM update_logs l LEFT JOIN users u ON l.author_id = u.id $where ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$logs = $stmt->execute();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>日志管理 - 后台管理</title>
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
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-header h2 { color: #fff; font-size: 24px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 8px 12px; background: #252525; border: 1px solid #333; border-radius: 6px; color: #fff; font-size: 14px; }
        .filter-bar input:focus, .filter-bar select:focus { outline: none; border-color: #4CAF50; }
        .filter-bar .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; cursor: pointer; border: none; color: #fff; }
        .btn-primary { background: #4CAF50; }
        .btn-primary:hover { background: #45a049; }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 4px; text-decoration: none; }
        .table-wrap { background: #1a1a1a; border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #222; }
        th { color: #888; font-size: 13px; font-weight: normal; }
        td { color: #ccc; font-size: 14px; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .badge-success { background: rgba(76,175,80,0.15); color: #4CAF50; }
        .badge-warning { background: rgba(255,152,0,0.15); color: #ff9800; }
        .action-group { display: flex; gap: 5px; }
        .action-group a, .action-group button { padding: 4px 10px; border-radius: 4px; font-size: 12px; text-decoration: none; cursor: pointer; border: none; }
        .btn-edit { background: rgba(76,175,80,0.15); color: #4CAF50; }
        .btn-delete { background: rgba(244,67,54,0.15); color: #f44336; }
        .btn-edit:hover { background: rgba(76,175,80,0.25); }
        .btn-delete:hover { background: rgba(244,67,54,0.25); }
        .pagination { margin-top: 20px; display: flex; gap: 5px; justify-content: center; }
        .pagination a { padding: 8px 14px; background: #1a1a1a; border: 1px solid #333; border-radius: 6px; color: #ccc; text-decoration: none; }
        .pagination a.active { background: #4CAF50; border-color: #4CAF50; color: #fff; }
        .pagination a:hover { border-color: #4CAF50; }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">⛏ 后台管理</div>
        <div class="sidebar-user">👤 <?= h($user['nickname']) ?> <span style="color:#4CAF50;">(<?= $user['role'] === 'admin' ? '管理员' : '编辑' ?>)</span></div>
        <ul class="admin-nav">
            <li><a href="index.php"><span class="nav-icon">📊</span>仪表盘</a></li>
            <li><a href="quick_settings.php"><span class="nav-icon">⚡</span>常用设置</a></li>
            <li><a href="logs.php" class="active"><span class="nav-icon">📝</span>日志管理</a></li>
            <li><a href="add_log.php"><span class="nav-icon">➕</span>写日志</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="users.php"><span class="nav-icon">👥</span>用户管理</a></li>
            <li><a href="settings.php"><span class="nav-icon">⚙️</span>高级设置</a></li>
            <?php endif; ?>
            <li><a href="change_password.php"><span class="nav-icon">🔑</span>修改密码</a></li>
            <li><a href="../index.php"><span class="nav-icon">🏠</span>返回首页</a></li>
            <li><a href="logout.php"><span class="nav-icon">🚪</span>退出登录</a></li>
        </ul>
    </aside>
    <main class="admin-main">
        <div class="admin-header">
            <h2>📝 日志管理</h2>
            <a href="add_log.php" class="btn btn-primary" style="padding:10px 20px;border-radius:8px;text-decoration:none;color:#fff;">✍️ 写新日志</a>
        </div>
        <form class="filter-bar" method="GET">
            <input type="text" name="search" placeholder="搜索标题..." value="<?= h($_GET['search'] ?? '') ?>">
            <select name="status">
                <option value="">全部状态</option>
                <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>已发布</option>
                <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>草稿</option>
            </select>
            <button type="submit" class="btn btn-primary">筛选</button>
        </form>
        <div class="table-wrap">
            <table>
                <thead><tr><th>标题</th><th>作者</th><th>版本</th><th>分类</th><th>状态</th><th>浏览</th><th>时间</th><th>操作</th></tr></thead>
                <tbody>
                    <?php while ($log = $logs->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                        <td><a href="edit_log.php?id=<?= $log['id'] ?>" style="color:#4CAF50;text-decoration:none;"><?= h(mb_substr($log['title'], 0, 40)) ?></a></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <?php
                                $authorDisplay = $log['author_display'] ?: $log['author_name'];
                                $aData = ['avatar' => $log['author_avatar'], 'nickname' => $authorDisplay];
                                echo '<div style="flex-shrink:0;">' . getAvatarHtml($aData, 24) . '</div>';
                                ?>
                                <span><?= h($authorDisplay) ?></span>
                            </div>
                        </td>
                        <td><?= h($log['mc_version'] ?: '-') ?></td>
                        <td><?= h($log['category']) ?></td>
                        <td><span class="badge <?= $log['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>"><?= $log['status'] === 'published' ? '已发布' : '草稿' ?></span></td>
                        <td><?= $log['views'] ?></td>
                        <td><?= date('Y-m-d', strtotime($log['created_at'])) ?></td>
                        <td>
                            <div class="action-group">
                                <a href="edit_log.php?id=<?= $log['id'] ?>" class="btn-edit">编辑</a>
                                <a href="../detail.php?id=<?= $log['id'] ?>" class="btn-edit" target="_blank">查看</a>
                                <a href="delete_log.php?id=<?= $log['id'] ?>" class="btn-delete" onclick="return confirm('确定删除吗？')">删除</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($total == 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#666;">暂无日志</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= h($_GET['status'] ?? '') ?>&search=<?= h($_GET['search'] ?? '') ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
