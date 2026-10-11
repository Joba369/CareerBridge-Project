<?php
require __DIR__ . '/includes/bootstrap.php';
$slug = (string) ($_GET['slug'] ?? '');
$st = db()->prepare('SELECT * FROM guides WHERE slug = ?');
$st->execute([$slug]);
$g = $st->fetch();
if (!$g) {
    http_response_code(404);
    $title = 'Guide not found';
    require __DIR__ . '/includes/partials/header.php';
    echo '<div class="container" style="padding-top:84px;padding-bottom:84px">' . empty_state('This guide could not be found', 'The link may be incorrect. Browse all career guides instead.', '<a class="btn btn-primary" href="' . e(url('freshers.php#guides')) . '">All guides</a>', 'book') . '</div>';
    require __DIR__ . '/includes/partials/footer.php';
    exit;
}
$rel = db()->prepare('SELECT slug, title, type, read_minutes FROM guides WHERE slug <> ? ORDER BY (career_path = ?) DESC, sort_order LIMIT 4');
$rel->execute([$g['slug'], $g['career_path']]);
$related = $rel->fetchAll();

// Relevant jobs for the guide's path
$pathQuery = ['Web Development' => 'remote.php?category=web-development', 'Digital Marketing' => 'remote.php?category=digital-marketing', 'Design' => 'remote.php?category=design', 'IT Support' => 'remote.php?category=it-support', 'Local Part-Time' => 'local.php'];
$jobsLink = $pathQuery[$g['career_path']] ?? 'freshers.php#jobs';

$title = $g['title'];
$nav = 'tools';
$metaDesc = $g['summary'];
require __DIR__ . '/includes/partials/header.php';
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li><a href="<?= e(url('freshers.php')) ?>">Fresher Career Guidance</a></li><li aria-current="page"><?= e($g['title']) ?></li></ol>
    <div class="guide-meta"><span class="badge badge-brand"><?= e($g['type']) ?></span><span class="badge badge-grey"><?= e($g['level']) ?></span><span class="badge badge-grey"><?= e($g['career_path']) ?></span></div>
    <h1><?= e($g['title']) ?></h1>
    <p class="lead"><?= e($g['summary']) ?></p>
    <p class="small muted mt-2 mb-0"><?= icon('clock', '', 16) ?> <?= (int) $g['read_minutes'] ?> minute read · Educational guide written for CareerBridge</p>
  </div>
</section>
<div class="container detail-layout" style="padding-top:36px;padding-bottom:60px">
  <article class="article">
    <div class="prose"><?= $g['content'] /* trusted, project-authored HTML from the database seed */ ?></div>
    <div class="notice mt-4"><?= icon('info', '', 18) ?><div>This guide gives general educational advice. Requirements differ between employers — always read each job description carefully.</div></div>
  </article>
  <aside class="sticky-side">
    <div class="card card-pad">
      <h2 style="font-size:1rem">Put it into practice</h2>
      <div class="stack-sm">
        <a class="btn btn-primary btn-block" href="<?= e(url($jobsLink)) ?>"><?= icon('briefcase', '', 18) ?> Find related jobs</a>
        <a class="btn btn-outline btn-block" href="<?= e(url('cv-builder.php')) ?>"><?= icon('pen', '', 18) ?> Update my CV</a>
        <a class="btn btn-outline btn-block" href="<?= e(url('matching.php')) ?>"><?= icon('target', '', 18) ?> Check my job matches</a>
      </div>
    </div>
    <?php if ($related): ?>
      <div class="card card-pad">
        <h2 style="font-size:1rem">More guides</h2>
        <ul class="list-plain" style="display:grid;gap:12px">
          <?php foreach ($related as $r): ?>
            <li><a href="<?= e(url('guide.php?slug=' . $r['slug'])) ?>" style="font-weight:600"><?= e($r['title']) ?></a><div class="small muted"><?= e($r['type']) ?> · <?= (int) $r['read_minutes'] ?> min</div></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </aside>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
