<?php
/** Streams an uploaded CV PDF to the applicant or to the employer who owns the job. */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$id = (int) ($_GET['application'] ?? 0);
$st = db()->prepare('SELECT a.cv_file, a.user_id, a.full_name, c.user_id AS owner_id FROM applications a JOIN jobs j ON j.id = a.job_id JOIN companies c ON c.id = j.company_id WHERE a.id = ?');
$st->execute([$id]);
$row = $st->fetch();
$allowed = $row && ((int) $row['user_id'] === (int) $user['id'] || (int) $row['owner_id'] === (int) $user['id']);
$path = $row && $row['cv_file'] ? __DIR__ . '/uploads/cvs/' . basename($row['cv_file']) : '';
if (!$allowed || !$path || !is_file($path)) {
    http_response_code(404);
    exit('File not found or you do not have permission to view it.');
}
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="CV-' . preg_replace('/[^A-Za-z0-9]+/', '-', $row['full_name']) . '.pdf"');
header('X-Content-Type-Options: nosniff');
readfile($path);
