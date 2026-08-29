-- ============================================================
-- ADD-ON to shield_jobportal_mysql_schema.sql
-- Import this the same way, into the same database
-- (wordpress-35303837748d), AFTER the main schema file.
-- Adds one table used for login brute-force protection.
-- ============================================================

CREATE TABLE IF NOT EXISTS jp_login_attempts (
    attempt_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(180) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    succeeded    TINYINT(1)   NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jp_login_attempts_lookup (email, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
