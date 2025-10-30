<section class="container py-6">
  <h1 style="margin:0 0 12px;">My Enrollments</h1>

  <?php if(!empty($items)): ?>
    <ul class="grid cards" style="list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
      <?php foreach($items as $it): ?>
        <li class="card" style="display:flex;flex-direction:column;gap:8px;">
          <div class="pill sm" style="align-self:flex-start;"><?= e($it['category'] ?: 'General') ?></div>
          <h3 style="margin:0;"><?= e($it['courseTitle']) ?></h3>
          <div class="muted">Enrolled: <?= e($it['enrollDate']) ?></div>
          <div class="muted">Status: <?= e($it['completionStatus']) ?> • Progress: <?= (int)($it['progress'] ?? 0) ?>%</div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <a class="btn btn-outline" href="<?= app_url('courses/view') ?>?id=<?= e($it['courseID']) ?>">Go to course</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="muted">You have no enrollments yet. <a href="<?= app_url('courses') ?>">Browse courses</a>.</p>
  <?php endif; ?>
</section>
