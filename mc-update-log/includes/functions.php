<?php
/**
 * 公共函数库
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * 获取数据库实例
 */
function db() {
    return Database::getInstance()->getDb();
}

/**
 * 安全输出
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 重定向
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * 获取设置值
 */
function getSetting($key, $default = '') {
    $stmt = db()->prepare("SELECT value FROM settings WHERE key_name = :key");
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ? $row['value'] : $default;
}

/**
 * 保存设置
 */
function saveSetting($key, $value) {
    $stmt = db()->prepare("INSERT OR REPLACE INTO settings (key_name, value) VALUES (:key, :value)");
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->bindValue(':value', $value, SQLITE3_TEXT);
    return $stmt->execute();
}

/**
 * 获取当前登录用户
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        $stmt = db()->prepare("SELECT * FROM users WHERE id = :id AND status = 1");
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    return null;
}

/**
 * 检查是否登录
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

/**
 * 检查是否管理员
 */
function requireAdmin() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        die('无权访问');
    }
}

/**
 * 格式化时间
 */
function formatTime($time) {
    if (!$time) return '';
    $timestamp = strtotime($time);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $timestamp);
}

/**
 * 获取头像HTML
 */
function getAvatarHtml($user, $size = 40) {
    if (!empty($user['avatar'])) {
        $avatarUrl = '../' . h($user['avatar']);
        return '<img src="' . $avatarUrl . '" alt="avatar" class="avatar-img" style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;object-fit:cover;">';
    }
    $initial = mb_substr(h($user['nickname']), 0, 1);
    $fontSize = $size * 0.45;
    return '<div class="avatar-initial" style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--primary),rgba(233,30,99,0.7));color:#fff;font-weight:700;font-size:' . $fontSize . 'px;">' . $initial . '</div>';
}

/**
 * 简易 Markdown 解析（支持图片、标题、列表、代码、粗斜体、链接等）
 */
function parseMarkdown($text) {
    // 转义HTML
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // 图片 ![alt](url)
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" class="md-image" loading="lazy">', $text);
    
    // 链接 [text](url)
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $text);
    
    // 加粗 **text** 或 __text__
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    
    // 斜体 *text* 或 _text_
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
    
    // 删除线 ~~text~~
    $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
    
    // 行内代码 `code`
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    
    // 分割线 --- 或 ***
    $text = preg_replace('/^([-*]{3,})$/m', '<hr>', $text);
    
    // 按行处理标题、列表、引用、代码块
    $lines = explode("\n", $text);
    $inCodeBlock = false;
    $codeContent = '';
    $output = '';
    $inList = false;
    $listType = '';
    
    foreach ($lines as $line) {
        // 代码块 ```lang 或 ~~~
        if (preg_match('/^(`{3,}|~{3,})/', $line)) {
            if ($inCodeBlock) {
                $output .= '<pre><code class="md-code-block">' . $codeContent . '</code></pre>';
                $codeContent = '';
                $inCodeBlock = false;
            } else {
                $inCodeBlock = true;
                $codeContent = '';
            }
            continue;
        }
        
        if ($inCodeBlock) {
            $codeContent .= $line . "\n";
            continue;
        }
        
        // 关闭列表
        if ($inList && !preg_match('/^(\s*[-*+]\s|\s*\d+\.\s)/', $line)) {
            if ($listType === 'ul') $output .= "</ul>\n";
            else $output .= "</ol>\n";
            $inList = false;
        }
        
        // 标题
        if (preg_match('/^(#{1,6})\s(.+)/', $line, $m)) {
            $level = strlen($m[1]);
            $output .= "<h{$level}>" . $m[2] . "</h{$level}>\n";
            continue;
        }
        
        // 无序列表
        if (preg_match('/^(\s*)[-*+]\s(.+)/', $line, $m)) {
            if (!$inList) {
                $inList = true;
                $listType = 'ul';
                $output .= "<ul>\n";
            }
            $output .= "<li>" . $m[2] . "</li>\n";
            continue;
        }
        
        // 有序列表
        if (preg_match('/^(\s*)\d+\.\s(.+)/', $line, $m)) {
            if (!$inList) {
                $inList = true;
                $listType = 'ol';
                $output .= "<ol>\n";
            }
            $output .= "<li>" . $m[2] . "</li>\n";
            continue;
        }
        
        // 引用
        if (preg_match('/^>\s?(.+)/', $line, $m)) {
            $output .= "<blockquote>" . $m[1] . "</blockquote>\n";
            continue;
        }
        
        // 空行
        if (trim($line) === '') {
            $output .= "\n";
            continue;
        }
        
        // 普通段落
        $output .= "<p>" . $line . "</p>\n";
    }
    
    // 关闭未闭合的列表
    if ($inList) {
        if ($listType === 'ul') $output .= "</ul>\n";
        else $output .= "</ol>\n";
    }
    // 关闭未闭合的代码块
    if ($inCodeBlock) {
        $output .= '<pre><code class="md-code-block">' . $codeContent . '</code></pre>';
    }
    
    return $output;
}

