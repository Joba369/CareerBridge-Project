<?php
/**
 * Job queries shared by all listing pages, plus the rule-based matcher.
 */

function job_select_sql(): string
{
    return "SELECT j.*, c.name AS company_name, c.is_demo AS company_demo, c.district AS company_district,
                   cat.name AS category_name, cat.slug AS category_slug, cat.grp AS category_group
            FROM jobs j
            JOIN companies c ON c.id = j.company_id
            JOIN categories cat ON cat.id = j.category_id";
}

function public_job_condition(): string
{
    return "j.status = 'published' AND (j.deadline IS NULL OR j.deadline >= CURDATE())";
}

function categories(?string $group = null): array
{
    static $cache = [];
    $key = $group ?? '*';
    if (isset($cache[$key])) return $cache[$key];
    if ($group) {
        $st = db()->prepare('SELECT * FROM categories WHERE grp = ? ORDER BY sort_order, name');
        $st->execute([$group]);
    } else {
        $st = db()->query('SELECT * FROM categories ORDER BY sort_order, name');
    }
    return $cache[$key] = $st->fetchAll();
}

/**
 * Read the common filter parameters from the query string.
 */
function read_job_filters(array $src): array
{
    $acc = array_keys(accessibility_features());
    return [
        'q'            => input_str($src, 'q', 100),
        'division'     => in_array($src['division'] ?? '', array_keys(bd_locations()), true) ? $src['division'] : '',
        'district'     => (($src['district'] ?? '') === 'remote' || in_array($src['district'] ?? '', all_districts(), true)) ? $src['district'] : '',
        'area'         => input_str($src, 'area', 60),
        'category'     => input_str($src, 'category', 60),
        'types'        => input_arr($src, 'type', array_keys(employment_types())),
        'arrangements' => input_arr($src, 'arrangement', array_keys(work_arrangements())),
        'levels'       => input_arr($src, 'level', array_keys(experience_levels())),
        'shifts'       => input_arr($src, 'shift', array_keys(shifts())),
        'salary_min'   => max(0, (int) input_int($src, 'salary_min', 0)),
        'skills'       => input_str($src, 'skills', 100),
        'acc'          => input_arr($src, 'acc', $acc),
        'sort'         => in_array($src['sort'] ?? '', ['newest', 'deadline', 'salary'], true) ? $src['sort'] : 'newest',
    ];
}

/**
 * Search published jobs.
 * $scope may be: '' (all), 'local', 'remote', 'fresher', 'inclusive'
 */
