<?php
  $moduleCount = count($modules);
  $totalMins = 0; foreach($modules as $m){ $totalMins += (int)($m['estimatedDuration'] ?? 0); }
  $hours = floor($totalMins/60); $mins = $totalMins%60;
?>

<section class="course-hero">
  <div class="container">
    <div class="hero-head">
      <div>
        <div class="muted" style="margin-bottom:6px;">Category: <?= e($course['category'] ?? 'General') ?></div>
        <h1 class="title"><?= e($course['courseTitle']) ?></h1>
        <p class="sub"><?= e($course['description'] ?? '') ?></p>
      </div>
      <div class="cta">
        <?php if(Auth::check() && Auth::user()['role']==='learner'): ?>
          <form method="post" action="<?= app_url('courses/enroll') ?>">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="courseID" value="<?= e($course['courseID']) ?>">
            <button class="btn">Enroll for free</button>
          </form>
        <?php else: ?>
          <a class="btn" href="<?= app_url('signup') ?>">Enroll for free</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="stats-bar">
      <div class="stat"><strong><?= (int)$moduleCount ?></strong><span>modules</span></div>
      <div class="stat"><strong><?= $hours ?>h <?= $mins ?>m</strong><span>total length</span></div>
      <div class="stat"><strong><?= e($course['category'] ?? 'General') ?></strong><span>category</span></div>
    </div>
  </div>
  <div class="hero-bg"></div>
</section>

<section class="container py-6">
  <div class="card">
    <div class="tabs-head">
      <span class="tab active">Modules</span>
    </div>
    <div class="module-list">
      <?php foreach($modules as $idx=>$m): ?>
        <div class="module-item">
          <div class="module-left">
            <div class="module-title"><?= e($m['content']) ?></div>
            <div class="muted">Module <?= $idx+1 ?> • <?= e((int)($m['estimatedDuration'] ?? 0)) ?> min</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
