</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a class="brand" href="<?= e(url('index.php')) ?>"><?= logo_mark(32) ?><span>Career<b>Bridge</b></span></a>
        <p class="mt-3" style="max-width:320px">A job discovery and career-building platform for students, freshers and professionals across Bangladesh.</p>
        <p class="small" style="color:#8e98ab">University project. Listings marked “Demo” are fictional sample data.</p>
      </div>
      <div>
        <h3>Find work</h3>
        <ul>
          <li><a href="<?= e(url('jobs.php')) ?>">All jobs</a></li>
          <li><a href="<?= e(url('local.php')) ?>">Local part-time</a></li>
          <li><a href="<?= e(url('remote.php')) ?>">Remote &amp; IT</a></li>
          <li><a href="<?= e(url('freshers.php')) ?>">Fresher opportunities</a></li>
          <li><a href="<?= e(url('inclusive.php')) ?>">Inclusive jobs</a></li>
        </ul>
      </div>
      <div>
        <h3>Career tools</h3>
        <ul>
          <li><a href="<?= e(url('cv-builder.php')) ?>">CV Writing &amp; Editing</a></li>
          <li><a href="<?= e(url('matching.php')) ?>">Smart Job Matching</a></li>
          <li><a href="<?= e(url('account/applications.php')) ?>">Application Tracking</a></li>
          <li><a href="<?= e(url('freshers.php#guides')) ?>">Career guidance</a></li>
        </ul>
      </div>
      <div>
        <h3>Employers</h3>
        <ul>
          <li><a href="<?= e(url('employers.php')) ?>">Why CareerBridge</a></li>
          <li><a href="<?= e(url('register.php?role=employer')) ?>">Create employer account</a></li>
          <li><a href="<?= e(url('employer/job-edit.php')) ?>">Post a job</a></li>
          <li><a href="<?= e(url('login.php')) ?>">Employer sign in</a></li>
        </ul>
      </div>
      <div>
        <h3>About</h3>
        <ul>
          <li><a href="<?= e(url('about.php')) ?>">About CareerBridge</a></li>
          <li><a href="<?= e(url('about.php#privacy')) ?>">Privacy</a></li>
          <li><a href="<?= e(url('about.php#accessibility')) ?>">Accessibility</a></li>
          <li><a href="<?= e(url('about.php#demo-data')) ?>">Demo data notice</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> CareerBridge · Built for Bangladesh</span>
      <span>Never pay a fee to apply for a job. <a href="<?= e(url('guide.php?slug=job-application-guidance')) ?>">Stay safe</a></span>
    </div>
  </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.CB_LOCATIONS = <?= json_encode(bd_locations()) ?>;</script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach (($extraJs ?? []) as $js): ?><script src="<?= e(asset($js)) ?>"></script><?php endforeach; ?>
</body>
</html>
