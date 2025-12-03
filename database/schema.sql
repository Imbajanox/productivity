-- =====================================================
-- Produktivitätstool - Datenbankschema
-- =====================================================
-- Erstellt die komplette Datenbankstruktur
-- Ausführung: mysql -u root -p < schema.sql
-- =====================================================

-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS productivity 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE productivity;

-- =====================================================
-- BENUTZER
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    theme ENUM('light', 'dark') DEFAULT 'light',
    language VARCHAR(5) DEFAULT 'de',
    settings JSON DEFAULT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- =====================================================
-- PASSWORT-RESET TOKENS
-- =====================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- =====================================================
-- PROJEKTE
-- =====================================================
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    status ENUM('active', 'paused', 'completed', 'archived') DEFAULT 'active',
    deadline DATE DEFAULT NULL,
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_position (position)
) ENGINE=InnoDB;

-- =====================================================
-- TAGS
-- =====================================================
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#95a5a6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tag (user_id, name),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- TODOS
-- =====================================================
CREATE TABLE IF NOT EXISTS todos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED DEFAULT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('todo', 'in_progress', 'done', 'cancelled') DEFAULT 'todo',
    due_date DATE DEFAULT NULL,
    due_time TIME DEFAULT NULL,
    reminder_at DATETIME DEFAULT NULL,
    recurring ENUM('none', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'none',
    recurring_interval INT UNSIGNED DEFAULT 1,
    estimated_minutes INT UNSIGNED DEFAULT NULL,
    actual_minutes INT UNSIGNED DEFAULT NULL,
    position INT UNSIGNED DEFAULT 0,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES todos(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_project (project_id),
    INDEX idx_due_date (due_date),
    INDEX idx_parent (parent_id),
    INDEX idx_position (position)
) ENGINE=InnoDB;

-- =====================================================
-- TODO-TAGS (Pivot-Tabelle)
-- =====================================================
CREATE TABLE IF NOT EXISTS todo_tags (
    todo_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    
    PRIMARY KEY (todo_id, tag_id),
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- SUBTASKS (Unteraufgaben)
-- =====================================================
CREATE TABLE IF NOT EXISTS subtasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    todo_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE CASCADE,
    INDEX idx_todo (todo_id),
    INDEX idx_position (position)
) ENGINE=InnoDB;

-- =====================================================
-- ZEITERFASSUNG
-- =====================================================
CREATE TABLE IF NOT EXISTS time_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED DEFAULT NULL,
    todo_id INT UNSIGNED DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    duration_seconds INT UNSIGNED DEFAULT NULL,
    is_running BOOLEAN DEFAULT FALSE,
    is_break BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, start_time),
    INDEX idx_running (user_id, is_running),
    INDEX idx_project (project_id),
    INDEX idx_todo (todo_id)
) ENGINE=InnoDB;

-- =====================================================
-- NOTIZEN
-- =====================================================
CREATE TABLE IF NOT EXISTS notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED DEFAULT NULL,
    todo_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT DEFAULT NULL,
    content_type ENUM('html', 'markdown', 'plain') DEFAULT 'html',
    folder VARCHAR(255) DEFAULT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE,
    public_token VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE SET NULL,
    INDEX idx_user_folder (user_id, folder),
    INDEX idx_pinned (user_id, is_pinned),
    INDEX idx_public (public_token),
    FULLTEXT idx_search (title, content)
) ENGINE=InnoDB;

-- =====================================================
-- KALENDER-EVENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS calendar_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    all_day BOOLEAN DEFAULT FALSE,
    color VARCHAR(7) DEFAULT '#3498db',
    reminder_minutes INT UNSIGNED DEFAULT NULL,
    recurring ENUM('none', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, start_datetime),
    INDEX idx_date_range (start_datetime, end_datetime)
) ENGINE=InnoDB;

-- =====================================================
-- CODE-SNIPPETS
-- =====================================================
CREATE TABLE IF NOT EXISTS snippets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    code LONGTEXT NOT NULL,
    language VARCHAR(50) DEFAULT 'plaintext',
    tags VARCHAR(255) DEFAULT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    use_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_language (user_id, language),
    INDEX idx_favorite (user_id, is_favorite),
    FULLTEXT idx_search (title, description, code)
) ENGINE=InnoDB;

-- =====================================================
-- BOOKMARKS
-- =====================================================
CREATE TABLE IF NOT EXISTS bookmarks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(2048) NOT NULL,
    description TEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'Allgemein',
    favicon VARCHAR(2048) DEFAULT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    click_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_category (user_id, category),
    INDEX idx_favorite (user_id, is_favorite)
) ENGINE=InnoDB;

-- =====================================================
-- GEWOHNHEITEN (HABITS)
-- =====================================================
CREATE TABLE IF NOT EXISTS habits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#27ae60',
    icon VARCHAR(50) DEFAULT 'check',
    frequency ENUM('daily', 'weekly', 'custom') DEFAULT 'daily',
    frequency_days VARCHAR(20) DEFAULT NULL, -- z.B. "1,2,3,4,5" für Mo-Fr
    target_count INT UNSIGNED DEFAULT 1,
    reminder_time TIME DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active)
) ENGINE=InnoDB;

-- =====================================================
-- GEWOHNHEITS-LOGS
-- =====================================================
CREATE TABLE IF NOT EXISTS habit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    habit_id INT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    completed_count INT UNSIGNED DEFAULT 1,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_habit_date (habit_id, log_date),
    INDEX idx_date (log_date)
) ENGINE=InnoDB;

-- =====================================================
-- POMODORO-SESSIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS pomodoro_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    todo_id INT UNSIGNED DEFAULT NULL,
    session_type ENUM('work', 'short_break', 'long_break') DEFAULT 'work',
    duration_minutes INT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME DEFAULT NULL,
    was_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (todo_id) REFERENCES todos(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, started_at)
) ENGINE=InnoDB;

-- =====================================================
-- QUICK NOTES (Schnelle Notizen)
-- =====================================================
CREATE TABLE IF NOT EXISTS quick_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    processed_to_type ENUM('todo', 'note', 'event', 'deleted') DEFAULT NULL,
    processed_to_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_processed (user_id, processed)
) ENGINE=InnoDB;

-- =====================================================
-- AKTIVITÄTS-LOG (für Statistiken)
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(50) NOT NULL, -- 'todo_completed', 'time_logged', etc.
    entity_type VARCHAR(50) DEFAULT NULL, -- 'todo', 'project', etc.
    entity_id INT UNSIGNED DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_action (action_type)
) ENGINE=InnoDB;

-- =====================================================
-- DEMO-BENUTZER (optional - für Tests)
-- =====================================================
-- Passwort: 'password123' (bcrypt hash)
INSERT INTO users (username, email, password_hash, first_name, last_name, theme) 
VALUES ('demo', 'demo@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4UoaV8xX4.PnKqJO', 'Demo', 'User', 'light')
ON DUPLICATE KEY UPDATE username = username;
