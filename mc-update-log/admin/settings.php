<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$user = getCurrentUser();
$db = db();
$success = '';
$error = '';

// 处理图片上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === 0) {
    $path = uploadImage($_FILES['bg_image'], 'bg');
    if ($path) {
        $old = getSetting('bg_image');
        if ($old) deleteImage($old);
        saveSetting('bg_image', $path);
        $success = '背景图片上传成功！';
    } else {
        $error = '图片上传失败，仅支持 jpg/png/gif/webp/svg 格式';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === 0) {
    $path = uploadImage($_FILES['site_icon'], 'icon');
    if ($path) {
        $old = getSetting('site_icon');
        if ($old) deleteImage($old);
        saveSetting('site_icon', $path);
        $success = '网站图标上传成功！';
    } else {
        $error = '图标上传失败，仅支持 jpg/png/gif/webp/ico/svg 格式';
    }
}

if (isset($_GET['delete_bg'])) {
    $old = getSetting('bg_image');
    if ($old) deleteImage($old);
    saveSetting('bg_image', '');
    $success = '背景图片已移除';
}
if (isset($_GET['delete_icon'])) {
    $old = getSetting('site_icon');
    if ($old) deleteImage($old);
    saveSetting('site_icon', '');
    $success = '网站图标已移除';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['bg_image']) && !isset($_FILES['site_icon'])) {
    $textSettings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_desc' => trim($_POST['site_desc'] ?? ''),
        'mc_version' => trim($_POST['mc_version'] ?? ''),
        'server_name' => trim($_POST['server_name'] ?? ''),
        'server_ip' => trim($_POST['server_ip'] ?? ''),
    ];

    $opacitySettings = [];
    if (isset($_POST['bg_opacity'])) $opacitySettings['bg_opacity'] = (string)max(0.1, min(1.0, floatval($_POST['bg_opacity'])));
    if (isset($_POST['content_opacity'])) $opacitySettings['content_opacity'] = (string)max(0.1, min(1.0, floatval($_POST['content_opacity'])));
    if (isset($_POST['hero_opacity'])) $opacitySettings['hero_opacity'] = (string)max(0.1, min(1.0, floatval($_POST['hero_opacity'])));

    $themeSettings = [];
    if (!empty($_POST['theme_primary'])) $themeSettings['theme_primary'] = trim($_POST['theme_primary']);
    if (!empty($_POST['theme_secondary'])) $themeSettings['theme_secondary'] = trim($_POST['theme_secondary']);
    if (!empty($_POST['theme_bg'])) $themeSettings['theme_bg'] = trim($_POST['theme_bg']);
    if (!empty($_POST['theme_card'])) $themeSettings['theme_card'] = trim($_POST['theme_card']);
    if (isset($_POST['blur_amount'])) $themeSettings['blur_amount'] = (string)max(4, min(30, intval($_POST['blur_amount'])));

    if (!empty($_POST['theme_mode'])) $themeSettings['theme_mode'] = $_POST['theme_mode'];

    $allSettings = array_merge($textSettings, $opacitySettings, $themeSettings);

    foreach ($allSettings as $key => $value) {
        if ($value !== '') saveSetting($key, $value);
    }
    $success = '设置保存成功！';
}

