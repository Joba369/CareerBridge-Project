<?php
/**
 * Fresher Career Guidance articles (educational content written for this project).
 * slug, title, path, level, type, minutes, summary, content (HTML)
 */
$guides = [];

$guides[] = ['web-development-roadmap', 'Web Development Roadmap for Beginners', 'Web Development', 'Beginner', 'Learning roadmap', 8,
'A step-by-step path from your first HTML page to a job-ready portfolio, with free practice ideas at every stage.', <<<'HTML'
<p>Web development is one of the most accessible tech careers in Bangladesh. You need a computer, an internet connection and regular practice — not an expensive degree. This roadmap breaks the journey into stages. Move to the next stage only when you can build something small on your own.</p>
<h2>Stage 1 — The foundations (4–6 weeks)</h2>
<ul>
<li><strong>HTML:</strong> headings, paragraphs, links, images, lists, tables and forms. Learn semantic tags such as <code>header</code>, <code>nav</code>, <code>main</code> and <code>footer</code>.</li>
<li><strong>CSS:</strong> selectors, the box model, colours, typography, Flexbox and Grid.</li>
<li><strong>Responsive design:</strong> media queries and mobile-first layouts.</li>
</ul>
<p><strong>Practice:</strong> rebuild a simple restaurant menu page, then a personal profile page that works on a phone.</p>
<h2>Stage 2 — JavaScript (6–8 weeks)</h2>
<ul>
<li>Variables, data types, conditions, loops, functions, arrays and objects.</li>
<li>The DOM: selecting elements, handling clicks and form input.</li>
<li>Fetching data from an API with <code>fetch</code> and showing it on the page.</li>
</ul>
<p><strong>Practice:</strong> a to-do list, a tip calculator in taka, and a weather page using a free API.</p>
<h2>Stage 3 — Tools developers use every day</h2>
<ul>
<li><strong>Git and GitHub</strong> to save versions of your code and share it.</li>
<li><strong>Browser DevTools</strong> to inspect layouts and debug errors.</li>
<li>A code editor such as VS Code with a formatter.</li>
</ul>
<h2>Stage 4 — Choose a direction</h2>
<ul>
<li><strong>Frontend:</strong> React, component thinking, accessibility and performance.</li>
<li><strong>Backend:</strong> PHP or Node.js, databases (MySQL), authentication and REST APIs.</li>
<li><strong>WordPress:</strong> themes, plugins and WooCommerce — useful for freelancing with local businesses.</li>
</ul>
<h2>Stage 5 — Become job-ready</h2>
<ol>
<li>Build 3 portfolio projects that solve real problems (see the project ideas guide).</li>
<li>Write a short README for each project explaining what it does and what you learned.</li>
<li>Prepare a one-page CV with links to your GitHub and live demos.</li>
<li>Apply for internships and junior roles — do not wait until you feel "perfect".</li>
</ol>
<div class="callout"><strong>Tip:</strong> Study for one focused hour every day instead of ten hours once a week. Consistency matters more than speed.</div>
HTML];

