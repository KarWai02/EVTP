<section class="card w-400 mx-auto mt-6">
  <h2>Reset password</h2>
  <form method="post" action="<?=app_url('reset')?>">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>" />
    <input type="hidden" name="token" value="<?=e($_GET['token'] ?? '')?>" />
    <p class="muted" style="margin:6px 0 12px;">
      Password must be at least 8 characters and include upper, lower, number, and special characters.
    </p>
    <label>New Password
      <div style="display:flex;gap:6px;align-items:center;">
        <input id="pw" type="password" name="password" minlength="8" required style="flex:1;">
        <button type="button" class="btn btn-outline btn-sm" onclick="togglePw('pw', this)">Show</button>
      </div>
      <?php if(!empty($errors['password'])): ?><small class="error"><?=e($errors['password'])?></small><?php endif; ?>
    </label>
    <label>Confirm Password
      <div style="display:flex;gap:6px;align-items:center;">
        <input id="pw2" type="password" name="password_confirmation" minlength="8" required style="flex:1;">
        <button type="button" class="btn btn-outline btn-sm" onclick="togglePw('pw2', this)">Show</button>
      </div>
      <?php if(!empty($errors['password_confirmation'])): ?><small class="error"><?=e($errors['password_confirmation'])?></small><?php endif; ?>
    </label>
    <div style="margin:8px 0 12px;">
      <div class="muted" style="margin-bottom:6px;">Strength</div>
      <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
        <div id="pwbar" style="height:8px;width:0;background:#f87171;"></div>
      </div>
      <ul id="req" class="muted" style="margin:8px 0 0 18px;line-height:1.6;">
        <li data-key="len">At least 8 characters</li>
        <li data-key="upper">Contains an uppercase letter (A-Z)</li>
        <li data-key="lower">Contains a lowercase letter (a-z)</li>
        <li data-key="num">Contains a number (0-9)</li>
        <li data-key="special">Contains a special character (!@#$%^&*)</li>
      </ul>
    </div>
    <button type="submit" class="btn">Update password</button>
  </form>
</section>

<script>
(function(){
  const pw = document.getElementById('pw');
  const bar = document.getElementById('pwbar');
  const req = document.getElementById('req');
  const items = req ? req.querySelectorAll('li') : [];
  function score(s){
    let sc = 0;
    const checks = {
      len: s.length >= 8,
      upper: /[A-Z]/.test(s),
      lower: /[a-z]/.test(s),
      num: /\d/.test(s),
      special: /[^A-Za-z0-9]/.test(s)
    };
    for(const k in checks){ if(checks[k]) sc++; const li = req && req.querySelector('li[data-key="'+k+'"]'); if(li){ li.style.color = checks[k] ? 'var(--success, #16a34a)' : 'var(--muted)'; } }
    const pct = Math.min(100, (sc/5)*100);
    if(bar){ bar.style.width = pct+'%'; bar.style.background = pct>=80? '#22c55e' : pct>=60? '#eab308' : '#f87171'; }
  }
  if(pw){ pw.addEventListener('input', function(){ score(pw.value); }); score(pw.value||''); }
})();
function togglePw(id, btn){
  const el = document.getElementById(id); if(!el) return; const is = el.type==='password'; el.type = is ? 'text' : 'password'; btn.textContent = is ? 'Hide' : 'Show';
}
</script>
