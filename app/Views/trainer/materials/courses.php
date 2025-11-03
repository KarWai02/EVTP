<section class="container py-6">
  <?php Auth::requireRole(['trainer']); ?>
  <div style="display:flex;align-items:center;gap:10px;justify-content:space-between;margin-bottom:10px;">
    <h1 style="margin:0;">My Courses</h1>
    <a class="btn" href="<?= app_url('trainer/courses/create') ?>">Create Course</a>
  </div>
  <?php if(!empty($rows)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $c): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($c['courseTitle']) ?></strong> <small class="muted">(<?= e($c['courseID']) ?>)</small></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;display:flex;gap:8px;justify-content:flex-end;">
                <a class="btn btn-outline" href="<?= app_url('trainer/courses/edit') ?>?id=<?= e($c['courseID']) ?>">Edit Course</a>
                <a class="btn" href="<?= app_url('trainer/materials/modules') ?>?course=<?= e($c['courseID']) ?>">Manage Materials</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card" style="max-width:720px;">
      <p class="muted" style="margin:0 0 8px;">No courses yet. Create your first course to start adding modules and materials.</p>
      <a class="btn" href="<?= app_url('trainer/courses/create') ?>">Create Course</a>
    </div>
  <?php endif; ?>
</section>
