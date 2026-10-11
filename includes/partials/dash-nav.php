<?php
/** @var array $user  @var string $dashActive */
if ($user['role'] === 'employer') {
    $co = $company ?? employer_company((int) $user['id']);
    $cnt = db()->prepare("SELECT
        (SELECT COUNT(*) FROM jobs WHERE company_id = ? AND status = 'published') AS active_n,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = ? AND a.status = 'submitted') AS new_n");
    $cnt->execute([$co['id'] ?? 0, $co['id'] ?? 0]);
    $cnt = $cnt->fetch();
    $items = [
        'overview' => ['employer/index.php', 'Overview', 'dashboard', null],
        'jobs'     => ['employer/jobs.php', 'Job listings', 'briefcase', $cnt['active_n']],
        'post'     => ['employer/job-edit.php', 'Post a job', 'plus', null],
        'apps'     => ['employer/applications.php', 'Applications', 'inbox', $cnt['new_n'] ? $cnt['new_n'] . ' new' : null],
        'company'  => ['employer/company.php', 'Company profile', 'building', null],
        'settings' => ['account/settings.php', 'Account settings', 'sliders', null],
    ];
    $sub = $co['name'] ?? 'Employer';
} else {
    $cnt = db()->prepare("SELECT (SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?) s, (SELECT COUNT(*) FROM tracker_entries WHERE user_id = ?) t");
    $cnt->execute([$user['id'], $user['id']]);
    $cnt = $cnt->fetch();
    $items = [
        'overview' => ['account/index.php', 'Overview', 'dashboard', null],
        'profile'  => ['account/profile.php', 'My profile', 'file', null],
        'matches'  => ['matching.php', 'Recommended jobs', 'target', null],
        'saved'    => ['account/saved.php', 'Saved jobs', 'bookmark', $cnt['s'] ?: null],
        'tracker'  => ['account/applications.php', 'Application tracker', 'checks', $cnt['t'] ?: null],
        'cv'       => ['cv-builder.php', 'CV Writing & Editing', 'pen', null],
        'guidance' => ['freshers.php', 'Career guidance', 'compass', null],
        'settings' => ['account/settings.php', 'Account settings', 'sliders', null],
    ];
    $sub = 'Job seeker';
}
?>
<nav class="dash-nav" aria-label="Dashboard">
  <div class="who"><span class="initials"><?= e(initials($user['name'])) ?></span><div><strong><?= e($user['name']) ?></strong><span><?= e($sub) ?></span></div></div>
  <ul>
    <?php foreach ($items as $key => [$href, $label, $ic, $count]): ?>
      <li><a href="<?= e(url($href)) ?>"<?= ($dashActive ?? '') === $key ? ' aria-current="page"' : '' ?>><?= icon($ic, '', 18) ?> <?= e($label) ?><?php if ($count): ?><span class="count"><?= e($count) ?></span><?php endif; ?></a></li>
    <?php endforeach; ?>
  </ul>
</nav>
