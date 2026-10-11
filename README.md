# CareerBridge — Job Portal for Bangladesh

CareerBridge is a responsive job portal built as a university project. It brings together **local part-time jobs**, **remote & IT roles**, **fresher opportunities** and **inclusive jobs**, plus free career tools: a **CV Writing & Editing Hub**, **transparent rule-based job matching**, **application tracking** and **fresher career guidance**. Employers get a separate portal to post jobs and review applications.

- **Stack:** PHP 8 (no framework) · MySQL / MariaDB (PDO) · hand-written CSS · vanilla JavaScript · inline SVG icons
- **Runs on:** XAMPP (Windows / macOS / Linux) at `http://localhost/careerbridge/`
- **No AI** is used anywhere. No external APIs are required. No photos or human imagery are used.

> **Demo data notice:** the database ships with fictional demo companies, jobs and accounts. Every demo listing is labelled **“Demo”**. None of them are real vacancies or real businesses.

---

## 1. Requirements

| Requirement | Notes |
|---|---|
| XAMPP 8.0 or newer | Includes Apache, PHP 8.x and MariaDB. PHP **8.0+** is required. |
| PHP extensions | `pdo_mysql`, `mbstring` (enabled by default in XAMPP). `fileinfo` is optional but recommended. |
| Browser | Any current Chrome, Edge, Firefox or Safari. |
| Internet | Optional. Only used to load the Inter / Plus Jakarta Sans web fonts; the site falls back to system fonts offline. |

---

## 2. Run it on localhost with XAMPP

1. **Copy the project folder** into XAMPP's web root so you have:
   - Windows: `C:\xampp\htdocs\careerbridge\`
   - macOS: `/Applications/XAMPP/htdocs/careerbridge/`
   - Linux: `/opt/lampp/htdocs/careerbridge/`
2. Open the **XAMPP Control Panel** and **Start** both **Apache** and **MySQL**.
3. Create the database — choose **one** option:
   - **Option A — one-click installer (easiest):** open <http://localhost/careerbridge/install.php> and click **Install CareerBridge**. It checks your setup, creates the `careerbridge` database and imports all tables and demo data.
   - **Option B — phpMyAdmin:** open <http://localhost/phpmyadmin>, click **Import**, choose `careerbridge/database/careerbridge.sql` and click **Import** (the file creates the database itself).
4. Open **<http://localhost/careerbridge/>** 🎉

### Demo accounts

All demo accounts use the password **`Demo@1234`**.

| Role | Email | What you can try |
|---|---|---|
| Job seeker | `seeker@demo.test` | Dashboard, profile, saved jobs, recommended jobs, tracker (with an upcoming interview), CV builder, applying |
| Job seeker 2 | `seeker2@demo.test` | A second applicant (used to show employers several applicants) |
| Employer | `employer@demo.test` | “Pixelbridge Labs” — job listings, drafts, closed jobs, 3 applications to review |
| Other employers | `kachpata@demo.test`, `nodibazar@demo.test`, `brightpath@demo.test`, `shapla@demo.test`, `orbit@demo.test`, `codenest@demo.test`, … | Each owns one demo company and only sees its own applicants |

You can also register brand-new seeker or employer accounts from **Get Started**.

---

## 3. Configuration

All settings are in **`includes/config.php`**. The defaults match a fresh XAMPP install.

| Setting | Default | Change it when… |
|---|---|---|
| `DB_HOST` / `DB_PORT` | `localhost` / `3306` | MySQL runs on another port (e.g. `3307` if 3306 is taken). |
| `DB_NAME` | `careerbridge` | You want a different database name (the installer respects it). |
| `DB_USER` / `DB_PASS` | `root` / *(empty)* | You set a MySQL root password or use another user. |
| `APP_DEV_MODE` | `true` | Set to `false` before sharing publicly (hides technical errors and the on-screen password-reset link, and disables re-installing). |
| `APP_BASE` | `null` (auto-detect) | Auto-detection fails, e.g. set `'/careerbridge'`. |
| `JOBS_PER_PAGE` | `9` | You want more or fewer results per page. |
| `MAX_CV_UPLOAD_BYTES` | 2 MB | You want to allow larger PDF CVs. |

Settings can also be supplied as environment variables (`CB_DB_HOST`, `CB_DB_PORT`, `CB_DB_NAME`, `CB_DB_USER`, `CB_DB_PASS`).

**Password recovery & e-mail:** XAMPP has no mail server configured, so in development mode the “Forgot password” page shows the reset link on screen (valid for 1 hour). To send real e-mails you would need to configure SMTP (not included).

**Folder permissions:** `uploads/cvs/` must be writable by Apache (it is by default on Windows XAMPP). On macOS/Linux: `chmod 775 uploads/cvs`.

---

## 4. Make your localhost public (share a live link)

Keep XAMPP's Apache + MySQL running, then use **one** of these tunnels. Ready-made scripts are in the `tools/` folder.

### Option 1 — Cloudflare Quick Tunnel (free, no account)
1. Install: `winget install --id Cloudflare.cloudflared` (Windows) or `brew install cloudflared` (macOS).
2. Double-click **`tools/share-with-cloudflare.bat`** (or run `cloudflared tunnel --url http://localhost:80`).
3. It prints an address like `https://random-words.trycloudflare.com` → share **`https://random-words.trycloudflare.com/careerbridge/`**.

