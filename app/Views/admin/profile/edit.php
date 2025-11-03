<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <h1 style="margin:0 0 10px;">My Profile</h1>

  <div class="grid" style="display:grid;grid-template-columns:1.1fr .9fr;gap:16px;align-items:start;">
    <form method="post" action="<?= app_url('admin/profile') ?>" class="card" style="padding:16px;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <h3 style="margin:0 0 8px;">Account details</h3>
      <label>Name
        <input type="text" name="name" value="<?= e(($old['name'] ?? '') ?: ($admin['adminName'] ?? '')) ?>" required>
        <?php if(!empty($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
      </label>
      <label>Email
        <input type="email" name="email" value="<?= e(($old['email'] ?? '') ?: ($admin['adminEmail'] ?? '')) ?>" required>
        <?php if(!empty($errors['email'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
      </label>
      <label>Phone
        <input type="text" name="phone" value="<?= e(($old['phone'] ?? '') ?: ($admin['adminPhone'] ?? '')) ?>" pattern="[0-9+\-\s]{7,15}" title="7-15 characters: digits, spaces, + or - allowed" required>
        <?php if(!empty($errors['phone'])): ?><small class="error"><?= e($errors['phone']) ?></small><?php endif; ?>
      </label>
      <div style="margin-top:10px;">
        <button class="btn" type="submit">Save</button>
      </div>
    </form>

    <form method="post" action="<?= app_url('admin/profile/password') ?>" class="card" style="padding:16px;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <h3 style="margin:0 0 8px;">Change password</h3>
      <label>Current password
        <input type="password" name="current_password" required>
        <?php if(!empty($errors['current_password'])): ?><small class="error"><?= e($errors['current_password']) ?></small><?php endif; ?>
      </label>
      <label>New password
        <input type="password" name="password" required>
        <?php if(!empty($errors['password'])): ?><small class="error"><?= e($errors['password']) ?></small><?php endif; ?>
      </label>
      <label>Confirm new password
        <input type="password" name="password_confirmation" required>
        <?php if(!empty($errors['password_confirmation'])): ?><small class="error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?>
      </label>
      <div style="margin-top:10px;">
        <button class="btn" type="submit">Update password</button>
      </div>
    </form>

    <div class="card" style="padding:16px; grid-column:1 / -1; display:grid; grid-template-columns: 1fr 2fr; gap:16px; align-items:start;">
      <div>
        <h3 style="margin:0 0 8px;">Avatar</h3>
        <div style="display:flex;gap:10px;align-items:center;">
          <img src="<?= e($avatarUrl ?: app_url('assets/img/avatar-placeholder.png')) ?>" alt="Avatar" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:1px solid var(--border);">
          <form method="post" action="<?= app_url('admin/profile/avatar') ?>" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="file" name="avatar" accept="image/png,image/jpeg" required>
            <button class="btn btn-outline" type="submit">Upload</button>
          </form>
        </div>
      </div>
      <div>
        <h3 style="margin:0 0 8px;">Recent activity</h3>
        <?php if(!empty($audit)): ?>
          <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;">
            <?php foreach($audit as $a): ?>
              <li class="card" style="padding:8px;display:flex;justify-content:space-between;gap:8px;">
                <span class="muted" style="min-width:140px;"><?= e($a['created_at'] ?? '') ?></span>
                <span style="flex:1;"><?= e($a['action'] ?? '') ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="muted" style="margin:0;">No recent actions.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
