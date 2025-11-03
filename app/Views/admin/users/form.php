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
      <?php if(($role ?? '')==='trainer'): ?>
      <label>Expertise
        <?php $expValue = trim((string)(($old['expertise'] ?? '') ?: ($u['expertise'] ?? ''))); ?>
        <input type="hidden" name="expertise" id="expertiseHidden" value="<?= e($expValue) ?>">
        <div id="tagEditor" class="card" style="padding:6px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;min-height:38px;cursor:text;">
          <input id="tagInput" type="text" placeholder="Type and press Enter or ," style="border:none;outline:none;background:transparent;flex:1;min-width:160px;">
        </div>
        <small class="muted">Press Enter or comma to add. Backspace deletes the last tag.</small>
      </label>
      <script>
        (function(){
          const hidden = document.getElementById('expertiseHidden');
          const wrap = document.getElementById('tagEditor');
          const input = document.getElementById('tagInput');
          function tokens(){ return hidden.value.split(',').map(s=>s.trim()).filter(Boolean); }
          function render(){
            // remove existing pills except input
            [...wrap.querySelectorAll('span[data-tag]')].forEach(el=>el.remove());
            tokens().forEach(t=>{
              const pill = document.createElement('span');
              pill.setAttribute('data-tag', t);
              pill.className = 'pill sm';
              pill.style.margin = '2px';
              pill.textContent = t;
              const x = document.createElement('button');
              x.type='button'; x.textContent='×'; x.style.marginLeft='6px'; x.className='btn btn-outline btn-sm';
              x.style.padding='0 6px'; x.style.lineHeight='16px';
              x.onclick=()=>{ removeTag(t); };
              pill.appendChild(x);
              wrap.insertBefore(pill, input);
            });
          }
          function addTag(t){ t=t.trim(); if(!t) return; const set=new Set(tokens()); if(set.has(t)) return; set.add(t); hidden.value=[...set].join(', '); input.value=''; render(); }
          function removeTag(t){ const list=tokens().filter(x=>x!==t); hidden.value=list.join(', '); render(); }
          input.addEventListener('keydown', (e)=>{
            if(e.key==='Enter' || e.key===','){ e.preventDefault(); addTag(input.value.replace(/,$/,'')); }
            else if(e.key==='Backspace' && input.value===''){ const list=tokens(); if(list.length){ removeTag(list[list.length-1]); }}
          });
          wrap.addEventListener('click', ()=> input.focus());
          // seed from hidden
          render();
        })();
      </script>
      <?php endif; ?>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('admin/users') ?>?role=<?= e($role) ?>">Cancel</a>
    </div>
  </form>
</section>
