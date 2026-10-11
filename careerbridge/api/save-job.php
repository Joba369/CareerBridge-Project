<?php
/** Toggle a saved job for the signed-in job seeker. Works with fetch (JSON) or a normal form post. */
require __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf();

$user = current_user();
if (!$user) {
    if (wants_json()) json_out(['ok' => false, 'error' => 'Please sign in to save jobs.'], 401);
    redirect('login.php');
}
if ($user['role'] !== 'seeker') {
    if (wants_json()) json_out(['ok' => false, 'error' => 'Only job seeker accounts can save jobs.'], 403);
    flash('error', 'Only job seeker accounts can save jobs.');
    redirect('index.php');
}

$jobId = (int) ($_POST['job_id'] ?? 0);
$st = db()->prepare("SELECT id FROM jobs WHERE id = ? AND status <> 'draft'");
$st->execute([$jobId]);
if (!$st->fetch()) {
    if (wants_json()) json_out(['ok' => false, 'error' => 'Job not found.'], 404);
    flash('error', 'That job could not be found.');
    redirect('jobs.php');
}

$exists = db()->prepare('SELECT 1 FROM saved_jobs WHERE user_id = ? AND job_id = ?');
$exists->execute([$user['id'], $jobId]);
if ($exists->fetch()) {
    db()->prepare('DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?')->execute([$user['id'], $jobId]);
    $saved = false;
} else {
    db()->prepare('INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)')->execute([$user['id'], $jobId]);
    $saved = true;
}

if (wants_json()) json_out(['ok' => true, 'saved' => $saved, 'job_id' => $jobId]);
flash('success', $saved ? 'Job saved.' : 'Job removed from your saved list.');
redirect(safe_return($_POST['return'] ?? null, 'account/saved.php'));
