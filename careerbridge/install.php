<?php
/**
 * CareerBridge one-click installer.
 * Creates the database and imports database/careerbridge.sql (schema + demo data).
 * Alternative: import the same file manually with phpMyAdmin (see README.md).
 *
 * Safety: once installed, re-installing (which ERASES all data) is only
 * possible while APP_DEV_MODE is true and after ticking a confirmation box.
 * Delete this file before sharing the site publicly.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
session_start();

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$checks = [];
$checks[] = ['PHP version 8.0 or newer', version_compare(PHP_VERSION, '8.0.0', '>='), 'You have PHP ' . PHP_VERSION];
$checks[] = ['PDO MySQL extension', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'Loaded' : 'Enable extension=pdo_mysql in php.ini'];
$checks[] = ['mbstring extension', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'Loaded' : 'Enable extension=mbstring in php.ini'];
$checks[] = ['fileinfo extension (CV uploads)', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'Loaded' : 'Optional – enable extension=fileinfo for stricter PDF checks'];
$uploadDir = __DIR__ . '/uploads/cvs';
$checks[] = ['uploads/cvs folder is writable', is_dir($uploadDir) && is_writable($uploadDir), is_writable($uploadDir) ? 'OK' : 'Make the folder writable for the web server'];
$sqlFile = __DIR__ . '/database/careerbridge.sql';
$checks[] = ['database/careerbridge.sql present', is_file($sqlFile), is_file($sqlFile) ? number_format((int) filesize($sqlFile)) . ' bytes' : 'File missing'];

$server = null; $serverError = ''; $installed = false; $counts = [];
try {
    $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $checks[] = ['Connect to MySQL / MariaDB', true, 'Connected to ' . DB_HOST . ':' . DB_PORT . ' as "' . DB_USER . '" (server ' . $server->getAttribute(PDO::ATTR_SERVER_VERSION) . ')'];
    $st = $server->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name IN ("users","jobs","companies")');
    $st->execute([DB_NAME]);
    $installed = (int) $st->fetchColumn() === 3;
    if ($installed) {
        foreach (['users', 'companies', 'jobs', 'applications', 'guides'] as $t) {
            $counts[$t] = (int) $server->query('SELECT COUNT(*) FROM `' . str_replace('`', '', DB_NAME) . "`.`$t`")->fetchColumn();
        }
    }
} catch (PDOException $ex) {
    $serverError = $ex->getMessage();
    $checks[] = ['Connect to MySQL / MariaDB', false, 'Could not connect. Start MySQL in the XAMPP Control Panel and check includes/config.php.'];
}

if (empty($_SESSION['install_token'])) $_SESSION['install_token'] = bin2hex(random_bytes(16));
$message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['install_token'], (string) ($_POST['token'] ?? ''))) {
        $error = 'The form expired. Please try again.';
    } elseif (!$server) {
        $error = 'Cannot install: no database connection.';
    } elseif ($installed && (!APP_DEV_MODE || empty($_POST['confirm_reset']))) {
        $error = APP_DEV_MODE ? 'Tick the confirmation box to erase and re-install the database.' : 'Re-installing is disabled because APP_DEV_MODE is false.';
    } else {
        try {
            $sql = (string) file_get_contents($sqlFile);
            $dbName = str_replace('`', '', DB_NAME);
            $sql = str_replace(['CREATE DATABASE IF NOT EXISTS `careerbridge`', 'USE `careerbridge`'], ["CREATE DATABASE IF NOT EXISTS `$dbName`", "USE `$dbName`"], $sql);
            // Statements in the generated file end with ";" + newline (newlines inside values are escaped).
            $statements = preg_split('/;\s*\n/', $sql);
            $n = 0;
            foreach ($statements as $stmt) {
                $lines = array_filter(explode("\n", $stmt), fn($l) => !preg_match('/^\s*(--|$)/', $l));
                $clean = trim(implode("\n", $lines));
                if ($clean === '') continue;
                $server->exec($clean);
                $n++;
            }
            $message = "Installation complete: $n SQL statements executed. You can now open CareerBridge.";
            $installed = true;
            foreach (['users', 'companies', 'jobs', 'applications', 'guides'] as $t) {
                $counts[$t] = (int) $server->query("SELECT COUNT(*) FROM `$dbName`.`$t`")->fetchColumn();
            }
            foreach (glob($uploadDir . '/*.pdf') ?: [] as $f) @unlink($f); // old uploads belong to erased applications
        } catch (Throwable $ex) {
            $error = 'Import failed: ' . $ex->getMessage();
        }
    }
}
$allOk = !in_array(false, array_map(fn($c) => $c[1] || str_starts_with($c[0], 'fileinfo'), $checks), true);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install CareerBridge</title>
<meta name="robots" content="noindex">
<style>
  body { margin: 0; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #fbf8f4; color: #111c33; line-height: 1.55; }
  main { max-width: 760px; margin: 48px auto; padding: 0 20px; }
  .card { background: #fff; border: 1px solid #e7e2da; border-radius: 14px; padding: 28px; margin-bottom: 20px; }
  h1 { font-size: 1.7rem; margin: 0 0 6px; } h2 { font-size: 1.1rem; margin: 0 0 14px; }
  .brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.2rem; margin-bottom: 24px; }
  .mark { width: 36px; height: 36px; border-radius: 10px; background: #f07a2a; display: grid; place-items: center; color: #fff; }
  table { width: 100%; border-collapse: collapse; font-size: .93rem; } td { padding: 10px 6px; border-top: 1px solid #f0ebe4; vertical-align: top; }
  .ok { color: #157a3e; font-weight: 700; } .bad { color: #b42318; font-weight: 700; } .muted { color: #5a6374; font-size: .88rem; }
  .btn { display: inline-flex; align-items: center; gap: 8px; height: 46px; padding: 0 20px; border-radius: 10px; border: 0; background: #c64d0a; color: #fff; font: 600 1rem inherit; cursor: pointer; text-decoration: none; }
  .btn:hover { background: #a63f07; } .btn.secondary { background: #fff; color: #111c33; border: 1px solid #e7e2da; }
  .btn:focus-visible, input:focus-visible { outline: 3px solid #f07a2a; outline-offset: 2px; }
  .alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 18px; } .alert.ok { background: #e8f6ee; border: 1px solid #bfe3cd; font-weight: 500; } .alert.bad { background: #fdecea; border: 1px solid #f4c5bf; font-weight: 500; }
  code { background: #f5efe7; padding: 1px 6px; border-radius: 5px; font-size: .9em; }
  pre { white-space: pre-wrap; background: #f6f3ef; padding: 12px; border-radius: 8px; font-size: 13px; }
  label { display: flex; gap: 10px; align-items: flex-start; margin: 12px 0 18px; font-size: .93rem; }
  .row { display: flex; gap: 10px; flex-wrap: wrap; }
</style>
</head>
<body>
<main>
  <div class="brand"><span class="mark" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></span> CareerBridge setup</div>

  <?php if ($message): ?><div class="alert ok" role="status"><?= h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert bad" role="alert"><?= h($error) ?></div><?php endif; ?>

  <div class="card">
    <h1>Install the database</h1>
    <p class="muted">This creates the <code><?= h(DB_NAME) ?></code> database and imports the tables plus clearly labelled demo data from <code>database/careerbridge.sql</code>. Settings are read from <code>includes/config.php</code>.</p>
    <h2>System check</h2>
    <table>
      <?php foreach ($checks as [$label, $ok, $detail]): ?>
        <tr><td style="width:28px" class="<?= $ok ? 'ok' : 'bad' ?>" aria-label="<?= $ok ? 'Passed' : 'Problem' ?>"><?= $ok ? '✓' : '✕' ?></td><td><strong><?= h($label) ?></strong><div class="muted"><?= h($detail) ?></div></td></tr>
      <?php endforeach; ?>
    </table>
    <?php if ($serverError && APP_DEV_MODE): ?><pre><?= h($serverError) ?></pre><?php endif; ?>
  </div>

  <div class="card">
    <?php if ($installed): ?>
      <h2>Status: installed</h2>
      <p class="muted">Found <?= (int) ($counts['users'] ?? 0) ?> users, <?= (int) ($counts['companies'] ?? 0) ?> companies, <?= (int) ($counts['jobs'] ?? 0) ?> jobs, <?= (int) ($counts['applications'] ?? 0) ?> applications and <?= (int) ($counts['guides'] ?? 0) ?> guides.</p>
      <div class="row" style="margin-bottom:18px">
        <a class="btn" href="<?= h($base . '/index.php') ?>">Open CareerBridge</a>
        <a class="btn secondary" href="<?= h($base . '/login.php') ?>">Sign in</a>
      </div>
      <p class="muted">Demo accounts (password <code>Demo@1234</code>): <code>seeker@demo.test</code> (job seeker) and <code>employer@demo.test</code> (employer).</p>
      <?php if (APP_DEV_MODE): ?>
        <hr style="border:0;border-top:1px solid #f0ebe4;margin:22px 0">
        <h2>Reset to fresh demo data</h2>
        <form method="post">
          <input type="hidden" name="token" value="<?= h($_SESSION['install_token']) ?>">
          <label><input type="checkbox" name="confirm_reset" value="1"> I understand this <strong>erases all accounts, jobs and applications</strong> in <code><?= h(DB_NAME) ?></code> and restores the demo data.</label>
          <button class="btn secondary" type="submit">Erase &amp; re-install</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <h2>Ready to install</h2>
      <form method="post">
        <input type="hidden" name="token" value="<?= h($_SESSION['install_token']) ?>">
        <button class="btn" type="submit"<?= $allOk ? '' : ' disabled aria-disabled="true"' ?>>Install CareerBridge</button>
        <?php if (!$allOk): ?><p class="muted">Fix the problems above first, then reload this page.</p><?php endif; ?>
      </form>
    <?php endif; ?>
    <p class="muted" style="margin-top:20px"><strong>Security:</strong> delete <code>install.php</code> (or set <code>APP_DEV_MODE</code> to <code>false</code>) before making the site public with ngrok or Cloudflare Tunnel.</p>
  </div>
</main>
</body>
</html>