### Option 2 — ngrok (free account)
1. Download from <https://ngrok.com/download>, sign up and run once: `ngrok config add-authtoken YOUR_TOKEN`.
2. Double-click **`tools/share-with-ngrok.bat`** (or run `ngrok http 80`).
3. Share the `https://….ngrok-free.app/careerbridge/` address it shows (use the exact domain ngrok prints). Free-plan visitors see an ngrok notice page first and click **Visit Site**.

On macOS/Linux you can use `./tools/share-public.sh cloudflare` or `./tools/share-public.sh ngrok`.
If Apache uses a different port (for example 8080), use `http://localhost:8080` in the commands above.

### Option 3 — Same Wi-Fi / LAN only
Find your computer's IP (`ipconfig` on Windows → IPv4 Address, e.g. `192.168.0.105`) and open `http://192.168.0.105/careerbridge/` from another device on the same network. Allow Apache through Windows Firewall if asked.

### Before you share publicly ✅
- Delete **`install.php`** (or at least set `APP_DEV_MODE` to `false`).
- Set `APP_DEV_MODE` to `false` in `includes/config.php`.
- Give the MySQL `root` user a password (and put it in `DB_PASS`), or create a dedicated MySQL user.
- Remember the demo accounts have a public password — reset or delete them if needed.
- The tunnel URL changes each time you restart a free tunnel.

---

## 5. Features (what is actually implemented)

### Public site
- **Homepage:** navigation with mobile menu, hero search (keyword + location), abstract hero artwork with sample job cards, Explore Opportunities (4 working cards), career tools, Fresher Career Guidance access, Featured jobs, Latest opportunities, Browse by category, How it works, employer section, footer.
- **Find Jobs** (`jobs.php`): keyword search, division → district → area filters, category, employment type, work arrangement, experience level, minimum salary, skills, accessibility filters, sorting (newest / deadline / salary), active-filter chips, pagination, empty states.
- **Local Part-Time** (`local.php`): part-time, temporary and shift-based on-site jobs at shops, restaurants, cafés, supermarkets, coaching centres and small businesses, with location, area, salary, category, shift and type filters. Results are only described as “in your area” when you actually choose a location.
- **Remote & IT** (`remote.php`): Web, Frontend, Backend, Software Engineering, UI/UX & Graphic Design, Digital Marketing, IT Support and Other Tech; remote / hybrid / on-site, full-time / part-time / internship, experience level, salary and skills filters.
- **Fresher Career Guidance** (`freshers.php`, `guide.php`): 11 educational guides (roadmaps, project ideas, CV tips, interview preparation, application guidance) filterable by career path and level, skills-by-role checklist, a 5-step first-job plan, and entry-level jobs & internships.
- **Inclusive Jobs** (`inclusive.php`): employer-provided accessibility information (step-free access/ramps, lift, accessible toilet, flexible hours, remote option, interview accommodations) with counts and filters. Voluntary preferences from a seeker's profile can pre-fill the filters; they are never used for matching or shown to employers. No medical information is ever requested.
- **Job details** (`job.php`): full description, requirements, skills, salary, schedule, location, deadline, accessibility details, company box, Apply and Save buttons, safety notice.
- **Company pages** (`company.php`), **For Employers** (`employers.php`), **Career Tools** hub (`tools.php`), **About / Demo data / Privacy / Accessibility** (`about.php`).

