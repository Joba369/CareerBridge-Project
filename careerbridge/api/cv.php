<?php
/** Save / load the signed-in job seeker's CV builder data (JSON). */
require __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
if (!$user) json_out(['ok' => false, 'error' => 'Please sign in to save your CV to your account.'], 401);
if ($user['role'] !== 'seeker') json_out(['ok' => false, 'error' => 'The CV builder is available for job seeker accounts.'], 403);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $st = db()->prepare('SELECT template, data, updated_at FROM cvs WHERE user_id = ?');
    $st->execute([$user['id']]);
    $row = $st->fetch();
    json_out(['ok' => true, 'cv' => $row ? ['template' => $row['template'], 'data' => json_decode($row['data'], true), 'updated_at' => $row['updated_at']] : null]);
}

if (!is_post()) json_out(['ok' => false, 'error' => 'Method not allowed'], 405);

$raw = file_get_contents('php://input');
if (strlen($raw) > 300000) json_out(['ok' => false, 'error' => 'Your CV is too large to save (limit 300 KB of text).'], 413);
$payload = json_decode($raw, true);
if (!is_array($payload)) json_out(['ok' => false, 'error' => 'Invalid data sent.'], 400);

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf'] ?? '');
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    json_out(['ok' => false, 'error' => 'Your session expired. Please refresh the page (your work is kept in this browser).'], 419);
}

$templates = ['classic', 'modern', 'minimal', 'compact'];
$template = in_array($payload['template'] ?? '', $templates, true) ? $payload['template'] : 'classic';
$data = $payload['data'] ?? null;
if (!is_array($data) || !isset($data['personal']) || !is_array($data['personal'])) {
    json_out(['ok' => false, 'error' => 'CV data is incomplete.'], 422);
}

/* Keep only plain strings / booleans / arrays – drop anything unexpected. */
function cv_clean($v, int $depth = 0)
{
    if ($depth > 5) return null;
    if (is_array($v)) {
        $out = [];
        foreach ($v as $k => $item) {
            if (!is_int($k) && !preg_match('/^[a-zA-Z_]{1,30}$/', (string) $k)) continue;
            $c = cv_clean($item, $depth + 1);
            if ($c !== null) $out[$k] = $c;
        }
        return $out;
    }
    if (is_bool($v)) return $v;
    if (is_string($v) || is_int($v) || is_float($v)) return mb_substr((string) $v, 0, 4000);
    return null;
}
$json = json_encode(cv_clean($data), JSON_UNESCAPED_UNICODE);

db()->prepare('INSERT INTO cvs (user_id, template, data) VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE template = VALUES(template), data = VALUES(data), updated_at = NOW()')
    ->execute([$user['id'], $template, $json]);

json_out(['ok' => true, 'saved_at' => date('g:i a')]);
