<?php if (($layout ?? 'app') === 'auth'): ?>
<?php elseif (($layout ?? 'app') === 'public'): ?>
</main>
<footer class="foot" id="contact"><div class="wrap foot-in">
  <div><a class="brand" href="index.php"><?= logo('#fff') ?><span>Telesom <b>LearnHub</b></span></a>
    <p>The learning and training platform of Telesom Company — building skills for Somaliland's digital future.</p></div>
  <div><h4>Platform</h4><a href="courses.php">Browse courses</a><a href="register.php">Create account</a><a href="verify.php">Verify certificate</a><a href="login.php">Log in</a></div>
  <div><h4>Contact</h4><p>Telesom Company<br>Hargeysa, Somaliland<br>learnhub@telesom.com<br>Sun – Thu · 8:00 – 17:00</p></div>
</div><div class="copy">© <?= date('Y') ?> Telesom Company · Telesom LearnHub. All rights reserved.</div></footer>
<?php else: ?>
</main></div></div>
<?php endif; ?>
<script src="assets/js/app.js"></script></body></html>
