<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/profile.php';
$user = current_user();
$isSeeker = $user && $user['role'] === 'seeker';
$errors = [];
$saved = $isSeeker ? seeker_profile((int) $user['id']) : [];

if (is_post()) {
    if (!$isSeeker) { flash('error', 'Sign in with a job seeker account to save a matching profile.'); redirect('login.php'); }
    verify_csrf();
    // Keep fields that are not part of the matching form (headline, phone, area, education details).
    $in = read_profile_input(array_merge(array_map(fn($v) => $v === null ? '' : (string) $v, $saved), $_POST));
    $errors = validate_profile($in);
    if (!$errors) {
        save_profile((int) $user['id'], $in);
        flash('success', 'Your matching profile was saved and your recommendations were refreshed.');
        redirect('matching.php#results');
    }
    $saved = array_merge($saved, $in);
}

// Which profile do we match against?
$tryMode = isset($_GET['try']);
if ($tryMode) {
    $profileRow = read_profile_input($_GET);           // quick check, nothing is stored
} else {
    $profileRow = $saved;
}
$hasProfile = !empty($profileRow) && (trim((string) ($profileRow['skills'] ?? '')) !== '' || !empty($profileRow['preferred_category_id']));
$limit = isset($_GET['more']) ? 40 : 12;
$recs = $hasProfile ? recommend_jobs(matcher_profile($profileRow), $limit) : [];
$saveIds = saved_job_ids();

$title = 'Smart Job Matching';
$nav = 'tools';
$dashActive = 'matches';
$metaDesc = 'Transparent, rule-based job matching for Bangladesh. See why each job is recommended, which skills match and which skills to learn next.';
require __DIR__ . '/includes/partials/header.php';
$levels = experience_levels();
$edu = education_levels();
$weights = [['Required skills', 25], ['Job category', 15], ['Experience level', 15], ['Preferred skills', 10], ['Education', 10], ['Location', 10], ['Employment type', 5], ['Remote / on-site preference', 5], ['Salary expectation', 5]];
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li><a href="<?= e(url('tools.php')) ?>">Career Tools</a></li><li aria-current="page">Smart Job Matching</li></ol>
    <span class="eyebrow">Rule-based · fully explained</span>
    <h1>Smart Job Matching</h1>
    <p class="lead">Recommendations based on your skills, education, experience and preferences. Every match shows exactly why it scored the way it did — and missing skills are shown as opportunities to grow, never as a reason to hide a job.</p>
  </div>
</section>

