<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_desc' => trim($_POST['site_desc'] ?? ''),
        'server_name' => trim($_POST['server_name'] ?? ''),
        'server_ip' => trim($_POST['server_ip'] ?? ''),
        'server_url' => trim($_POST['server_url'] ?? ''),
        'mc_version' => trim($_POST['mc_version'] ?? ''),
        'theme_primary' => trim($_POST['theme_primary'] ?? '#F48FB1'),
        'theme_bg' => trim($_POST['theme_bg'] ?? '#f8f9fa'),
        'blur_amount' => (string)max(4, min(30, intval($_POST['blur_amount'] ?? 16))),
    ];
    if (!empty($_POST['theme_mode'])) {
        $settings['theme_mode'] = $_POST['theme_mode'];
    }

    foreach ($settings as $key => $value) {
        if ($value !== '') saveSetting($key, $value);
    }
    $success = '常用设置已保存！';
}

$site_name = getSetting('site_name');
$site_desc = getSetting('site_desc');
$server_name = getSetting('server_name');
$server_ip = getSetting('server_ip');
$server_url = getSetting('server_url');
$mc_version = getSetting('mc_version');
$theme_primary = getSetting('theme_primary', '#F48FB1');
$theme_bg = getSetting('theme_bg', '#f8f9fa');
$blur_amount = getSetting('blur_amount', '16');
$theme_mode = getSetting('theme_mode', 'light');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>常用设置 - 后台管理</title>
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
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 24px; }
        .card h3 { color: #fff; font-size: 17px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #222; }
        .card.full { grid-column: 1 / -1; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; background: #252525; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #F48FB1; }
        .form-group textarea { min-height: 50px; resize: vertical; font-family: inherit; }
        .form-group .hint { color: #666; font-size: 12px; margin-top: 3px; }
        .color-pick-group { display: flex; gap: 10px; align-items: center; }
        .color-pick-group input[type="color"] { width: 50px; height: 40px; padding: 2px; background: transparent; border: 1px solid #333; border-radius: 6px; cursor: pointer; }
        .color-pick-group input[type="text"] { flex: 1; }
        .range-group { margin-bottom: 16px; }
        .range-group label { display: flex; justify-content: space-between; color: #ccc; margin-bottom: 6px; font-size: 13px; }
        .range-group label .val { color: #F48FB1; font-weight: 600; }
        .range-group input[type="range"] { -webkit-appearance: none; width: 100%; height: 6px; background: #333; border-radius: 3px; outline: none; }
        .range-group input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; background: #F48FB1; border-radius: 50%; cursor: pointer; border: 2px solid #2a2a2a; }
        .range-group input[type="range"]::-moz-range-thumb { width: 18px; height: 18px; background: #F48FB1; border-radius: 50%; cursor: pointer; border: 2px solid #2a2a2a; }
        .mode-select { display: flex; gap: 12px; }
        .mode-select label { flex: 1; cursor: pointer; }
        .mode-select label input { display: none; }
        .mode-select label .mode-box { padding: 14px; border-radius: 10px; border: 2px solid #333; text-align: center; transition: all .2s; }
        .mode-select label input:checked + .mode-box { border-color: #F48FB1; background: rgba(244,143,177,0.08); }
        .mode-select label .mode-box .icon { font-size: 26px; margin-bottom: 4px; }
        .mode-select label .mode-box .name { color: #fff; font-size: 14px; font-weight: 600; }
        .btn { padding: 10px 28px; border-radius: 8px; font-size: 14px; cursor: pointer; border: none; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #F48FB1; }
        .btn-primary:hover { background: #e91e63; }
        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .message-success { background: rgba(244,143,177,0.1); border: 1px solid rgba(244,143,177,0.3); color: #F48FB1; }
        @media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">⛏ 后台管理</div>
        <div class="sidebar-user">👤 <?= h($user['nickname']) ?> <span style="color:#F48FB1;">(<?= $user['role'] === 'admin' ? '管理员' : '编辑' ?>)</span></div>
        <ul class="admin-nav">
            <li><a href="index.php"><span class="nav-icon">📊</span>仪表盘</a></li>
            <li><a href="quick_settings.php" class="active"><span class="nav-icon">⚡</span>常用设置</a></li>
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
            <h2>⚡ 常用设置</h2>
            <p style="color:#888;font-size:14px;">快速修改最常用的配置项，完整设置请前往「高级设置」</p>
        </div>
        <?php if ($success): ?><div class="message message-success"><?= h($success) ?></div><?php endif; ?>
        <form method="POST">
            <div class="settings-grid">
                <!-- 基本信息 -->
                <div class="card">
                    <h3>📋 站点信息</h3>
                    <div class="form-group">
                        <label>网站标题</label>
                        <input type="text" name="site_name" value="<?= h($site_name) ?>">
                    </div>
                    <div class="form-group">
                        <label>网站描述</label>
                        <textarea name="site_desc"><?= h($site_desc) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>服务器名称</label>
                        <input type="text" name="server_name" value="<?= h($server_name) ?>">
                        <div class="hint">显示在首页顶部</div>
                    </div>
                    <div class="form-group">
                        <label>MC 版本</label>
                        <input type="text" name="mc_version" value="<?= h($mc_version) ?>">
                    </div>
                </div>
                <!-- 链接 -->
                <div class="card">
                    <h3>🔗 链接设置</h3>
                    <div class="form-group">
                        <label>服务器 IP</label>
                        <input type="text" name="server_ip" value="<?= h($server_ip) ?>" placeholder="play.example.com">
                        <div class="hint">显示在首页顶部信息栏</div>
                    </div>
                    <div class="form-group">
                        <label>🌐 官网链接</label>
                        <input type="url" name="server_url" value="<?= h($server_url) ?>" placeholder="https://example.com">
                        <div class="hint">设置后首页会显示醒目的「访问官网」按钮</div>
                    </div>
                </div>
                <!-- 主题 -->
                <div class="card full">
                    <h3>🎨 外观主题</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                        <div class="form-group">
                            <label>主色调</label>
                            <div class="color-pick-group">
                                <input type="color" name="theme_primary" value="<?= h($theme_primary) ?>">
                                <input type="text" name="theme_primary_hex" value="<?= h($theme_primary) ?>" readonly>
                            </div>
                            <div class="hint">按钮、链接颜色</div>
                        </div>
                        <div class="form-group">
                            <label>背景色</label>
                            <div class="color-pick-group">
                                <input type="color" name="theme_bg" value="<?= h($theme_bg) ?>">
                                <input type="text" name="theme_bg_hex" value="<?= h($theme_bg) ?>" readonly>
                            </div>
                            <div class="hint">页面渐变底色</div>
                        </div>
                        <div class="range-group">
                            <label>
                                <span>🔮 毛玻璃模糊</span>
                                <span class="val" id="blur_val"><?= $blur_amount ?>px</span>
                            </label>
                            <input type="range" name="blur_amount" min="4" max="30" step="1" value="<?= $blur_amount ?>" oninput="document.getElementById('blur_val').textContent=this.value+'px'">
                        </div>
                    </div>
                    <div style="margin-top:16px;">
                        <label style="display:block;color:#ccc;font-size:13px;font-weight:500;margin-bottom:10px;">🌓 主题模式</label>
                        <div class="mode-select">
                            <label>
                                <input type="radio" name="theme_mode" value="light" <?= $theme_mode==='light'?'checked':'' ?>>
                                <div class="mode-box">
                                    <div class="icon">☀️</div>
                                    <div class="name">浅色模式</div>
                                </div>
                            </label>
                            <label>
                                <input type="radio" name="theme_mode" value="dark" <?= $theme_mode==='dark'?'checked':'' ?>>
                                <div class="mode-box">
                                    <div class="icon">🌙</div>
                                    <div class="name">深色模式</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top:24px;">
                <button type="submit" class="btn btn-primary">💾 保存所有常用设置</button>
            </div>
        </form>
    </main>
</div>
<script>
document.querySelectorAll('input[type="color"]').forEach(p => {
    p.addEventListener('input', function() {
        const t = this.closest('.color-pick-group').querySelector('input[readonly]');
        if (t) t.value = this.value;
    });
});
</script>
</body>
</html>
