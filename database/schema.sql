-- ---------------------------------------------------------------------
-- CareerBridge database schema
-- Compatible with MySQL 5.7+/8.x and MariaDB 10.3+ (XAMPP).
-- ---------------------------------------------------------------------
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS tracker_entries;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS saved_jobs;
DROP TABLE IF EXISTS cvs;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS seeker_profiles;
DROP TABLE IF EXISTS guides;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Accounts for both job seekers and employers --------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role          ENUM('seeker','employer') NOT NULL,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_demo       TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login    DATETIME NULL,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job seeker profile (used by Smart Job Matching) ----------------------
CREATE TABLE seeker_profiles (
  user_id                   INT UNSIGNED PRIMARY KEY,
  headline                  VARCHAR(150) NULL,
  phone                     VARCHAR(30) NULL,
  division                  VARCHAR(40) NULL,
  district                  VARCHAR(40) NULL,
  area                      VARCHAR(80) NULL,
  education_level           TINYINT UNSIGNED NULL,
  education_detail          VARCHAR(200) NULL,
  experience_level          ENUM('fresher','junior','mid','senior') NOT NULL DEFAULT 'fresher',
  skills                    TEXT NULL,
  preferred_category_id     INT UNSIGNED NULL,
  preferred_employment_type VARCHAR(20) NULL,
  preferred_arrangement     ENUM('any','onsite','remote','hybrid') NOT NULL DEFAULT 'any',
  expected_salary           INT UNSIGNED NULL,
  accessibility_prefs       VARCHAR(255) NULL COMMENT 'Optional, voluntary. Only used to pre-fill accessibility filters. Never used for matching.',
  bio                       TEXT NULL,
  updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One company per employer account ------------------------------------
CREATE TABLE companies (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  name        VARCHAR(150) NOT NULL,
  industry    VARCHAR(100) NULL,
  size        VARCHAR(40) NULL,
  division    VARCHAR(40) NULL,
  district    VARCHAR(40) NULL,
  address     VARCHAR(255) NULL,
  website     VARCHAR(255) NULL,
  phone       VARCHAR(30) NULL,
  description TEXT NULL,
  is_demo     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_company_user (user_id),
  CONSTRAINT fk_company_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80) NOT NULL,
  slug       VARCHAR(80) NOT NULL,
  grp        ENUM('local','it','general') NOT NULL DEFAULT 'general',
  icon       VARCHAR(30) NOT NULL DEFAULT 'briefcase',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job listings --------------------------------------------------------
CREATE TABLE jobs (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id         INT UNSIGNED NOT NULL,
  category_id        INT UNSIGNED NOT NULL,
  title              VARCHAR(150) NOT NULL,
  description        TEXT NOT NULL,
  responsibilities   TEXT NULL,
  requirements       TEXT NULL,
  how_to_apply       TEXT NULL,
  employment_type    ENUM('full_time','part_time','temporary','shift','internship','contract') NOT NULL,
  work_arrangement   ENUM('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite',
  experience_level   ENUM('fresher','junior','mid','senior') NOT NULL DEFAULT 'fresher',
  education_level    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  division           VARCHAR(40) NULL,
  district           VARCHAR(40) NULL,
  area               VARCHAR(80) NULL,
  salary_min         INT UNSIGNED NULL,
  salary_max         INT UNSIGNED NULL,
  salary_period      ENUM('month','hour','day','shift','project') NOT NULL DEFAULT 'month',
  working_hours      VARCHAR(120) NULL,
  shift              ENUM('morning','day','evening','night','weekend','flexible') NULL,
  vacancies          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  required_skills    VARCHAR(500) NULL,
  preferred_skills   VARCHAR(500) NULL,
  deadline           DATE NULL,
  status             ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
  acc_step_free      TINYINT(1) NOT NULL DEFAULT 0,
  acc_lift           TINYINT(1) NOT NULL DEFAULT 0,
  acc_toilet         TINYINT(1) NOT NULL DEFAULT 0,
  acc_flexible_hours TINYINT(1) NOT NULL DEFAULT 0,
  acc_remote_option  TINYINT(1) NOT NULL DEFAULT 0,
  acc_interview      TINYINT(1) NOT NULL DEFAULT 0,
  acc_notes          TEXT NULL,
  is_featured        TINYINT(1) NOT NULL DEFAULT 0,
  is_demo            TINYINT(1) NOT NULL DEFAULT 0,
  views              INT UNSIGNED NOT NULL DEFAULT 0,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_jobs_status_deadline (status, deadline),
  KEY idx_jobs_company (company_id),
  KEY idx_jobs_category (category_id),
  KEY idx_jobs_location (division, district),
  KEY idx_jobs_type (employment_type, work_arrangement),
  KEY idx_jobs_created (created_at),
  CONSTRAINT fk_job_company  FOREIGN KEY (company_id)  REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_job_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_jobs (
  user_id    INT UNSIGNED NOT NULL,
  job_id     INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, job_id),
  KEY idx_saved_job (job_id),
  CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_saved_job  FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CV Writing & Editing Hub (one CV per job seeker, stored as JSON text) --
CREATE TABLE cvs (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  template   VARCHAR(20) NOT NULL DEFAULT 'classic',
  data       LONGTEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cv_user (user_id),
  CONSTRAINT fk_cv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Applications submitted through CareerBridge (visible to the employer) --
CREATE TABLE applications (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id                  INT UNSIGNED NOT NULL,
  user_id                 INT UNSIGNED NOT NULL,
  full_name               VARCHAR(120) NOT NULL,
  email                   VARCHAR(190) NOT NULL,
  phone                   VARCHAR(30) NOT NULL,
  education_summary       VARCHAR(255) NULL,
  skills                  VARCHAR(500) NULL,
  cover_letter            TEXT NULL,
  accommodation_request   TEXT NULL COMMENT 'Optional interview accommodation request. Visible only to the job owner.',
  cv_snapshot             LONGTEXT NULL COMMENT 'Copy of the CV builder data at the time of applying',
  cv_template             VARCHAR(20) NULL,
  cv_file                 VARCHAR(120) NULL COMMENT 'Stored file name in uploads/cvs (PDF)',
  status                  ENUM('submitted','under_review','shortlisted','interview','offer','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
  interview_at            DATETIME NULL,
  employer_note           TEXT NULL COMMENT 'Private to the employer',
  created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_application (job_id, user_id),
  KEY idx_app_user (user_id),
  KEY idx_app_status (status),
  CONSTRAINT fk_app_job  FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE,
  CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Private application tracker for job seekers ---------------------------
CREATE TABLE tracker_entries (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  application_id INT UNSIGNED NULL,
  job_id         INT UNSIGNED NULL,
  job_title      VARCHAR(150) NOT NULL,
  company        VARCHAR(150) NOT NULL,
  location       VARCHAR(120) NULL,
  job_url        VARCHAR(255) NULL,
  applied_on     DATE NOT NULL,
  status         ENUM('applied','under_review','interview','offer','rejected','withdrawn','no_response') NOT NULL DEFAULT 'applied',
  interview_at   DATETIME NULL,
  notes          TEXT NULL COMMENT 'Private notes, visible only to the owner',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tracker_application (application_id),
  KEY idx_tracker_user_status (user_id, status),
  KEY idx_tracker_interview (user_id, interview_at),
  CONSTRAINT fk_tracker_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tracker_app  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
  CONSTRAINT fk_tracker_job  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fresher career guidance articles -------------------------------------
CREATE TABLE guides (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(120) NOT NULL,
  title        VARCHAR(180) NOT NULL,
  career_path  VARCHAR(60) NOT NULL,
  level        ENUM('Beginner','Intermediate') NOT NULL DEFAULT 'Beginner',
  type         VARCHAR(40) NOT NULL,
  summary      VARCHAR(300) NOT NULL,
  read_minutes TINYINT UNSIGNED NOT NULL DEFAULT 5,
  content      LONGTEXT NOT NULL,
  sort_order   SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_guide_slug (slug),
  KEY idx_guide_path (career_path, level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reset_token (token_hash),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
