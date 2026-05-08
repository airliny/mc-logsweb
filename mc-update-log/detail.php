<?php
require_once __DIR__ . '/includes/functions.php';

$db = db();
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(404);
    die('日志不存在');
}

$log = getLogById($id);

if (!$log || $log['status'] === 'draft') {
    http_response_code(404);
    die('日志不存在或尚未发布');
}

// 增加浏览量
incrementViews($id);
$log['views']++;

$site_name = getSetting('site_name', 'MC 更新日志');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($log['title']) ?> - <?= h($site_name) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .detail-page { padding: 40px 0; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--text); text-decoration: none; margin-bottom: 20px; opacity: 0.7; transition: 0.2s; }
        .back-link:hover { opacity: 1; }
        
        .detail-card { max-width: 860px; margin: 0 auto; }
        .detail-header { margin-bottom: 30px; }
        .detail-meta { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
        .detail-meta .badge { padding: 4px 12px; border-radius: 20px; background: var(--primary)22; color: var(--primary); font-size: 13px; }
        .detail-meta .version { color: #999; font-size: 13px; }
        .detail-meta .views { color: #999; font-size: 13px; }
        .detail-title { font-size: 2em; color: var(--text); margin-bottom: 16px; line-height: 1.3; }
        
        .detail-author { display: flex; align-items: center; gap: 12px; padding: 16px 0; border-top: 1px solid rgba(255,255,255,0.08); border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 24px; }
        .detail-author .avatar-wrapper { flex-shrink: 0; }
        .detail-author .author-detail-info { display: flex; flex-direction: column; }
        .detail-author .author-detail-name { color: var(--primary); font-weight: 600; font-size: 15px; }
        .detail-author .author-detail-time { color: #999; font-size: 13px; }
        
        .detail-body { line-height: 1.9; color: var(--text); }
        .detail-body h1, .detail-body h2, .detail-body h3, .detail-body h4 { margin: 1.4em 0 0.7em; color: var(--text); }
        .detail-body h1 { font-size: 1.7em; }
        .detail-body h2 { font-size: 1.5em; }
        .detail-body h3 { font-size: 1.25em; }
        .detail-body p { margin: 0.7em 0; }
        .detail-body ul, .detail-body ol { margin: 0.7em 0; padding-left: 2em; }
        .detail-body li { margin: 0.35em 0; }
        .detail-body code { background: rgba(0,0,0,0.35); padding: 3px 8px; border-radius: 4px; font-size: 0.9em; }
        .detail-body pre { background: rgba(0,0,0,0.45); padding: 20px; border-radius: 10px; overflow-x: auto; border: 1px solid rgba(255,255,255,0.08); margin: 1em 0; }
        .detail-body pre code { background: none; padding: 0; }
        .detail-body blockquote { border-left: 4px solid var(--primary); padding: 10px 20px; margin: 1em 0; background: rgba(0,0,0,0.2); border-radius: 0 10px 10px 0; }
        .detail-body .md-image { max-width: 100%; border-radius: 10px; margin: 16px 0; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .detail-body a { color: var(--primary); text-decoration: underline; }
        .detail-body hr { border: none; height: 1px; background: rgba(255,255,255,0.1); margin: 2em 0; }
        .detail-body del { color: #999; }
        .detail-body strong { font-weight: 700; }
        .detail-body em { font-style: italic; }
        
        .detail-footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); }
        .detail-tags { display: flex; gap: 8px; flex-wrap: wrap; }
        .detail-tags .tag { font-size: 13px; color: #aaa; }
        
        .nav-links { display: flex; justify-content: space-between; margin-top: 30px; gap: 20px; }
        .nav-link { padding: 12px 20px; border-radius: 8px; background: rgba(255,255,255,0.05); color: var(--text); text-decoration: none; transition: 0.2s; flex: 1; }
        .nav-link:hover { background: rgba(255,255,255,0.1); }
        .nav-link.next { text-align: right; }
        .nav-link .nav-label { font-size: 12px; color: #999; display: block; margin-bottom: 4px; }
        .nav-link .nav-title { font-size: 14px; font-weight: 500; }
        
        @media (max-width: 768px) {
            .detail-title { font-size: 1.5em; }
            .nav-links { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="detail-page">
        <div class="container">
            <a href="index.php" class="back-link">← 返回首页</a>
            
            <article class="detail-card glass">
                <div class="detail-header">
                    <div class="detail-meta">
                        <span class="badge"><?= h($log['category']) ?></span>
                        <?php if ($log['mc_version']): ?>
                            <span class="version">⛏ <?= h($log['mc_version']) ?></span>
                        <?php endif; ?>
                        <span class="views">👁 <?= $log['views'] ?></span>
                    </div>
                    <h1 class="detail-title"><?= h($log['title']) ?></h1>
                    
                    <div class="detail-author">
                        <div class="avatar-wrapper">
                            <?php
                            $authorData = [
                                'avatar' => $log['author_avatar'],
                                'nickname' => $log['author_display'] ?: $log['author_name']
                            ];
                            echo getAvatarHtml($authorData, 40);
                            ?>
                        </div>
                        <div class="author-detail-info">
                            <span class="author-detail-name"><?= h($log['author_display'] ?: $log['author_name']) ?></span>
                            <span class="author-detail-time"><?= formatTime($log['created_at']) ?> · 发布于 <?= $log['created_at'] ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-body">
                    <?= parseMarkdown($log['content']) ?>
                </div>
                
                <?php if ($log['tags']): ?>
                <div class="detail-footer">
                    <div class="detail-tags">
                        <?php foreach (explode(',', $log['tags']) as $tag): ?>
                            <span class="tag">#<?= h(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </article>
            
            <?php
            // 上下篇导航
            $prev = $db->querySingle("SELECT id, title FROM update_logs WHERE id < $id AND status='published' ORDER BY id DESC LIMIT 1", true);
            $next = $db->querySingle("SELECT id, title FROM update_logs WHERE id > $id AND status='published' ORDER BY id ASC LIMIT 1", true);
            if ($prev || $next):
            ?>
            <div class="nav-links">
                <div>
                    <?php if ($prev): ?>
                    <a href="detail.php?id=<?= $prev['id'] ?>" class="nav-link">
                        <span class="nav-label">← 上一篇</span>
                        <span class="nav-title"><?= h($prev['title']) ?></span>
                    </a>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($next): ?>
                    <a href="detail.php?id=<?= $next['id'] ?>" class="nav-link next">
                        <span class="nav-label">下一篇 →</span>
                        <span class="nav-title"><?= h($next['title']) ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
