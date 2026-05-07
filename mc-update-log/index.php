<?php
require_once __DIR__ . '/includes/functions.php';

$db = db();
$page = max(1, intval($_GET['page'] ?? 1));
$category = trim($_GET['category'] ?? '');
$version = trim($_GET['version'] ?? '');
$search = trim($_GET['search'] ?? '');
$perPage = 10;

$where = " AND l.status = 'published'";
$params = [];

if ($category) {
    $where .= " AND l.category = :category";
    $params[':category'] = $category;
}
if ($version) {
    $where .= " AND l.mc_version = :version";
    $params[':version'] = $version;
}
if ($search) {
    $where .= " AND (l.title LIKE :search OR l.content LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}

$data = getLogs($page, $perPage, $where, $params);
$logs = $data['logs'];

$site_name = getSetting('site_name', 'MC 更新日志');
$site_desc = getSetting('site_desc', '记录服务器的每次更新');
$mc_version = getSetting('mc_version', '1.21');
$server_name = getSetting('server_name', '我的世界');
$server_ip = getSetting('server_ip', '');
$server_url = getSetting('server_url', '');
$categories = getCategories();
$versions = getVersions();
$pageTitle = $site_name;

include __DIR__ . '/includes/header.php';
?>

<style>
/* 整体布局 */
.page-content { max-width: 900px; margin: 0 auto; padding: 24px 0 60px; }

/* 顶部简介条 - 简洁轻量 */
.server-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; padding: 16px 20px; border-radius: 12px; }
.server-bar .server-info { display: flex; align-items: center; gap: 10px; }
.server-bar .server-name { font-size: 18px; font-weight: 700; color: var(--text); }
.server-bar .server-desc { color: #888; font-size: 13px; }
.server-bar .server-actions { display: flex; gap: 8px; }
.server-bar .btn-sm { padding: 6px 14px; border-radius: 6px; font-size: 13px; text-decoration: none; border: none; cursor: pointer; }
.server-bar .btn-primary { background: var(--primary); color: #fff; }
.server-bar .btn-primary:hover { opacity: 0.85; }
.server-bar .btn-outline { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: var(--text); }
.server-bar .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.server-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; background: rgba(255,255,255,0.08); border-radius: 100px; font-size: 12px; color: #999; cursor: pointer; }
.server-badge:hover { background: rgba(255,255,255,0.15); }

/* 筛选栏 - 紧凑 */
.filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-bar select, .filter-bar input { padding: 8px 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: var(--text); font-size: 13px; outline: none; }
.filter-bar select:focus, .filter-bar input:focus { border-color: var(--primary); }
.filter-bar select { min-width: 110px; }
.filter-bar input { flex: 1; min-width: 150px; }

/* 日志卡片 - 干净清爽 */
.log-card { padding: 20px 24px; margin-bottom: 12px; border-radius: 12px; transition: background 0.2s; }
.log-card:hover { background: rgba(255,255,255,0.65); }
.log-card .card-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.log-card .badge { padding: 2px 10px; border-radius: 100px; font-size: 12px; background: rgba(255,255,255,0.1); color: #888; }
.log-card .badge-cat { color: var(--primary); }
.log-card .badge-views { color: #999; }
.log-card h2 { margin: 0 0 6px; font-size: 17px; }
.log-card h2 a { color: var(--text); text-decoration: none; }
.log-card h2 a:hover { color: var(--primary); }

/* 作者行 */
.log-card .author-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 13px; color: #999; }
.log-card .author-row .avatar-wrapper { flex-shrink: 0; }

/* 内容缩略 */
.log-card .excerpt { color: #666; font-size: 14px; line-height: 1.7; }
.log-card .excerpt .log-content p:first-of-type { margin: 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.log-card .excerpt .log-content .md-image { max-height: 160px; object-fit: cover; border-radius: 6px; margin: 8px 0; }

/* 底部 */
.log-card .card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.15); }
.log-card .read-more { font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 500; }
.log-card .read-more:hover { text-decoration: underline; }
.log-card .tags { display: flex; gap: 6px; flex-wrap: wrap; }
.log-card .tags .tag { font-size: 11px; color: #888; }

/* 空状态 */
.empty-state { text-align: center; padding: 60px 20px; border-radius: 12px; }
.empty-state .icon { font-size: 40px; margin-bottom: 12px; opacity: 0.5; }
.empty-state h3 { color: var(--text); margin-bottom: 6px; font-size: 16px; }
.empty-state p { color: #999; font-size: 14px; margin-bottom: 16px; }

/* 分页 */
.pagination { display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 24px; }
.page-btn { padding: 8px 18px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: var(--text); font-size: 13px; text-decoration: none; transition: 0.2s; }
.page-btn:hover { border-color: var(--primary); color: var(--primary); }
.page-info { color: #888; font-size: 13px; }

@media (max-width: 768px) {
    .server-bar { flex-direction: column; align-items: flex-start; }
    .server-bar .server-info { width: 100%; }
    .filter-bar input { width: 100%; }
    .log-card { padding: 16px; }
    .card-footer { flex-direction: column; align-items: flex-start; gap: 8px; }
}
</style>

<div class="page-content">
    <!-- 顶部简介条 -->
    <div class="server-bar glass">
        <div class="server-info">
            <div>
                <div class="server-name"><?= h($server_name ?: $site_name) ?></div>
                <div class="server-desc"><?= h($site_desc) ?></div>
            </div>
        </div>
        <div class="server-actions">
            <?php if ($mc_version): ?>
                <span class="server-badge">⛏ <?= h($mc_version) ?></span>
            <?php endif; ?>
            <?php if ($server_ip): ?>
                <span class="server-badge" onclick="navigator.clipboard.writeText('<?= h($server_ip) ?>').then(()=>{this.innerHTML='✅ 已复制';setTimeout(()=>{this.innerHTML='🎮 <?= h($server_ip) ?>'},2000)})">🎮 <?= h($server_ip) ?></span>
            <?php endif; ?>
            <?php if ($server_url): ?>
                <a href="<?= h($server_url) ?>" target="_blank" class="btn-sm btn-primary">访问官网</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 筛选栏 -->
    <form class="filter-bar" method="GET">
        <select name="category" onchange="this.form.submit()">
            <option value="">全部分类</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="version" onchange="this.form.submit()">
            <option value="">全部版本</option>
            <?php foreach ($versions as $ver): ?>
                <option value="<?= h($ver) ?>" <?= $version === $ver ? 'selected' : '' ?>><?= h($ver) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="搜索日志..." value="<?= h($search) ?>" onkeydown="if(event.key==='Enter')this.form.submit()">
    </form>

    <!-- 日志列表 -->
    <?php if (empty($logs)): ?>
        <div class="empty-state glass">
            <div class="icon">📭</div>
            <h3>暂无更新日志</h3>
            <p>服务器还没有发布任何更新记录</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin/add_log.php" class="btn-sm btn-primary" style="padding:10px 24px;border-radius:8px;text-decoration:none;display:inline-block;">写第一篇日志</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <article class="log-card glass">
            <div class="card-top">
                <span class="badge badge-cat"><?= h($log['category']) ?></span>
                <?php if ($log['mc_version']): ?>
                    <span class="badge">⛏ <?= h($log['mc_version']) ?></span>
                <?php endif; ?>
                <?php if ($log['views'] > 0): ?>
                    <span class="badge badge-views">👁 <?= $log['views'] ?></span>
                <?php endif; ?>
            </div>
            <h2><a href="detail.php?id=<?= $log['id'] ?>"><?= h($log['title']) ?></a></h2>
            <div class="author-row">
                <?php
                $authorData = [
                    'avatar' => $log['author_avatar'],
                    'nickname' => $log['author_display'] ?: $log['author_name']
                ];
                echo getAvatarHtml($authorData, 24);
                ?>
                <span><?= h($log['author_display'] ?: $log['author_name']) ?></span>
                <span>·</span>
                <span><?= formatTime($log['created_at']) ?></span>
            </div>
            <div class="excerpt">
                <div class="log-content"><?= parseMarkdown($log['content']) ?></div>
            </div>
            <div class="card-footer">
                <a href="detail.php?id=<?= $log['id'] ?>" class="read-more">阅读全文 →</a>
                <?php if ($log['tags']): ?>
                <div class="tags">
                    <?php foreach (explode(',', $log['tags']) as $tag): ?>
                        <span class="tag">#<?= h(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>

        <?php if ($data['pages'] > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $version ? '&version=' . urlencode($version) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn">← 上一页</a>
            <?php endif; ?>
            <span class="page-info">第 <?= $page ?> / <?= $data['pages'] ?> 页</span>
            <?php if ($page < $data['pages']): ?>
                <a href="?page=<?= $page + 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $version ? '&version=' . urlencode($version) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn">下一页 →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