/**
 * 获取日志详情（包含作者显示名和头像）
 */
function getLogById($id) {
    $stmt = db()->prepare("
        SELECT l.*, u.nickname as author_name, u.avatar as author_avatar
        FROM update_logs l
        LEFT JOIN users u ON l.author_id = u.id
        WHERE l.id = :id
    ");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

/**
 * 增加浏览量
 */
function incrementViews($id) {
    $stmt = db()->prepare("UPDATE update_logs SET views = views + 1 WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
}

/**
 * 获取用户头像路径
 */
function getUserAvatar($userId) {
    $stmt = db()->prepare("SELECT avatar FROM users WHERE id = :id");
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $r = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $r ? $r['avatar'] : '';
}

/**
 * 获取所有分类（优先从分类表，再兼容旧数据）
 */
function getCategories() {
    // 从分类表获取
    $result = db()->query("SELECT name FROM categories ORDER BY sort_order ASC, name ASC");
    $cats = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $cats[] = $row['name'];
    }
    // 如果分类表为空，从日志表提取
    if (empty($cats)) {
        $result = db()->query("SELECT DISTINCT category FROM update_logs WHERE category != '' ORDER BY category ASC");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cats[] = $row['category'];
        }
    }
    return $cats;
}

/**
 * 获取所有版本
 */
function getVersions() {
    $result = db()->query("SELECT DISTINCT mc_version FROM update_logs WHERE mc_version != '' ORDER BY mc_version DESC");
    $versions = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $versions[] = $row['mc_version'];
    }
    return $versions;
}

/**
 * 获取分页数据
 */
function getLogs($page = 1, $perPage = 10, $where = '', $params = []) {
    $offset = ($page - 1) * $perPage;
    
    $countSql = "SELECT COUNT(*) as total FROM update_logs l WHERE 1=1 $where";
    $countStmt = db()->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $countResult = $countStmt->execute();
    $total = $countResult->fetchArray(SQLITE3_ASSOC)['total'];
    
    $sql = "
        SELECT l.*, u.nickname as author_name, u.avatar as author_avatar
        FROM update_logs l
        LEFT JOIN users u ON l.author_id = u.id
        WHERE 1=1 $where
        ORDER BY l.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    
    $stmt = db()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
    }
    $result = $stmt->execute();
    
    $logs = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $logs[] = $row;
    }
    
    return [
        'logs' => $logs,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current' => $page
    ];
}

/**
 * 获取所有用户
 */
function getUsers() {
    $result = db()->query("SELECT id, username, nickname, avatar, role, status, created_at FROM users ORDER BY id ASC");
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    return $users;
}

/**
 * 上传图片并返回路径
 */
function uploadImage($file, $subdir = '') {
    $uploadDir = __DIR__ . '/../data/uploads/';
    if ($subdir) {
        $uploadDir .= $subdir . '/';
    }
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'svg'];
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $relPath = 'data/uploads/' . ($subdir ? $subdir . '/' : '') . $filename;
        return $relPath;
    }
    return false;
}

/**
 * 删除图片文件
 */
function deleteImage($path) {
    $fullPath = __DIR__ . '/../' . $path;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * 文章内容中的图片上传（编辑器用）
 */
function uploadContentImage($file) {
    return uploadImage($file, 'content');
}