$guides[] = ['frontend-project-ideas', 'Frontend Portfolio Project Ideas', 'Web Development', 'Intermediate', 'Project ideas', 6,
'Eight practical projects that show employers you can build real interfaces — from easy to challenging.', <<<'HTML'
<p>Employers hiring junior developers want proof that you can turn an idea into a working product. These project ideas are grounded in everyday Bangladeshi life, which makes them memorable in interviews.</p>
<h2>Starter projects</h2>
<ol>
<li><strong>Bus fare calculator:</strong> choose a start and end point from a list and show an estimated fare. Practises forms and conditions.</li>
<li><strong>Bazar list app:</strong> add items with quantity and price, tick them off, and show the total in taka. Save the list in <code>localStorage</code>.</li>
<li><strong>Prayer or class timetable:</strong> show today's schedule and highlight the next item.</li>
</ol>
<h2>Intermediate projects</h2>
<ol start="4">
<li><strong>Local shop catalogue:</strong> a product grid with search, category filter and a cart drawer.</li>
<li><strong>Expense tracker with charts:</strong> monthly summaries using a simple chart library.</li>
<li><strong>Accessible form wizard:</strong> a multi-step admission form with validation, keyboard support and clear error messages.</li>
</ol>
<h2>Advanced projects</h2>
<ol start="7">
<li><strong>Job board front end:</strong> listings, filters, pagination and a saved-jobs page using a mock API.</li>
<li><strong>Tuition finder:</strong> map-free location filters (division → district → area), tutor cards and a booking request form.</li>
</ol>
<h2>How to present each project</h2>
<ul>
<li>Deploy a live demo (GitHub Pages, Netlify or Vercel are free).</li>
<li>Write a README: the problem, features, tech used, screenshots of the interface and what you would improve next.</li>
<li>Keep commits small and meaningful — interviewers often look at commit history.</li>
</ul>
HTML];

$guides[] = ['digital-marketing-roadmap', 'Digital Marketing Starter Roadmap', 'Digital Marketing', 'Beginner', 'Learning roadmap', 7,
'Learn the core channels — social media, SEO, content and ads — and build a small portfolio using real local businesses.', <<<'HTML'
<p>Most small businesses in Bangladesh sell through Facebook pages, so practical digital marketing skills are in high demand. You can start learning with free resources and practise on a friend's or family member's business.</p>
<h2>Core skills to learn</h2>
<ul>
<li><strong>Social media management:</strong> content calendars, post design basics, captions and community replies.</li>
<li><strong>Paid ads:</strong> Facebook/Meta ad objectives, audiences, budgets and reading results.</li>
<li><strong>SEO:</strong> keyword research, page titles, meta descriptions and local search (Google Business Profile).</li>
<li><strong>Content writing:</strong> clear Bangla and English copy for posts, blogs and product descriptions.</li>
<li><strong>Analytics:</strong> reach, engagement, click-through rate and cost per result.</li>
</ul>
<h2>A 10-week learning plan</h2>
<ol>
<li>Weeks 1–2: social media basics and a 2-week content calendar for a sample business.</li>
<li>Weeks 3–4: design simple posts in Canva and write captions.</li>
<li>Weeks 5–6: SEO fundamentals; optimise one blog post or product page.</li>
<li>Weeks 7–8: ads fundamentals; plan a small campaign with a clear goal and budget.</li>
<li>Weeks 9–10: build a case study: what you did, the numbers, and what you learned.</li>
</ol>
<div class="callout"><strong>Ethics reminder:</strong> never buy fake likes or followers. Employers can tell, and it damages trust.</div>
HTML];

$guides[] = ['design-first-portfolio', 'UI/UX & Graphic Design: Your First Portfolio', 'Design', 'Beginner', 'Project ideas', 6,
'What to include in a beginner design portfolio and how to explain your process clearly.', <<<'HTML'
<p>A design portfolio is more important than certificates. Recruiters want to see how you think, not only the final picture.</p>
<h2>Skills to build</h2>
<ul>
<li><strong>Graphic design:</strong> layout, typography, colour, Photoshop/Illustrator or free tools like Canva and Figma.</li>
<li><strong>UI design:</strong> grids, components, spacing systems and responsive screens in Figma.</li>
<li><strong>UX basics:</strong> user interviews, user flows, wireframes and usability testing.</li>
<li><strong>Accessibility:</strong> colour contrast, readable font sizes and clear labels.</li>
</ul>
<h2>Portfolio pieces for beginners</h2>
<ol>
<li>A brand kit (logo, colours, fonts, sample posts) for a fictional local café.</li>
<li>A redesign of a confusing form you used in real life — show the before and after.</li>
<li>A mobile app concept, such as a bus-ticket or tuition-booking app, with wireframes and final screens.</li>
</ol>
<h2>How to write a case study</h2>
<ul>
<li>The problem and who it affects.</li>
<li>Your research and key insights.</li>
<li>Sketches and wireframes — show the messy middle.</li>
<li>Final design and the reasons behind your decisions.</li>
<li>What you would test or improve next.</li>
</ul>
HTML];

