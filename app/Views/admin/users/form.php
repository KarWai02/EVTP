<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <?php $role = $role ?? 'learner'; $mode = $mode ?? 'create'; $u = $user ?? []; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;"><?= $mode==='create' ? 'Create' : 'Edit' ?> <?= e(ucfirst($role)) ?></h1>

  <form method="post" action="<?= $mode==='create' ? app_url('admin/users') : app_url('admin/users/update') ?>" class="card" style="max-width:640px;padding:20px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="role" value="<?= e($role) ?>">
    <?php if($mode==='edit'): ?><input type="hidden" name="id" value="<?= e($u['id'] ?? '') ?>"><?php endif; ?>

    <div style="display:grid;gap:10px;">
      <label>Name
        <input type="text" name="name" value="<?= e(($old['name'] ?? '') ?: ($u['name'] ?? '')) ?>" required>
        <?php if(!empty($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
      </label>
      <?php if(($role ?? '')==='employer'): ?>
      <label>Company name
        <input type="text" name="companyName" value="<?= e(($old['companyName'] ?? '') ?: ($u['companyName'] ?? '')) ?>">
        <?php if(!empty($errors['companyName'])): ?><small class="error"><?= e($errors['companyName']) ?></small><?php endif; ?>
      </label>
      <label>Company industry
        <input type="text" name="companyIndustry" value="<?= e(($old['companyIndustry'] ?? '') ?: ($u['companyIndustry'] ?? '')) ?>">
        <?php if(!empty($errors['companyIndustry'])): ?><small class="error"><?= e($errors['companyIndustry']) ?></small><?php endif; ?>
      </label>
      <?php endif; ?>
      <label>Email
        <input type="email" name="email" value="<?= e(($old['email'] ?? '') ?: ($u['email'] ?? '')) ?>" required>
        <?php if(!empty($errors['email'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
      </label>
      <label>Phone
        <input type="text" name="phone" value="<?= e(($old['phone'] ?? '') ?: ($u['phone'] ?? '')) ?>" pattern="[0-9+\-\s]{7,15}" title="7-15 characters: digits, spaces, + or - allowed" required>
        <?php if(!empty($errors['phone'])): ?><small class="error"><?= e($errors['phone']) ?></small><?php endif; ?>
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('admin/users') ?>?role=<?= e($role) ?>">Cancel</a>
    </div>
  </form>
</section>
