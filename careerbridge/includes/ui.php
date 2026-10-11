<?php
/**
 * Reusable UI components (rendered server-side).
 */

function save_button(array $job, string $class = ''): string
{
    $u = current_user();
    $id = (int) $job['id'];
    if ($u && $u['role'] === 'employer') return '';
    if (!$u) {
        $next = urlencode(url('job.php?id=' . $id));
        return '<a class="save-btn ' . e($class) . '" href="' . e(url('login.php?next=' . $next)) . '" aria-label="Sign in to save ' . e($job['title']) . '" title="Sign in to save">' . icon('bookmark', '', 18) . '</a>';
    }
    $saved = in_array($id, saved_job_ids(), true);
    return '<form class="save-form" method="post" action="' . e(url('api/save-job.php')) . '" data-save-form>'
        . csrf_field()
        . '<input type="hidden" name="job_id" value="' . $id . '">'
        . '<input type="hidden" name="return" value="' . e($_SERVER['REQUEST_URI'] ?? '') . '">'
        . '<button type="submit" class="save-btn ' . ($saved ? 'saved ' : '') . e($class) . '" aria-pressed="' . ($saved ? 'true' : 'false') . '" aria-label="' . ($saved ? 'Remove from saved jobs: ' : 'Save job: ') . e($job['title']) . '" title="' . ($saved ? 'Saved' : 'Save job') . '">'
        . icon('bookmark', '', 18) . '</button></form>';
}

function job_card(array $job, string $variant = 'grid', ?array $match = null): string
{
    $types = employment_types();
    $arr = work_arrangements();
    $id = (int) $job['id'];
    $skills = array_slice(skills_list($job['required_skills']), 0, 4);
    $left = days_left($job['deadline'] ?? null);
    ob_start(); ?>
    <article class="job-card <?= $variant === 'row' ? 'job-row' : '' ?>">
      <?php if ($variant === 'row'): ?>
        <span class="logo-tile <?= logo_tone((int) $job['company_id']) ?>" aria-hidden="true"><?= e(initials($job['company_name'])) ?></span>
        <div class="job-card-body">
          <div class="job-card-title">
            <h3><a href="<?= e(url('job.php?id=' . $id)) ?>"><?= e($job['title']) ?></a></h3>
            <div class="company"><?= e($job['company_name']) ?><?php if ($job['is_demo']): ?> <span class="badge badge-demo">Demo</span><?php endif; ?></div>
          </div>
          <div class="job-meta">
            <span><?= icon('pin', '', 16) ?><?= e(format_location($job)) ?></span>
            <span><?= icon('briefcase', '', 16) ?><?= e($types[$job['employment_type']]) ?> · <?= e($arr[$job['work_arrangement']]) ?></span>
            <?php if (!empty($job['working_hours'])): ?><span><?= icon('clock', '', 16) ?><?= e($job['working_hours']) ?></span><?php endif; ?>
          </div>
          <?php if ($skills): ?><div class="chips"><?php foreach ($skills as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
        </div>
        <div class="job-card-side">
          <span class="salary"><?= e(format_salary($job)) ?></span>
          <div class="flex items-center gap-2 z">
            <?= save_button($job) ?>
          </div>
          <span class="small muted"><?= $left !== null ? ($left <= 3 ? '<span class="deadline-soon">' . ($left === 0 ? 'Closes today' : "Closes in $left days") . '</span>' : 'Deadline ' . e(format_date($job['deadline'], 'j M'))) : e(time_ago($job['created_at'])) ?></span>
        </div>
      <?php else: ?>
        <div class="job-card-head">
          <span class="logo-tile <?= logo_tone((int) $job['company_id']) ?>" aria-hidden="true"><?= e(initials($job['company_name'])) ?></span>
          <div class="job-card-title">
            <h3><a href="<?= e(url('job.php?id=' . $id)) ?>"><?= e($job['title']) ?></a></h3>
            <div class="company"><?= e($job['company_name']) ?><?php if ($job['is_demo']): ?> <span class="badge badge-demo">Demo</span><?php endif; ?></div>
          </div>
          <?= save_button($job) ?>
        </div>
        <div class="job-meta">
          <span><?= icon('pin', '', 16) ?><?= e(format_location($job)) ?></span>
          <span><?= icon('briefcase', '', 16) ?><?= e($types[$job['employment_type']]) ?></span>
          <span><?= icon('wallet', '', 16) ?><?= e(format_salary($job)) ?></span>
        </div>
        <?php if ($skills): ?><div class="chips"><?php foreach ($skills as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="job-card-foot">
          <span class="badge <?= $job['work_arrangement'] === 'remote' ? 'badge-blue' : ($job['work_arrangement'] === 'hybrid' ? 'badge-violet' : 'badge-grey') ?>"><?= e($arr[$job['work_arrangement']]) ?></span>
          <span><?= $left !== null ? ($left <= 3 ? '<span class="deadline-soon">' . ($left === 0 ? 'Closes today' : "Closes in $left days") . '</span>' : 'Apply by ' . e(format_date($job['deadline'], 'j M')) ) : e(time_ago($job['created_at'])) ?></span>
        </div>
      <?php endif; ?>
    </article>
    <?php
    return (string) ob_get_clean();
}

function pagination(int $page, int $pages): string
{
    if ($pages <= 1) return '';
    $html = '<nav aria-label="Pagination"><ul class="pagination">';
    $html .= $page > 1
        ? '<li><a href="' . e(query_with(['page' => $page - 1])) . '" aria-label="Previous page">' . icon('chevron-left', '', 16) . '</a></li>'
        : '<li><span class="disabled" aria-hidden="true">' . icon('chevron-left', '', 16) . '</span></li>';
    $window = [];
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === 1 || $i === $pages || abs($i - $page) <= 1) $window[] = $i;
    }
    $prev = 0;
    foreach ($window as $i) {
        if ($i - $prev > 1) $html .= '<li><span aria-hidden="true">…</span></li>';
        $html .= $i === $page
            ? '<li><span aria-current="page">' . $i . '</span></li>'
            : '<li><a href="' . e(query_with(['page' => $i])) . '">' . $i . '</a></li>';
        $prev = $i;
    }
    $html .= $page < $pages
        ? '<li><a href="' . e(query_with(['page' => $page + 1])) . '" aria-label="Next page">' . icon('chevron-right', '', 16) . '</a></li>'
        : '<li><span class="disabled" aria-hidden="true">' . icon('chevron-right', '', 16) . '</span></li>';
    return $html . '</ul></nav>';
}

