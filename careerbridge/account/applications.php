<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login('seeker');
$uid = (int) $user['id'];
$statuses = tracker_statuses();
$errors = [];
$addOpen = false;

/** Parse an HTML datetime-local value. */
function parse_dt(?string $v): ?string
{
    $v = trim((string) $v);
    if ($v === '') return null;
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $v) ?: DateTime::createFromFormat('Y-m-d H:i:s', $v);
    return $d ? $d->format('Y-m-d H:i:s') : false;
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $entryId = (int) ($_POST['id'] ?? 0);

    if ($action === 'add') {
        $in = [
            'job_title' => input_str($_POST, 'job_title', 150),
            'company' => input_str($_POST, 'company', 150),
            'location' => input_str($_POST, 'location', 120),
            'job_url' => input_str($_POST, 'job_url', 255),
            'applied_on' => input_str($_POST, 'applied_on', 10),
            'status' => array_key_exists($_POST['status'] ?? '', $statuses) ? $_POST['status'] : 'applied',
            'notes' => input_str($_POST, 'notes', 3000),
        ];
        $iv = parse_dt($_POST['interview_at'] ?? '');
        if ($in['job_title'] === '') $errors['job_title'] = 'Enter the job title.';
        if ($in['company'] === '') $errors['company'] = 'Enter the company or employer name.';
        $d = DateTime::createFromFormat('Y-m-d', $in['applied_on']);
        if (!$d || $d->format('Y-m-d') !== $in['applied_on']) $errors['applied_on'] = 'Enter a valid date.';
        elseif ($d > new DateTime('today')) $errors['applied_on'] = 'The application date cannot be in the future.';
        if ($in['job_url'] !== '' && !filter_var($in['job_url'], FILTER_VALIDATE_URL)) $errors['job_url'] = 'Enter a full link starting with http:// or https://';
        if ($iv === false) $errors['interview_at'] = 'Enter a valid interview date and time.';
        if (!$errors) {
            db()->prepare('INSERT INTO tracker_entries (user_id, job_title, company, location, job_url, applied_on, status, interview_at, notes) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$uid, $in['job_title'], $in['company'], $in['location'] ?: null, $in['job_url'] ?: null, $in['applied_on'], $in['status'], $iv ?: null, $in['notes'] ?: null]);
            flash('success', 'Application added to your tracker.');
            redirect('account/applications.php');
        }
        keep_old($in + ['interview_at' => $_POST['interview_at'] ?? '']);
        $addOpen = true;
    } else {
        // All other actions require that the entry belongs to the signed-in user.
        $st = db()->prepare('SELECT * FROM tracker_entries WHERE id = ? AND user_id = ?');
        $st->execute([$entryId, $uid]);
        $entry = $st->fetch();
        if (!$entry) {
            flash('error', 'That application record was not found.');
            redirect('account/applications.php');
        }
        if ($action === 'update') {
            $status = array_key_exists($_POST['status'] ?? '', $statuses) ? $_POST['status'] : $entry['status'];
            $iv = parse_dt($_POST['interview_at'] ?? '');
            if ($iv === false) {
                flash('error', 'The interview date was not valid, so changes were not saved.');
            } else {
                $title = $entry['application_id'] ? $entry['job_title'] : (input_str($_POST, 'job_title', 150) ?: $entry['job_title']);
                $company = $entry['application_id'] ? $entry['company'] : (input_str($_POST, 'company', 150) ?: $entry['company']);
                db()->prepare('UPDATE tracker_entries SET job_title = ?, company = ?, status = ?, interview_at = ?, notes = ? WHERE id = ? AND user_id = ?')
                    ->execute([$title, $company, $status, $iv ?: null, input_str($_POST, 'notes', 3000) ?: null, $entryId, $uid]);
                flash('success', 'Application updated.');
            }
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM tracker_entries WHERE id = ? AND user_id = ?')->execute([$entryId, $uid]);
            flash('success', 'The record was removed from your tracker.' . ($entry['application_id'] ? ' Your application to the employer was not withdrawn.' : ''));
        } elseif ($action === 'withdraw' && $entry['application_id']) {
            db()->prepare("UPDATE applications SET status = 'withdrawn' WHERE id = ? AND user_id = ?")->execute([$entry['application_id'], $uid]);
            db()->prepare("UPDATE tracker_entries SET status = 'withdrawn' WHERE id = ? AND user_id = ?")->execute([$entryId, $uid]);
            flash('success', 'Your application was withdrawn. The employer will see it as withdrawn.');
        }
        redirect(safe_return($_POST['return'] ?? null, 'account/applications.php'));
    }
}

