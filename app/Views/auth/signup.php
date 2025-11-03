<section class="container py-6">
  <div class="card mx-auto" style="max-width:640px;padding:24px;border-radius:16px;">
    <h1 style="margin:0 0 6px;">Create your account</h1>
    <p class="muted" style="margin:0 0 16px;">Join EVTP to start learning. It only takes a minute.</p>

    <form method="post" action="<?=app_url('signup')?>">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>" />

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <label style="flex:1 1 240px;">Name
          <input type="text" name="name" value="<?=e($old['name'] ?? '')?>" required />
          <?php if(!empty($errors['name'])): ?><small class="error"><?=e($errors['name'])?></small><?php endif; ?>
        </label>
        <label style="flex:1 1 240px;">Email
          <input type="email" name="email" value="<?=e($old['email'] ?? '')?>" required inputmode="email" autocomplete="email" />
          <small class="muted">Use a valid email like name@example.com</small>
          <?php if(!empty($errors['email'])): ?><small class="error"><?=e($errors['email'])?></small><?php endif; ?>
        </label>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <label style="flex:1 1 240px;">Phone
          <input type="text" name="phone" value="<?=e($old['phone'] ?? '')?>" required pattern="^\d{3}-\d{7,8}$" title="Use xxx-xxxxxxx or xxx-xxxxxxxx format" placeholder="012-3456789" inputmode="tel" autocomplete="tel" />
          <small class="muted">Format: 012-3456789 or 012-34567890</small>
          <?php if(!empty($errors['phone'])): ?><small class="error"><?=e($errors['phone'])?></small><?php endif; ?>
        </label>
        <label style="flex:1 1 240px;">Password
          <div style="display:flex;gap:8px;align-items:center;">
            <input id="pass" type="password" name="password" minlength="8" required autocomplete="new-password" />
            <button type="button" class="btn btn-outline btn-sm" onclick="togglePwd('pass', this)">Show</button>
          </div>
          <?php if(!empty($errors['password'])): ?><small class="error"><?=e($errors['password'])?></small><?php endif; ?>
        </label>
      </div>

      <label>Confirm Password
        <div style="display:flex;gap:8px;align-items:center;">
          <input id="pass2" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password" />
          <button type="button" class="btn btn-outline btn-sm" onclick="togglePwd('pass2', this)">Show</button>
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

      <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
        <button type="submit" class="btn">Sign up</button>
        <small class="muted">Already have an account? <a href="<?=app_url('login')?>">Log in</a></small>
      </div>
    </form>

    <script>
      function togglePwd(id, btn){
        var el=document.getElementById(id); if(!el) return; var s=el.getAttribute('type')==='password'?'text':'password'; el.setAttribute('type', s); btn.textContent = (s==='text'?'Hide':'Show');
      }
      (function(){
        var input = document.getElementById('pass');
        var input2 = document.getElementById('pass2');
        var bar = document.getElementById('pwbar');
        var req = document.getElementById('req');
        function update(){
          var v = input.value || '';
          var checks = {
            len: v.length >= 8,
            upper: /[A-Z]/.test(v),
            lower: /[a-z]/.test(v),
            num: /\d/.test(v),
            special: /[^A-Za-z0-9]/.test(v)
          };
          var sc = 0; for (var k in checks){ if(checks[k]) sc++; var li = req && req.querySelector('li[data-key="'+k+'"]'); if(li){ li.style.color = checks[k] ? 'var(--success, #16a34a)' : 'var(--muted)'; } }
          var pct = Math.min(100, (sc/5)*100);
          if(bar){ bar.style.width = pct+'%'; bar.style.background = pct>=80? '#22c55e' : pct>=60? '#eab308' : '#f87171'; }
          if(input2){ input2.setCustomValidity( (input2.value && input2.value!==v) ? 'Passwords do not match' : '' ); }
        }
        function match(){ if(!input2) return; var v1=input.value||'', v2=input2.value||''; input2.setCustomValidity(v2 && v1!==v2 ? 'Passwords do not match' : ''); }
        input && input.addEventListener('input', update);
        input2 && input2.addEventListener('input', match);
        update();
      })();
    </script>
  </div>
</section>

