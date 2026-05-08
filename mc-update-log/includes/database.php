<?php
/**
 * SQLite 数据库初始化和管理
 */

class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        $dbPath = __DIR__ . '/../data/mc_update_log.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $this->db = new SQLite3($dbPath);
        $this->db->enableExceptions(true);
        $this->db->exec('PRAGMA journal_mode=WAL');
        $this->db->exec('PRAGMA foreign_keys=ON');
        
        $this->initTables();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDb() {
        return $this->db;
    }

    private function initTables() {
        // 网站配置表
        $this->db->exec("CREATE TABLE IF NOT EXISTS settings (
            key_name TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )");

        // 用户表
        $this->db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            nickname TEXT NOT NULL,
            avatar TEXT DEFAULT '',
            role TEXT DEFAULT 'editor' CHECK(role IN ('admin', 'editor')),
            status INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT (datetime('now','localtime')),
            updated_at DATETIME DEFAULT (datetime('now','localtime'))
        )");

        // 更新日志表
        $this->db->exec("CREATE TABLE IF NOT EXISTS update_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            mc_version TEXT DEFAULT '',
            category TEXT DEFAULT '更新',
            author_id INTEGER NOT NULL,
            author_display TEXT DEFAULT '',
            status TEXT DEFAULT 'published' CHECK(status IN ('draft', 'published')),
            views INTEGER DEFAULT 0,
            tags TEXT DEFAULT '',
            created_at DATETIME DEFAULT (datetime('now','localtime')),
            updated_at DATETIME DEFAULT (datetime('now','localtime')),
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // 分类表
        $this->db->exec("CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            sort_order INTEGER DEFAULT 0
        )");

        // 检查是否需要插入默认数据
        $result = $this->db->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row['count'] == 0) {
            $this->insertDefaultData();
        }
        
        // 兼容性迁移：旧表没有 avatar 或 author_display 列时添加
        try {
            $this->db->exec("ALTER TABLE users ADD COLUMN avatar TEXT DEFAULT ''");
        } catch (Exception $e) {}
        try {
            $this->db->exec("ALTER TABLE update_logs ADD COLUMN author_display TEXT DEFAULT ''");
        } catch (Exception $e) {}
    }

    private function insertDefaultData() {
        // 默认管理员密码: admin123
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $this->db->exec("INSERT INTO users (username, password, nickname, role) VALUES 
            ('admin', '$password', '服主', 'admin')");
        
        // 默认分类
        $defaultCats = ['更新', '新内容', 'BUG修复', '公告', '活动', '维护', '优化', '通知'];
        foreach ($defaultCats as $i => $cat) {
            $this->db->exec("INSERT OR IGNORE INTO categories (name, sort_order) VALUES ('$cat', $i)");
        }
        
        // 默认网站设置
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('site_name', 'MC 服务器更新日志')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('site_desc', '记录服务器的每一次更新与变化')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('mc_version', '1.21')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('server_name', '我的世界服务器')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('server_ip', 'play.example.com')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('server_url', '')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('bg_image', '')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('site_icon', '')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('bg_opacity', '0.3')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('content_opacity', '0.85')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('hero_opacity', '0.9')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('theme_primary', '#F48FB1')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('theme_secondary', '#F8BBD0')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('theme_bg', '#f8f9fa')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('theme_card', 'rgba(255,255,255,0.65)')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('blur_amount', '16')");
        $this->db->exec("INSERT OR IGNORE INTO settings (key_name, value) VALUES ('theme_mode', 'light')");
    }

}