function search_jobs(array $f, string $scope = '', int $page = 1, int $per = JOBS_PER_PAGE): array
{
    $where  = [public_job_condition()];
    $params = [];

    switch ($scope) {
        case 'local':
            $where[] = "j.employment_type IN ('part_time','temporary','shift') AND j.work_arrangement = 'onsite'";
            break;
        case 'remote':
            $where[] = "(cat.grp = 'it' OR j.work_arrangement IN ('remote','hybrid'))";
            break;
        case 'fresher':
            $where[] = "(j.experience_level = 'fresher' OR j.employment_type = 'internship')";
            break;
        case 'inclusive':
            $where[] = "(j.acc_step_free = 1 OR j.acc_lift = 1 OR j.acc_toilet = 1 OR j.acc_flexible_hours = 1
                        OR j.acc_remote_option = 1 OR j.acc_interview = 1 OR j.work_arrangement = 'remote')";
            break;
    }

    if ($f['q'] !== '') {
        $words = array_slice(preg_split('/\s+/', $f['q']) ?: [], 0, 6);
        foreach ($words as $i => $w) {
            $k = ':q' . $i;
            $where[] = "(j.title LIKE $k OR c.name LIKE $k OR j.required_skills LIKE $k OR j.preferred_skills LIKE $k
                        OR cat.name LIKE $k OR j.area LIKE $k OR j.district LIKE $k OR j.description LIKE $k)";
            $params[$k] = '%' . $w . '%';
        }
    }
    if ($f['district'] === 'remote') {
        $where[] = "j.work_arrangement = 'remote'";
    } elseif ($f['district'] !== '') {
        $where[] = 'j.district = :district';
        $params[':district'] = $f['district'];
    } elseif ($f['division'] !== '') {
        $where[] = 'j.division = :division';
        $params[':division'] = $f['division'];
    }
    if ($f['area'] !== '') {
        $where[] = 'j.area LIKE :area';
        $params[':area'] = '%' . $f['area'] . '%';
    }
    if ($f['category'] !== '') {
        $where[] = 'cat.slug = :cat';
        $params[':cat'] = $f['category'];
    }
    foreach (['types' => 'employment_type', 'arrangements' => 'work_arrangement', 'levels' => 'experience_level', 'shifts' => 'shift'] as $key => $col) {
        if ($f[$key]) {
            $ph = [];
            foreach ($f[$key] as $i => $v) {
                $ph[] = ":{$key}{$i}";
                $params[":{$key}{$i}"] = $v;
            }
            $where[] = "j.$col IN (" . implode(',', $ph) . ')';
        }
    }
    if ($f['salary_min'] > 0) {
        $where[] = "j.salary_period = 'month' AND COALESCE(j.salary_max, j.salary_min) >= :smin";
        $params[':smin'] = $f['salary_min'];
    }
    if ($f['skills'] !== '') {
        foreach (array_slice(skills_list($f['skills']), 0, 5) as $i => $s) {
            $where[] = "(j.required_skills LIKE :sk{$i} OR j.preferred_skills LIKE :sk{$i})";
            $params[":sk{$i}"] = '%' . $s . '%';
        }
    }
    foreach ($f['acc'] as $col) {
        // Columns come from a fixed whitelist (accessibility_features()).
        if ($col === 'acc_remote_option') {
            $where[] = "(j.acc_remote_option = 1 OR j.work_arrangement = 'remote')";
        } else {
            $where[] = "j.$col = 1";
        }
    }

    $whereSql = implode(' AND ', $where);
    $base = " FROM jobs j JOIN companies c ON c.id = j.company_id JOIN categories cat ON cat.id = j.category_id WHERE $whereSql";

    $count = db()->prepare('SELECT COUNT(*)' . $base);
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $order = match ($f['sort']) {
        'deadline' => 'j.deadline IS NULL, j.deadline ASC, j.created_at DESC',
        'salary'   => "(j.salary_period = 'month') DESC, COALESCE(j.salary_max, j.salary_min, 0) DESC",
        default    => 'j.created_at DESC, j.id DESC',
    };
    if ($f['q'] !== '' && $f['sort'] === 'newest') {
        $order = '(j.title LIKE :qorder) DESC, ' . $order;
        $params[':qorder'] = '%' . $f['q'] . '%';
    }

    $pages = max(1, (int) ceil($total / $per));
    $page  = min(max(1, $page), $pages);
    $offset = ($page - 1) * $per;

    $sql = "SELECT j.*, c.name AS company_name, c.is_demo AS company_demo,
                   cat.name AS category_name, cat.slug AS category_slug, cat.grp AS category_group
            $base ORDER BY $order LIMIT $per OFFSET $offset";
    $st = db()->prepare($sql);
    $st->execute($params);

    return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function get_job(int $id): ?array
{
    $st = db()->prepare(job_select_sql() . ' WHERE j.id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function job_is_open(array $job): bool
{
    return $job['status'] === 'published' && (!$job['deadline'] || days_left($job['deadline']) >= 0);
}

function latest_jobs(int $limit = 6, string $extraWhere = '', string $order = 'j.created_at DESC'): array
{
    $sql = job_select_sql() . ' WHERE ' . public_job_condition() . ($extraWhere ? " AND $extraWhere" : '') . " ORDER BY $order LIMIT " . (int) $limit;
    return db()->query($sql)->fetchAll();
}

function saved_job_ids(): array
{
    static $ids = null;
    if ($ids !== null) return $ids;
    $u = current_user();
    if (!$u || $u['role'] !== 'seeker') return $ids = [];
    $st = db()->prepare('SELECT job_id FROM saved_jobs WHERE user_id = ?');
    $st->execute([$u['id']]);
    return $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

/* ======================================================================
 * Rule-based Smart Job Matching
 * ----------------------------------------------------------------------
 * A transparent weighted score out of 100. No AI / machine learning.
 *   Required skills .... 25   Preferred skills ... 10
 *   Job category ....... 15   Experience level ... 15
 *   Education .......... 10   Location ........... 10
 *   Employment type ....  5   Work arrangement ...  5
 *   Salary expectation .  5
 * Disability, gender, age and other personal characteristics are never
 * used. Missing skills only lower the score; they never exclude a job.
 * ==================================================================== */

function normalize_skill(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = str_replace(['.js', ' js'], 'js', $s);
    return preg_replace('/[^a-z0-9+#]/', '', $s);
}

function match_job(array $p, array $job): array
{
    $score = 0;
    $reasons = [];  // [tone, text]  tone: good | info | gap
    $userSkills = [];
    foreach ($p['skills'] as $s) $userSkills[normalize_skill($s)] = $s;

    $req  = skills_list($job['required_skills']);
    $pref = skills_list($job['preferred_skills']);
    $matched = $missingReq = $missingPref = [];
    foreach ($req as $s)  isset($userSkills[normalize_skill($s)]) ? $matched[] = $s : $missingReq[] = $s;
    foreach ($pref as $s) isset($userSkills[normalize_skill($s)]) ? $matched[] = $s : $missingPref[] = $s;

    // Required skills (25)
    if ($req) {
        $hit = count($req) - count($missingReq);
        $score += 25 * $hit / count($req);
        $reasons[] = [$hit === count($req) ? 'good' : ($hit ? 'info' : 'gap'),
            "You have $hit of " . count($req) . ' required skills.'];
    } else {
        $score += 12;   // neutral: nothing to compare against
        $reasons[] = ['info', 'No specific skills are listed — training is usually provided.'];
    }
    // Preferred skills (10)
    if ($pref) {
        $hit = count($pref) - count($missingPref);
        $score += 10 * $hit / count($pref);
        if ($missingPref) $reasons[] = ['info', 'Preferred skills are a bonus, not a requirement.'];
    } else {
        $score += 5;
    }
    // Category (15)
    if ($p['category_id'] && (int) $p['category_id'] === (int) $job['category_id']) {
        $score += 15;
        $reasons[] = ['good', 'Matches your preferred category: ' . $job['category_name'] . '.'];
    } elseif ($p['category_group'] && $p['category_group'] === $job['category_group']) {
        $score += 7;
        $reasons[] = ['info', 'Related to your preferred field.'];
    } elseif (!$p['category_id']) {
        $score += 7;
    }
    // Experience (15)
    $need = experience_rank($job['experience_level']);
    $have = experience_rank($p['experience_level'] ?: 'fresher');
    $levels = experience_levels();
    if ($have >= $need) {
        $score += 15;
        $reasons[] = ['good', 'Your experience level meets the requirement (' . $levels[$job['experience_level']] . ').'];
    } elseif ($need - $have === 1) {
        $score += 7;
        $reasons[] = ['gap', 'Asks for slightly more experience (' . $levels[$job['experience_level']] . '). Projects and internships can help close this gap.'];
    } else {
        $reasons[] = ['gap', 'Requires more experience (' . $levels[$job['experience_level']] . ').'];
    }
    // Education (10)
    $eduNeed = (int) $job['education_level'];
    $eduHave = $p['education_level'];
    $edu = education_levels();
    if ($eduHave === null) {
        $score += 5;
    } elseif ($eduHave >= $eduNeed) {
        $score += 10;
        if ($eduNeed > 0) $reasons[] = ['good', 'Meets the education requirement (' . $edu[$eduNeed] . ').'];
    } elseif ($eduNeed - $eduHave === 1) {
        $score += 4;
        $reasons[] = ['gap', 'Education requirement is ' . $edu[$eduNeed] . '. Some employers accept equivalent experience.'];
    } else {
        $reasons[] = ['gap', 'Education requirement is ' . $edu[$eduNeed] . '.'];
    }
    // Location (10)
    $arr = $job['work_arrangement'];
    if ($arr === 'remote') {
        $score += in_array($p['arrangement'], ['remote', 'any', ''], true) ? 10 : 6;
        $reasons[] = ['good', 'Remote role — location is not a barrier.'];
    } elseif (!$p['district']) {
        $score += 5;
    } elseif ($p['district'] === $job['district']) {
        $score += 10;
        $reasons[] = ['good', 'Located in your preferred district (' . $job['district'] . ').'];
    } elseif ($p['division'] && $p['division'] === $job['division']) {
        $score += 5;
        $reasons[] = ['info', 'In your division (' . $job['division'] . '), but a different district.'];
    } else {
        $reasons[] = ['gap', 'Located outside your preferred area (' . format_location($job) . ').'];
    }
    // Employment type (5)
    if (!$p['employment_type'] || $p['employment_type'] === $job['employment_type']) {
        $score += 5;
        if ($p['employment_type']) $reasons[] = ['good', 'Matches your preferred employment type.'];
    }
    // Arrangement (5)
    if (in_array($p['arrangement'], ['', 'any'], true) || $p['arrangement'] === $arr) {
        $score += 5;
    }
    // Salary (5)
    $exp = (int) ($p['expected_salary'] ?? 0);
    $top = (int) ($job['salary_max'] ?: $job['salary_min']);
    if ($exp <= 0) {
        $score += 5;
    } elseif (!$top) {
        $score += 3;
        $reasons[] = ['info', 'Salary is not disclosed — you can ask during the interview.'];
    } elseif ($job['salary_period'] !== 'month') {
        $score += 3;
        $reasons[] = ['info', 'Pay is per ' . $job['salary_period'] . ', so compare it with your monthly expectation yourself.'];
    } elseif ($top >= $exp) {
        $score += 5;
        $reasons[] = ['good', 'Salary range reaches your expectation of ' . taka($exp) . '.'];
    } else {
        $reasons[] = ['gap', 'Salary range is below your expectation of ' . taka($exp) . '.'];
    }

    $score = (int) round($score);
    $label = $score >= 75 ? 'Strong match' : ($score >= 55 ? 'Good match' : 'Partial match');

    return [
        'score' => $score, 'label' => $label, 'reasons' => $reasons,
        'matched' => $matched, 'missing_required' => $missingReq, 'missing_preferred' => $missingPref,
    ];
}

/** Build the matcher profile from a seeker_profiles row (or form data). */
function matcher_profile(array $row): array
{
    $catGroup = null;
    if (!empty($row['preferred_category_id'])) {
        foreach (categories() as $c) if ((int) $c['id'] === (int) $row['preferred_category_id']) $catGroup = $c['grp'];
    }
    return [
        'skills'          => skills_list($row['skills'] ?? ''),
        'education_level' => isset($row['education_level']) && $row['education_level'] !== '' && $row['education_level'] !== null ? (int) $row['education_level'] : null,
        'experience_level'=> $row['experience_level'] ?? 'fresher',
        'category_id'     => $row['preferred_category_id'] ?? null,
        'category_group'  => $catGroup,
        'district'        => $row['district'] ?? '',
        'division'        => $row['division'] ?? (isset($row['district']) ? division_of((string) $row['district']) : ''),
        'employment_type' => $row['preferred_employment_type'] ?? '',
        'arrangement'     => $row['preferred_arrangement'] ?? 'any',
        'expected_salary' => $row['expected_salary'] ?? null,
    ];
}

function recommend_jobs(array $profile, int $limit = 12, int $minScore = 40): array
{
    $jobs = db()->query(job_select_sql() . ' WHERE ' . public_job_condition())->fetchAll();
    $out = [];
    foreach ($jobs as $job) {
        $m = match_job($profile, $job);
        if ($m['score'] >= $minScore) $out[] = ['job' => $job, 'match' => $m];
    }
    usort($out, fn($a, $b) => $b['match']['score'] <=> $a['match']['score'] ?: strcmp($b['job']['created_at'], $a['job']['created_at']));
    return array_slice($out, 0, $limit);
}

function seeker_profile(int $userId): array
{
    $st = db()->prepare('SELECT * FROM seeker_profiles WHERE user_id = ?');
    $st->execute([$userId]);
    return $st->fetch() ?: [];
}

function profile_completeness(array $p): int
{
    $fields = ['headline', 'phone', 'district', 'education_level', 'education_detail', 'skills', 'experience_level', 'preferred_category_id', 'preferred_employment_type', 'preferred_arrangement'];
    $done = 0;
    foreach ($fields as $f) if (isset($p[$f]) && $p[$f] !== '' && $p[$f] !== null) $done++;
    return (int) round($done / count($fields) * 100);
}
