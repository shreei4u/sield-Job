-- ============================================================
-- ADD-ON #2 to shield_jobportal_mysql_schema.sql
-- Import into the same database, after 02_login_attempts_addon.sql.
--
-- The original schema had no table for "Employer requests a
-- Freelancer/Trainer" (README's "request-and-track flow"), and no
-- way to trace WHO sent a manpower/contractor request. This adds
-- both without touching any existing table's data.
-- ============================================================

-- Employer -> Freelancer offering / Trainer course requests
CREATE TABLE IF NOT EXISTS jp_hire_requests (
    request_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id   INT UNSIGNED NOT NULL,
    target_type   ENUM('freelancer_offering','trainer_course') NOT NULL,
    target_id     INT UNSIGNED NOT NULL,      -- offering_id or course_id
    provider_id   INT UNSIGNED NOT NULL,      -- the freelancer_id or trainer_id, denormalized for fast lookup
    message       TEXT,
    status        ENUM('Pending','Accepted','Declined') NOT NULL DEFAULT 'Pending',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_jp_hire_requests_provider (provider_id),
    KEY idx_jp_hire_requests_employer (employer_id),
    CONSTRAINT fk_hr_employer FOREIGN KEY (employer_id) REFERENCES jp_users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_hr_provider FOREIGN KEY (provider_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Track which employer sent a manpower deployment request (nullable = safe on old rows)
ALTER TABLE jp_manpower_deployment_requests
    ADD COLUMN IF NOT EXISTS employer_id INT UNSIGNED NULL AFTER provider_id,
    ADD CONSTRAINT fk_mdr_employer FOREIGN KEY (employer_id) REFERENCES jp_users(user_id) ON DELETE SET NULL;

-- Track which employer sent a "Project Work" request that shows up
-- in the contractor's Received Bids list
ALTER TABLE jp_contractor_received_bids
    ADD COLUMN IF NOT EXISTS employer_id INT UNSIGNED NULL AFTER contractor_id,
    ADD CONSTRAINT fk_crb_employer FOREIGN KEY (employer_id) REFERENCES jp_users(user_id) ON DELETE SET NULL;
