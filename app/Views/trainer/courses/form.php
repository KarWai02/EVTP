<section class="container py-6">
  <?php Auth::requireRole(['trainer']); $mode=$mode??'create'; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;">Create Course</h1>

  <form method="post" action="<?= app_url('trainer/courses') ?>" class="card" style="max-width:820px;padding:16px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
      <label style="grid-column:1 / -1;">Title
        <input type="text" name="courseTitle" value="<?= e($old['courseTitle'] ?? '') ?>" required>
        <?php if(!empty($errors['courseTitle'])): ?><small class="error"><?= e($errors['courseTitle']) ?></small><?php endif; ?>
      </label>
      <label>Category
        <input type="text" name="category" value="<?= e($old['category'] ?? '') ?>">
      </label>
      <label>Sector
        <input type="text" name="sector" value="<?= e($old['sector'] ?? '') ?>">
      </label>
      <label style="grid-column:1 / -1;">Description
        <textarea name="description" rows="6" placeholder="Describe the course objectives, outline, prerequisites..."><?= e($old['description'] ?? '') ?></textarea>
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Create</button>
      <a class="btn btn-outline" href="<?= app_url('trainer/courses') ?>">Cancel</a>
    </div>
  </form>
</section>
