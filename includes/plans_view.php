<?php
// Monthly / yearly plan cards. Expects: $plans (rows), $cta (callable: plan => [label, href]), $cur (current plan id or null)
$by = ['monthly' => [], 'yearly' => []]; $mp = [];
foreach ($plans as $p) { $by[$p['billing']][] = $p; if ($p['billing'] === 'monthly') $mp[$p['name']] = (float)$p['price']; }
$maxSave = 0; foreach ($by['yearly'] as $p) if (!empty($mp[$p['name']])) $maxSave = max($maxSave, round((1 - $p['price'] / ($mp[$p['name']] * 12)) * 100));
?>
<div class="plans-root">
 <div class="bill-wrap"><div class="bill-tg"><button type="button" class="on" data-bill="monthly">Monthly</button><button type="button" data-bill="yearly">Yearly<?php if ($maxSave > 0): ?> <span class="save">Save up to <?= $maxSave ?>%</span><?php endif; ?></button></div></div>
 <?php foreach (['monthly', 'yearly'] as $b): ?>
 <div class="plan-grid" data-b="<?= $b ?>" <?= $b === 'yearly' ? 'style="display:none"' : '' ?>>
  <?php foreach ($by[$b] as $p): $sv = ($b === 'yearly' && !empty($mp[$p['name']])) ? round((1 - $p['price'] / ($mp[$p['name']] * 12)) * 100) : 0; [$lbl, $href] = $cta($p); ?>
  <div class="plan <?= $p['name'] === 'Business' ? 'pop' : '' ?> <?= $cur && $cur == $p['id'] ? 'cur' : '' ?>">
   <?php if ($p['name'] === 'Business'): ?><span class="ribbon">Most popular</span><?php endif; ?>
   <h3><?= e($p['name']) ?></h3>
   <div class="pr"><?= money($p['price']) ?><small>/<?= $b === 'yearly' ? 'year' : 'month' ?></small></div>
   <?php if ($sv > 0): ?><span class="sv">Save <?= $sv ?>% vs monthly</span><?php endif; ?>
   <ul><li><b><?= ((int)$p['max_learners']) ?: 'Unlimited' ?></b> learners</li><li><b><?= ((int)$p['max_courses']) ?: 'Unlimited' ?></b> private courses</li>
    <?php foreach (array_filter(array_map('trim', explode('|', (string)$p['features']))) as $f) echo '<li>'.e($f).'</li>'; ?></ul>
   <?php if ($href === '#'): ?><button class="btn btn-block" disabled><?= e($lbl) ?></button>
   <?php else: ?><a class="btn btn-block <?= $p['name'] === 'Business' ? '' : 'btn-outline' ?>" href="<?= e($href) ?>"><?= e($lbl) ?></a><?php endif; ?>
  </div>
  <?php endforeach; ?>
 </div>
 <?php endforeach; ?>
</div>
