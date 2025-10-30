<section class="container py-6">
  <?php Auth::requireRole(['trainer']); $errors=$errors??[]; ?>
  <h1 style="margin:0 0 10px;">Materials</h1>

  <div class="grid" style="gap:12px;grid-template-columns:1fr 1fr;align-items:start;">
    <form method="post" action="<?= app_url('trainer/materials') ?>" enctype="multipart/form-data" class="card" style="padding:16px;">
      <h3 style="margin-top:0;">Upload file</h3>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="module" value="<?= e($moduleID) ?>">
      <input type="hidden" name="type" value="file">
      <label>Title
        <input type="text" name="title" required>
        <?php if(!empty($errors['title'])): ?><small class="error"><?= e($errors['title']) ?></small><?php endif; ?>
      </label>
      <label>File
        <input type="file" name="file" required>
        <?php if(!empty($errors['file'])): ?><small class="error"><?= e($errors['file']) ?></small><?php endif; ?>
      </label>
      <button class="btn" type="submit">Add File</button>
    </form>

    <form method="post" action="<?= app_url('trainer/materials') ?>" class="card" style="padding:16px;">
      <h3 style="margin-top:0;">Add link</h3>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="module" value="<?= e($moduleID) ?>">
      <input type="hidden" name="type" value="link">
      <label>Title
        <input type="text" name="title" required>
      </label>
      <label>URL
        <input type="url" name="url" required placeholder="https://...">
        <?php if(!empty($errors['url'])): ?><small class="error"><?= e($errors['url']) ?></small><?php endif; ?>
      </label>
      <button class="btn" type="submit">Add Link</button>
    </form>
  </div>

  <div class="card" style="padding:0; overflow:auto; margin-top:12px;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Type</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Link / File</th>
          <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach(($materials ?? []) as $m): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($m['title']) ?></strong></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($m['type']) ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)">
              <?php if($m['type']==='link'): ?>
                <a href="<?= e($m['url']) ?>" target="_blank" rel="noopener">Open</a>
              <?php else: ?>
                <?php if(!empty($m['filePath'])): ?>
                  <a class="btn btn-outline btn-sm" href="<?= app_url('trainer/materials/download') ?>?id=<?= e($m['materialID']) ?>">Download</a>
                <?php else: ?>
                  <span class="muted">No file</span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <span class="muted">v<?= (int)($m['version'] ?? 1) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="margin-top:12px;">
    <a class="btn btn-outline" href="<?= app_url('trainer/materials/modules') ?>?course=<?= e($_GET['course'] ?? '') ?>">Back to Modules</a>
  </div>
</section>
