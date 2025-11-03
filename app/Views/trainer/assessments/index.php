<section class="container py-6">
  <?php Auth::requireRole(['trainer']); ?>
  <h1 style="margin:0 0 10px;">Assessments</h1>

  <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
    <a class="btn" href="<?= app_url('trainer/assessments/create') ?>?module=<?= e($moduleID) ?>">Add Assessment</a>
    <a class="btn btn-outline" href="<?= app_url('trainer/materials/list') ?>?module=<?= e($moduleID) ?>">Back to Materials</a>
  </div>

  <?php if(!empty($rows)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Type</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Pass</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Max</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Duration</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $a): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['assessType']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)$a['passScore'] ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)$a['maxScore'] ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)($a['durationLimit'] ?? 0) ?>m</td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <a class="btn btn-outline btn-sm" href="<?= app_url('trainer/assessments/edit') ?>?id=<?= e($a['assessmentID']) ?>">Edit</a>
                <form method="post" action="<?= app_url('trainer/assessments/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this assessment?');">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= e($a['assessmentID']) ?>">
                  <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No assessments yet for this module.</p>
  <?php endif; ?>
</section>
