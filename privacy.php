<?php
require 'includes/config.php';
$layout = 'public'; $title = 'Privacy policy'; include 'includes/header.php';
?>
<div class="hero-sm"><div class="wrap"><h1>Privacy policy</h1><p>Telesom LearnHub · Last updated <?= date('F Y') ?></p></div></div>
<div class="wrap legal">
<!-- TEMPLATE: have this policy reviewed by a legal adviser before launch. -->
<p>This policy explains what personal information <b>Telesom Company</b> collects through <b>Telesom LearnHub</b>, why, and what choices you have.</p>
<h2>1. What we collect</h2><ul><li><b>Account details:</b> name, e-mail, phone number, country, password (stored only as a one-way hash), role and the date you accepted our terms.</li><li><b>Profile details you choose to add:</b> biography, education, skills.</li><li><b>Learning data:</b> enrollments, lesson progress, quiz and exam results, assignment submissions and grades, certificates, reviews, discussion posts and messages.</li><li><b>Payment records:</b> amount, method, phone number used and transaction reference (never your PIN or card details).</li><li><b>Technical data:</b> IP address and time of login attempts (to protect accounts), and your language and display preferences.</li></ul>
<h2>2. Why we use it</h2><ul><li>To provide courses, track progress, grade work and issue certificates.</li><li>To process and confirm payments and subscriptions.</li><li>To send notifications and reminders (live classes, deadlines, subscription renewals) by in-app message, e-mail and — if you turn it on — SMS.</li><li>To keep the platform secure and to prevent fraud and abuse.</li></ul>
<h2>3. Who can see your information</h2><ul><li><b>Instructors</b> see the progress, results and submissions of learners in their own courses.</li><li><b>Organizations</b> see the progress and results of their own members.</li><li><b>Administrators</b> can see account, payment and usage information needed to run the platform.</li><li>Anyone with a certificate code can verify that certificate (name, course, date, score).</li><li>We use e-mail and SMS delivery services to send you messages; we do not sell your personal information.</li></ul>
<h2>4. Cookies and local storage</h2><p>We use a session cookie to keep you signed in, and your browser's local storage to remember your language, night/day mode and sidebar choice. We do not use advertising cookies.</p>
<h2>5. How long we keep it</h2><p>We keep your information while your account is active and as long as needed for certificates, payment records and legal obligations. Login-attempt records are deleted automatically after one day.</p>
<h2>6. Security</h2><p>Passwords are hashed, forms are protected against forgery, sessions are protected, and access is limited by role. No system is perfectly secure, so please use a strong, unique password.</p>
<h2>7. Your choices and rights</h2><ul><li>Update your details, language and reminder settings in your profile.</li><li>Ask us for a copy of your data, to correct it, or to delete your account.</li><li>Turn off e-mail or SMS reminders at any time in your profile.</li></ul>
<h2>8. Children</h2><p>Learners under 18 should use the platform with the knowledge of a parent, guardian or their school or organization.</p>
<h2>9. Changes and contact</h2><p>We may update this policy and will tell you about important changes. Questions or requests: <b>learnhub@telesom.com</b> · Telesom Company, Hargeysa, Somaliland.</p>
<p style="margin-top:24px"><a class="btn btn-outline" href="terms.php">Read the Terms of use →</a></p>
</div>
<?php include 'includes/footer.php'; ?>
