<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();
$success = '';
$error = '';

// 内容图片上传（AJAX）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['upload_image'])) {
    header('Content-Type: application/json');
    if (isset($_FILES['image'])) {
        $path = uploadContentImage($_FILES['image']);
        if ($path) {
            $url = '../' . $path;
            echo json_encode(['success' => true, 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'msg' => '上传失败']);
        }
    } else {
        echo json_encode(['success' => false, 'msg' => '未选择文件']);
    }
    exit;
}

// 获取分类列表
function getCategoryList() {
    $cats = db()->query("SELECT name FROM categories ORDER BY sort_order ASC, name ASC");
    $list = [];
    while ($r = $cats->fetchArray(SQLITE3_ASSOC)) {
        $list[] = $r['name'];
    }
    if (empty($list)) {
        $list = ['更新', '新内容', 'BUG修复', '公告', '活动', '维护', '优化', '通知'];
    }
    return $list;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['upload_image'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $mc_version = trim($_POST['mc_version'] ?? '');
    $category = trim($_POST['category'] ?? '更新');
    $tags = trim($_POST['tags'] ?? '');
    $author_display = trim($_POST['author_display'] ?? '');
    $status = $_POST['status'] ?? 'published';
    
    if (empty($title)) {
        $error = '请输入日志标题';
    } elseif (empty($content)) {
        $error = '请输入日志内容';
    } else {
        $stmt = $db->prepare("INSERT INTO update_logs (title, content, mc_version, category, tags, author_id, author_display, status) VALUES (:title, :content, :mc_version, :category, :tags, :author_id, :author_display, :status)");
        $stmt->bindValue(':title', $title, SQLITE3_TEXT);
        $stmt->bindValue(':content', $content, SQLITE3_TEXT);
        $stmt->bindValue(':mc_version', $mc_version, SQLITE3_TEXT);
        $stmt->bindValue(':category', $category, SQLITE3_TEXT);
        $stmt->bindValue(':tags', $tags, SQLITE3_TEXT);
        $stmt->bindValue(':author_id', $user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':author_display', $author_display, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $success = '日志发布成功！';
            echo "<script>setTimeout(function(){ window.location.href='logs.php'; }, 1500);</script>";
        } else {
            $error = '发布失败，请重试';
        }
    }
}

$existingVersions = getVersions();
$existingCategories = getCategoryList();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>写日志 - 后台管理</title>
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
        
        .form-card { background: #1a1a1a; border: 1px solid #333; border-radius: 12px; padding: 30px; max-width: 960px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; background: #252525; border: 1px solid #333; border-radius: 8px; color: #fff; font-size: 15px; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #F48FB1; }
        .form-group textarea { min-height: 400px; font-family: 'Consolas','Courier New',monospace; line-height: 1.6; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 12px 30px; border-radius: 8px; font-size: 15px; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: #F48FB1; color: #fff; }
        .btn-primary:hover { background: #e91e63; }
        .btn-secondary { background: #333; color: #ccc; }
        .btn-secondary:hover { background: #444; }
        .btn-upload { background: #444; color: #fff; padding: 8px 16px; border-radius: 6px; cursor: pointer; border: none; font-size: 13px; }
        .btn-upload:hover { background: #555; }
        .message { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .message-success { background: rgba(244,143,177,0.1); border: 1px solid rgba(244,143,177,0.3); color: #F48FB1; }
        .message-error { background: rgba(244,67,54,0.1); border: 1px solid rgba(244,67,54,0.3); color: #f44336; }
        .hint { color: #666; font-size: 12px; margin-top: 4px; }
        .editor-toolbar { display: flex; gap: 8px; padding: 10px 14px; background: #1d1d1d; border: 1px solid #333; border-bottom: none; border-radius: 8px 8px 0 0; flex-wrap: wrap; }
        .editor-toolbar button { background: #333; border: none; color: #ccc; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .editor-toolbar button:hover { background: #444; color: #fff; }
        .editor-toolbar .toolbar-group { display: flex; gap: 4px; align-items: center; }
        .editor-toolbar .separator { width: 1px; height: 24px; background: #444; margin: 0 6px; }
        .editor-toolbar .upload-label { background: #333; color: #ccc; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .editor-toolbar .upload-label:hover { background: #444; color: #fff; }
        .editor-toolbar .upload-label input { display: none; }
        @media (max-width: 900px) { .form-row { grid-template-columns: 1fr 1fr; } }
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
            <li><a href="add_log.php" class="active"><span class="nav-icon">➕</span>写日志</a></li>
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
            <h2>✍️ 写日志</h2>
            <p style="color:#888;font-size:14px;">支持 Markdown 格式 | 图片: <code>![描述](图片URL)</code> 或点击工具栏上传</p>
        </div>
        
        <?php if ($success): ?><div class="message message-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message message-error"><?= h($error) ?></div><?php endif; ?>
        
        <form class="form-card" method="POST" id="logForm">
            <div class="form-group">
                <label>日志标题 *</label>
                <input type="text" name="title" required placeholder="例如：服务器 V2.0 大更新预告" value="<?= h($_POST['title'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Minecraft 版本</label>
                    <input type="text" name="mc_version" list="versionList" placeholder="如 1.21" value="<?= h($_POST['mc_version'] ?? '') ?>">
                    <datalist id="versionList">
                        <?php foreach ($existingVersions as $v): ?>
                        <option value="<?= h($v) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>分类</label>
                    <input type="text" name="category" list="categoryList" value="<?= h($_POST['category'] ?? '更新') ?>">
                    <datalist id="categoryList">
                        <?php foreach ($existingCategories as $c): ?>
                        <option value="<?= h($c) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>发布者名称</label>
                    <input type="text" name="author_display" placeholder="留空使用用户名" value="<?= h($_POST['author_display'] ?? '') ?>">
                    <div class="hint">自定义本条日志的发布者名称</div>
                </div>
                <div class="form-group">
                    <label>标签（逗号分隔）</label>
                    <input type="text" name="tags" placeholder="如 新内容,优化" value="<?= h($_POST['tags'] ?? '') ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>日志内容 * <span style="color:#666;font-weight:normal;">（Markdown 格式，支持图片、代码块等）</span></label>
                <div class="editor-toolbar">
                    <div class="toolbar-group">
                        <button type="button" onclick="mdInsert('**','**')" title="加粗">B</button>
                        <button type="button" onclick="mdInsert('*','*')" title="斜体">I</button>
                        <button type="button" onclick="mdInsert('# ','')" title="标题">H</button>
                        <button type="button" onclick="mdInsert('- ','')" title="列表">•</button>
                        <button type="button" onclick="mdInsert('`','`')" title="代码"></></button>
                        <button type="button" onclick="mdInsert('```\n','\n```')" title="代码块">代码块</button>
                        <button type="button" onclick="mdInsert('[','](url)')" title="链接">🔗</button>
                    </div>
                    <div class="separator"></div>
                    <div class="toolbar-group">
                        <button type="button" onclick="mdInsert('> ','')" title="引用">❝</button>
                        <button type="button" onclick="mdInsert('---\n','')" title="分割线">—</button>
                    </div>
                    <div class="separator"></div>
                    <label class="upload-label">
                        📷 上传图片
                        <input type="file" accept="image/*" id="imageInput">
                    </label>
                    <span id="uploadStatus" style="font-size:12px;color:#666;"></span>
                </div>
                <textarea name="content" id="content" required placeholder="在这里写下更新日志的内容（支持 Markdown）...

输入图片格式: ![图片描述](图片URL)
或者点击工具栏的「上传图片」按钮"><?= h($_POST['content'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="status" value="published" class="btn btn-primary">📢 发布日志</button>
                <button type="submit" name="status" value="draft" class="btn btn-secondary">💾 存为草稿</button>
                <a href="logs.php" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </main>
</div>
<script>
function mdInsert(before, after) {
    const ta = document.getElementById('content');
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selected = ta.value.substring(start, end);
    const insert = before + selected + after;
    ta.value = ta.value.substring(0, start) + insert + ta.value.substring(end);
    ta.focus();
    ta.selectionStart = start + before.length;
    ta.selectionEnd = start + before.length + selected.length;
}

// 图片上传
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const status = document.getElementById('uploadStatus');
    status.textContent = '上传中...';
    
    const formData = new FormData();
    formData.append('image', file);
    
    fetch('add_log.php?upload_image=1', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const ta = document.getElementById('content');
            const imgMd = '![' + file.name + '](' + data.url + ')';
            ta.value = ta.value.substring(0, ta.selectionStart) + imgMd + ta.value.substring(ta.selectionEnd);
            status.textContent = '✅ 图片已插入';
        } else {
            status.textContent = '❌ ' + (data.msg || '上传失败');
        }
    })
    .catch(() => {
        status.textContent = '❌ 上传失败';
    });
    this.value = '';
});

function validateForm() {
    var title = document.querySelector('input[name="title"]').value.trim();
    var content = document.querySelector('textarea[name="content"]').value.trim();
    if (!title) { alert('请输入日志标题'); return false; }
    if (!content) { alert('请输入日志内容'); return false; }
    return true;
}
</script>
</body>
</html>