$filter = array_key_exists($_GET['status'] ?? '', $statuses) ? $_GET['status'] : '';
$counts = db()->prepare('SELECT status, COUNT(*) n FROM tracker_entries WHERE user_id = ? GROUP BY status');
$counts->execute([$uid]);
$counts = $counts->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);

$sql = 'SELECT t.*, a.status AS employer_status, a.interview_at AS employer_interview FROM tracker_entries t
        LEFT JOIN applications a ON a.id = t.application_id AND a.user_id = t.user_id
        WHERE t.user_id = ?' . ($filter ? ' AND t.status = ?' : '') . ' ORDER BY t.applied_on DESC, t.id DESC';
$st = db()->prepare($sql);
$st->execute($filter ? [$uid, $filter] : [$uid]);
$entries = $st->fetchAll();

$up = db()->prepare('SELECT * FROM tracker_entries WHERE user_id = ? AND interview_at >= NOW() ORDER BY interview_at LIMIT 5');
$up->execute([$uid]);
$upcoming = $up->fetchAll();
$appStatuses = application_statuses();

$title = 'Application tracker';
$dashActive = 'tracker';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1>Application tracker</h1><p>Your private record of every application. Notes are visible only to you.</p></div>
      <a class="btn btn-primary" href="#add" data-open-add><?= icon('plus', '', 18) ?> Add application</a>
    </div>

    <div class="stat-grid">
      <div class="card stat"><div class="k"><?= icon('checks', '', 16) ?> Total</div><div class="v"><?= $total ?></div></div>
      <div class="card stat"><div class="k"><?= icon('clock', '', 16) ?> In progress</div><div class="v"><?= ($counts['applied'] ?? 0) + ($counts['under_review'] ?? 0) + ($counts['no_response'] ?? 0) ?></div></div>
      <div class="card stat"><div class="k"><?= icon('calendar', '', 16) ?> Interviews</div><div class="v"><?= $counts['interview'] ?? 0 ?></div></div>
      <div class="card stat"><div class="k"><?= icon('star', '', 16) ?> Offers</div><div class="v"><?= $counts['offer'] ?? 0 ?></div></div>
    </div>

    <?php if ($upcoming): ?>
      <section class="card mb-4" aria-labelledby="up-title">
        <div class="panel-head"><h2 id="up-title"><?= icon('calendar', 'text-brand', 18) ?> Upcoming interviews</h2></div>
        <ul class="list-plain">
          <?php foreach ($upcoming as $t): $dl = days_left(substr($t['interview_at'], 0, 10)); ?>
            <li><div style="flex:1;min-width:0"><strong class="small" style="display:block"><?= e($t['job_title']) ?></strong><span class="small muted"><?= e($t['company']) ?></span></div>
              <span class="small" style="text-align:right"><strong><?= e(format_date($t['interview_at'], 'D, j M · g:i A')) ?></strong><br><span class="muted"><?= $dl === 0 ? 'Today' : ($dl === 1 ? 'Tomorrow' : "In $dl days") ?></span></span></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <details class="card card-pad mb-4" id="add" <?= $addOpen ? 'open' : '' ?>>
      <summary style="cursor:pointer;font-weight:700;font-family:var(--font-head);display:flex;align-items:center;gap:8px"><?= icon('plus', 'text-brand', 18) ?> Record an application made outside CareerBridge</summary>
      <p class="small muted mt-2">Applications you submit through CareerBridge are added automatically.</p>
      <?php if ($errors): ?><div class="alert alert-error mb-3" role="alert"><?= icon('alert', '', 18) ?><div>Please fix the highlighted fields.</div></div><?php endif; ?>
      <form method="post" action="" novalidate data-validate>
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div class="form-row">
          <div class="field"><label class="label" for="a-title">Job title <span class="req" aria-hidden="true">*</span></label><input class="input<?= invalid($errors, 'job_title') ?>" id="a-title" name="job_title" required maxlength="150" value="<?= e(old('job_title')) ?>"><?= field_error($errors, 'job_title') ?></div>
          <div class="field"><label class="label" for="a-company">Company <span class="req" aria-hidden="true">*</span></label><input class="input<?= invalid($errors, 'company') ?>" id="a-company" name="company" required maxlength="150" value="<?= e(old('company')) ?>"><?= field_error($errors, 'company') ?></div>
        </div>
        <div class="form-row">
          <div class="field"><label class="label" for="a-loc">Location <span class="opt">(optional)</span></label><input class="input" id="a-loc" name="location" maxlength="120" value="<?= e(old('location')) ?>"></div>
          <div class="field"><label class="label" for="a-url">Job link <span class="opt">(optional)</span></label><input class="input<?= invalid($errors, 'job_url') ?>" id="a-url" name="job_url" type="url" placeholder="https://" value="<?= e(old('job_url')) ?>"><?= field_error($errors, 'job_url') ?></div>
        </div>
        <div class="form-row-3">
          <div class="field"><label class="label" for="a-date">Date applied <span class="req" aria-hidden="true">*</span></label><input class="input<?= invalid($errors, 'applied_on') ?>" id="a-date" name="applied_on" type="date" required max="<?= date('Y-m-d') ?>" value="<?= e(old('applied_on', date('Y-m-d'))) ?>"><?= field_error($errors, 'applied_on') ?></div>
          <div class="field"><label class="label" for="a-status">Status</label><select class="select" id="a-status" name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?= e($k) ?>"<?= old('status', 'applied') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label class="label" for="a-iv">Interview date <span class="opt">(if any)</span></label><input class="input<?= invalid($errors, 'interview_at') ?>" id="a-iv" name="interview_at" type="datetime-local" value="<?= e(old('interview_at')) ?>"><?= field_error($errors, 'interview_at') ?></div>
        </div>
        <div class="field"><label class="label" for="a-notes">Private notes <span class="opt">(only you can see these)</span></label><textarea class="textarea" id="a-notes" name="notes" maxlength="3000" style="min-height:80px"><?= e(old('notes')) ?></textarea></div>
        <button class="btn btn-primary" type="submit">Add to tracker</button>
      </form>
    </details>
    <?php clear_old(); ?>

    <nav class="pills" aria-label="Filter by status">
      <a href="?"<?= $filter === '' ? ' aria-current="page"' : '' ?>>All (<?= $total ?>)</a>
      <?php foreach ($statuses as $k => $l): ?><a href="?status=<?= e($k) ?>"<?= $filter === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a><?php endforeach; ?>
    </nav>

    <?php if ($filter === 'no_response'): ?>
      <div class="notice mb-3"><?= icon('info', '', 18) ?><div><strong>No response yet doesn't mean rejection.</strong> Employers can take several weeks to reply. A polite follow-up after 7–10 working days is fine.</div></div>
    <?php endif; ?>

    <?php if (!$entries): ?>
      <?= empty_state($filter ? 'No applications with this status' : 'No applications recorded yet', $filter ? 'Try another status filter.' : 'Apply through CareerBridge or add applications you made elsewhere to keep everything in one place.', '<a class="btn btn-primary" href="' . e(url('jobs.php')) . '">Find jobs</a>', 'checks') ?>
    <?php endif; ?>

    <?php foreach ($entries as $t): $stale = $t['status'] === 'applied' && days_left($t['applied_on']) <= -14; ?>
      <article class="card track-card">
        <div class="track-top">
          <div style="min-width:0;flex:1">
            <h3><?php if ($t['job_id']): ?><a href="<?= e(url('job.php?id=' . $t['job_id'])) ?>" style="color:inherit"><?= e($t['job_title']) ?></a><?php else: ?><?= e($t['job_title']) ?><?php endif; ?></h3>
            <p class="small muted mb-0"><?= e($t['company']) ?><?= $t['location'] ? ' · ' . e($t['location']) : '' ?> · Applied <?= e(format_date($t['applied_on'])) ?></p>
          </div>
          <div class="flex gap-2 wrap items-center">
            <?= badge($statuses[$t['status']], status_tone($t['status'])) ?>
            <?php if ($t['application_id']): ?><span class="badge badge-brand" title="Submitted through CareerBridge"><?= icon('link', '', 12) ?> Via CareerBridge</span><?php endif; ?>
          </div>
        </div>
        <?php if ($t['application_id'] && $t['employer_status']): ?>
          <p class="small mt-2 mb-0"><span class="muted">Employer's status:</span> <strong><?= e($appStatuses[$t['employer_status']]) ?></strong><?php if ($t['employer_interview']): ?> · <span class="muted">Interview set by employer:</span> <strong><?= e(format_date($t['employer_interview'], 'D, j M · g:i A')) ?></strong><?php endif; ?></p>
        <?php endif; ?>
        <?php if ($t['interview_at']): ?><p class="small mt-2 mb-0"><?= icon('calendar', 'text-brand', 14) ?> Interview: <strong><?= e(format_date($t['interview_at'], 'l, j M Y · g:i A')) ?></strong></p><?php endif; ?>
        <?php if ($t['job_url'] && !$t['job_id']): ?><p class="small mt-2 mb-0"><a href="<?= e($t['job_url']) ?>" target="_blank" rel="noopener noreferrer">Job link <?= icon('external', '', 12) ?></a></p><?php endif; ?>
        <?php if ($t['notes']): ?><div class="note-box"><span class="sr-only">Private notes: </span><?= e($t['notes']) ?></div><?php endif; ?>
        <?php if ($stale): ?><p class="small muted mt-2 mb-0"><?= icon('info', '', 14) ?> No update for 2+ weeks? You can follow up politely or mark it as “No Response Yet” — it's not a rejection.</p><?php endif; ?>

        <div class="track-details">
          <details class="track-edit">
            <summary><?= icon('edit', '', 16) ?> Update status, interview or notes</summary>
            <form method="post" action="">
              <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
              <?php if (!$t['application_id']): ?>
                <div class="form-row">
                  <div class="field"><label class="label" for="t<?= $t['id'] ?>-title">Job title</label><input class="input" id="t<?= $t['id'] ?>-title" name="job_title" value="<?= e($t['job_title']) ?>" maxlength="150"></div>
                  <div class="field"><label class="label" for="t<?= $t['id'] ?>-co">Company</label><input class="input" id="t<?= $t['id'] ?>-co" name="company" value="<?= e($t['company']) ?>" maxlength="150"></div>
                </div>
              <?php endif; ?>
              <div class="form-row">
                <div class="field"><label class="label" for="t<?= $t['id'] ?>-st">Status</label><select class="select" id="t<?= $t['id'] ?>-st" name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?= e($k) ?>"<?= $t['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label class="label" for="t<?= $t['id'] ?>-iv">Interview date &amp; time</label><input class="input" id="t<?= $t['id'] ?>-iv" type="datetime-local" name="interview_at" value="<?= $t['interview_at'] ? e(date('Y-m-d\TH:i', strtotime($t['interview_at']))) : '' ?>"></div>
              </div>
              <div class="field"><label class="label" for="t<?= $t['id'] ?>-n">Private notes</label><textarea class="textarea" id="t<?= $t['id'] ?>-n" name="notes" maxlength="3000" style="min-height:80px"><?= e($t['notes']) ?></textarea></div>
              <button class="btn btn-primary btn-sm" type="submit">Save changes</button>
            </form>
          </details>
          <div class="flex gap-2 wrap mt-3">
            <?php if ($t['application_id'] && !in_array($t['employer_status'], ['withdrawn', 'rejected'], true)): ?>
              <form method="post" action="" data-confirm="Withdraw this application? The employer will see it as withdrawn."><?= csrf_field() ?><input type="hidden" name="action" value="withdraw"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Withdraw application</button></form>
            <?php endif; ?>
            <form method="post" action="" data-confirm="Remove this record from your tracker?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><?= icon('trash', '', 16) ?> Remove record</button></form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<script>document.querySelector('[data-open-add]').addEventListener('click', function () { var d = document.getElementById('add'); d.open = true; setTimeout(function(){ document.getElementById('a-title').focus(); }, 50); });</script>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
