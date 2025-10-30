<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <?php $mode = $mode ?? 'create'; $c = $course ?? []; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;"><?= $mode==='create' ? 'Create Course' : 'Edit Course' ?></h1>

  <form method="post" action="<?= $mode==='create' ? app_url('admin/courses') : app_url('admin/courses/update') ?>" class="card" style="max-width:800px;padding:20px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <?php if($mode==='edit'): ?><input type="hidden" name="id" value="<?= e($c['courseID'] ?? '') ?>"><?php endif; ?>

    <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
      <label style="grid-column:1 / -1;">Title
        <input type="text" name="courseTitle" value="<?= e(($old['courseTitle'] ?? '') ?: ($c['courseTitle'] ?? '')) ?>" required>
        <?php if(!empty($errors['courseTitle'])): ?><small class="error"><?= e($errors['courseTitle']) ?></small><?php endif; ?>
      </label>
      <label>Category
        <input type="text" name="category" value="<?= e(($old['category'] ?? '') ?: ($c['category'] ?? '')) ?>">
      </label>
      <label>Sector
        <input type="text" name="sector" value="<?= e(($old['sector'] ?? '') ?: ($c['sector'] ?? '')) ?>">
      </label>
      <label style="grid-column:1 / -1;">Description
        <textarea name="description" rows="6"><?= e(($old['description'] ?? '') ?: ($c['description'] ?? '')) ?></textarea>
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('admin/courses') ?>">Cancel</a>
    </div>
  </form>
</section>