$guides[] = ['it-support-fundamentals', 'IT Support Fundamentals', 'IT Support', 'Beginner', 'Learning roadmap', 6,
'The hardware, software and communication skills needed for help desk and IT technician roles.', <<<'HTML'
<p>IT support is a practical entry point into technology. Offices, schools, banks and call centres all need people who can keep computers and networks working.</p>
<h2>Technical skills</h2>
<ul>
<li><strong>Hardware:</strong> identify PC parts, replace RAM or storage, set up printers.</li>
<li><strong>Operating systems:</strong> install Windows, manage users, update drivers and remove malware.</li>
<li><strong>Networking basics:</strong> IP addresses, Wi-Fi setup, routers, cables and simple troubleshooting.</li>
<li><strong>Office tools:</strong> email setup, Microsoft 365 or Google Workspace basics.</li>
</ul>
<h2>Soft skills that matter just as much</h2>
<ul>
<li>Listening carefully and asking clear questions.</li>
<li>Explaining fixes in simple language without blaming the user.</li>
<li>Writing short, clear notes in a ticketing system.</li>
</ul>
<h2>Practice at home</h2>
<ol>
<li>Install a free virtual machine program and practise installing Windows or Linux.</li>
<li>Keep a "fix log" of every computer problem you solve for friends and family.</li>
<li>Learn the steps for resetting a router and setting a secure Wi-Fi password.</li>
</ol>
HTML];

$guides[] = ['skills-for-office-and-service-roles', 'Skills for Office, Retail and Customer Service Roles', 'Office & Customer Service', 'Beginner', 'Skills guide', 5,
'Everyday skills employers look for in shops, cafés, call centres and offices — and how to show them on your CV.', <<<'HTML'
<p>Many first jobs are in retail, hospitality, customer service and office support. These roles build skills that are valuable in any career.</p>
<h2>Skills employers ask for</h2>
<table>
<thead><tr><th>Role</th><th>Key skills</th></tr></thead>
<tbody>
<tr><td>Shop / boutique assistant</td><td>Customer service, product knowledge, basic cash handling</td></tr>
<tr><td>Café or restaurant staff</td><td>Teamwork, food hygiene, staying calm at busy times</td></tr>
<tr><td>Supermarket cashier</td><td>Accuracy, POS systems, honesty</td></tr>
<tr><td>Call centre agent</td><td>Clear speaking, typing, patience, problem solving</td></tr>
<tr><td>Front desk / office assistant</td><td>MS Office, record keeping, polite communication</td></tr>
<tr><td>Data entry operator</td><td>Typing speed, attention to detail, Excel</td></tr>
</tbody>
</table>
<h2>How to build these skills quickly</h2>
<ul>
<li>Practise typing for 15 minutes daily with a free typing tutor.</li>
<li>Learn basic Excel: formulas (SUM, AVERAGE), sorting and filtering.</li>
<li>Volunteer at university events to gain customer-facing experience.</li>
</ul>
<h2>Showing them on your CV</h2>
<p>Use short, specific bullet points. Instead of "good communication", write "Answered 40+ customer queries per day during a university fair."</p>
HTML];

