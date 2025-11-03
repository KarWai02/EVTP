<section class="container py-6">
  <h1 style="margin:0 0 10px;">Create thread</h1>
  <?php $errors=$errors??[]; $old=$old??[]; ?>
  <form method="post" action="<?= app_url('community/create') ?>" enctype="multipart/form-data" class="card" style="padding:16px;max-width:820px;display:grid;gap:10px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Title
      <input type="text" name="title" value="<?= e($old['title'] ?? '') ?>" required>
      <?php if(!empty($errors['title'])): ?><small class="error"><?= e($errors['title']) ?></small><?php endif; ?>
    </label>
    <label>Group
      <?php $groups=['General','Course help','Career advice','Internships','Announcements']; ?>
      <select name="group">
        <option value="">-- Select --</option>
        <?php foreach($groups as $g): ?>
          <option value="<?= e($g) ?>" <?= (($old['group'] ?? '')===$g)?'selected':'' ?>><?= e($g) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Body
      <textarea name="body" rows="8" placeholder="Share your question, tip or story..." required><?= e($old['body'] ?? '') ?></textarea>
      <?php if(!empty($errors['body'])): ?><small class="error"><?= e($errors['body']) ?></small><?php endif; ?>
    </label>
    <label>Image (optional)
      <input type="file" name="image" accept="image/png,image/jpeg">
      <small class="muted">PNG/JPG up to 2MB</small>
    </label>
    <?php if(!Auth::check()): ?>
      <div class="card" style="padding:12px;">
        <div class="muted" style="margin-bottom:8px;">Posting as guest (optional name/email)</div>
        <label>Name
          <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Anonymous">
        </label>
        <label>Email
          <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="(optional)">
        </label>
      </div>
    <?php endif; ?>
    <div>
      <button class="btn" type="submit">Post</button>
      <a class="btn btn-outline" href="<?= app_url('community') ?>">Cancel</a>
    </div>
  </form>
</section>
