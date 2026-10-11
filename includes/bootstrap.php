<?php
/**
 * Loaded at the top of every page. Starts the session, connects to the
 * database and loads helper functions.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', APP_DEV_MODE ? '1' : '0');
date_default_timezone_set('Asia/Dhaka');

/* ---------- Session ---------- */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('cb_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    session_start();
}

/* ---------- Base URL ---------- */
function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    if (APP_BASE !== null) return $base = rtrim((string) APP_BASE, '/');
    $appRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $base = '';
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    }
    return $base = rtrim($base, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = __DIR__ . '/../assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

/* ---------- Database ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true, // allows re-using named placeholders; charset is set in the DSN so this stays safe
        ]);
        $pdo->exec("SET time_zone = '+06:00'");
    } catch (PDOException $ex) {
        render_setup_error($ex->getMessage());
    }
    return $pdo;
}

function render_setup_error(string $detail): void
{
    http_response_code(503);
    $install = e(url('install.php'));
    $msg = APP_DEV_MODE ? '<pre style="white-space:pre-wrap;background:#f6f3ef;padding:12px;border-radius:8px;font-size:13px">' . e($detail) . '</pre>' : '';
    echo <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CareerBridge – Setup required</title></head>
<body style="font-family:system-ui,sans-serif;background:#FBF8F4;color:#111C33;display:grid;place-items:center;min-height:100vh;margin:0;padding:20px">
<main style="max-width:560px;background:#fff;border:1px solid #E7E2DA;border-radius:14px;padding:32px">
<h1 style="margin-top:0;font-size:22px">Database setup required</h1>
<p>CareerBridge could not connect to the MySQL database <strong>"careerbridge"</strong>.</p>
<ol style="line-height:1.7"><li>Open the XAMPP Control Panel and start <strong>Apache</strong> and <strong>MySQL</strong>.</li>
<li>Run the installer: <a href="{$install}" style="color:#C64D0A">install.php</a> (or import <code>database/careerbridge.sql</code> in phpMyAdmin).</li>
<li>If your MySQL password is not empty, edit <code>includes/config.php</code>.</li></ol>
{$msg}
</main></body></html>
HTML;
    exit;
}

/* ---------- Output helpers ---------- */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    $target = preg_match('#^https?://#', $path) || str_starts_with($path, '/') ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/** Only allow redirects back into this site (prevents open redirects). */
function safe_return(?string $target, string $fallback = 'index.php'): string
{
    $target = (string) $target;
    if ($target !== '' && str_starts_with($target, '/') && !str_starts_with($target, '//')) {
        return $target;
    }
    return url($fallback);
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if (wants_json()) json_out(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
        http_response_code(419);
        flash('error', 'Your session expired or the form was submitted twice. Please try again.');
        redirect(safe_return($_SERVER['HTTP_REFERER'] ?? null) ?: 'index.php');
    }
}

/* ---------- Form helpers ---------- */
function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function keep_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['current_password'], $data['csrf']);
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function input_str(array $src, string $key, int $max = 255): string
{
    $v = $src[$key] ?? '';
    if (!is_string($v)) return '';
    $v = trim($v);
    return mb_substr($v, 0, $max);
}

function input_int(array $src, string $key, ?int $default = null): ?int
{
    $v = $src[$key] ?? null;
    if ($v === null || $v === '' || !is_scalar($v) || !preg_match('/^-?\d+$/', (string) $v)) return $default;
    return (int) $v;
}

function input_arr(array $src, string $key, array $allowed): array
{
    $v = $src[$key] ?? [];
    if (!is_array($v)) $v = [$v];
    return array_values(array_intersect(array_map('strval', $v), $allowed));
}

/* ---------- Auth ---------- */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) return $user;
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return $user = null;
    $st = db()->prepare('SELECT id, role, name, email, created_at FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch() ?: null;
    if (!$user) unset($_SESSION['user_id']);
    return $user;
}

function is_role(string $role): bool
{
    $u = current_user();
    return $u && $u['role'] === $role;
}

function require_login(?string $role = null): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please sign in to continue.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? url('index.php')));
    }
    if ($role && $u['role'] !== $role) {
        http_response_code(403);
        flash('error', $role === 'employer'
            ? 'That area is only available to employer accounts.'
            : 'That area is only available to job seeker accounts.');
        redirect($u['role'] === 'employer' ? 'employer/index.php' : 'account/index.php');
    }
    return $u;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
}

function employer_company(int $userId): ?array
{
    $st = db()->prepare('SELECT * FROM companies WHERE user_id = ?');
    $st->execute([$userId]);
    return $st->fetch() ?: null;
}

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/jobs.php';
require_once __DIR__ . '/ui.php';

/** Require an employer account and return [user, company]. Creates an empty company row if missing. */
function employer_context(): array
{
    $user = require_login('employer');
    $company = employer_company((int) $user['id']);
    if (!$company) {
        db()->prepare('INSERT INTO companies (user_id, name) VALUES (?, ?)')->execute([$user['id'], $user['name']]);
        $company = employer_company((int) $user['id']);
    }
    return [$user, $company];
}

/** Load a job only if it belongs to the given company (permission check). */
function owned_job(int $jobId, int $companyId): ?array
{
    $st = db()->prepare('SELECT * FROM jobs WHERE id = ? AND company_id = ?');
    $st->execute([$jobId, $companyId]);
    return $st->fetch() ?: null;
}
