<section class="card w-400 mx-auto mt-6">
  <h2>Sign in</h2>
  <form method="post" action="<?=app_url('login')?>">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>" />
    <label>Email
      <input type="email" name="email" value="<?=e($old['email'] ?? '')?>" required />
      <?php if(!empty($errors['email'])): ?><small class="error"><?=e($errors['email'])?></small><?php endif; ?>
    </label>
    <label>Password
      <div style="display:flex;gap:6px;align-items:center;">
        <input id="loginpw" type="password" name="password" required style="flex:1;" />
        <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('loginpw'); if(!el) return; var show=el.type==='password'; el.type=show?'text':'password'; this.textContent=show?'Hide':'Show';}).call(this)">Show</button>
      </div>
      <?php if(!empty($errors['password'])): ?><small class="error"><?=e($errors['password'])?></small><?php endif; ?>
    </label>
    <button type="submit" class="btn">Login</button>
  </form>
  <div class="mt-2">
    <a href="<?=app_url('forgot')?>">Forgot your password?</a>
  </div>
</section>