$guides[] = ['writing-your-first-cv', 'Writing Your First CV', 'All career paths', 'Beginner', 'CV tips', 7,
'A practical CV guide for students and fresh graduates, including what to include when you have little experience.', <<<'HTML'
<p>Your CV is a one- or two-page summary that helps an employer decide whether to interview you. Recruiters often spend less than a minute on the first read, so clarity matters more than decoration.</p>
<h2>Recommended structure for freshers</h2>
<ol>
<li><strong>Contact details:</strong> name, phone, professional email, city, and LinkedIn or portfolio link.</li>
<li><strong>Career objective:</strong> 2–3 lines on the role you want and what you offer.</li>
<li><strong>Education:</strong> most recent first, with results if they are strong.</li>
<li><strong>Skills:</strong> grouped (e.g. Technical, Languages, Tools).</li>
<li><strong>Projects:</strong> very important when you have little work experience.</li>
<li><strong>Experience:</strong> part-time jobs, internships, tutoring and volunteering all count.</li>
<li><strong>Training & certificates.</strong></li>
<li><strong>References:</strong> "Available on request" is acceptable.</li>
</ol>
<h2>Write strong bullet points</h2>
<p>Start with an action verb and add a result where possible:</p>
<ul>
<li>Weak: "Responsible for social media."</li>
<li>Better: "Planned and posted 3 Facebook posts per week for a student club, growing followers from 400 to 1,100 in 4 months."</li>
</ul>
<h2>Common mistakes to avoid</h2>
<ul>
<li>Spelling mistakes and inconsistent dates.</li>
<li>Unprofessional email addresses.</li>
<li>Long paragraphs — use bullets.</li>
<li>Listing skills you cannot discuss in an interview.</li>
<li>Including unnecessary personal details. You do not need to share religion, marital status or health information.</li>
</ul>
<div class="callout"><strong>Try it:</strong> Open the <a href="cv-builder.php">CV Writing &amp; Editing Hub</a> and load the fresher example to see this structure in action.</div>
HTML];

$guides[] = ['basic-interview-preparation', 'Basic Interview Preparation', 'All career paths', 'Beginner', 'Interview preparation', 7,
'Common interview questions, how to structure your answers, and a checklist for the day of the interview.', <<<'HTML'
<p>Interviews feel less stressful when you prepare. Most employers want to understand three things: can you do the work, will you learn, and will you work well with the team?</p>
<h2>Before the interview</h2>
<ul>
<li>Read the job description again and match your skills to each requirement.</li>
<li>Learn what the company does — check its website or social media page.</li>
<li>Prepare two or three short stories about challenges you solved.</li>
<li>If you need an interview accommodation (extra time, a ground-floor room, written questions), you may ask the employer in advance. You do not need to share medical details.</li>
</ul>
<h2>Common questions</h2>
<ol>
<li><strong>Tell me about yourself.</strong> Answer in under a minute: current study/work, key skills, why this role.</li>
<li><strong>Why do you want this job?</strong> Connect the role to your goals and the company's work.</li>
<li><strong>What are your strengths and weaknesses?</strong> Give a real weakness and explain how you are improving it.</li>
<li><strong>Describe a challenge you faced.</strong> Use the STAR method below.</li>
<li><strong>Do you have any questions for us?</strong> Always prepare one or two.</li>
</ol>
<h2>The STAR method</h2>
<ul>
<li><strong>S</strong>ituation — the context.</li>
<li><strong>T</strong>ask — what you needed to do.</li>
<li><strong>A</strong>ction — what <em>you</em> did.</li>
<li><strong>R</strong>esult — what happened and what you learned.</li>
</ul>
<h2>On the day</h2>
<ul>
<li>Arrive 10–15 minutes early, or test your internet and camera for online interviews.</li>
<li>Bring printed copies of your CV and certificates.</li>
<li>After the interview, send a short thank-you message.</li>
</ul>
HTML];

