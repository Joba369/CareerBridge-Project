<?php
/** Shared job-seeker profile handling (profile page + Smart Job Matching). */

function read_profile_input(array $src): array
{
    $district = in_array($src['district'] ?? '', all_districts(), true) ? $src['district'] : '';
    $edu = input_int($src, 'education_level');
    $cat = input_int($src, 'preferred_category_id');
    $validCats = array_map(fn($c) => (int) $c['id'], categories());
    $salary = input_int($src, 'expected_salary');
    $skills = implode(', ', array_slice(array_unique(skills_list(input_str($src, 'skills', 500))), 0, 30));
    return [
        'headline' => input_str($src, 'headline', 150),
        'phone' => input_str($src, 'phone', 30),
        'district' => $district,
        'division' => $district ? division_of($district) : '',
        'area' => input_str($src, 'area', 80),
        'education_level' => ($edu !== null && array_key_exists($edu, education_levels())) ? $edu : null,
        'education_detail' => input_str($src, 'education_detail', 200),
        'experience_level' => array_key_exists($src['experience_level'] ?? '', experience_levels()) ? $src['experience_level'] : 'fresher',
        'skills' => $skills,
        'preferred_category_id' => in_array($cat, $validCats, true) ? $cat : null,
        'preferred_employment_type' => array_key_exists($src['preferred_employment_type'] ?? '', employment_types()) ? $src['preferred_employment_type'] : '',
        'preferred_arrangement' => in_array($src['preferred_arrangement'] ?? '', ['any', 'onsite', 'remote', 'hybrid'], true) ? $src['preferred_arrangement'] : 'any',
        'expected_salary' => ($salary !== null && $salary >= 0 && $salary <= 10000000) ? $salary : null,
    ];
}

function validate_profile(array $p): array
{
    $errors = [];
    if ($p['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{10,20}$/', $p['phone'])) $errors['phone'] = 'Please enter a valid phone number.';
    if (isset($_POST['expected_salary']) && $_POST['expected_salary'] !== '' && $p['expected_salary'] === null) $errors['expected_salary'] = 'Enter a number in taka, e.g. 25000.';
    return $errors;
}

function save_profile(int $uid, array $p, array $extra = []): void
{
    $data = array_merge($p, $extra);
    foreach ($data as $k => $v) if ($v === '') $data[$k] = null;
    $data['experience_level'] = $data['experience_level'] ?? 'fresher';
    $data['preferred_arrangement'] = $data['preferred_arrangement'] ?? 'any';
    $cols = array_keys($data);
    $sql = 'INSERT INTO seeker_profiles (user_id, ' . implode(', ', $cols) . ') VALUES (:user_id, :' . implode(', :', $cols) . ')
            ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(fn($c) => "$c = VALUES($c)", $cols));
    $params = [':user_id' => $uid];
    foreach ($data as $k => $v) $params[':' . $k] = $v;
    db()->prepare($sql)->execute($params);
}

/** Renders the matching-related profile fields. $p = profile values, $errors = validation errors. */
function profile_match_fields(array $p, array $errors = []): string
{
    ob_start(); ?>
    <div class="form-row">
      <div class="field">
        <label class="label" for="education_level">Highest education</label>
        <select class="select" id="education_level" name="education_level">
          <option value="">Prefer not to say</option>
          <?php foreach (education_levels() as $k => $l): if ($k === 0) $l = 'Below SSC / none'; ?>
            <option value="<?= $k ?>"<?= isset($p['education_level']) && $p['education_level'] !== null && $p['education_level'] !== '' && (int) $p['education_level'] === $k ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label" for="experience_level">Experience level</label>
        <select class="select" id="experience_level" name="experience_level">
          <?php foreach (experience_levels() as $k => $l): ?><option value="<?= e($k) ?>"<?= ($p['experience_level'] ?? 'fresher') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label class="label" for="skills">Skills</label>
      <input class="input" id="skills" name="skills" value="<?= e($p['skills'] ?? '') ?>" placeholder="e.g. HTML, CSS, Customer service, MS Excel" list="skill-suggestions">
      <p class="help">Separate skills with commas. Include both technical and everyday skills.</p>
      <datalist id="skill-suggestions">
        <?php foreach (['HTML', 'CSS', 'JavaScript', 'React', 'PHP', 'Laravel', 'MySQL', 'Python', 'Git', 'Figma', 'Adobe Photoshop', 'Canva', 'SEO', 'Facebook Ads', 'MS Excel', 'MS Office', 'Typing', 'Customer service', 'Communication', 'English communication', 'Cash handling', 'Teaching', 'Mathematics', 'Windows troubleshooting', 'Networking basics'] as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?>
      </datalist>
    </div>
    <div class="form-row">
      <div class="field">
        <label class="label" for="preferred_category_id">Preferred job category</label>
        <select class="select" id="preferred_category_id" name="preferred_category_id">
          <option value="">Any category</option>
          <?php foreach (['local' => 'Local & service', 'it' => 'Tech & digital', 'general' => 'Office & business'] as $g => $gl): ?>
            <optgroup label="<?= e($gl) ?>">
              <?php foreach (categories($g) as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) ($p['preferred_category_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label" for="district">Preferred location</label>
        <?= district_select('district', (string) ($p['district'] ?? ''), ['placeholder' => 'Anywhere in Bangladesh']) ?>
      </div>
    </div>
    <div class="form-row-3">
      <div class="field">
        <label class="label" for="preferred_employment_type">Employment type</label>
        <select class="select" id="preferred_employment_type" name="preferred_employment_type">
          <option value="">Any type</option>
          <?php foreach (employment_types() as $k => $l): ?><option value="<?= e($k) ?>"<?= ($p['preferred_employment_type'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label" for="preferred_arrangement">Remote or on-site</label>
        <select class="select" id="preferred_arrangement" name="preferred_arrangement">
          <?php foreach (['any' => 'No preference', 'onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid'] as $k => $l): ?><option value="<?= e($k) ?>"<?= ($p['preferred_arrangement'] ?? 'any') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label" for="expected_salary">Expected salary <span class="opt">(Tk/month)</span></label>
        <input class="input<?= invalid($errors, 'expected_salary') ?>" id="expected_salary" name="expected_salary" type="number" min="0" step="500" inputmode="numeric" value="<?= e($p['expected_salary'] ?? '') ?>" placeholder="Optional">
        <?= field_error($errors, 'expected_salary') ?>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
