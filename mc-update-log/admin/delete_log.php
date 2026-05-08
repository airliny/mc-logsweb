<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = db();
$id = intval($_GET['id'] ?? 0);

// 获取日志验证权限
$stmt = $db->prepare("SELECT * FROM update_logs WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$log = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$log) {
    header('Location: logs.php');
    exit;
}

// 非管理员只能删除自己的
if ($user['role'] !== 'admin' && $log['author_id'] != $user['id']) {
    die('无权删除此日志');
}

$stmt = $db->prepare("DELETE FROM update_logs WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$stmt->execute();

header('Location: logs.php?deleted=1');
exit;