$guides[] = ['job-application-guidance', 'How to Apply for Jobs Safely and Effectively', 'All career paths', 'Beginner', 'Application guidance', 6,
'Tailor each application, follow up professionally, track your progress and recognise job scams.', <<<'HTML'
<p>Sending many identical applications rarely works. A smaller number of tailored applications usually gets better results.</p>
<h2>A simple application routine</h2>
<ol>
<li><strong>Read carefully:</strong> highlight required and preferred skills.</li>
<li><strong>Tailor your CV:</strong> move the most relevant skills and projects to the top.</li>
<li><strong>Write a short cover note:</strong> 3–5 sentences explaining why you fit this role.</li>
<li><strong>Track it:</strong> record the date and status in the <a href="account/applications.php">Application Tracker</a>.</li>
<li><strong>Follow up:</strong> if you hear nothing after 7–10 working days, a polite message is fine.</li>
</ol>
<h2>"No response" is not a rejection</h2>
<p>Employers often receive many applications and may take weeks to reply. Keep applying elsewhere while you wait, and mark the application as "No Response Yet" rather than giving up on it.</p>
<h2>Protect yourself from job scams</h2>
<ul>
<li><strong>Never pay a fee</strong> to apply, to "confirm" a job, or for training that guarantees employment.</li>
<li>Be careful with offers that promise very high pay for very little work.</li>
<li>Do not share your NID, bank PIN or OTP codes during the application stage.</li>
<li>Meet in official workplaces, and tell a family member or friend where you are going.</li>
<li>Check that the company has a real address, phone number and online presence.</li>
</ul>
HTML];

$guides[] = ['first-part-time-job-students', 'Finding Your First Part-Time Job as a Student', 'Local Part-Time', 'Beginner', 'Application guidance', 5,
'Where to look, how to balance work with study, and what to ask before accepting a part-time role.', <<<'HTML'
<p>A part-time job helps you earn, gain confidence and build references for your future career. The key is to choose work that fits your class schedule.</p>
<h2>Good first part-time jobs</h2>
<ul>
<li>Tutoring and coaching centre teaching.</li>
<li>Café, restaurant and shop assistant roles.</li>
<li>Supermarket cashier or stock assistant shifts.</li>
<li>Data entry, call centre and remote support roles.</li>
</ul>
<h2>Balancing work and study</h2>
<ul>
<li>Write down your weekly class timetable before applying.</li>
<li>Look for evening, weekend or flexible-hour shifts.</li>
<li>Keep work to a level that does not hurt your exam results — many students find 12–20 hours per week manageable.</li>
</ul>
<h2>Questions to ask before you accept</h2>
<ol>
<li>What are the exact working hours and days?</li>
<li>How and when is payment made (cash, bank, mobile banking)?</li>
<li>Is there a trial period, and is it paid?</li>
<li>Who is my supervisor?</li>
<li>Is there flexibility during exams?</li>
</ol>
<div class="callout"><strong>Search tip:</strong> On the <a href="local.php">Local Part-Time Jobs</a> page, filter by your district and choose "Evening" or "Weekends" under working hours.</div>
HTML];

$guides[] = ['making-the-most-of-internships', 'Internships: Finding One and Making It Count', 'All career paths', 'Intermediate', 'Career guide', 5,
'How to find internships, what to do in the first weeks, and how to turn an internship into a job offer or strong reference.', <<<'HTML'
<p>An internship is a structured way to gain real experience. Even a short internship gives you examples to discuss in future interviews.</p>
<h2>Finding internships</h2>
<ul>
<li>Use the internship filter on <a href="jobs.php?type[]=internship">Find Jobs</a>.</li>
<li>Ask your university career office and teachers about partner organisations.</li>
<li>Send short, polite emails to small companies whose work interests you.</li>
</ul>
<h2>Your first two weeks</h2>
<ul>
<li>Ask your supervisor what success looks like at the end of the internship.</li>
<li>Take notes, and ask questions in batches rather than one at a time.</li>
<li>Keep a simple daily log of tasks you completed.</li>
</ul>
<h2>Finishing strong</h2>
<ul>
<li>Summarise your contributions in a short document.</li>
<li>Ask for feedback and, if it went well, a reference.</li>
<li>Add the internship to your CV with 2–3 results-focused bullet points.</li>
</ul>
HTML];
