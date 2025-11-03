<section class="container py-6">
  <?php $errors = $errors ?? []; $old = $old ?? []; $t = $trainer ?? ['name'=>'','email'=>'','phone'=>'','expertise'=>'']; ?>
  <h1 style="margin:0 0 12px;">My Profile</h1>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; align-items:start;">
    <form method="post" action="<?= app_url('trainer/profile') ?>" class="card" style="padding:16px;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <h3 style="margin:0 0 10px;">Account details</h3>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <label style="grid-column: 1 / -1;">Name
          <input type="text" name="fullName" value="<?= e($old['fullName'] ?? $t['name'] ?? '') ?>" required>
          <?php if(!empty($errors['fullName'])): ?><small class="error"><?= e($errors['fullName']) ?></small><?php endif; ?>
        </label>
        <label>Email
          <input type="email" name="email" value="<?= e($old['email'] ?? $t['email'] ?? '') ?>" placeholder="you@example.com">
          <?php if(!empty($errors['email'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
        </label>
        <label>Phone
          <input type="text" name="phone" value="<?= e($old['phone'] ?? $t['phone'] ?? '') ?>" placeholder="012-3456789" pattern="^\d{3}-\d{7,8}$" title="Use xxx-xxxxxxx or xxx-xxxxxxxx format">
          <?php if(!empty($errors['phone'])): ?><small class="error"><?= e($errors['phone']) ?></small><?php endif; ?>
        </label>
        <label style="grid-column: 1 / -1;">Expertise
          <div id="exp-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px;"></div>
          <input type="text" id="exp-input" placeholder="Type and press Enter to add...">
          <input type="hidden" name="expertise" id="exp-hidden" value="<?= e($old['expertise'] ?? $t['expertise'] ?? '') ?>">
          <small class="muted">Press Enter to add; click × to remove.</small>
          <?php if(!empty($errors['expertise'])): ?><small class="error"><?= e($errors['expertise']) ?></small><?php endif; ?>
        </label>
      </div>

      <div style="margin-top:12px; display:flex; gap:8px;">
        <button class="btn" type="submit">Save</button>
        <a class="btn btn-outline" href="<?= app_url('dashboard') ?>">Back</a>
      </div>
    </form>

    <form method="post" action="<?= app_url('trainer/profile/password') ?>" class="card" style="padding:16px;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <h3 style="margin:0 0 10px;">Change password</h3>
      <div style="display:grid; grid-template-columns:1fr; gap:12px;">
        <label>Current password
          <input type="password" name="current_password" autocomplete="current-password">
          <?php if(!empty($errors['current_password'])): ?><small class="error"><?= e($errors['current_password']) ?></small><?php endif; ?>
        </label>
        <label>New password
          <input type="password" name="password" autocomplete="new-password">
          <?php if(!empty($errors['password'])): ?><small class="error"><?= e($errors['password']) ?></small><?php endif; ?>
        </label>
        <label>Confirm new password
          <input type="password" name="password_confirmation" autocomplete="new-password">
          <?php if(!empty($errors['password_confirmation'])): ?><small class="error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?>
        </label>
      </div>
      <div style="margin-top:12px;">
        <button class="btn" type="submit">Update password</button>
      </div>
    </form>
  </div>
</section>
<script>
(function(){
  var hidden = document.getElementById('exp-hidden');
  var wrap = document.getElementById('exp-chips');
  var input = document.getElementById('exp-input');
  if(!hidden || !wrap || !input) return;
  function parse(){
    var val = (hidden.value || '').split(',').map(function(s){return s.trim();}).filter(Boolean);
    return Array.from(new Set(val));
  }
  function render(){
    wrap.innerHTML = '';
    parse().forEach(function(tag){
      var chip = document.createElement('span');
      chip.className = 'pill sm';
      chip.style.cssText = 'display:inline-flex;gap:6px;align-items:center;background:var(--surface-2);border:1px solid var(--border);border-radius:999px;padding:6px 10px;';
      chip.textContent = tag;
      var btn = document.createElement('button');
      btn.type='button'; btn.textContent='×'; btn.setAttribute('aria-label','Remove');
      btn.style.cssText='margin-left:6px;background:transparent;border:0;color:inherit;cursor:pointer;';
      btn.addEventListener('click', function(){ remove(tag); });
      chip.appendChild(btn); wrap.appendChild(chip);
    });
  }
  function setTags(list){ hidden.value = list.join(', '); render(); }
  function add(tag){ var list = parse(); if(tag && list.indexOf(tag)===-1){ list.push(tag); setTags(list); } }
  function remove(tag){ var list = parse().filter(function(t){ return t!==tag; }); setTags(list); }
  input.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); var t=input.value.trim(); if(t){ add(t); input.value=''; } }});
  // Seed initial
  render();
})();
</script>
