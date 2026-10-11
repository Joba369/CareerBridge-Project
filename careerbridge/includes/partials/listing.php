<?php
/**
 * Shared filter sidebar + results list.
 * Expects: $f (filters), $result (search_jobs output), $show (filter groups), $scopeLabel
 *   $show keys: q, location, area, category (null|'local'|'it'), types (array), arrangements,
 *               levels, shifts, salary, skills, acc
 */
$typeLabels = employment_types();
$catList = array_key_exists('category', $show) ? categories($show['category']) : [];
$hidden = $hiddenFields ?? [];
?>
<div class="container listing">
  <form class="filters" method="get" action="" data-filters aria-labelledby="filters-title">
    <?php foreach ($hidden as $hk => $hv): ?><input type="hidden" name="<?= e($hk) ?>" value="<?= e($hv) ?>"><?php endforeach; ?>
    <input type="hidden" name="sort" value="<?= e($f['sort']) ?>">
    <div class="filters-head">
      <h2 id="filters-title"><?= icon('filter', '', 18) ?> Filters</h2>
      <a class="small" href="?">Reset</a>
    </div>

    <?php if (!empty($show['q'])): ?>
      <div class="filter-group">
        <label class="label" for="f-q">Keyword</label>
        <input class="input" type="search" id="f-q" name="q" value="<?= e($f['q']) ?>" placeholder="Title, skill or company">
      </div>
    <?php endif; ?>

    <?php if (!empty($show['location'])): ?>
      <div class="filter-group">
        <label class="label" for="f-division">Division</label>
        <select class="select" name="division" id="f-division" data-division-select data-target="f-district">
          <option value="">All divisions</option>
          <?php foreach (array_keys(bd_locations()) as $div): ?><option value="<?= e($div) ?>"<?= $f['division'] === $div ? ' selected' : '' ?>><?= e($div) ?></option><?php endforeach; ?>
        </select>
        <label class="label mt-3" for="f-district">District</label>
        <?= district_select('district', $f['district'], ['id' => 'f-district', 'placeholder' => 'Any district', 'remote' => !empty($show['remote_option'])]) ?>
        <?php if (!empty($show['area'])): ?>
          <label class="label mt-3" for="f-area">Area / upazila</label>
          <input class="input" type="text" id="f-area" name="area" value="<?= e($f['area']) ?>" placeholder="e.g. Mirpur, Agrabad">
          <p class="help">Matches the area the employer entered.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($catList): ?>
      <div class="filter-group">
        <label class="label" for="f-cat">Category</label>
        <select class="select" name="category" id="f-cat">
          <option value="">All categories</option>
          <?php foreach ($catList as $c): ?><option value="<?= e($c['slug']) ?>"<?= $f['category'] === $c['slug'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <?php if (!empty($show['arrangements'])): ?>
      <div class="filter-group"><fieldset><legend class="label">Work arrangement</legend>
        <?= checkbox_group('arrangement', work_arrangements(), $f['arrangements']) ?>
      </fieldset></div>
    <?php endif; ?>

    <?php if (!empty($show['types'])): ?>
      <div class="filter-group"><fieldset><legend class="label">Employment type</legend>
        <?= checkbox_group('type', array_intersect_key($typeLabels, array_flip($show['types'])), $f['types']) ?>
      </fieldset></div>
    <?php endif; ?>

    <?php if (!empty($show['shifts'])): ?>
      <div class="filter-group"><fieldset><legend class="label">Working hours</legend>
        <?= checkbox_group('shift', shifts(), $f['shifts']) ?>
      </fieldset></div>
    <?php endif; ?>

    <?php if (!empty($show['levels'])): ?>
      <div class="filter-group"><fieldset><legend class="label">Experience level</legend>
        <?= checkbox_group('level', experience_levels(), $f['levels']) ?>
      </fieldset></div>
    <?php endif; ?>

    <?php if (!empty($show['salary'])): ?>
      <div class="filter-group">
        <label class="label" for="f-salary">Minimum monthly pay</label>
        <select class="select" name="salary_min" id="f-salary">
          <?php foreach (($show['salary_options'] ?? [0 => 'Any', 10000 => 'Tk 10,000+', 20000 => 'Tk 20,000+', 30000 => 'Tk 30,000+', 50000 => 'Tk 50,000+', 80000 => 'Tk 80,000+']) as $v => $l): ?>
            <option value="<?= $v ?>"<?= $f['salary_min'] === $v ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="help">Only jobs with a monthly salary are compared. Hourly or per-shift jobs are hidden while this filter is on.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($show['skills'])): ?>
      <div class="filter-group">
        <label class="label" for="f-skills">Required skills</label>
        <input class="input" type="text" id="f-skills" name="skills" value="<?= e($f['skills']) ?>" placeholder="e.g. React, SEO">
        <p class="help">Separate skills with commas.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($show['acc'])): ?>
      <div class="filter-group"><fieldset><legend class="label">Accessibility &amp; flexibility</legend>
        <?php foreach (accessibility_features() as $k => $a): ?>
          <label class="check"><input type="checkbox" name="acc[]" value="<?= e($k) ?>"<?= in_array($k, $f['acc'], true) ? ' checked' : '' ?>> <?= e($a['label']) ?></label>
        <?php endforeach; ?>
        <p class="help">Based only on information provided by employers.</p>
      </fieldset></div>
    <?php endif; ?>

    <div class="filters-mobile-actions">
      <button class="btn btn-outline only-mobile" type="button" data-close-filters>Close</button>
      <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
    </div>
  </form>

  <section aria-labelledby="results-title">
    <div class="results-bar">
      <h2 id="results-title" aria-live="polite"><?= $result['total'] ?> <?= $result['total'] === 1 ? 'job' : 'jobs' ?> found<?= !empty($scopeLabel) ? ' <span class="muted" style="font-weight:500">· ' . e($scopeLabel) . '</span>' : '' ?></h2>
      <div class="flex gap-2 items-center">
        <button class="btn btn-outline btn-sm filter-toggle" type="button" data-open-filters><?= icon('filter', '', 16) ?> Filters</button>
        <form method="get" action="">
          <?php foreach ($_GET as $k => $v): if ($k === 'sort' || $k === 'page') continue;
            foreach ((array) $v as $vv): if (!is_scalar($vv)) continue; ?>
              <input type="hidden" name="<?= e(is_array($v) ? $k . '[]' : $k) ?>" value="<?= e($vv) ?>">
          <?php endforeach; endforeach; ?>
          <label class="sr-only" for="sort">Sort by</label>
          <select class="select" id="sort" name="sort" data-sort>
            <option value="newest"<?= $f['sort'] === 'newest' ? ' selected' : '' ?>>Newest first</option>
            <option value="deadline"<?= $f['sort'] === 'deadline' ? ' selected' : '' ?>>Deadline soonest</option>
            <option value="salary"<?= $f['sort'] === 'salary' ? ' selected' : '' ?>>Highest salary</option>
          </select>
          <noscript><button class="btn btn-outline btn-sm" type="submit">Sort</button></noscript>
        </form>
      </div>
    </div>

    <?= active_filter_chips($f) ?>

    <?php if (!empty($nearbyNote)) echo $nearbyNote; ?>

    <?php if ($result['items']): ?>
      <div class="job-list">
        <?php foreach ($result['items'] as $job) echo job_card($job, 'row'); ?>
      </div>
      <?= pagination($result['page'], $result['pages']) ?>
      <p class="small muted center mt-3">Showing <?= ($result['page'] - 1) * JOBS_PER_PAGE + 1 ?>–<?= min($result['total'], $result['page'] * JOBS_PER_PAGE) ?> of <?= $result['total'] ?> listings</p>
    <?php else: ?>
      <?= empty_state(
          'No jobs match these filters',
          'Try removing a filter, searching a nearby district, or using a broader keyword. New listings are added by employers regularly.',
          '<div class="flex gap-2 wrap" style="justify-content:center"><a class="btn btn-primary" href="?">Clear all filters</a><a class="btn btn-outline" href="' . e(url('jobs.php')) . '">Browse all jobs</a></div>'
      ) ?>
    <?php endif; ?>
  </section>
</div>