<div class="container detail-layout match-layout" style="padding-top:32px;padding-bottom:64px">
  <aside>
    <div class="card card-pad">
      <h2 style="font-size:1.1rem" class="flex items-center gap-2"><?= icon('sliders', 'text-brand', 20) ?> Your matching profile</h2>
      <?php if ($isSeeker): ?>
        <p class="small muted">Saved to your profile. Change anything and press <strong>Save &amp; refresh</strong>.</p>
        <form method="post" action="<?= e(url('matching.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <?= profile_match_fields($saved, $errors) ?>
          <button class="btn btn-primary btn-block" type="submit"><?= icon('refresh', '', 18) ?> Save &amp; refresh matches</button>
        </form>
        <p class="help">More details (headline, phone, accessibility preferences) are on <a href="<?= e(url('account/profile.php')) ?>">My profile</a>.</p>
      <?php else: ?>
        <p class="small muted">Try it now — nothing is stored. <?php if (!$user): ?><a href="<?= e(url('register.php')) ?>">Create an account</a> to save your profile and get matches on your dashboard.<?php endif; ?></p>
        <form method="get" action="<?= e(url('matching.php')) ?>">
          <input type="hidden" name="try" value="1">
          <?= profile_match_fields($tryMode ? $profileRow : [], []) ?>
          <button class="btn btn-primary btn-block" type="submit"><?= icon('target', '', 18) ?> Show my matches</button>
        </form>
      <?php endif; ?>
      <p class="help mb-0" style="margin-top:12px"><?= icon('shield', '', 14) ?> Disability, gender, religion and age are never used.</p>
    </div>
  </aside>

  <section id="results" aria-labelledby="res-title" style="scroll-margin-top:90px">
    <?php if ($user && !$isSeeker): ?>
      <div class="notice mb-4"><?= icon('info', '', 18) ?><div>You are signed in as an employer. You can test the matcher here; saved matching profiles are for job seeker accounts.</div></div>
    <?php endif; ?>

    <?php if (!$hasProfile): ?>
      <h2 id="res-title" class="sr-only">Recommended jobs</h2>
      <?= empty_state('Tell us a little about yourself', 'Add your skills (and optionally a preferred category, location and job type) in the form, then we will rank open jobs and explain every score.', '', 'target') ?>
    <?php else: ?>
      <div class="results-bar">
        <h2 id="res-title" aria-live="polite"><?= count($recs) ?> recommended <?= count($recs) === 1 ? 'job' : 'jobs' ?><?= $tryMode ? ' <span class="badge badge-grey">Quick check — not saved</span>' : '' ?></h2>
        <a class="btn btn-outline btn-sm" href="<?= e($_SERVER['REQUEST_URI']) ?>"><?= icon('refresh', '', 16) ?> Refresh</a>
      </div>
      <p class="small muted" style="margin-top:-6px">Calculated <?= e(date('j M Y, g:i a')) ?> from <?= count(skills_list($profileRow['skills'] ?? '')) ?> skills and your preferences. Only open jobs scoring 40 or more are shown.</p>

      <?php if (!$recs): ?>
        <?= empty_state('No strong matches right now', 'Try adding more skills, choosing “Any category”, or widening your location. New jobs are posted regularly — check back soon.', '<a class="btn btn-outline" href="' . e(url('jobs.php')) . '">Browse all jobs</a>', 'search') ?>
      <?php else: ?>
        <div class="job-list">
          <?php foreach ($recs as ['job' => $job, 'match' => $m]):
            $tone = $m['score'] >= 75 ? 'badge-green' : ($m['score'] >= 55 ? 'badge-blue' : 'badge-amber'); ?>
            <article class="card match-card">
              <div class="match-top">
                <div class="score" style="--p: <?= (int) $m['score'] ?>" role="img" aria-label="Match score <?= (int) $m['score'] ?> out of 100"><b><?= (int) $m['score'] ?></b></div>
                <div style="flex:1;min-width:0">
                  <div class="flex justify-between gap-3 wrap" style="align-items:flex-start">
                    <div>
                      <h3 style="font-size:1.08rem;margin-bottom:2px"><a href="<?= e(url('job.php?id=' . $job['id'])) ?>" style="color:var(--ink)"><?= e($job['title']) ?></a></h3>
                      <div class="small muted"><?= e($job['company_name']) ?><?php if ($job['is_demo']): ?> <span class="badge badge-demo">Demo</span><?php endif; ?> · <?= e(format_location($job)) ?> · <?= e(employment_types()[$job['employment_type']]) ?></div>
                    </div>
                    <span class="badge <?= $tone ?>"><?= e($m['label']) ?></span>
                  </div>
                  <div class="small" style="margin-top:8px;color:var(--ink-2)">
                    <strong>Requirements:</strong> <?= e($levels[$job['experience_level']]) ?> · <?= e($edu[(int) $job['education_level']]) ?> · <?= e(format_salary($job)) ?>
                  </div>
                </div>
              </div>

              <?php if ($m['matched'] || $m['missing_required'] || $m['missing_preferred']): ?>
                <div class="grid grid-2" style="gap:14px;margin-top:16px">
                  <div>
                    <div class="small" style="font-weight:600;margin-bottom:6px">Skills you have</div>
                    <?php if ($m['matched']): ?><div class="chips"><?php foreach ($m['matched'] as $s): ?><span class="chip chip-match"><?= icon('check', '', 14) ?> <?= e($s) ?></span><?php endforeach; ?></div>
                    <?php else: ?><p class="small muted mb-0">None of the listed skills yet.</p><?php endif; ?>
                  </div>
                  <div>
                    <div class="small" style="font-weight:600;margin-bottom:6px">Skills to develop</div>
                    <?php if ($m['missing_required'] || $m['missing_preferred']): ?>
                      <div class="chips">
                        <?php foreach ($m['missing_required'] as $s): ?><span class="chip chip-gap" title="Required skill"><?= e($s) ?></span><?php endforeach; ?>
                        <?php foreach ($m['missing_preferred'] as $s): ?><span class="chip" title="Preferred (bonus) skill"><?= e($s) ?> <span class="muted">· bonus</span></span><?php endforeach; ?>
                      </div>
                    <?php else: ?><p class="small muted mb-0">You have every listed skill.</p><?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>

              <details class="track-edit" style="margin-top:14px">
                <summary><?= icon('info', '', 16) ?> Why this score?</summary>
                <ul class="reasons">
                  <?php foreach ($m['reasons'] as [$rt, $text]): ?>
                    <li class="<?= e($rt) ?>"><?= icon($rt === 'good' ? 'check-circle' : ($rt === 'gap' ? 'arrow-up' : 'info'), '', 16) ?><span><?= e($text) ?></span></li>
                  <?php endforeach; ?>
                </ul>
                <?php if ($m['missing_required']): ?><p class="small muted" style="margin:10px 0 0">You can still apply — employers often hire people who are learning. See <a href="<?= e(url('freshers.php#guides')) ?>">career guides</a> to build these skills.</p><?php endif; ?>
              </details>

              <div class="flex gap-2 wrap items-center" style="margin-top:16px;border-top:1px solid var(--line-2);padding-top:14px">
                <a class="btn btn-primary btn-sm" href="<?= e(url('job.php?id=' . $job['id'])) ?>">View &amp; apply</a>
                <?= save_button($job) ?>
                <?php $left = days_left($job['deadline']); if ($left !== null): ?><span class="small muted" style="margin-left:auto"><?= $left === 0 ? 'Closes today' : 'Apply by ' . e(format_date($job['deadline'], 'j M')) ?></span><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <?php if (!isset($_GET['more']) && count($recs) >= 12): ?>
          <div class="center mt-4"><a class="btn btn-outline" href="<?= e(query_with(['more' => 1])) ?>#results">Show more matches</a></div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    <div class="card card-pad-lg" id="how" style="margin-top:32px;scroll-margin-top:90px">
      <h2 style="font-size:1.2rem" class="flex items-center gap-2"><?= icon('chart', 'text-brand', 20) ?> How the match score works</h2>
      <p class="muted small">CareerBridge uses a simple, published points system — not artificial intelligence. Each open job gets up to 100 points:</p>
      <div class="grid grid-2" style="gap:28px">
        <div class="weights">
          <?php foreach ($weights as [$w, $pts]): ?><div><span><?= e($w) ?></span><strong><?= $pts ?> pts</strong></div><?php endforeach; ?>
        </div>
        <ul class="small" style="margin:0;padding-left:18px;display:grid;gap:6px;color:var(--ink-2)">
          <li>Skills earn points in proportion to how many of the listed skills you have. Jobs that list no skills get half points, because there is nothing to compare.</li>
          <li>Close matches get partial points (e.g. same division but a different district, or one experience level below).</li>
          <li>Fields you leave empty get neutral points, so you are never penalised for not answering.</li>
          <li>Missing skills lower the score but <strong>never hide or reject</strong> a job.</li>
          <li>75+ is a <em>Strong match</em>, 55–74 a <em>Good match</em>, 40–54 a <em>Partial match</em>.</li>
          <li>Disability, accessibility preferences, gender, religion and age are <strong>never</strong> used.</li>
        </ul>
      </div>
    </div>
  </section>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
