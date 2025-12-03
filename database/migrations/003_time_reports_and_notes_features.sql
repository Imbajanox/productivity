-- =====================================================
-- Migration 003: Time Reports and Notes Features
-- =====================================================
-- Phase 2.2: Pausenzeiten, Statistiken
-- Phase 2.3: Notizen-Ordner, öffentliche Links
-- =====================================================

USE productivity;

-- Add folders table for notes organization
CREATE TABLE IF NOT EXISTS note_folders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6c757d',
    parent_id INT UNSIGNED DEFAULT NULL,
    position INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_parent (user_id, parent_id)
) ENGINE=InnoDB;

-- Add folder_id column to notes table for folder organization
-- Note: Using a procedure to check if column exists before adding
DROP PROCEDURE IF EXISTS add_folder_id_to_notes;
DELIMITER //
CREATE PROCEDURE add_folder_id_to_notes()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'productivity' 
        AND TABLE_NAME = 'notes' 
        AND COLUMN_NAME = 'folder_id'
    ) THEN
        ALTER TABLE notes 
            ADD COLUMN folder_id INT UNSIGNED NULL AFTER project_id,
            ADD INDEX idx_notes_folder_id (folder_id);
    END IF;
END //
DELIMITER ;
CALL add_folder_id_to_notes();
DROP PROCEDURE IF EXISTS add_folder_id_to_notes;

-- Create time reports table for saved reports
CREATE TABLE IF NOT EXISTS time_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    report_type ENUM('daily', 'weekly', 'monthly', 'custom') DEFAULT 'weekly',
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    project_ids JSON DEFAULT NULL,
    include_breaks BOOLEAN DEFAULT TRUE,
    group_by ENUM('day', 'project', 'task') DEFAULT 'day',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_dates (user_id, date_from, date_to)
) ENGINE=InnoDB;

