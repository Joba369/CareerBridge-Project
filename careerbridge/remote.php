<?php
require __DIR__ . '/includes/bootstrap.php';

$f = read_job_filters($_GET);
$result = search_jobs($f, 'remote', max(1, (int) ($_GET['page'] ?? 1)));
$show = [
    'q' => true, 'category' => 'it', 'arrangements' => true,
    'types' => ['full_time', 'part_time', 'internship', 'contract', 'shift'], 'levels' => true, 'salary' => true, 'skills' => true,
    'location' => true, 'remote_option' => true,
];
$scopeLabel = 'Remote, hybrid & IT roles';
$title = 'Remote & IT Jobs';
$nav = 'remote';
require __DIR__ . '/includes/partials/header.php';
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li aria-current="page">Remote &amp; IT</li></ol>
    <span class="eyebrow">Work from anywhere</span>
    <h1>Remote &amp; IT Jobs</h1>
    <p class="lead">Web development, software engineering, design, digital marketing and IT support roles — remote, hybrid or on-site. Filter by skills, experience and salary.</p>
    <form class="inline-search" method="get" action="" role="search" aria-label="Search remote and IT jobs">
      <div class="input-icon"><?= icon('search', '', 18) ?><label class="sr-only" for="top-q">Keyword</label><input class="input" type="search" id="top-q" name="q" value="<?= e($f['q']) ?>" placeholder="e.g. React, SEO, IT support"></div>
      <div class="input-icon"><?= icon('laptop', '', 18) ?><label class="sr-only" for="top-arr">Arrangement</label>
        <select class="select" id="top-arr" name="arrangement[]">
          <option value="">Any arrangement</option>
          <?php foreach (work_arrangements() as $k => $l): ?><option value="<?= e($k) ?>"<?= in_array($k, $f['arrangements'], true) ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <div class="hero-tags">
      <span>Categories:</span>
      <?php foreach (categories('it') as $c): ?><a href="<?= e(url('remote.php?category=' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/listing.php'; ?>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
