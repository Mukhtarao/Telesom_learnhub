<?php if (($layout ?? 'app') === 'auth'): ?>
<?php elseif (($layout ?? 'app') === 'public'): ?>
</main>
<footer class="foot" id="contact"><div class="wrap foot-in">
  <div><a class="brand" href="index.php"><?= logo('#fff') ?><span>Telesom <b>LearnHub</b></span></a>
    <p>The learning and training platform of Telesom Company — building skills for Somaliland's digital future.</p></div>
  <div><h4><?= t('Platform') ?></h4><a href="courses.php"><?= t('Browse courses') ?></a><a href="register.php"><?= t('Create account') ?></a><a href="verify.php"><?= t('Verify certificate') ?></a><a href="login.php"><?= t('Log in') ?></a><a href="terms.php"><?= t('Terms of use') ?></a><a href="privacy.php"><?= t('Privacy policy') ?></a></div>
  <div><h4><?= t('Contact') ?></h4><p><?= t('Telesom Company') ?><br><?= t('Hargeysa, Somaliland') ?><br>learnhub@telesom.com<br><?= t('Sun – Thu · 8:00 – 17:00') ?></p></div>
</div><div class="copy">© <?= date('Y') ?> Telesom Company · Telesom LearnHub. All rights reserved.</div></footer>
<?php else: ?>
</main></div></div>
<?php endif; ?>
<script src="assets/js/app.js"></script></body></html>
