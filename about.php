<?php
require __DIR__ . '/includes/bootstrap.php';
$title = 'About CareerBridge';
$nav = '';
$metaDesc = 'About CareerBridge, a university project job portal for Bangladesh: demo data notice, privacy and accessibility statement.';
require __DIR__ . '/includes/partials/header.php';
$toc = ['mission' => 'Our mission', 'demo-data' => 'Demo data notice', 'how' => 'How CareerBridge works', 'privacy' => 'Privacy', 'accessibility' => 'Accessibility statement', 'safety' => 'Safe job searching'];
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li aria-current="page">About</li></ol>
    <span class="eyebrow">About</span>
    <h1>Connecting people in Bangladesh with the right work</h1>
    <p class="lead">CareerBridge is a job portal built as a university project. It brings together local part-time work, remote and IT roles, fresher opportunities and inclusive jobs, along with free career tools.</p>
  </div>
</section>
<div class="container detail-layout about-layout">
  <nav class="sticky-side" aria-label="On this page">
    <div class="card card-pad">
      <h2 class="small" style="text-transform:uppercase;letter-spacing:.08em;color:var(--muted)">On this page</h2>
      <ul class="list-plain" style="display:grid;gap:8px">
        <?php foreach ($toc as $id => $label): ?><li><a href="#<?= e($id) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
  </nav>
  <div class="article stack" style="max-width:780px">
    <section id="mission" class="card card-pad-lg" style="scroll-margin-top:90px">
      <h2><?= icon('target', 'text-brand', 22) ?> Our mission</h2>
      <div class="prose">
        <p>Finding the first job — or a part-time job that fits around classes — is hard. Listings are scattered, requirements are unclear and many young people do not know where to start. CareerBridge aims to make that journey simpler for students, freshers and professionals across Bangladesh.</p>
        <ul>
          <li><strong>Discover</strong> local part-time jobs at shops, restaurants, cafés, supermarkets and coaching centres, plus remote and IT roles.</li>
          <li><strong>Prepare</strong> with a free CV builder and practical career guides.</li>
          <li><strong>Apply and track</strong> applications in one organised place.</li>
          <li><strong>Include everyone</strong> by showing accessibility information that employers provide.</li>
        </ul>
      </div>
    </section>

    <section id="demo-data" class="card card-pad-lg" style="scroll-margin-top:90px;border-color:#f6c9a8">
      <h2><?= icon('info', 'text-brand', 22) ?> Demo data notice</h2>
      <div class="prose">
        <p>This installation contains <strong>fictional sample data</strong> so the features can be demonstrated. Every demo company and listing is labelled <span class="badge badge-demo">Demo</span>.</p>
        <ul>
          <li>Demo companies, jobs, salaries and addresses are invented. They are <strong>not real vacancies</strong> and any similarity to real businesses is coincidental.</li>
          <li>Do not contact or visit addresses shown in demo listings.</li>
          <li>Demo accounts (password <code>Demo@1234</code>) exist so reviewers can explore the job seeker and employer dashboards.</li>
          <li>Jobs posted by real employer accounts created on this installation are not labelled as demo.</li>
        </ul>
      </div>
    </section>

    <section id="how" class="card card-pad-lg" style="scroll-margin-top:90px">
      <h2><?= icon('sliders', 'text-brand', 22) ?> How CareerBridge works</h2>
      <div class="prose">
        <p><strong>No artificial intelligence is used.</strong> Smart Job Matching uses a published points system based on skills, category, experience, education, location, employment type, work arrangement and salary. You can read the exact weights on the <a href="<?= e(url('matching.php#how')) ?>">matching page</a>. The CV builder formats the text you write — it does not generate content for you.</p>
        <p>Job seekers decide what to share. Employers only see an application when a candidate submits one to their job, and they can only see applications for their own listings.</p>
      </div>
    </section>

    <section id="privacy" class="card card-pad-lg" style="scroll-margin-top:90px">
      <h2><?= icon('lock', 'text-brand', 22) ?> Privacy</h2>
      <div class="prose">
        <ul>
          <li><strong>What we store:</strong> your account details (name, email, hashed password), your profile, saved jobs, CV builder content, applications and tracker entries.</li>
          <li><strong>Who can see it:</strong> your profile, saved jobs and tracker notes are private to you. When you apply, the employer for that job sees the details you submit with the application (contact details, cover note, CV and any accommodation request).</li>
          <li><strong>Accessibility preferences</strong> are optional. They only pre-fill filters on the Inclusive Jobs page and are never used in matching or shown to employers. We never ask for medical information.</li>
          <li><strong>Passwords</strong> are stored using secure one-way hashing. Forms are protected against cross-site request forgery.</li>
          <li><strong>Deleting your account</strong> from Account settings permanently removes your data, including uploaded CV files.</li>
          <li>The CV builder also keeps a draft in your own browser (local storage) so you do not lose work.</li>
        </ul>
      </div>
    </section>

    <section id="accessibility" class="card card-pad-lg" style="scroll-margin-top:90px">
      <h2><?= icon('keyboard', 'text-brand', 22) ?> Accessibility statement</h2>
      <div class="prose">
        <p>We want CareerBridge to be usable by everyone, including people who use a keyboard, screen reader, magnification or voice control. We aim to follow the Web Content Accessibility Guidelines (WCAG) 2.1 at level AA.</p>
        <ul>
          <li>All features work with a keyboard. A “Skip to main content” link appears when you press <kbd>Tab</kbd>.</li>
          <li>Focus outlines are always visible; text and buttons meet colour-contrast guidelines.</li>
          <li>Form fields have visible labels and clear error messages linked to each field.</li>
          <li>Pages use headings and landmarks so screen-reader users can navigate quickly.</li>
          <li>Animations are reduced when your device asks for reduced motion.</li>
          <li>No information is conveyed by colour alone — statuses also use text labels.</li>
        </ul>
        <p>Known limitations: the CV preview is a visual representation; screen-reader users can review all content in the editor fields instead. If you find a barrier, please let the project team know.</p>
      </div>
    </section>

    <section id="safety" class="card card-pad-lg" style="scroll-margin-top:90px">
      <h2><?= icon('shield', 'text-brand', 22) ?> Safe job searching</h2>
      <div class="prose">
        <ul>
          <li>Never pay money to apply for a job, attend training or “secure” a position.</li>
          <li>Be careful with offers that promise very high pay for very little work.</li>
          <li>Meet employers in public or official workplaces, and tell someone where you are going.</li>
        </ul>
        <p>Read more in <a href="<?= e(url('guide.php?slug=job-application-guidance')) ?>">How to Apply for Jobs Safely and Effectively</a>.</p>
      </div>
    </section>
  </div>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
