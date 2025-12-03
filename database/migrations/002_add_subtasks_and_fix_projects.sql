-- =====================================================
-- Migration: Add subtasks table and fix projects
-- =====================================================
-- Ausführung: mysql -u root -p productivity < 002_add_subtasks_and_fix_projects.sql
-- =====================================================

USE productivity;

-- =====================================================
-- SUBTASKS (Unteraufgaben für Todos)
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
-- Fix projects table: Add is_active column if not exists
-- =====================================================
-- Check and add is_active column
SET @columnExists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'productivity' 
    AND TABLE_NAME = 'projects' 
    AND COLUMN_NAME = 'is_active'
);

SET @sql = IF(@columnExists = 0, 
    'ALTER TABLE projects ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER color',
    'SELECT "Column is_active already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update is_active based on status if column was just added
UPDATE projects SET is_active = (status = 'active' OR status = 'paused') WHERE is_active IS NULL;

-- =====================================================
-- Verify changes
-- =====================================================
SELECT 'Migration completed successfully!' AS message;
SHOW TABLES LIKE 'subtasks';
DESCRIBE subtasks;
