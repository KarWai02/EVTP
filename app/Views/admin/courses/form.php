<section class="container py-6">
  <?php Auth::requireRole(['admin','trainer']); ?>
  <?php $mode = $mode ?? 'create'; $c = $course ?? []; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;"><?= $mode==='create' ? 'Create Course' : 'Edit Course' ?></h1>

  <?php $role = Auth::user()['role'] ?? ''; $isTrainer = strtolower((string)$role)==='trainer'; ?>
  <form method="post" action="<?= $mode==='create'
      ? ($isTrainer ? app_url('trainer/courses') : app_url('admin/courses'))
      : ($isTrainer ? app_url('trainer/courses/update') : app_url('admin/courses/update')) ?>" class="card" style="max-width:900px;padding:20px;">
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
      <label>Level
        <input type="text" name="level" value="<?= e(($old['level'] ?? '') ?: ($c['level'] ?? '')) ?>" placeholder="Beginner / Intermediate / Advanced">
      </label>
      <label>Trainer
        <?php $selTrainer = ($old['trainerID'] ?? '') ?: ($c['trainerID'] ?? ''); ?>
        <select name="trainerID">
          <option value="">Select a trainer…</option>
          <?php foreach(($trainers ?? []) as $t): ?>
            <option value="<?= e($t['trainerID']) ?>" <?= ($selTrainer==($t['trainerID'] ?? ''))?'selected':'' ?>><?= e($t['name'] ?? $t['trainerID']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if(!empty($errors['trainerID'] ?? '')): ?><small class="error"><?= e($errors['trainerID']) ?></small><?php endif; ?>
      </label>
      <label>Created date
        <input type="date" name="createdDate" value="<?= e(($old['createdDate'] ?? '') ?: ($c['createdDate'] ?? date('Y-m-d'))) ?>">
      </label>
      <label style="grid-column:1 / -1;">Description
        <textarea name="description" rows="6"><?= e(($old['description'] ?? '') ?: ($c['description'] ?? '')) ?></textarea>
        <small class="muted">Tip: You can leave category/level empty if unknown; you can set modules later.</small>
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= $isTrainer ? app_url('trainer/courses') : app_url('admin/courses') ?>">Cancel</a>
      <?php if($mode==='edit' && !empty($c['courseID'])): ?>
        <a class="btn btn-outline" href="<?= app_url('admin/courses/modules') ?>?courseID=<?= e($c['courseID']) ?>">Manage Modules</a>
      <?php endif; ?>
    </div>
  </form>
</section>
