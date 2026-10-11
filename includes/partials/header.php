<?php
/** @var string $title  @var string $nav  @var string|null $metaDesc */
$title = $title ?? APP_NAME;
$nav = $nav ?? '';
$user = current_user();
$navItems = [
    'home'      => ['index.php', 'Home', 'grid'],
    'jobs'      => ['jobs.php', 'Find Jobs', 'search'],
    'local'     => ['local.php', 'Local Part-Time', 'store'],
    'remote'    => ['remote.php', 'Remote & IT', 'laptop'],
    'tools'     => ['tools.php', 'Career Tools', 'compass'],
    'employers' => ['employers.php', 'For Employers', 'building'],
];
$dashUrl = $user ? ($user['role'] === 'employer' ? 'employer/index.php' : 'account/index.php') : '';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title === APP_NAME ? 'CareerBridge — Find the Right Job in Bangladesh' : $title . ' · CareerBridge') ?></title>
<meta name="description" content="<?= e($metaDesc ?? 'CareerBridge helps students, freshers and professionals in Bangladesh discover local part-time, remote and IT jobs, write CVs and track applications.') ?>">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?php foreach (($extraCss ?? []) as $css): ?><link rel="stylesheet" href="<?= e(asset($css)) ?>"><?php endforeach; ?>
<script>window.CB = { base: <?= json_encode(base_path()) ?>, csrf: <?= json_encode(csrf_token()) ?>, loggedIn: <?= $user ? 'true' : 'false' ?>, role: <?= json_encode($user['role'] ?? null) ?> };</script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to main content</a>

<div class="demo-bar" id="demoBar" role="note">
  <div class="container">
    <?= icon('info', '', 16) ?>
    <span><strong>Demo data:</strong> listings marked “Demo” are fictional samples for this university project — not real vacancies. <a href="<?= e(url('about.php#demo-data')) ?>">Learn more</a></span>
    <button type="button" data-dismiss-demo aria-label="Hide demo notice"><?= icon('x', '', 16) ?></button>
  </div>
</div>

<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="CareerBridge home"><?= logo_mark() ?><span>Career<b>Bridge</b></span></a>

    <nav aria-label="Main">
      <ul class="nav-links">
        <?php foreach ($navItems as $key => [$href, $label]): ?>
          <li><a href="<?= e(url($href)) ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="nav-actions desktop">
      <?php if ($user): ?>
        <div class="account-menu">
          <button class="account-btn" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown="accountDd">
            <span class="initials"><?= e(initials($user['name'])) ?></span>
            <span><?= e($user['role'] === 'employer' ? 'Employer' : 'My account') ?></span>
            <?= icon('chevron-down', '', 16) ?>
          </button>
          <div class="dropdown" id="accountDd">
            <div class="dd-head"><strong><?= e($user['name']) ?></strong><span><?= e($user['email']) ?></span></div>
            <a href="<?= e(url($dashUrl)) ?>"><?= icon('dashboard', '', 18) ?> Dashboard</a>
            <?php if ($user['role'] === 'seeker'): ?>
              <a href="<?= e(url('account/saved.php')) ?>"><?= icon('bookmark', '', 18) ?> Saved jobs</a>
              <a href="<?= e(url('account/applications.php')) ?>"><?= icon('checks', '', 18) ?> Application tracker</a>
              <a href="<?= e(url('cv-builder.php')) ?>"><?= icon('file', '', 18) ?> My CV</a>
              <a href="<?= e(url('account/settings.php')) ?>"><?= icon('sliders', '', 18) ?> Settings</a>
            <?php else: ?>
              <a href="<?= e(url('employer/job-edit.php')) ?>"><?= icon('plus', '', 18) ?> Post a job</a>
              <a href="<?= e(url('employer/applications.php')) ?>"><?= icon('inbox', '', 18) ?> Applications</a>
              <a href="<?= e(url('employer/company.php')) ?>"><?= icon('building', '', 18) ?> Company profile</a>
            <?php endif; ?>
            <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout', '', 18) ?> Sign out</button></form>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= e(url('login.php')) ?>">Sign In</a>
        <a class="btn btn-primary" href="<?= e(url('register.php')) ?>">Get Started</a>
      <?php endif; ?>
    </div>

    <button class="btn btn-outline btn-icon nav-toggle" type="button" aria-expanded="false" aria-controls="mobileNav" aria-label="Open menu" data-nav-toggle>
      <?= icon('menu') ?>
    </button>
  </div>
  <div class="mobile-panel" id="mobileNav">
    <ul>
      <?php foreach ($navItems as $key => [$href, $label, $ic]): ?>
        <li><a href="<?= e(url($href)) ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= icon($ic, '', 20) ?> <?= e($label) ?></a></li>
      <?php endforeach; ?>
      <?php if ($user): ?>
        <li><a href="<?= e(url($dashUrl)) ?>"><?= icon('dashboard', '', 20) ?> Dashboard</a></li>
      <?php endif; ?>
    </ul>
    <?php if ($user): ?>
      <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn btn-outline" type="submit"><?= icon('logout', '', 18) ?> Sign out</button></form>
    <?php else: ?>
      <a class="btn btn-outline" href="<?= e(url('login.php')) ?>">Sign In</a>
      <a class="btn btn-primary" href="<?= e(url('register.php')) ?>">Get Started</a>
    <?php endif; ?>
  </div>
</header>

<main id="main" tabindex="-1">
<?php $flashes = take_flashes(); if ($flashes && empty($noFlashContainer)): ?>
  <div class="container"><div class="flash-wrap" role="status" aria-live="polite">
    <?php foreach ($flashes as $f): $ic = ['success' => 'check-circle', 'error' => 'alert', 'warning' => 'alert'][$f['type']] ?? 'info'; ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= icon($ic, '', 18) ?><div><?= e($f['message']) ?></div><button class="close" type="button" aria-label="Dismiss" data-close-alert><?= icon('x', '', 16) ?></button></div>
    <?php endforeach; ?>
  </div></div>
<?php endif; ?>
