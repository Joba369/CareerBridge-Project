<?php
require __DIR__ . '/includes/bootstrap.php';

$f = read_job_filters($_GET);
$result = search_jobs($f, '', max(1, (int) ($_GET['page'] ?? 1)));
$show = [
    'q' => true, 'location' => true, 'remote_option' => true, 'category' => null,
    'arrangements' => true, 'types' => array_keys(employment_types()), 'levels' => true, 'salary' => true, 'acc' => true,
];
$scopeLabel = '';
$title = $f['q'] !== '' ? 'Jobs for “' . $f['q'] . '”' : 'Find Jobs';
$nav = 'jobs';
require __DIR__ . '/includes/partials/header.php';
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li aria-current="page">Find Jobs</li></ol>
    <h1>Find jobs across Bangladesh</h1>
    <p class="lead">Search part-time, full-time, remote, internship and shift-based roles. Use the filters to narrow results by location, type and accessibility information.</p>
    <form class="inline-search" method="get" action="" role="search" aria-label="Search jobs">
      <div class="input-icon"><?= icon('search', '', 18) ?><label class="sr-only" for="top-q">Keyword</label><input class="input" type="search" id="top-q" name="q" value="<?= e($f['q']) ?>" placeholder="Job title, skill or company"></div>
      <div class="input-icon"><?= icon('pin', '', 18) ?><label class="sr-only" for="top-loc">Location</label><?= district_select('district', $f['district'], ['id' => 'top-loc', 'placeholder' => 'Any location', 'remote' => true]) ?></div>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/listing.php'; ?>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