### Job seeker account
- Register, sign in, sign out, password recovery (token, 1-hour expiry).
- **Dashboard:** stats, upcoming interviews, recommended jobs, profile completeness.
- **Profile:** education, skills, experience, preferred category/location/type/arrangement, expected salary, optional accessibility preferences.
- **Saved jobs:** save/unsave from any card (AJAX with normal-form fallback).
- **Apply workflow:** pre-filled form, choose your CareerBridge CV (snapshot is sent), upload a PDF (≤ 2 MB, validated), or apply without a CV; optional interview-accommodation request; consent checkbox; duplicate and closed-job protection.
- **Application tracker:** statuses *Applied, Under Review, Interview, Offer, Rejected, Withdrawn, No Response Yet* (shown as “not a rejection”); add external applications; edit status, interview date/time and **private notes**; filter by status; upcoming interviews; withdraw a CareerBridge application; delete records. Employer status changes sync to the tracker automatically.
- **Account settings:** change name/email, change password, delete account (also deletes uploaded CV files).

### CV Writing & Editing Hub (`cv-builder.php`)
- Works for guests (saved in the browser) and seekers (also saved to the account and attachable to applications).
- Sections: personal details, career objective, education, work experience, projects, skills, training & certifications, languages, references (“Available on request” when empty).
- Add / edit / remove / reorder entries, reorder / rename / hide sections.
- 4 templates (Classic, Modern two-column, Minimal, Compact); switching never loses data.
- Live A4 preview with page-count hint; mobile Edit/Preview tabs.
- **Download PDF** via the browser's print dialog (“Save as PDF”) with print-optimised A4 styles.
- Beginner instructions, a tip for every section, and **fresher** and **experienced** example CVs. No AI, no photo field.

### Smart Job Matching (`matching.php`) — rule-based, not AI
Each open job gets up to 100 points:

| Factor | Points | Factor | Points |
|---|---|---|---|
| Required skills | 25 | Location | 10 |
| Job category | 15 | Employment type | 5 |
| Experience level | 15 | Remote / on-site preference | 5 |
| Preferred skills | 10 | Salary expectation | 5 |
| Education | 10 | | |

Partial points are given for close matches; empty profile fields score neutrally; jobs listing no skills get half the skill points. Results show the score, a label (Strong ≥ 75, Good ≥ 55, Partial ≥ 40), **skills you have**, **skills to develop** (required vs. bonus), education/experience requirements and a line-by-line explanation. Missing skills never hide a job. Disability, accessibility preferences, gender, religion and age are never used. Guests can run a quick check without saving.

### Employer portal
- Register as an employer (company created automatically), company profile with public company page.
- **Dashboard:** active listings, applications, new applications, upcoming interviews, recent applicants.
- **Jobs:** create, edit, save as draft, publish, close, reopen (requires a future deadline), delete drafts. Tabs for Active / Drafts / Closed. Validation for every field (district required unless remote, salary range, vacancies 1–999, deadline not in the past).
- Job fields: title, category, vacancies, employment type, work arrangement, experience, minimum education, district/area, salary range & period, working hours, shift, description, responsibilities, requirements, required & preferred skills, how to apply, deadline, accessibility features and notes.
- **Applications:** filter by job and status, paginated; review page with contact details, cover note, rendered CareerBridge CV or PDF download, accommodation request; update status (Under review, Shortlisted, Interview with date/time, Offer, Not selected) and keep a private internal note.
- **Permissions:** employers can only see/edit their own jobs and applications; uploaded CVs are served only to the applicant and the owning employer.

### Security & quality
- PDO prepared statements everywhere, `password_hash`/`password_verify`, session ID regeneration on login, CSRF tokens on every form and AJAX call, output escaping, role guards, safe redirects (no open redirects), private upload folder (`.htaccess` deny + permission-checked download), PDF signature and size validation.
- Accessibility: skip link, landmarks, visible focus outlines, labelled form fields with linked error messages, keyboard-operable menus/dropdowns/filters/CV editor, `aria-live` status messages, reduced-motion support, WCAG-AA colour contrast for text and buttons.
- Responsive from 320 px phones to large desktops; filters become a drawer on mobile.

---

## 6. Project structure

