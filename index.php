<?php
require 'includes/config.php';
$layout = 'public'; $title = 'Welcome';
$st = [
  'users' => one("SELECT COUNT(*) c FROM users WHERE role='learner'")['c'],
  'courses' => one("SELECT COUNT(*) c FROM courses WHERE status='approved'")['c'],
  'instr' => one("SELECT COUNT(*) c FROM users WHERE role='instructor'")['c'],
  'certs' => one("SELECT COUNT(*) c FROM certificates")['c'],
];
$feat = all("SELECT c.*, u.name inst, k.name cat, (SELECT ROUND(AVG(rating),1) FROM reviews WHERE course_id=c.id) rt,
  (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) n FROM courses c JOIN users u ON u.id=c.instructor_id LEFT JOIN categories k ON k.id=c.category_id
  WHERE c.status='approved' AND c.org_id IS NULL ORDER BY n DESC, c.id LIMIT 3");
$plans = all("SELECT * FROM plans WHERE active=1 ORDER BY sort_order, price");
include 'includes/header.php';
?>
<section class="hero"><div class="wrap hero-in">
  <div>
    <span class="pill">🇸🇴 Telesom Company · Learning & Training Platform</span>
    <h1>Learn without limits with <span>Telesom LearnHub</span></h1>
    <p>One platform for courses, videos, assignments, quizzes, exams and verified certificates — built for Telesom staff, partners and every learner in Somaliland.</p>
    <a class="btn" href="register.php">Start learning free →</a>
    <a class="btn btn-outline" style="margin-left:8px" href="courses.php">Browse courses</a>
    <div style="display:flex;gap:22px;margin-top:30px;flex-wrap:wrap;color:var(--mut);font-size:14px;font-weight:600"><span>✔ Free to join</span><span>✔ Learn on any device</span><span>✔ Verified certificates</span></div>
  </div>
  <div class="hero-card">
    <div style="background:var(--g-soft);border-radius:14px;padding:14px;margin-bottom:6px"><div style="display:flex;justify-content:space-between;font-weight:700"><span>Web Development</span><span style="color:var(--g)">85%</span></div><div class="bar" style="margin-top:8px"><i style="width:85%"></i></div></div>
    <div class="row"><span class="ic">🎬</span><div><b>Video & document lessons</b><small>Learn on any device, anytime</small></div></div>
    <div class="row"><span class="ic">📝</span><div><b>Assignments & exams</b><small>Automatic grading & instant results</small></div></div>
    <div class="row"><span class="ic">📈</span><div><b>Progress tracking</b><small>See exactly how far you have come</small></div></div>
    <div class="row"><span class="ic">🎓</span><div><b>Verified certificates</b><small>Share a code employers can verify</small></div></div>
  </div>
</div></section>

<div class="wrap"><div class="stats">
  <div class="stat-b"><h3 data-count="<?= $st['users'] ?>">0</h3><p>Active learners</p></div>
  <div class="stat-b"><h3 data-count="<?= $st['courses'] ?>">0</h3><p>Published courses</p></div>
  <div class="stat-b"><h3 data-count="<?= $st['instr'] ?>">0</h3><p>Expert instructors</p></div>
  <div class="stat-b"><h3 data-count="<?= $st['certs'] ?>">0</h3><p>Certificates issued</p></div>
</div></div>

<section class="sec" id="about"><div class="wrap two">
  <div>
    <span class="muted" style="font-weight:700;letter-spacing:1.5px;color:var(--g)">ABOUT TELESOM</span>
    <h2>Connecting Somaliland to the digital world</h2>
    <p>Telesom is a leading telecommunications and digital services company in Somaliland, headquartered in Hargeysa. We provide mobile voice and data, internet connectivity and digital financial services that keep families, businesses and institutions connected every day.</p>
    <p>Investing in people is part of that mission. Telesom LearnHub is our own learning platform — a home for staff development, customer and partner training, and open learning for the wider community.</p>
    <ul class="checks"><li>Reliable mobile, internet and digital financial services</li><li>Customer-first culture and continuous innovation</li><li>Commitment to skills, jobs and community growth</li></ul>
  </div>
  <div class="panel-g"><h3>Why Telesom built LearnHub</h3>
    <div class="it"><span>🎯</span><div><b>Upskill our people</b><p>Onboarding, technical and customer-service training in one place.</p></div></div>
    <div class="it"><span>🤝</span><div><b>Train partners & agents</b><p>Private courses for organisations, assigned and tracked centrally.</p></div></div>
    <div class="it"><span>🌍</span><div><b>Open to everyone</b><p>Free and paid courses for students, professionals and job seekers.</p></div></div>
  </div></div></section>

<section class="sec alt" id="platform"><div class="wrap">
  <div class="sec-h"><span class="tag">The platform</span><h2>What Telesom LearnHub does</h2><p>A complete Learning Management System for schools, universities, companies, training centres, government institutions and NGOs.</p></div>
  <div class="grid-3">
    <?php foreach ([['📚','Course management','Instructors build courses with lessons, videos, PDFs, slides, audio and links.'],['🔎','Search & discovery','Search, filter and sort by category and level; see popular and recent courses.'],['✅','Enrollment options','Free, paid, instructor-approved or organisation-assigned private courses.'],['📝','Assignments','Set deadlines, collect uploads, grade work and give feedback.'],['🧠','Quizzes & exams','Multiple choice, true/false and short answer with timers and auto-grading.'],['🎓','Certificates','Automatic certificates with unique verification codes.'],['💬','Communication','Course discussions, private messages and notifications.'],['🏢','Organisations','Add members, assign courses and view training reports.'],['📊','Reports & dashboards','Role-based dashboards and analytics for learners, instructors and admins.']] as $f): ?>
    <div class="feat"><div class="ic"><?= $f[0] ?></div><h3><?= $f[1] ?></h3><p><?= $f[2] ?></p></div>
    <?php endforeach; ?>
  </div></div></section>

<section class="sec"><div class="wrap">
  <div class="sec-h"><span class="tag">Featured</span><h2>Popular courses</h2></div>
  <div class="grid"><?php foreach ($feat as $i => $c): ?>
    <div class="cc"><div class="th c<?= $i % 3 + 1 ?>" data-i="<?= e(substr($c['title'], 0, 1)) ?>"><?= e($c['cat']) ?></div>
    <div class="bd"><h3><?= e($c['title']) ?></h3><div class="meta"><span>👤 <?= e($c['inst']) ?></span><span>⏱ <?= e($c['duration']) ?></span><span><?= e($c['level']) ?></span></div>
    <div class="ft"><span class="star"><?= $c['rt'] ? stars($c['rt']).' '.$c['rt'] : 'New' ?></span><a class="btn btn-sm" href="course.php?id=<?= $c['id'] ?>">View</a></div></div></div>
  <?php endforeach; ?></div>
  <p style="text-align:center;margin-top:26px"><a class="btn btn-outline" href="courses.php">See all courses</a></p>
</div></section>

<section class="sec alt"><div class="wrap">
  <div class="sec-h"><span class="tag">How it works</span><h2>Start in three steps</h2></div>
  <div class="grid-3 steps"><div class="step"><h3>Create your account</h3><p>Sign up as a learner, instructor or organisation in under a minute.</p></div>
  <div class="step"><h3>Enroll & learn</h3><p>Pick a course, study lessons, submit assignments and take quizzes.</p></div>
  <div class="step"><h3>Get certified</h3><p>Complete the course and download your verifiable certificate.</p></div></div>
</div></section>

<section class="sec"><div class="wrap">
  <div class="sec-h"><span class="tag">Who it's for</span><h2>Built for every kind of learner</h2></div>
  <div class="aud"><div><span>🎓</span>Universities</div><div><span>🏫</span>Schools</div><div><span>🏢</span>Companies</div><div><span>🏛️</span>Government</div><div><span>🤝</span>NGOs</div><div><span>🧑‍💻</span>Individuals</div></div>
  <div class="paystrip"><span>Pay for premium courses with</span><b>ZAAD</b><b>eDahab</b><b>Bank transfer</b></div>
</div></section>
<section class="sec alt"><div class="wrap"><div class="sec-h"><span class="tag">FAQ</span><h2>Frequently asked questions</h2></div><div class="faq">
  <details><summary>Is Telesom LearnHub free?</summary><p>Many courses are free. Premium courses show their price and can be paid with ZAAD, eDahab or bank transfer.</p></details>
  <details><summary>How do I get a certificate?</summary><p>Finish all lessons and pass every quiz or exam in the course. Your certificate is issued automatically with a code anyone can verify.</p></details>
  <details><summary>Can my organization train its own staff?</summary><p>Yes. Register as an organization, add your members, create private courses and assign them. You get training reports for your team.</p></details>
  <details><summary>Can I learn on my phone?</summary><p>Yes — the whole platform is responsive and works on phones, tablets and computers.</p></details>
</div></div></section>

<section class="sec alt"><div class="wrap two">
  <div class="player rv">
    <div class="screen"><div class="play">▶</div><span class="live">● Lesson 3 · Styling with CSS</span></div>
    <div class="ctrl"><i style="width:62%"></i></div>
    <div class="chips"><span>HD video</span><span>Notes</span><span>Quiz</span><span>Certificate</span></div>
  </div>
  <div><span class="tag-l">Learning experience</span><h2>Watch, practise and prove your skills</h2>
    <p>Every course combines video lessons, readings, downloadable files, assignments and quizzes. Your progress is saved automatically, so you can stop and continue on any device.</p>
    <ul class="checks"><li>Stream video from YouTube, Vimeo or uploaded MP4 files</li><li>Download PDFs, slides and documents</li><li>Instant quiz results and instructor feedback</li><li>Certificate issued automatically when you finish</li></ul>
    <p style="margin-top:20px"><a class="btn" href="register.php">Try it free</a></p></div>
</div></section>

<section class="sec"><div class="wrap"><div class="sec-h"><span class="tag">For everyone</span><h2>Built for every kind of learner</h2></div>
  <div class="aud"><?php foreach ([['🎓','Students','Build skills for your first job'],['💼','Professionals','Grow your career step by step'],['🏢','Companies','Train staff and track results'],['🏛️','Government','Standardise public-sector training'],['🤝','NGOs','Reach communities at scale'],['📡','Telesom teams','Onboarding and technical upskilling']] as $a): ?>
    <div class="aud-i"><span><?= $a[0] ?></span><b><?= $a[1] ?></b><small><?= $a[2] ?></small></div><?php endforeach; ?></div></div></section>

<section class="sec" id="plans"><div class="wrap"><div class="sec-h"><span class="tag">For organizations</span><h2>Train your whole team with one subscription</h2><p>Companies, schools, NGOs and institutions register, get approved by our team, then pick a monthly or yearly plan. Your learners never pay — the organization does.</p></div>
  <div class="grid-3 steps" style="margin-bottom:34px"><div class="step"><h3>Register & get approved</h3><p>Create an organization account. Our administrator reviews it.</p></div><div class="step"><h3>Choose a plan</h3><p>Pick monthly or yearly and the size that fits your team.</p></div><div class="step"><h3>Pay & start training</h3><p>Pay by ZAAD, eDahab or bank, add your members and assign private courses.</p></div></div>
  <?php $cta = fn($p) => ['Register your organization', 'register.php?org=1']; $cur = null; include 'includes/plans_view.php'; ?>
  <p style="text-align:center;margin-top:20px">Need a custom package, or a sponsored account for a Telesom department? Contact <b>learnhub@telesom.com</b>.</p></div></section>

<section class="sec alt"><div class="wrap"><div class="sec-h"><span class="tag">Community</span><h2>What learners say</h2></div>
  <div class="grid-3"><?php foreach ([['Clear lessons and quick quizzes. I finished my first certificate in a week.','Ifrah Y.','Learner'],['Assigning private courses to my team and seeing reports saves hours every month.','Training Manager','Organisation'],['Creating a course and grading assignments is simple and fast.','Ahmed W.','Instructor']] as $t): ?>
    <div class="feat"><div class="star" style="font-size:18px">★★★★★</div><p style="color:var(--ink);margin:10px 0 16px">“<?= $t[0] ?>”</p><div style="display:flex;gap:10px;align-items:center"><span class="av"><?= initials($t[1]) ?></span><div><b><?= $t[1] ?></b><br><small class="muted"><?= $t[2] ?></small></div></div></div><?php endforeach; ?></div></div></section>

<section class="sec"><div class="wrap" style="max-width:820px"><div class="sec-h"><span class="tag">FAQ</span><h2>Common questions</h2></div>
  <?php foreach ([['Is Telesom LearnHub free?','Many courses are free. Some are paid or need instructor approval — the price is shown on every course.'],['How do I get a certificate?','Finish all lessons and pass the quizzes or exam. Your certificate is created automatically with a verification code.'],['Can my organisation train its own staff?','Yes. Organisations add members, create private courses, assign them and view training reports.'],['Can I learn on my phone?','Yes. The platform is fully responsive and works on phones, tablets and computers.']] as $f): ?>
    <details class="faq"><summary><?= $f[0] ?></summary><p><?= $f[1] ?></p></details><?php endforeach; ?></div></section>

<section class="sec"><div class="wrap"><div class="cta"><h2>Ready to grow your skills with Telesom?</h2><p>Join Telesom LearnHub today — learn at your own pace, from anywhere.</p>
<a class="btn btn-white" href="register.php">Create free account</a></div></div></section>
<?php include 'includes/footer.php'; ?>
