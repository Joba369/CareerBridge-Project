<?php
/**
 * Static reference data and label helpers.
 */

/** All 8 divisions and 64 districts of Bangladesh. */
function bd_locations(): array
{
    return [
        'Dhaka'      => ['Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail'],
        'Chattogram' => ['Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', "Cox's Bazar", 'Cumilla', 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati'],
        'Rajshahi'   => ['Bogura', 'Chapai Nawabganj', 'Joypurhat', 'Naogaon', 'Natore', 'Pabna', 'Rajshahi', 'Sirajganj'],
        'Khulna'     => ['Bagerhat', 'Chuadanga', 'Jashore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura', 'Meherpur', 'Narail', 'Satkhira'],
        'Barishal'   => ['Barguna', 'Barishal', 'Bhola', 'Jhalokati', 'Patuakhali', 'Pirojpur'],
        'Sylhet'     => ['Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet'],
        'Rangpur'    => ['Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon'],
        'Mymensingh' => ['Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'],
    ];
}

function all_districts(): array
{
    $out = [];
    foreach (bd_locations() as $d) $out = array_merge($out, $d);
    sort($out);
    return $out;
}

function division_of(string $district): ?string
{
    foreach (bd_locations() as $div => $list) {
        if (in_array($district, $list, true)) return $div;
    }
    return null;
}

function employment_types(): array
{
    return [
        'full_time'  => 'Full-time',
        'part_time'  => 'Part-time',
        'temporary'  => 'Temporary',
        'shift'      => 'Shift-based',
        'internship' => 'Internship',
        'contract'   => 'Contract',
    ];
}

function work_arrangements(): array
{
    return ['onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid'];
}

function experience_levels(): array
{
    return [
        'fresher' => 'Fresher / Entry-level',
        'junior'  => 'Junior (1–2 yrs)',
        'mid'     => 'Mid-level (3–5 yrs)',
        'senior'  => 'Senior (5+ yrs)',
    ];
}

function experience_rank(string $level): int
{
    return ['fresher' => 0, 'junior' => 1, 'mid' => 2, 'senior' => 3][$level] ?? 0;
}

/** Education levels, stored as a small integer so they can be compared. */
function education_levels(): array
{
    return [
        0 => 'No formal requirement',
        1 => 'SSC / O Level',
        2 => 'HSC / A Level',
        3 => 'Diploma',
        4 => "Bachelor's degree",
        5 => "Master's degree",
    ];
}

function shifts(): array
{
    return [
        'morning'  => 'Morning',
        'day'      => 'Day',
        'evening'  => 'Evening',
        'night'    => 'Night',
        'weekend'  => 'Weekends',
        'flexible' => 'Flexible hours',
    ];
}

function salary_periods(): array
{
    return ['month' => 'month', 'hour' => 'hour', 'day' => 'day', 'shift' => 'shift', 'project' => 'project'];
}

/** Accessibility facilities an employer can confirm on a job listing. */
function accessibility_features(): array
{
    return [
        'acc_step_free'      => ['label' => 'Step-free access / ramp', 'icon' => 'ramp'],
        'acc_lift'           => ['label' => 'Lift access', 'icon' => 'lift'],
        'acc_toilet'         => ['label' => 'Accessible toilet', 'icon' => 'door-open'],
        'acc_flexible_hours' => ['label' => 'Flexible working hours', 'icon' => 'clock'],
        'acc_remote_option'  => ['label' => 'Remote work option', 'icon' => 'laptop'],
        'acc_interview'      => ['label' => 'Interview accommodations', 'icon' => 'message'],
    ];
}

function application_statuses(): array
{
    // Status that the EMPLOYER sets on an application submitted through CareerBridge.
    return [
        'submitted'    => 'Submitted',
        'under_review' => 'Under Review',
        'shortlisted'  => 'Shortlisted',
        'interview'    => 'Interview',
        'offer'        => 'Offer',
        'rejected'     => 'Not selected',
        'withdrawn'    => 'Withdrawn',
    ];
}

function tracker_statuses(): array
{
    // Status that the JOB SEEKER sets in their private application tracker.
    return [
        'applied'      => 'Applied',
        'under_review' => 'Under Review',
        'interview'    => 'Interview',
        'offer'        => 'Offer',
        'rejected'     => 'Rejected',
        'withdrawn'    => 'Withdrawn',
        'no_response'  => 'No Response Yet',
    ];
}

function status_tone(string $status): string
{
    return [
        'applied' => 'blue', 'submitted' => 'blue',
        'under_review' => 'amber', 'shortlisted' => 'amber',
        'interview' => 'violet',
        'offer' => 'green',
        'rejected' => 'red',
        'withdrawn' => 'grey',
        'no_response' => 'slate',
        'published' => 'green', 'draft' => 'grey', 'closed' => 'slate',
    ][$status] ?? 'grey';
}

function badge(string $text, string $tone = 'grey'): string
{
    return '<span class="badge badge-' . e($tone) . '">' . e($text) . '</span>';
}

/* ---------- Formatting ---------- */
function taka($n): string
{
    return 'Tk ' . number_format((float) $n);
}

function format_salary(array $job): string
{
    $min = $job['salary_min'] ?? null;
    $max = $job['salary_max'] ?? null;
    $per = $job['salary_period'] ?? 'month';
    if (!$min && !$max) return 'Salary not disclosed';
    if ($min && $max && $min != $max) $s = taka($min) . '–' . number_format((float) $max);
    else $s = taka($max ?: $min);
    return $s . ' / ' . $per;
}

function format_location(array $job): string
{
    if (($job['work_arrangement'] ?? '') === 'remote' && empty($job['district'])) return 'Remote · Bangladesh';
    $parts = array_filter([$job['area'] ?? '', $job['district'] ?? '']);
    $loc = $parts ? implode(', ', $parts) : ($job['division'] ?? 'Bangladesh');
    return ($job['work_arrangement'] ?? '') === 'remote' ? 'Remote · ' . $loc : $loc;
}

function format_date(?string $d, string $fmt = 'j M Y'): string
{
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date($fmt, $t) : '';
}

function time_ago(?string $d): string
{
    if (!$d) return '';
    $diff = time() - strtotime($d);
    if ($diff < 3600) return 'Just now';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    $days = (int) floor($diff / 86400);
    if ($days === 1) return 'Yesterday';
    if ($days < 30) return $days . ' days ago';
    return format_date($d);
}

function days_left(?string $deadline): ?int
{
    if (!$deadline) return null;
    $today = new DateTime('today');
    $dl = new DateTime($deadline);
    return (int) $today->diff($dl)->format('%r%a');
}

function skills_list(?string $csv): array
{
    if (!$csv) return [];
    return array_values(array_filter(array_map('trim', explode(',', $csv)), fn($s) => $s !== ''));
}

function initials(string $name): string
{
    $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $i = '';
    foreach (array_slice($words, 0, 2) as $w) $i .= mb_strtoupper(mb_substr($w, 0, 1));
    return $i ?: 'CB';
}

function logo_tone(int $id): string
{
    $tones = ['t1', 't2', 't3', 't4', 't5', 't6'];
    return $tones[$id % count($tones)];
}

function query_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    foreach ($q as $k => $v) if ($v === null || $v === '') unset($q[$k]);
    return '?' . http_build_query($q);
}

/** "1 application", "3 applications" */
function plural(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}
