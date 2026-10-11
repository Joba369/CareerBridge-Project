<?php
require __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$isSeeker = $user && $user['role'] === 'seeker';

$server = null;
if ($isSeeker) {
    $st = db()->prepare('SELECT template, data, updated_at FROM cvs WHERE user_id = ?');
    $st->execute([$user['id']]);
    if ($row = $st->fetch()) {
        $server = ['template' => $row['template'], 'data' => json_decode($row['data'], true), 'updated_at' => $row['updated_at'], 'updated_label' => format_date($row['updated_at'], 'j M Y, g:i a')];
    }
}

$title = 'CV Writing & Editing Hub';
$nav = 'tools';
$metaDesc = 'Write and edit a professional CV with ready-made templates, live preview and print-to-PDF. Free, no AI, made for students and job seekers in Bangladesh.';
$extraCss = ['css/cv.css'];
$extraJs = ['js/cv-render.js', 'js/cv-builder.js'];
require __DIR__ . '/includes/partials/header.php';

$templates = [
    'classic' => ['Classic', '<i class="h" style="margin:0 auto 5px"></i><i class="xs" style="margin:0 auto 8px"></i><i class="rule"></i><i></i><i class="s"></i><i class="rule"></i><i></i><i class="xs"></i>'],
    'modern'  => ['Modern', '<span><i class="h" style="width:90%"></i><i class="a" style="width:70%"></i><i></i><i class="s"></i><i class="xs"></i></span><span><i class="a"></i><i></i><i class="s"></i><i></i><i class="a"></i><i class="xs"></i></span>'],
    'minimal' => ['Minimal', '<i class="h"></i><i class="xs" style="margin-bottom:10px"></i><i class="a"></i><i></i><i class="s"></i><i class="a"></i><i></i>'],
    'compact' => ['Compact', '<span class="band" style="display:block"><i class="h"></i><i class="xs"></i></span><i class="a"></i><i></i><i class="s"></i><i class="a"></i><i></i><i class="xs"></i>'],
];
?>
<div class="cvb-toolbar">
  <div class="container">
    <h1><?= icon('pen', 'text-brand', 22) ?> CV Writing &amp; Editing Hub</h1>
    <span class="save-status" id="saveStatus" role="status" aria-live="polite"><?= icon('check-circle', '', 16) ?> <span>Ready</span></span>
    <?php if ($isSeeker): ?>
      <button type="button" class="btn btn-outline btn-sm" id="saveServer"><?= icon('save', '', 18) ?> Save to my account</button>
    <?php endif; ?>
    <button type="button" class="btn btn-primary btn-sm" id="printCv"><?= icon('download', '', 18) ?> Download PDF</button>
  </div>
</div>

