<section class="container py-6">
  <?php Auth::requireRole(['trainer']); $c=$course??[]; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;">Edit Course</h1>

  <form method="post" action="<?= app_url('trainer/courses/update') ?>" class="card" style="max-width:900px;padding:20px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= e($c['courseID'] ?? '') ?>">

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
      <?php $lvl = ($old['level'] ?? '') ?: ($c['level'] ?? ($c['courseLevel'] ?? ($c['difficulty'] ?? ''))); ?>
      <label>Level
        <input type="text" name="level" value="<?= e($lvl) ?>" placeholder="Beginner / Intermediate / Advanced">
      </label>
      <label>Created date
        <input type="date" name="createdDate" value="<?= e(($old['createdDate'] ?? '') ?: ($c['createdDate'] ?? date('Y-m-d'))) ?>">
      </label>
      <label style="grid-column:1 / -1;">Description
        <textarea name="description" rows="6"><?= e(($old['description'] ?? '') ?: ($c['description'] ?? '')) ?></textarea>
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('trainer/courses') ?>">Cancel</a>
    </div>
  </form>
</section>
