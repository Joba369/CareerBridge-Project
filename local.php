<?php
require __DIR__ . '/includes/bootstrap.php';

$f = read_job_filters($_GET);
$result = search_jobs($f, 'local', max(1, (int) ($_GET['page'] ?? 1)));
$show = [
    'q' => true, 'location' => true, 'area' => true, 'category' => 'local',
    'types' => ['part_time', 'temporary', 'shift'], 'shifts' => true, 'salary' => true,
    'salary_options' => [0 => 'Any', 8000 => 'Tk 8,000+', 10000 => 'Tk 10,000+', 12000 => 'Tk 12,000+', 15000 => 'Tk 15,000+'],
    'acc' => true,
];
$scopeLabel = 'Part-time, temporary & shift-based · on-site';

// Only describe results as "in your area" when the user actually chose a location.
$nearbyNote = '';
if ($f['district'] !== '' || $f['area'] !== '') {
    $place = trim(($f['area'] ? $f['area'] . ', ' : '') . $f['district'], ', ');
    $nearbyNote = '<div class="notice mb-4">' . icon('pin', '', 18) . '<div>Showing jobs where the employer listed the location as <strong>' . e($place) . '</strong>. Distances are not calculated — check the address on each listing.</div></div>';
}

$title = 'Local Part-Time Jobs';
$nav = 'local';
require __DIR__ . '/includes/partials/header.php';
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li aria-current="page">Local Part-Time</li></ol>
    <span class="eyebrow">Flexible work near you</span>
    <h1>Local Part-Time Jobs</h1>
    <p class="lead">Part-time, temporary and shift-based work at shops, restaurants, cafés, supermarkets, coaching centres and small businesses. Choose your district and the hours that fit around your classes.</p>
    <form class="inline-search" method="get" action="" role="search" aria-label="Search local jobs">
      <div class="input-icon"><?= icon('search', '', 18) ?><label class="sr-only" for="top-q">Keyword</label><input class="input" type="search" id="top-q" name="q" value="<?= e($f['q']) ?>" placeholder="e.g. tutor, cashier, café"></div>
      <div class="input-icon"><?= icon('pin', '', 18) ?><label class="sr-only" for="top-loc">District</label><?= district_select('district', $f['district'], ['id' => 'top-loc', 'placeholder' => 'Choose your district']) ?></div>
      <button class="btn btn-primary" type="submit">Find local jobs</button>
    </form>
    <div class="hero-tags">
      <span>Employers:</span>
      <?php foreach (categories('local') as $c): ?><a href="<?= e(url('local.php?category=' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/listing.php'; ?>
<section class="section-sm section-warm">
  <div class="container flex items-center justify-between gap-3 wrap">
    <div class="flex items-center gap-3"><span class="icon-tile" style="margin:0"><?= icon('store', '', 22) ?></span><div><h2 class="mb-0" style="font-size:1.2rem">Own a shop, café or coaching centre?</h2><p class="muted mb-0">Post a local vacancy for free from your employer account.</p></div></div>
    <a class="btn btn-dark" href="<?= e(url('employer/job-edit.php')) ?>">Post a local job</a>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
