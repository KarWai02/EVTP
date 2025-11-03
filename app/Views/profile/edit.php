<section class="container py-6">
  <h1 style="margin:0 0 12px;">My Profile</h1>

  <!-- Stats row -->
  <div class="grid cards" style="display:grid;grid-template-columns:repeat(3, minmax(160px,1fr));gap:12px;margin-bottom:14px;">
    <div class="card" style="padding:16px;">
      <div class="muted">Enrolled</div>
      <div style="font-size:24px;font-weight:800;"><?= (int)($stats['enrolled'] ?? 0) ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">In progress</div>
      <div style="font-size:24px;font-weight:800;"><?= (int)($stats['in_progress'] ?? 0) ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Completed</div>
      <div style="font-size:24px;font-weight:800;"><?= (int)($stats['completed'] ?? 0) ?></div>
    </div>
  </div>

  <!-- Two-column layout -->
  <div style="display:grid;grid-template-columns:1.4fr .9fr;gap:16px;">
    <!-- Left: Details and edit -->
    <div class="card" style="padding:20px;">
      <h2>Account details</h2>
      <form method="post" action="<?=app_url('profile')?>" class="mb-4">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>" />
        <div class="grid" style="display:grid;grid-template-columns:1fr;gap:10px;">
          <label>Name
            <input type="text" name="name" value="<?=e(($old['name'] ?? '') ?: ($learner['learnerName'] ?? ''))?>" required />
            <?php if(!empty($errors['name'])): ?><small class="error"><?=e($errors['name'])?></small><?php endif; ?>
          </label>
          <label>Email (read-only)
            <input type="email" value="<?=e($learner['learnerEmail'] ?? '')?>" readonly />
          </label>
          <label>Phone
            <input type="text" name="phone" value="<?=e(($old['phone'] ?? '') ?: ($learner['learnerPhone'] ?? ''))?>" required />
            <?php if(!empty($errors['phone'])): ?><small class="error"><?=e($errors['phone'])?></small><?php endif; ?>
          </label>
        </div>
        <button type="submit" class="btn" style="margin-top:8px;">Save changes</button>
      </form>

      <h3>Change Password</h3>
      <form method="post" action="<?=app_url('profile/password')?>">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>" />
        <label>Current Password
          <input type="password" name="current_password" required />
          <?php if(!empty($errors['current_password'])): ?><small class="error"><?=e($errors['current_password'])?></small><?php endif; ?>
        </label>
        <label>New Password
          <input type="password" name="password" minlength="8" required />
          <?php if(!empty($errors['password'])): ?><small class="error"><?=e($errors['password'])?></small><?php endif; ?>
        </label>
        <label>Confirm New Password
          <input type="password" name="password_confirmation" minlength="8" required />
          <?php if(!empty($errors['password_confirmation'])): ?><small class="error"><?=e($errors['password_confirmation'])?></small><?php endif; ?>
        </label>
        <button type="submit" class="btn">Update password</button>
      </form>
    </div>

    <!-- Right: Recent enrollments -->
    <aside class="card" style="padding:20px;">
      <h2>Recent enrollments</h2>
      <?php if(!empty($recent)): ?>
        <ul style="list-style:none;padding:0;margin:10px 0 0;display:flex;flex-direction:column;gap:10px;">
          <?php foreach($recent as $r): ?>
            <li style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
              <div>
                <div style="font-weight:700;"><?= e($r['courseTitle']) ?></div>
                <small class="muted">Enrolled: <?= e($r['enrollDate']) ?> • <?= e($r['completionStatus']) ?></small>
              </div>
              <a class="btn btn-outline btn-sm" href="<?= app_url('courses/view') ?>?id=<?= e($r['courseID']) ?>">View</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted" style="margin-top:6px;">No recent enrollments.</p>
      <?php endif; ?>
    </aside>
  </div>
</section>
