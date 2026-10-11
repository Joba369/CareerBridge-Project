<?php
/**
 * CareerBridge configuration
 * ------------------------------------------------------------------
 * Default values match a fresh XAMPP installation:
 *   MySQL/MariaDB user "root" with an empty password on port 3306.
 * Change them here if your setup is different.
 *
 * Never commit real production passwords. This file is only loaded on
 * the server (PHP) and is never sent to the browser.
 */

define('DB_HOST', getenv('CB_DB_HOST') ?: 'localhost');
define('DB_PORT', (int) (getenv('CB_DB_PORT') ?: 3306));
define('DB_NAME', getenv('CB_DB_NAME') ?: 'careerbridge');
define('DB_USER', getenv('CB_DB_USER') ?: 'root');
define('DB_PASS', getenv('CB_DB_PASS') !== false ? getenv('CB_DB_PASS') : '');

/**
 * Development mode.
 * true  = show the password-reset link on screen (XAMPP has no mail server
 *         configured by default) and show detailed database errors.
 * false = hide technical details. Configure real e-mail before using false
 *         with password recovery.
 */
define('APP_DEV_MODE', true);

/**
 * Base URL path of the project. Leave as null to auto-detect
 * (e.g. "/careerbridge" when the folder is htdocs/careerbridge).
 * Set manually if auto-detection fails, e.g. define('APP_BASE', '/careerbridge');
 */
define('APP_BASE', null);

define('APP_NAME', 'CareerBridge');
define('JOBS_PER_PAGE', 9);
define('MAX_CV_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB
