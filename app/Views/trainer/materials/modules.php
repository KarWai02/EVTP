<section class="container py-6">
  <?php Auth::requireRole(['trainer']); $errors=$errors??[]; ?>
  <h1 style="margin:0 0 10px;">Modules</h1>

  <form method="post" action="<?= app_url('trainer/modules') ?>" class="card" style="max-width:760px;padding:16px;margin-bottom:12px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="course" value="<?= e($courseID) ?>">
    <div style="display:grid;gap:10px;grid-template-columns:1fr;">
      <label>Module title
        <input type="text" name="title" required>
        <?php if(!empty($errors['title'])): ?><small class="error"><?= e($errors['title']) ?></small><?php endif; ?>
      </label>
      <label>Description
        <textarea name="description" rows="4" placeholder="Outline, objectives, notes..."></textarea>
      </label>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Add Module</button>
      <a class="btn btn-outline" href="<?= app_url('trainer/courses') ?>">Back</a>
    </div>
  </form>

  <?php if(!empty($modules)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Module</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Description</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($modules as $m): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($m['title']) ?></strong></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><div style="max-width:700px;white-space:pre-wrap;"><?= e($m['description']) ?></div></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <a class="btn btn-outline btn-sm" href="<?= app_url('trainer/materials/list') ?>?module=<?= e($m['moduleID']) ?>">Manage Materials</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No modules yet. Add one above.</p>
  <?php endif; ?>
</section>
