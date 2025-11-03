<section class="container py-6">
  <?php Auth::requireRole(['trainer']); ?>
  <h1 style="margin:0 0 10px;">Workshops</h1>

  <form method="get" action="<?= app_url('trainer/workshops') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    <input type="text" name="q" placeholder="Search title or topic" value="<?= e($_GET['q'] ?? '') ?>" style="flex:1;min-width:260px;">
    <label>Scope
      <select name="scope" onchange="this.form.submit()">
        <?php foreach(['upcoming','past','all'] as $s): ?>
          <option value="<?= $s ?>" <?= (($scope ?? 'upcoming')===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <a class="btn" href="<?= app_url('trainer/workshops/create') ?>">Schedule Workshop</a>
    <button class="btn btn-outline" type="submit">Search</button>
  </form>

  <?php if(!empty($rows)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Topic</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Date & Time</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Link</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $w): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($w['workshopTitle']) ?></strong></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($w['workshopTopic'] ?? '') ?></td>
              <?php $__dt = (string)($w['dateTime'] ?? ''); $__pretty = $__dt!=='' ? date('Y-m-d h:i A', strtotime($__dt)) : ''; ?>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($__pretty) ?> (<?= (int)($w['duration'] ?? 0) ?>m)</td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($w['courseID'] ?? '-') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><a href="<?= e($w['platformLink']) ?>" target="_blank" rel="noopener">Join</a></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <a class="btn btn-outline btn-sm" href="<?= app_url('trainer/workshops/edit') ?>?id=<?= e($w['workshopID']) ?>">Edit</a>
                <form method="post" action="<?= app_url('trainer/workshops/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this workshop?');">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= e($w['workshopID']) ?>">
                  <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No workshops yet. Click Schedule Workshop.</p>
  <?php endif; ?>
</section>