<div class="container">
  <?php if (!$user): ?>
    <div class="notice" style="margin-top:20px"><?= icon('info', '', 20) ?><div>You can use the CV builder without an account — your work is saved in this browser. <a href="<?= e(url('register.php')) ?>">Create a free job seeker account</a> to save it to your profile and attach it when you apply.</div></div>
  <?php elseif (!$isSeeker): ?>
    <div class="notice" style="margin-top:20px"><?= icon('info', '', 20) ?><div>You are signed in as an employer. You can try the builder, but saving to an account and attaching CVs to applications is for job seeker accounts.</div></div>
  <?php elseif ($server): ?>
    <div class="notice" style="margin-top:20px"><?= icon('check-circle', '', 20) ?><div>Loaded the CV saved to your account (last saved <?= e($server['updated_label']) ?>). This saved version is attached when you choose “Use my CareerBridge CV” while applying.</div></div>
  <?php else: ?>
    <div class="notice" style="margin-top:20px"><?= icon('info', '', 20) ?><div>Build your CV below, then press <strong>Save to my account</strong> so you can attach it to job applications.</div></div>
  <?php endif; ?>

  <div class="cvb" id="cvb" data-view="edit">
    <div class="cvb-tabs" role="tablist" aria-label="Builder view">
      <button type="button" role="tab" aria-selected="true" data-view-btn="edit"><?= icon('edit', '', 16) ?> Edit</button>
      <button type="button" role="tab" aria-selected="false" data-view-btn="preview"><?= icon('eye', '', 16) ?> Preview</button>
    </div>

    <div class="cvb-editor">
      <div class="cvb-card open">
        <div class="cvb-card-head"><h2><?= icon('layout', 'text-brand', 18) ?> Template &amp; examples</h2></div>
        <div class="cvb-card-body" style="padding-bottom:16px">
          <fieldset class="fieldset" style="border:0;padding:0;margin:0 0 16px">
            <legend class="label" style="margin-bottom:8px">Choose a template <span class="muted small">(your content stays the same when you switch)</span></legend>
            <div class="tpl-grid">
              <?php foreach ($templates as $key => [$label, $thumb]): ?>
                <label class="tpl">
                  <input type="radio" name="template" value="<?= e($key) ?>"<?= $key === 'classic' ? ' checked' : '' ?>>
                  <span class="thumb t-<?= e($key) ?>" aria-hidden="true"><?= $thumb ?></span>
                  <span class="name"><?= e($label) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <div class="label" style="margin-bottom:8px">Start from an example</div>
          <div class="examples-row">
            <button type="button" class="btn btn-outline btn-sm" data-example="fresher"><?= icon('cap', '', 16) ?> Fresher example</button>
            <button type="button" class="btn btn-outline btn-sm" data-example="experienced"><?= icon('briefcase', '', 16) ?> Experienced example</button>
            <button type="button" class="btn btn-ghost btn-sm" id="clearCv"><?= icon('refresh', '', 16) ?> Start blank</button>
          </div>
          <p class="help" style="margin-top:10px">Examples use fictional sample details. Replace everything with your own information.</p>
        </div>
      </div>

      <details class="cvb-card cvb-guide">
        <summary class="cvb-card-head" style="cursor:pointer;list-style:none"><h2><?= icon('book', 'text-brand', 18) ?> New to CV writing? Read this first</h2><?= icon('chevron-down', 'chev', 18) ?></summary>
        <div style="padding:4px 18px 16px">
          <ol class="small" style="padding-left:18px;margin:8px 0 0;display:grid;gap:6px">
            <li><strong>Fill in your personal details.</strong> Use a professional email address and a phone number you answer.</li>
            <li><strong>Open each section</strong> and press <em>Add entry</em>. Every section has a short tip to guide you.</li>
            <li><strong>Reorder sections</strong> with the up/down arrows. Put your strongest section first — freshers usually lead with Education and Projects.</li>
            <li><strong>Hide sections</strong> you don't need with the eye icon. Hidden sections keep their content.</li>
            <li><strong>Pick a template.</strong> Switching templates never deletes your text.</li>
            <li><strong>Download PDF:</strong> choose “Save as PDF” as the printer in the print dialog. Turn off “Headers and footers” for a clean result.</li>
          </ol>
          <p class="small muted" style="margin:10px 0 0">Keep it to 1 page as a fresher and 2 pages when experienced. Do not add a photo, religion or marital status unless an employer specifically asks. More tips in the <a href="<?= e(url('guide.php?slug=writing-your-first-cv')) ?>">CV writing guide</a>.</p>
        </div>
      </details>

      <div class="cvb-card open" id="personalCard">
        <div class="cvb-card-head"><h2><?= icon('file', 'text-brand', 18) ?> Personal details</h2></div>
        <div class="cvb-card-body">
          <div class="form-row">
            <div class="field"><label class="label" for="p_fullName">Full name</label><input class="input" id="p_fullName" data-path="personal.fullName" autocomplete="name" placeholder="e.g. Your full name"></div>
            <div class="field"><label class="label" for="p_headline">Professional title</label><input class="input" id="p_headline" data-path="personal.headline" placeholder="e.g. Junior Web Developer"></div>
          </div>
          <div class="form-row">
            <div class="field"><label class="label" for="p_email">Email</label><input class="input" id="p_email" type="email" data-path="personal.email" autocomplete="email" placeholder="name@example.com"></div>
            <div class="field"><label class="label" for="p_phone">Phone</label><input class="input" id="p_phone" type="tel" data-path="personal.phone" autocomplete="tel" placeholder="01XXX-XXXXXX"></div>
          </div>
          <div class="form-row">
            <div class="field"><label class="label" for="p_location">Location</label><input class="input" id="p_location" data-path="personal.location" placeholder="e.g. Mirpur, Dhaka"></div>
            <div class="field"><label class="label" for="p_linkedin">LinkedIn <span class="opt">(optional)</span></label><input class="input" id="p_linkedin" data-path="personal.linkedin" placeholder="linkedin.com/in/yourname"></div>
          </div>
          <div class="field"><label class="label" for="p_website">Portfolio / GitHub <span class="opt">(optional)</span></label><input class="input" id="p_website" data-path="personal.website" placeholder="github.com/yourname"></div>
        </div>
      </div>

      <div id="sectionCards" class="cvb-editor"></div>
      <p class="small muted" style="text-align:center">Your CV is autosaved in this browser as you type<?= $isSeeker ? '. Press “Save to my account” to update the version employers receive.' : '.' ?></p>
    </div>

    <div class="cvb-preview-wrap">
      <div class="cvb-preview-meta"><span><?= icon('eye', '', 16) ?> Live preview · A4</span><span id="pageHint"></span></div>
      <div class="cvb-preview-frame" id="previewFrame">
        <div class="cvb-preview-scale" id="previewScale"><div id="cvPreview" class="cv-page"></div></div>
      </div>
    </div>
  </div>
</div>

<script id="cvServerData" type="application/json"><?= json_encode($server, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script>window.CV_ICONS = <?= json_encode(['up' => icon('arrow-up', '', 18), 'down' => icon('arrow-down', '', 18), 'eye' => icon('eye', '', 18), 'eyeOff' => icon('eye-off', '', 18), 'trash' => icon('trash', '', 18), 'plus' => icon('plus', '', 18), 'chev' => icon('chevron-down', 'chev', 18), 'bulb' => icon('bulb', '', 16), 'check' => icon('check-circle', '', 16), 'alert' => icon('alert', '', 16), 'sec' => icon('layers', 'text-brand', 18)]) ?>;
window.CV_CTX = { canSave: <?= $isSeeker ? 'true' : 'false' ?>, userKey: <?= json_encode($user ? 'u' . $user['id'] : 'guest') ?>, api: <?= json_encode(url('api/cv.php')) ?> };</script>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