```
careerbridge/
├── index.php, jobs.php, local.php, remote.php, inclusive.php, freshers.php
├── job.php, apply.php, company.php, guide.php, tools.php, matching.php
├── cv-builder.php, employers.php, about.php
├── login.php, register.php, logout.php, forgot-password.php, reset-password.php
├── cv-file.php            # permission-checked PDF CV download
├── install.php            # one-click database installer (delete before going public)
├── account/               # job seeker dashboard, profile, saved, tracker, settings
├── employer/              # employer dashboard, company, jobs, job editor, applications
├── api/                   # save-job.php, cv.php (JSON endpoints)
├── includes/              # config, bootstrap (DB, sessions, CSRF, auth), data, jobs & matcher, UI helpers, partials
├── assets/css/            # app.css (design system), cv.css (CV builder & templates)
├── assets/js/             # app.js, cv-render.js, cv-builder.js
├── database/              # careerbridge.sql (schema + demo data), schema.sql, seed & generator scripts
├── uploads/cvs/           # uploaded PDF CVs (not web-accessible)
└── tools/                 # scripts to share localhost via Cloudflare Tunnel or ngrok
```

To regenerate the SQL file after editing demo data: `php database/build-sql.php` (developer helper, not needed to run the site).

---

## 7. Troubleshooting

| Problem | Fix |
|---|---|
| “Database connection failed” page | Start **MySQL** in XAMPP, then run `install.php` or import the SQL file. Check `DB_*` values in `includes/config.php`. |
| Apache won't start (port 80 busy) | Another program (Skype, IIS, VMware) uses port 80. Change Apache's port in XAMPP (`httpd.conf` → `Listen 8080`) and open `http://localhost:8080/careerbridge/`. |
| MySQL won't start (port 3306 busy) | Change MySQL's port in XAMPP (`my.ini`) and set `DB_PORT` accordingly. |
| Links/styles broken | The folder must be inside `htdocs`. If you renamed or nested it, set `APP_BASE` in `config.php`. |
| CV upload fails | Make sure `uploads/cvs/` is writable and the file is a real PDF under 2 MB. |
| Forgot-password e-mail never arrives | Expected on XAMPP — use the link shown on screen (development mode). |

---

## 8. Honest limitations

- **No e-mail sending** (no SMTP configured); password reset links are shown on screen in development mode.
- **PDF export uses the browser's “Print → Save as PDF”** rather than a server-side PDF library.
- One CV per job seeker account; no photo field by design.
- No admin/moderation panel; no real-time notifications or chat (by design — no chatbots).
- Location matching is by division/district/area text; distances are not calculated, so “nearby” is never claimed without a chosen location.
- All companies, jobs and accounts in the database are **fictional demo data** for this university project.
- Inter / Plus Jakarta Sans fonts load from Google Fonts when online; offline the site uses system fonts.

---

## 9. Final quality checklist

| # | Check | Status |
|---|---|---|
| 1 | Consistent design system: warm orange accent, off-white/white backgrounds, navy text, thin borders, soft shadows | ✅ |
| 2 | **No human images**, avatars, silhouettes or person icons anywhere (initials tiles and abstract UI artwork only) | ✅ |
| 3 | Fully responsive (phone, tablet, desktop), mobile menu and filter drawer | ✅ |
| 4 | Every button and link works — no dead links (automated crawl of guest, seeker and employer pages) | ✅ |
| 5 | Search, filters, sorting and pagination work on all listing pages | ✅ |
| 6 | Apply workflow (CV snapshot, PDF upload, no-CV), save jobs, duplicate/closed protection | ✅ |
| 7 | CV Writing & Editing Hub: templates, live preview, reorder/hide, examples, print-to-PDF, no AI | ✅ |
| 8 | Smart Job Matching is rule-based, labelled as such, explains every score and shows skill gaps | ✅ |
| 9 | Application tracker with all 7 statuses, private notes, interviews, filters, delete/withdraw | ✅ |
| 10 | Employer portal: company profile, create/edit/publish/close jobs, review only own applications, update progress | ✅ |
| 11 | Keyboard accessible, labelled forms, visible focus, good contrast; disability-inclusive filters without medical data | ✅ |
| 12 | Demo data clearly labelled; no real companies or live vacancies claimed; no AI/JobShield/chatbot features | ✅ |
