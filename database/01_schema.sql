-- ============================================================
-- SHIELD JOB PORTAL — MySQL SCHEMA (converted from PostgreSQL)
-- Target: MySQL 5.7+ / MariaDB 10.2+  (phpMyAdmin import-ready)
-- Engine: InnoDB | Charset: utf8mb4 (full emoji/Unicode support)
--
-- IMPORTANT — DATABASE SELECTION
-- You confirmed these tables should live inside the EXISTING
-- WordPress database `wordpress-35303837748d`, not a new database.
-- To avoid any chance of colliding with WordPress core tables
-- (wp_*) or plugin tables now or in future, every table below is
-- prefixed `jp_` (Job Portal). Do NOT remove this prefix.
--
-- HOW TO IMPORT VIA phpMyAdmin:
--   1. Log in to phpMyAdmin.
--   2. Click on the `wordpress-35303837748d` database in the left
--      sidebar (select it — do NOT create a new database).
--   3. Go to the "Import" tab.
--   4. Choose this file and click "Go".
--   5. Confirm in the table list that only jp_* tables were added —
--      nothing with a wp_ prefix should be touched.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. USERS (core account table for every role, incl. admin)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_users (
    user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)  NOT NULL,
    email           VARCHAR(180)  NOT NULL,
    phone           VARCHAR(20)   NULL,
    password_hash   VARCHAR(255)  NOT NULL,       -- bcrypt/argon2 hash only, never plain text
    role            ENUM('admin','jobseeker','employer','freelancer',
                         'trainer','manpower','contractor') NOT NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_jp_users_email (email),
    KEY idx_jp_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. JOB SEEKER PROFILES (one-to-one with jp_users, role=jobseeker)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_job_seeker_profiles (
    profile_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id              INT UNSIGNED NOT NULL UNIQUE,
    desired_title        VARCHAR(150),
    total_experience     VARCHAR(50),
    qualification        VARCHAR(150),
    projects_done        VARCHAR(100),
    current_company      VARCHAR(150),
    current_location     VARCHAR(150),
    desired_location     VARCHAR(150),
    current_salary       VARCHAR(60),
    expected_salary      VARCHAR(60),
    notice_period        VARCHAR(60),
    reason_for_change    TEXT,
    key_skills           TEXT,
    about_summary        TEXT,
    whatsapp_number      VARCHAR(20),
    linkedin_url         VARCHAR(255),
    project_link         VARCHAR(255),
    reference_1          VARCHAR(255),
    reference_2          VARCHAR(255),
    marital_status        ENUM('Married','Not Married') NULL,
    resume_file_name      VARCHAR(255),
    resume_file_url       VARCHAR(500),           -- real storage URL, not a browser File object
    ats_boost_active       TINYINT(1) NOT NULL DEFAULT 0,
    ats_paid_at            TIMESTAMP NULL DEFAULT NULL,
    review_status          ENUM('Pending','Published','Rejected') NOT NULL DEFAULT 'Pending',
    updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_jsp_user FOREIGN KEY (user_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. EMPLOYER JOB POSTINGS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_job_postings (
    job_id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employer_id               INT UNSIGNED NOT NULL,
    title                     VARCHAR(150) NOT NULL,
    company_name              VARCHAR(150) NOT NULL,
    department                VARCHAR(100),
    location                  VARCHAR(150) NOT NULL,
    employment_type           ENUM('Full-time','Part-time','Contract','Temporary') NOT NULL,
    compensation              VARCHAR(120),
    summary                   TEXT,
    responsibilities          TEXT,
    requirements              TEXT,
    application_instructions  VARCHAR(60) DEFAULT 'Updated CV',
    status_update_via         ENUM('Call','WhatsApp') NULL,
    close_within              ENUM('7 Days','15 Days','21 Days','30 Days','45 Days') NULL,
    interview_mode            ENUM('Face to Face','Online') NULL,
    notice_period             VARCHAR(60),
    special_notes             TEXT,
    hiring_assistance         ENUM('normal','custom') NOT NULL DEFAULT 'normal',
    category                  VARCHAR(50),
    open_status               ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
    review_status              ENUM('Pending','Published','Rejected') NOT NULL DEFAULT 'Pending',
    posted_at                 TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at                 TIMESTAMP NULL DEFAULT NULL,
    KEY idx_jp_job_postings_employer (employer_id),
    KEY idx_jp_job_postings_status (review_status, open_status),
    CONSTRAINT fk_jp_employer FOREIGN KEY (employer_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_job_posting_background_checks (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id        INT UNSIGNED NOT NULL,
    check_type    ENUM('Experience','Residential','Reference','Educational','Police') NOT NULL,
    CONSTRAINT fk_jpbc_job FOREIGN KEY (job_id) REFERENCES jp_job_postings(job_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. JOB APPLICATIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_job_applications (
    application_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id           INT UNSIGNED NOT NULL,
    jobseeker_id     INT UNSIGNED NOT NULL,
    status           ENUM('Pending','Shortlisted','Screening','Interview',
                           'Offer','Hired','Rejected') NOT NULL DEFAULT 'Pending',
    applied_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_job_seeker (job_id, jobseeker_id),
    KEY idx_jp_job_applications_job (job_id),
    KEY idx_jp_job_applications_jobseeker (jobseeker_id),
    CONSTRAINT fk_ja_job FOREIGN KEY (job_id) REFERENCES jp_job_postings(job_id) ON DELETE CASCADE,
    CONSTRAINT fk_ja_jobseeker FOREIGN KEY (jobseeker_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. ATS PIPELINE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_ats_pipeline (
    pipeline_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id   INT UNSIGNED NULL,
    candidate_name   VARCHAR(150) NOT NULL,
    role_title       VARCHAR(150),
    stage            ENUM('Applied','Screening','Interview','Offer','Hired') NOT NULL DEFAULT 'Applied',
    added_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ap_application FOREIGN KEY (application_id) REFERENCES jp_job_applications(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. FREELANCER TABLES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_freelancer_offerings (
    offering_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    freelancer_id   INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT,
    price           DECIMAL(10,2) NOT NULL,
    delivery_time   VARCHAR(60),
    category        VARCHAR(50),
    review_status   ENUM('Pending','Published','Rejected') NOT NULL DEFAULT 'Pending',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jp_freelancer_offerings_freelancer (freelancer_id),
    CONSTRAINT fk_fo_freelancer FOREIGN KEY (freelancer_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_freelancer_profiles (
    profile_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id              INT UNSIGNED NOT NULL UNIQUE,
    professional_title   VARCHAR(150),
    experience           VARCHAR(50),
    portfolio_url        VARCHAR(255),
    skills                TEXT,
    about_bio             TEXT,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fp_user FOREIGN KEY (user_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. TRAINER COURSES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_trainer_courses (
    course_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id      INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT,
    fee             DECIMAL(10,2) NOT NULL,
    duration        VARCHAR(60),
    category        VARCHAR(50),
    review_status   ENUM('Pending','Published','Rejected') NOT NULL DEFAULT 'Pending',
    added_by_admin  TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jp_trainer_courses_trainer (trainer_id),
    CONSTRAINT fk_tc_trainer FOREIGN KEY (trainer_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. MANPOWER PROVIDER TABLES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_manpower_workforce_pool (
    pool_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id     INT UNSIGNED NOT NULL,
    role_type       VARCHAR(100) NOT NULL,
    skill_level     ENUM('Skilled','Semi-Skilled','Unskilled') NULL,
    experience      VARCHAR(50),
    worker_count    INT UNSIGNED NOT NULL DEFAULT 1,
    is_available    TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mwp_provider FOREIGN KEY (provider_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_manpower_deployment_requests (
    request_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id     INT UNSIGNED NOT NULL,
    client_name     VARCHAR(150) NOT NULL,
    location        VARCHAR(150) NOT NULL,
    roles_needed    VARCHAR(255),
    quantity        INT UNSIGNED NOT NULL,
    duration        VARCHAR(60),
    status          ENUM('New','In Progress','Fulfilled','Cancelled') NOT NULL DEFAULT 'New',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mdr_provider FOREIGN KEY (provider_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_manpower_service_locations (
    location_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id     INT UNSIGNED NOT NULL,
    location_name   VARCHAR(150) NOT NULL,
    CONSTRAINT fk_msl_provider FOREIGN KEY (provider_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_manpower_contract_details (
    detail_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NOT NULL UNIQUE,
    location_address  TEXT,
    contact_name      VARCHAR(150),
    contact_number    VARCHAR(20),
    contract_period   VARCHAR(100),
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_mcd_user FOREIGN KEY (user_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_manpower_contract_roles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    detail_id       INT UNSIGNED NOT NULL,
    role_name       ENUM('Skilled Labour','Unskilled Labour','Security Guard',
                        'Housekeeping Staff','Driver','Technician','Supervisor','Other') NOT NULL,
    CONSTRAINT fk_mcr_detail FOREIGN KEY (detail_id) REFERENCES jp_manpower_contract_details(detail_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. PROJECT CONTRACTOR TABLES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_contractor_projects (
    project_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contractor_id      INT UNSIGNED NOT NULL,
    project_name       VARCHAR(150) NOT NULL,
    client_name        VARCHAR(150) NOT NULL,
    location_address   TEXT,
    contact_name       VARCHAR(150),
    contact_number     VARCHAR(20),
    contract_value     DECIMAL(14,2),
    start_date         DATE,
    end_date           DATE,
    contract_period    VARCHAR(100),
    manpower_required  INT UNSIGNED,
    description        TEXT,
    status             ENUM('Ongoing','Completed','On Hold') NOT NULL DEFAULT 'Ongoing',
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jp_contractor_projects_contractor (contractor_id),
    CONSTRAINT fk_cp_contractor FOREIGN KEY (contractor_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_contractor_project_manpower_roles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id      INT UNSIGNED NOT NULL,
    role_name       VARCHAR(50) NOT NULL,
    CONSTRAINT fk_cpmr_project FOREIGN KEY (project_id) REFERENCES jp_contractor_projects(project_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_contractor_received_bids (
    bid_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contractor_id   INT UNSIGNED NOT NULL,
    project_id      INT UNSIGNED NULL,
    bidder_name     VARCHAR(150) NOT NULL,
    bid_amount      DECIMAL(14,2) NOT NULL,
    contact_info    VARCHAR(150),
    status          ENUM('New','Accepted','Rejected') NOT NULL DEFAULT 'New',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_crb_contractor FOREIGN KEY (contractor_id) REFERENCES jp_users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_crb_project FOREIGN KEY (project_id) REFERENCES jp_contractor_projects(project_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. SUBSCRIPTIONS & PRICING
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_subscriptions (
    subscription_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL UNIQUE,
    plan             ENUM('Free','Premium') NOT NULL DEFAULT 'Free',
    activated_at     TIMESTAMP NULL DEFAULT NULL,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES jp_users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_pricing_settings (
    setting_id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ats_boost_price                   DECIMAL(10,2) NOT NULL DEFAULT 299.00,
    employer_normal_posting_price     DECIMAL(10,2) NOT NULL DEFAULT 999.00,
    hiring_assistant_starting_price   DECIMAL(10,2) NOT NULL DEFAULT 4999.00,
    premium_subscription_price        DECIMAL(10,2) NOT NULL DEFAULT 499.00,
    updated_at                        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by                        INT UNSIGNED NULL,
    CONSTRAINT fk_ps_updated_by FOREIGN KEY (updated_by) REFERENCES jp_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. CRM MODULE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jp_crm_lead_status (
    user_id         INT UNSIGNED PRIMARY KEY,
    status          ENUM('New','Contacted','Converted','Lost') NOT NULL DEFAULT 'New',
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by      INT UNSIGNED NULL,
    CONSTRAINT fk_cls_user FOREIGN KEY (user_id) REFERENCES jp_users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_cls_updated_by FOREIGN KEY (updated_by) REFERENCES jp_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_crm_notes (
    note_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    note_text       TEXT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED NULL,
    CONSTRAINT fk_cn_user FOREIGN KEY (user_id) REFERENCES jp_users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_cn_created_by FOREIGN KEY (created_by) REFERENCES jp_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jp_activity_log (
    activity_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description        TEXT NOT NULL,
    related_user_id    INT UNSIGNED NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jp_activity_log_created_at (created_at),
    CONSTRAINT fk_al_related_user FOREIGN KEY (related_user_id) REFERENCES jp_users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. SEED DATA — default pricing row so the app has values on first load
-- ------------------------------------------------------------
INSERT INTO jp_pricing_settings (ats_boost_price, employer_normal_posting_price, hiring_assistant_starting_price, premium_subscription_price)
SELECT 299.00, 999.00, 4999.00, 499.00
WHERE NOT EXISTS (SELECT 1 FROM jp_pricing_settings);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- NOTE ON THE ADMIN ACCOUNT
-- ============================================================
-- The admin login is intentionally NOT created by this file.
-- Run the included seed_admin.php once instead — it hashes the
-- password with PHP's password_hash() (bcrypt) before inserting
-- it, so a plain-text password is never written into the database
-- or into any SQL file you might back up, email, or commit.
-- ============================================================

-- ============================================================
-- SECURITY / IMPLEMENTATION NOTES
-- ============================================================
-- 1. All jp_* tables use InnoDB with real foreign keys and ON
--    DELETE CASCADE / SET NULL, matching the relationships in the
--    original schema — deleting a user correctly cleans up their
--    profile rows instead of leaving orphaned data.
-- 2. password_hash is sized for bcrypt/argon2 output (255 chars) —
--    never write a plain-text password into this column.
-- 3. resume_file_url expects a real storage path/URL (local disk
--    outside the web root, or S3/GCS). The front-end prototype
--    currently keeps the uploaded file only in browser memory —
--    that upload flow still needs to be built server-side.
-- 4. This file only creates structure + one pricing seed row. It
--    does not insert any job seekers, jobs, or other demo data.
-- 5. Nothing here touches or reads any wp_* table — the job
--    portal tables are fully independent, just co-located in the
--    same physical database.
-- ============================================================