function empty_state(string $title, string $text, string $actionsHtml = '', string $ic = 'search'): string
{
    return '<div class="empty"><span class="icon-tile">' . icon($ic, '', 24) . '</span><h3>' . e($title) . '</h3><p>' . e($text) . '</p>' . $actionsHtml . '</div>';
}

/** <select> for district with optgroups per division. */
function district_select(string $name, string $selected = '', array $opts = []): string
{
    $id = $opts['id'] ?? $name;
    $placeholder = $opts['placeholder'] ?? 'All of Bangladesh';
    $html = '<select class="select ' . e($opts['class'] ?? '') . '" name="' . e($name) . '" id="' . e($id) . '"' . (!empty($opts['required']) ? ' required' : '') . '>';
    $html .= '<option value="">' . e($placeholder) . '</option>';
    if (!empty($opts['remote'])) $html .= '<option value="remote"' . ($selected === 'remote' ? ' selected' : '') . '>Remote (work from anywhere)</option>';
    foreach (bd_locations() as $div => $districts) {
        $html .= '<optgroup label="' . e($div) . ' Division">';
        foreach ($districts as $d) $html .= '<option value="' . e($d) . '"' . ($selected === $d ? ' selected' : '') . '>' . e($d) . '</option>';
        $html .= '</optgroup>';
    }
    return $html . '</select>';
}

function division_select(string $name, string $selected = '', string $placeholder = 'Any division', string $id = ''): string
{
    $html = '<select class="select" name="' . e($name) . '" id="' . e($id ?: $name) . '" data-division-select>';
    $html .= '<option value="">' . e($placeholder) . '</option>';
    foreach (array_keys(bd_locations()) as $div) $html .= '<option value="' . e($div) . '"' . ($selected === $div ? ' selected' : '') . '>' . e($div) . '</option>';
    return $html . '</select>';
}

function checkbox_group(string $name, array $options, array $selected): string
{
    $html = '';
    foreach ($options as $val => $label) {
        $html .= '<label class="check"><input type="checkbox" name="' . e($name) . '[]" value="' . e($val) . '"' . (in_array((string) $val, $selected, true) ? ' checked' : '') . '> ' . e($label) . '</label>';
    }
    return $html;
}

/** Field error text helper. */
function field_error(array $errors, string $key): string
{
    if (empty($errors[$key])) return '';
    return '<p class="error-text" id="err-' . e($key) . '">' . icon('alert', '', 14) . e($errors[$key]) . '</p>';
}

function invalid(array $errors, string $key): string
{
    return !empty($errors[$key]) ? ' is-invalid" aria-invalid="true" aria-describedby="err-' . e($key) : '';
}

function active_filter_chips(array $f): string
{
    $chips = [];
    $labelMaps = ['types' => ['type', employment_types()], 'arrangements' => ['arrangement', work_arrangements()], 'levels' => ['level', experience_levels()], 'shifts' => ['shift', shifts()]];
    if ($f['q'] !== '') $chips[] = ['“' . $f['q'] . '”', ['q' => null]];
    if ($f['district'] !== '') $chips[] = [$f['district'] === 'remote' ? 'Remote' : $f['district'], ['district' => null]];
    elseif ($f['division'] !== '') $chips[] = [$f['division'] . ' Division', ['division' => null]];
    if ($f['area'] !== '') $chips[] = ['Area: ' . $f['area'], ['area' => null]];
    if ($f['category'] !== '') {
        foreach (categories() as $c) if ($c['slug'] === $f['category']) $chips[] = [$c['name'], ['category' => null]];
    }
    foreach ($labelMaps as $key => [$param, $labels]) {
        foreach ($f[$key] as $v) {
            $rest = array_values(array_diff($f[$key], [$v]));
            $chips[] = [$labels[$v], [$param => $rest ?: null]];
        }
    }
    if ($f['salary_min'] > 0) $chips[] = ['Min ' . taka($f['salary_min']) . '/month', ['salary_min' => null]];
    if ($f['skills'] !== '') $chips[] = ['Skills: ' . $f['skills'], ['skills' => null]];
    $acc = accessibility_features();
    foreach ($f['acc'] as $v) {
        $rest = array_values(array_diff($f['acc'], [$v]));
        $chips[] = [$acc[$v]['label'], ['acc' => $rest ?: null]];
    }
    if (!$chips) return '';
    $html = '<div class="active-filters" aria-label="Active filters">';
    foreach ($chips as [$label, $change]) {
        $html .= '<a href="' . e(query_with($change + ['page' => null])) . '" aria-label="Remove filter ' . e($label) . '">' . e($label) . icon('x', '', 14) . '</a>';
    }
    $html .= '<a href="?" style="background:transparent;color:var(--muted)">Clear all</a></div>';
    return $html;
}
