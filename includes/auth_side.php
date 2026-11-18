<aside class="split-l">
  <a class="brand" href="index.php"><?= logo('#fff') ?><span>Telesom <b>LearnHub</b></span></a>
  <div class="sl-body">
    <span class="pill"><?= $side['tag'] ?></span>
    <h1><?= $side['h'] ?></h1>
    <p><?= $side['p'] ?></p>
    <div class="sl-cards">
      <div><span>🎬</span><b><?= t('Video lessons') ?></b><small><?= t('Learn anywhere, anytime') ?></small></div>
      <div><span>📝</span><b><?= t('Quizzes & exams') ?></b><small><?= t('Instant results') ?></small></div>
      <div><span>🎓</span><b><?= t('Certificates') ?></b><small><?= t('Verified by code') ?></small></div>
    </div>
    <ul class="sl-list"><?php foreach ($side['pts'] as $pt): ?><li><i>✓</i><?= $pt ?></li><?php endforeach; ?></ul>
  </div>
  <small>© <?= date('Y') ?> Telesom Company · Hargeysa, Somaliland</small>
</aside>
