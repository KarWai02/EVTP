<section class="container py-6">
  <?php Auth::requireRole(['trainer']); ?>
  <h1 style="margin:0 0 10px;">Learner Detail</h1>

  <div class="card" style="padding:16px; margin-bottom:12px;">
    <div style="display:flex;gap:24px;flex-wrap:wrap;">
      <div>
        <div class="muted">Name</div>
        <div style="font-weight:700;"><?= e($enroll['learnerName']) ?></div>
      </div>
      <div>
        <div class="muted">Email</div>
        <div><?= e($enroll['learnerEmail']) ?></div>
      </div>
      <div>
        <div class="muted">Progress</div>
        <div><?= number_format((float)$enroll['progress'],1) ?>%</div>
      </div>
      <div>
        <div class="muted">Status</div>
        <div><?= e($enroll['completionStatus'] ?: 'In Progress') ?></div>
      </div>
      <div>
        <div class="muted">Last activity</div>
        <div><?= e($lastActivity ?: '-') ?></div>
      </div>
    </div>
  </div>

  <div class="card" style="padding:0; overflow:auto;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Progress</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Enroll date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach(($history ?? []) as $h): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($h['courseTitle']) ?> <small class="muted"><?= e($h['courseID']) ?></small></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= number_format((float)$h['progress'],1) ?>%</td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($h['completionStatus'] ?: 'In Progress') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($h['enrollDate']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="margin-top:12px;">
    <a class="btn btn-outline" href="<?= app_url('trainer/progress') ?>?course=<?= e($courseID) ?>">Back to Progress</a>
  </div>
</section>