$site_name = getSetting('site_name');
$site_desc = getSetting('site_desc');
$mc_version = getSetting('mc_version');
$server_name = getSetting('server_name');
$server_ip = getSetting('server_ip');
$bg_image = getSetting('bg_image');
$site_icon = getSetting('site_icon');
$bg_opacity = getSetting('bg_opacity', '0.3');
$content_opacity = getSetting('content_opacity', '0.85');
$hero_opacity = getSetting('hero_opacity', '0.9');
$theme_primary = getSetting('theme_primary', '#4CAF50');
$theme_secondary = getSetting('theme_secondary', '#81C784');
$theme_bg = getSetting('theme_bg', '#0a0a0a');
$theme_card = getSetting('theme_card', 'rgba(20,20,20,0.7)');
$blur_amount = getSetting('blur_amount', '12');
$theme_mode = getSetting('theme_mode', 'dark');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站设置 - 后台管理</title>
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

        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 25px; }
        .card h3 { color: #fff; font-size: 18px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #222; }
        .full-width { grid-column: 1 / -1; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 6px; font-size: 13px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; background: #252525; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #4CAF50; }
        .form-group textarea { min-height: 60px; resize: vertical; font-family: inherit; }
        .form-group .hint { color: #666; font-size: 12px; margin-top: 4px; }

        .btn { padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; border: none; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #4CAF50; }
        .btn-primary:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #d32f2f; }
        .btn-secondary { background: #444; }
        .btn-secondary:hover { background: #555; }

        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .message-success { background: rgba(76,175,80,0.1); border: 1px solid rgba(76,175,80,0.3); color: #4CAF50; }
        .message-error { background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.3); color: #f44336; }

        .image-preview { margin-top: 10px; position: relative; display: inline-block; }
        .image-preview img { max-width: 200px; max-height: 120px; border-radius: 8px; border: 1px solid #333; }
        .image-preview .delete-btn { position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #f44336; border: none; border-radius: 50%; color: #fff; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .image-preview .delete-btn:hover { background: #d32f2f; }

        .range-group { margin-bottom: 18px; }
        .range-group label { display: flex; justify-content: space-between; color: #ccc; margin-bottom: 8px; font-size: 13px; }
        .range-group label .val { color: #4CAF50; font-weight: 600; }
        .range-group input[type="range"] { -webkit-appearance: none; width: 100%; height: 6px; background: #333; border-radius: 3px; outline: none; }
        .range-group input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; background: #4CAF50; border-radius: 50%; cursor: pointer; border: 2px solid #2a2a2a; }
        .range-group input[type="range"]::-moz-range-thumb { width: 18px; height: 18px; background: #4CAF50; border-radius: 50%; cursor: pointer; border: 2px solid #2a2a2a; }

        .upload-area { border: 2px dashed #444; border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { border-color: #4CAF50; background: rgba(76,175,80,0.05); }
        .upload-area .icon { font-size: 40px; margin-bottom: 10px; }
        .upload-area p { color: #888; font-size: 14px; }
        .upload-area .small { color: #555; font-size: 12px; margin-top: 5px; }
        .upload-area input[type="file"] { display: none; }

        .preview-box { margin-top: 20px; background: #252525; border: 1px solid #333; border-radius: 8px; padding: 15px; }
        .preview-box h4 { color: #888; font-size: 13px; margin-bottom: 10px; }
        .preview-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #222; font-size: 14px; }
        .preview-item:last-child { border-bottom: none; }
        .preview-item span:first-child { color: #888; }
        .preview-item span:last-child { color: #fff; }

        @media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }
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
            <li><a href="users.php"><span class="nav-icon">👥</span>用户管理</a></li>
            <li><a href="settings.php" class="active"><span class="nav-icon">⚙️</span>高级设置</a></li>
            <li><a href="profile.php"><span class="nav-icon">👤</span>个人资料</a></li>
            <li><a href="change_password.php"><span class="nav-icon">🔑</span>修改密码</a></li>
            <li><a href="../index.php"><span class="nav-icon">🏠</span>返回首页</a></li>
            <li><a href="logout.php"><span class="nav-icon">🚪</span>退出登录</a></li>
        </ul>
    </aside>
    <main class="admin-main">
        <div class="admin-header">
            <h2>⚙️ 网站设置</h2>
        </div>

        <?php if ($success): ?><div class="message message-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message message-error"><?= h($error) ?></div><?php endif; ?>

        <div class="settings-grid">
            <div class="card">
                <h3>📋 基本信息</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>网站标题</label>
                        <input type="text" name="site_name" value="<?= h($site_name) ?>">
                        <div class="hint">显示在浏览器标签和页面顶部</div>
                    </div>
                    <div class="form-group">
                        <label>网站描述</label>
                        <textarea name="site_desc"><?= h($site_desc) ?></textarea>
                        <div class="hint">显示在首页副标题</div>
                    </div>
                    <div class="form-group">
                        <label>服务器名称</label>
                        <input type="text" name="server_name" value="<?= h($server_name) ?>">
                        <div class="hint">例如：我的世界生存服务器</div>
                    </div>
                    <div class="form-group">
                        <label>Minecraft 版本</label>
                        <input type="text" name="mc_version" value="<?= h($mc_version) ?>">
                        <div class="hint">例如：1.21</div>
                    </div>
                    <div class="form-group">
                        <label>服务器 IP</label>
                        <input type="text" name="server_ip" value="<?= h($server_ip) ?>">
                        <div class="hint">玩家连接服务器的地址，留空则不显示</div>
                    </div>
                    <div class="form-group">
                        <label>🌐 官网链接</label>
                        <input type="url" name="server_url" value="<?= h(getSetting('server_url', '')) ?>" placeholder="https://example.com">
                        <div class="hint">设置后首页 Hero 区域显示醒目的「访问官网」按钮</div>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 保存基本信息</button>
                </form>
            </div>

            <div class="card">
                <h3>🖼️ 图片设置</h3>
                <div class="form-group">
                    <label>主页背景图片</label>
                    <?php if ($bg_image): ?>
                    <div class="image-preview">
                        <img src="../<?= h($bg_image) ?>" alt="背景图片">
                        <a href="?delete_bg=1" class="delete-btn" onclick="return confirm('移除背景图片？')">×</a>
                    </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                        <div class="upload-area" onclick="document.getElementById('bg-input').click()">
                            <div class="icon">🌄</div>
                            <p>点击上传背景图片</p>
                            <div class="small">推荐 1920×1080 以上，支持 jpg/png/webp</div>
                        </div>
                        <label class="file-label" style="display:block;margin-top:8px;">
                            <input type="file" id="bg-input" name="bg_image" accept="image/*" onchange="this.form.submit()" style="display:none;">
                            <span style="color:#888;font-size:13px;" id="bg-filename">未选择文件</span>
                        </label>
                    </form>
                    <div class="hint">上传后将覆盖当前背景图片</div>
                </div>

                <div class="form-group" style="margin-top:20px;padding-top:20px;border-top:1px solid #222;">
                    <label>网站图标 (Favicon)</label>
                    <?php if ($site_icon): ?>
                    <div class="image-preview">
                        <img src="../<?= h($site_icon) ?>" alt="网站图标" style="max-width:48px;max-height:48px;">
                        <a href="?delete_icon=1" class="delete-btn" onclick="return confirm('移除网站图标？')">×</a>
                    </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                        <div class="upload-area" onclick="document.getElementById('icon-input').click()" style="padding:15px;">
                            <div class="icon" style="font-size:28px;">🔖</div>
                            <p style="font-size:13px;">点击上传图标</p>
                            <div class="small">推荐 32×32 或 64×64，支持 ico/png</div>
                        </div>
                        <label class="file-label" style="display:block;margin-top:8px;">
                            <input type="file" id="icon-input" name="site_icon" accept="image/*" onchange="this.form.submit()" style="display:none;">
                            <span style="color:#888;font-size:13px;" id="icon-filename">未选择文件</span>
                        </label>
                    </form>
                </div>
            </div>

            <div class="card full-width">
                <h3>🎨 视觉效果调节</h3>
                <form method="POST">
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                        <div class="range-group">
                            <label>
                                <span>🌄 背景图片可见度</span>
                                <span class="val" id="bg_opacity_val"><?= $bg_opacity ?></span>
                            </label>
                            <input type="range" name="bg_opacity" min="0.1" max="1.0" step="0.05"
                                   value="<?= $bg_opacity ?>"
                                   oninput="document.getElementById('bg_opacity_val').textContent=this.value">
                            <div class="hint">数值越高背景图片越明显（默认 0.3）</div>
                        </div>
                        <div class="range-group">
                            <label>
                                <span>📦 内容区域透明度</span>
                                <span class="val" id="content_opacity_val"><?= $content_opacity ?></span>
                            </label>
                            <input type="range" name="content_opacity" min="0.1" max="1.0" step="0.05"
                                   value="<?= $content_opacity ?>"
                                   oninput="document.getElementById('content_opacity_val').textContent=this.value">
                            <div class="hint">数值越高内容越不透明（默认 0.85）</div>
                        </div>
                        <div class="range-group">
                            <label>
                                <span>🎯 顶部横幅透明度</span>
                                <span class="val" id="hero_opacity_val"><?= $hero_opacity ?></span>
                            </label>
                            <input type="range" name="hero_opacity" min="0.1" max="1.0" step="0.05"
                                   value="<?= $hero_opacity ?>"
                                   oninput="document.getElementById('hero_opacity_val').textContent=this.value">
                            <div class="hint">数值越高顶部区域越不透明（默认 0.9）</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:20px;">💾 保存透明度设置</button>
                </form>
            </div>

            <div class="card full-width">
                <h3>🎨 主题自定义</h3>
                <form method="POST" style="margin-bottom:25px;padding-bottom:25px;border-bottom:1px solid #222;">
                    <label style="color:#ccc;font-size:13px;font-weight:500;display:block;margin-bottom:12px;">🌓 主题模式</label>
                    <div style="display:flex;gap:12px;">
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="theme_mode" value="dark" <?= $theme_mode === 'dark' ? 'checked' : '' ?> style="display:none;">
                            <div style="padding:14px 20px;border-radius:10px;border:2px solid <?= $theme_mode === 'dark' ? 'var(--primary, #4CAF50)' : '#333' ?>;background:<?= $theme_mode === 'dark' ? 'rgba(76,175,80,0.08)' : '#1a1a1a' ?>;text-align:center;transition:all 0.2s;">
                                <div style="font-size:28px;margin-bottom:6px;">🌙</div>
                                <div style="color:#fff;font-size:15px;font-weight:600;">深色模式</div>
                                <div style="color:#888;font-size:12px;margin-top:4px;">经典暗色，适合游戏氛围</div>
                            </div>
                        </label>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="theme_mode" value="light" <?= $theme_mode === 'light' ? 'checked' : '' ?> style="display:none;">
                            <div style="padding:14px 20px;border-radius:10px;border:2px solid <?= $theme_mode === 'light' ? 'var(--primary, #4CAF50)' : '#333' ?>;background:<?= $theme_mode === 'light' ? 'rgba(76,175,80,0.08)' : '#1a1a1a' ?>;text-align:center;transition:all 0.2s;">
                                <div style="font-size:28px;margin-bottom:6px;">☀️</div>
                                <div style="color:#fff;font-size:15px;font-weight:600;">浅色模式</div>
                                <div style="color:#888;font-size:12px;margin-top:4px;">明亮白底，毛玻璃效果</div>
                            </div>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:15px;">💾 保存模式</button>
                </form>

                <form method="POST">
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label>主色调</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" name="theme_primary" value="<?= h($theme_primary) ?>" style="width:50px;height:40px;padding:2px;background:transparent;border:1px solid #333;border-radius:6px;cursor:pointer;">
                                <input type="text" name="theme_primary_hex" value="<?= h($theme_primary) ?>" style="flex:1;padding:10px 14px;background:#252525;border:1px solid #333;border-radius:8px;color:#fff;font-size:13px;" readonly>
                            </div>
                            <div class="hint">按钮、链接、强调色（默认 #4CAF50）</div>
                        </div>
                        <div class="form-group">
                            <label>辅助色</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" name="theme_secondary" value="<?= h($theme_secondary) ?>" style="width:50px;height:40px;padding:2px;background:transparent;border:1px solid #333;border-radius:6px;cursor:pointer;">
                                <input type="text" name="theme_secondary_hex" value="<?= h($theme_secondary) ?>" style="flex:1;padding:10px 14px;background:#252525;border:1px solid #333;border-radius:8px;color:#fff;font-size:13px;" readonly>
                            </div>
                            <div class="hint">渐变、高亮色（默认 #81C784）</div>
                        </div>
                        <div class="form-group">
                            <label>背景色</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" name="theme_bg" value="<?= h($theme_bg) ?>" style="width:50px;height:40px;padding:2px;background:transparent;border:1px solid #333;border-radius:6px;cursor:pointer;">
                                <input type="text" name="theme_bg_hex" value="<?= h($theme_bg) ?>" style="flex:1;padding:10px 14px;background:#252525;border:1px solid #333;border-radius:8px;color:#fff;font-size:13px;" readonly>
                            </div>
                            <div class="hint">页面底色（默认 #0a0a0a）</div>
                        </div>
                        <div class="form-group">
                            <label>卡片底色</label>
                            <input type="text" name="theme_card" value="<?= h($theme_card) ?>" placeholder="rgba(20,20,20,0.7)">
                            <div class="hint">毛玻璃卡片底色，支持 rgba（默认 rgba(20,20,20,0.7)）</div>
                        </div>
                    </div>
                    <div class="range-group" style="max-width:300px;margin-top:10px;">
                        <label>
                            <span>🔮 毛玻璃模糊程度</span>
                            <span class="val" id="blur_val"><?= $blur_amount ?>px</span>
                        </label>
                        <input type="range" name="blur_amount" min="4" max="30" step="1"
                               value="<?= $blur_amount ?>"
                               oninput="document.getElementById('blur_val').textContent=this.value+'px'">
                        <div class="hint">数值越高背景越模糊（默认 12px）</div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:15px;">💾 保存主题设置</button>
                </form>
                <script>
                document.querySelectorAll('input[type="color"]').forEach(picker => {
                    picker.addEventListener('input', function() {
                        const hexInput = this.closest('.form-group').querySelector('input[readonly]');
                        if (hexInput) hexInput.value = this.value;
                    });
                });
                </script>
            </div>

            <div class="card full-width">
                <h3>📌 首页信息预览</h3>
                <div class="preview-box">
                    <div class="preview-item"><span>网站标题</span><span><?= h($site_name) ?></span></div>
                    <div class="preview-item"><span>网站描述</span><span><?= h($site_desc) ?></span></div>
                    <div class="preview-item"><span>服务器名称</span><span><?= h($server_name) ?></span></div>
                    <div class="preview-item"><span>MC 版本</span><span><?= h($mc_version) ?></span></div>
                    <?php if ($server_ip): ?>
                    <div class="preview-item"><span>服务器 IP</span><span><?= h($server_ip) ?></span></div>
                    <?php endif; ?>
                    <div class="preview-item"><span>背景图片</span><span><?= $bg_image ? '✅ 已上传' : '❌ 未设置' ?></span></div>
                    <div class="preview-item"><span>网站图标</span><span><?= $site_icon ? '✅ 已上传' : '❌ 未设置' ?></span></div>
                    <div class="preview-item"><span>背景可见度</span><span><?= $bg_opacity ?></span></div>
                    <div class="preview-item"><span>内容透明度</span><span><?= $content_opacity ?></span></div>
                    <div class="preview-item"><span>横幅透明度</span><span><?= $hero_opacity ?></span></div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
document.getElementById('bg-input')?.addEventListener('change', function(e) {
    document.getElementById('bg-filename').textContent = e.target.files[0]?.name || '未选择文件';
});
document.getElementById('icon-input')?.addEventListener('change', function(e) {
    document.getElementById('icon-filename').textContent = e.target.files[0]?.name || '未选择文件';
});
</script>
</body>
</html>
