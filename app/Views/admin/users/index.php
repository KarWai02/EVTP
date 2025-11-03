<section class="container py-6">
  <h1 style="margin:0 0 10px;">Users</h1>
  <?php Auth::requireRole(['admin']); ?>
  <?php if(!empty($_SESSION['flash'])): ?>
    <div class="alert"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>
  <?php if(!empty($_SESSION['temp_pass'])): $tp=$_SESSION['temp_pass']; unset($_SESSION['temp_pass']); ?>
    <div class="alert">
      <strong>Temporary password created.</strong>
      <div>Email: <code><?= e($tp['email']) ?></code></div>
      <div>Password: <code><?= e($tp['password']) ?></code></div>
      <small>This password is shown once. Ask the admin to log in and change it immediately.</small>
    </div>
  <?php endif; ?>
  <?php $role = $role ?? 'learner'; $roles=['admin','trainer','employer','learner']; ?>
  <nav class="pillbar" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
    <?php foreach($roles as $r): ?>
      <a class="pill <?= $r===$role?'':'sm' ?>" href="<?= app_url('admin/users') ?>?role=<?= e($r) ?>"><?= ucfirst($r) ?></a>
    <?php endforeach; ?>
  </nav>

  <form method="get" action="<?= app_url('admin/users') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    <input type="hidden" name="role" value="<?= e($role) ?>">
    <?php if($role==='employer'): ?>
      <input type="hidden" name="view" value="<?= e($view ?? 'table') ?>">
    <?php endif; ?>
    <input type="text" name="q" placeholder="Search name or email" value="<?= e($_GET['q'] ?? '') ?>" style="flex:1;min-width:240px;">
    <?php if($role==='trainer'): ?>
      <input type="text" name="expertise" placeholder="Filter by expertise (e.g. Welding)" value="<?= e($expertiseFilter ?? '') ?>" style="min-width:200px;">
    <?php endif; ?>
    <label class="muted">Per page
      <select name="pp">
        <?php $ppSel = (int)($perPage ?? 10); foreach([10,25,50] as $pp): ?>
          <option value="<?= $pp ?>" <?= $pp===$ppSel ? 'selected' : '' ?>><?= $pp ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if($role==='employer'): ?>
      <label class="muted">Sort
        <?php $s = $sort ?? 'name'; ?>
        <select name="sort">
          <option value="name" <?= $s==='name'?'selected':'' ?>>Name</option>
          <option value="company" <?= $s==='company'?'selected':'' ?>>Company</option>
          <option value="industry" <?= $s==='industry'?'selected':'' ?>>Industry</option>
        </select>
      </label>
      <?php $v = $view ?? 'table'; ?>
      <div class="muted" style="display:flex;gap:6px;align-items:center;">
        <span>View</span>
        <?php
          $qs = $_GET; $qs['role']=$role; $qs['view']='table'; $tableUrl = app_url('admin/users').'?'.http_build_query($qs);
          $qs['view']='cards'; $cardsUrl = app_url('admin/users').'?'.http_build_query($qs);
        ?>
        <a class="btn <?= $v==='table'?'':'btn-outline' ?> btn-sm" href="<?= e($tableUrl) ?>">Table</a>
        <a class="btn <?= $v==='cards'?'':'btn-outline' ?> btn-sm" href="<?= e($cardsUrl) ?>">Cards</a>
      </div>
    <?php endif; ?>
    <a class="btn" href="<?= app_url('admin/users/create') ?>?role=<?= e($role) ?>">New <?= e(ucfirst($role)) ?></a>
    <?php $expQs = $_GET; $expQs['role']=$role; $exportUrl = app_url('admin/users/export').'?'.http_build_query($expQs); ?>
    <a class="btn btn-outline" href="<?= e($exportUrl) ?>">Export CSV</a>
    <button class="btn btn-outline" type="submit">Search</button>
  </form>

  <?php if(($role ?? '')==='trainer' && !empty($popular)): ?>
    <div class="card" style="padding:8px;margin:-2px 0 10px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
      <span class="muted">Popular expertise:</span>
      <?php
        $selected = $expertiseSelected ?? [];
        // helper to build query with toggled tag in expertise[]
        $toggleUrl = function($tag) use($selected){
          $qs = $_GET; $qs['role']='trainer';
          $arr = is_array($qs['expertise'] ?? null) ? $qs['expertise'] : ((isset($qs['expertise']) && $qs['expertise']!=='') ? [$qs['expertise']] : []);
          $arr = array_values(array_filter(array_map('strval',$arr)));
          if(in_array($tag, $arr, true)){
            $arr = array_values(array_filter($arr, function($t) use($tag){ return $t!==$tag; }));
          } else {
            $arr[] = $tag;
          }
          unset($qs['expertise']);
          foreach($arr as $t){ $qs['expertise'][] = $t; }
          return app_url('admin/users').'?'.http_build_query($qs);
        };
      ?>
      <?php foreach($popular as $tag=>$cnt): $isSel = in_array($tag, $selected ?? [], true); $url = $toggleUrl($tag); ?>
        <a class="pill sm" href="<?= e($url) ?>" style="<?= $isSel ? 'background:rgba(96,165,250,0.15);border:1px solid rgba(96,165,250,0.25);color:#93c5fd;' : '' ?>"><?= e($tag) ?></a>
      <?php endforeach; ?>
      <?php if(!empty($selected)): ?>
        <?php $qs = $_GET; $qs['role']='trainer'; unset($qs['expertise']); $clearUrl = app_url('admin/users').'?'.http_build_query($qs); ?>
        <span class="muted" style="margin-left:auto;">Filters: <strong><?= e(implode(', ', $selected)) ?></strong></span>
        <a class="btn btn-outline btn-sm" href="<?= e($clearUrl) ?>">Clear</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if(!empty($rows)): ?>
    <?php if($role==='employer' && ($view ?? 'table')==='cards'): ?>
      <div class="card" style="padding:12px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;">
          <?php foreach($rows as $u): ?>
            <?php
              $eid = $u['id'] ?? '';
              $logoUrl = null;
              $base = app_url('uploads/employers/'.($eid));
              foreach(['png','jpg','jpeg','webp'] as $lx){
                $p = __DIR__.'/../../../public/uploads/employers/'.preg_replace('/[^A-Za-z0-9_\-]/','',$eid).'/logo.'.$lx;
                if(file_exists($p)){ $v = @filemtime($p) ?: time(); $logoUrl = $base.'/logo.'.$lx.'?v='.$v; break; }
              }
            ?>
            <div class="card" style="padding:12px;display:flex;gap:12px;align-items:flex-start;">
              <div style="width:48px;height:48px;border-radius:8px;background:var(--border);overflow:hidden;display:flex;align-items:center;justify-content:center;">
                <?php if($logoUrl): ?>
                  <img src="<?= $logoUrl ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;" />
                <?php endif; ?>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-weight:700;"><?= e($u['companyName'] ?? '-') ?></div>
                <div class="muted" style="margin-top:2px;">Industry:
                  <?php $ind = trim((string)($u['companyIndustry'] ?? '')); ?>
                  <?php if($ind!==''): ?>
                    <span style="display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(125,211,252,0.15);border:1px solid rgba(125,211,252,0.25);color:#7dd3fc;"><?= e($ind) ?></span>
                  <?php else: ?>-
                  <?php endif; ?>
                </div>
                <div style="margin-top:6px;">
                  <div><strong>Contact:</strong> <?= e($u['name']) ?></div>
                  <div><strong>Email:</strong> <?= e($u['email']) ?></div>
                  <div><strong>Phone:</strong> <?= e($u['phone']) ?></div>
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
                  <a class="btn btn-outline btn-sm" href="<?= app_url('admin/users/edit') ?>?id=<?= e($u['id']) ?>&role=<?= e($role) ?>">Edit</a>
                  <form method="post" action="<?= app_url('admin/users/delete') ?>" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                    <input type="hidden" name="role" value="<?= e($role) ?>">
                    <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Name</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
            <?php if($role==='employer'): ?>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Company</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Industry</th>
            <?php endif; ?>
            <?php if($role==='trainer'): ?>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Expertise</th>
            <?php endif; ?>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Phone</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $u): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)">
              <?php if($role==='employer'): ?>
                <?php
                  $eid = $u['id'] ?? '';
                  $logoUrl = null; $base = app_url('uploads/employers/'.($eid));
                  foreach(['png','jpg','jpeg','webp'] as $lx){ $p = __DIR__.'/../../../public/uploads/employers/'.preg_replace('/[^A-Za-z0-9_\-]/','',$eid).'/logo.'.$lx; if(file_exists($p)){ $v=@filemtime($p)?:time(); $logoUrl = $base.'/logo.'.$lx.'?v='.$v; break; } }
                ?>
                <span style="display:inline-flex;width:24px;height:24px;border-radius:6px;background:var(--border);overflow:hidden;vertical-align:middle;margin-right:6px;">
                  <?php if($logoUrl): ?><img src="<?= $logoUrl ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;" /><?php endif; ?>
                </span>
              <?php else: ?>
                <?php $nm = trim((string)($u['name'] ?? '')); $ini = strtoupper(mb_substr($nm,0,1)); ?>
                <span style="display:inline-flex;width:24px;height:24px;border-radius:999px;background:var(--border);color:#9ca3af;align-items:center;justify-content:center;font-size:12px;margin-right:6px;">
                  <?= e($ini ?: 'U') ?>
                </span>
              <?php endif; ?>
              <?= e($u['name']) ?>
            </td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($u['email']) ?></td>
            <?php if($role==='employer'): ?>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($u['companyName'] ?? '-') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?php $ind = trim((string)($u['companyIndustry'] ?? '')); if($ind!==''){ ?><span style="display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(125,211,252,0.15);border:1px solid rgba(125,211,252,0.25);color:#7dd3fc;"><?= e($ind) ?></span><?php } else { echo '-'; } ?></td>
            <?php endif; ?>
            <?php if($role==='trainer'): ?>
              <td style="padding:10px;border-bottom:1px solid var(--border)">
                <?php $exp = trim((string)($u['expertise'] ?? '')); if($exp!==''){ ?>
                  <?php
                    $tags = array_values(array_filter(array_map('trim', explode(',', $exp))));
                    $shown = 0; $max = 3;
                    foreach($tags as $tg){ if($shown>=$max) break; $shown++; $qs=$_GET; $qs['role']='trainer'; $qs['expertise']=$tg; $url=app_url('admin/users').'?'.http_build_query($qs);
                  ?>
                    <a class="pill sm" href="<?= e($url) ?>" style="background:rgba(134,239,172,0.15);border:1px solid rgba(134,239,172,0.25);color:#86efac;"><?= e($tg) ?></a>
                  <?php } if(count($tags)>$max): ?>
                    <span class="pill sm" title="<?= e(implode(', ', array_slice($tags,$max))) ?>">+<?= count($tags)-$max ?></span>
                  <?php endif; ?>
                <?php } else { echo '-'; } ?>
              </td>
            <?php endif; ?>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($u['phone']) ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <a class="btn btn-outline btn-sm" href="<?= app_url('admin/users/edit') ?>?id=<?= e($u['id']) ?>&role=<?= e($role) ?>">Edit</a>
              <?php if($role==='trainer'): ?>
                <button class="btn btn-outline btn-sm" type="button" data-exp-id="<?= e($u['id']) ?>" data-exp-name="<?= e($u['name']) ?>" data-exp="<?= e($u['expertise'] ?? '') ?>" onclick="openExpertiseModalFromEl(this)">Edit Expertise</button>
              <?php endif; ?>
              <form method="post" action="<?= app_url('admin/users/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                <input type="hidden" name="role" value="<?= e($role) ?>">
                <button class="btn btn-outline btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
    <?php if(($pages ?? 1) > 1): ?>
      <?php
        $current = (int)($page ?? 1);
        $last    = (int)$pages;
        $qs = ['role'=>$role,'q'=>($q ?? ''),'pp'=>(int)($perPage ?? 10)];
        if($role==='employer') { $qs['sort']=$sort ?? 'name'; $qs['view']=$view ?? 'table'; }
        $link = function($p) use ($qs){ $qs['page']=$p; return app_url('admin/users').'?'.http_build_query($qs); };
      ?>
      <nav class="pagination" aria-label="Pagination" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <span class="muted">Page <?= $current ?> of <?= $last ?> (<?= (int)($total ?? 0) ?>)</span>
        <a class="btn btn-outline btn-sm" href="<?= $link(1) ?>"                <?= $current<=1?'aria-disabled="true" tabindex="-1"':'' ?>>« First</a>
        <a class="btn btn-outline btn-sm" href="<?= $link(max(1,$current-1)) ?>" <?= $current<=1?'aria-disabled="true" tabindex="-1"':'' ?>>‹ Prev</a>
        <?php
          $start = max(1, $current - 2);
          $end   = min($last, $current + 2);
          if ($start > 1) echo '<span class="muted">…</span>';
          for($p=$start; $p<=$end; $p++){
            $cls = $p===$current ? 'btn btn-sm' : 'btn btn-outline btn-sm';
            echo '<a class="'.$cls.'" href="'.e($link($p)).'">'.$p.'</a>';
          }
          if ($end < $last) echo '<span class="muted">…</span>';
        ?>
        <a class="btn btn-outline btn-sm" href="<?= $link(min($last,$current+1)) ?>" <?= $current>=$last?'aria-disabled="true" tabindex="-1"':'' ?>>Next ›</a>
        <a class="btn btn-outline btn-sm" href="<?= $link($last) ?>"            <?= $current>=$last?'aria-disabled="true" tabindex="-1"':'' ?>>Last »</a>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted">No users found.</p>
  <?php endif; ?>

  <?php if(($role ?? '')==='trainer'): ?>
  <div id="expModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="card" style="width:min(560px,90vw);padding:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <h3 style="margin:0;">Edit Expertise — <span id="expModalName"></span></h3>
        <button class="btn btn-outline btn-sm" type="button" onclick="closeExpertiseModal()">Close</button>
      </div>
      <form method="post" action="<?= app_url('admin/users/update-expertise') ?>">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" id="expModalId" value="">
        <input type="hidden" name="expertise" id="expHidden2" value="">
        <label>Expertise
          <div id="tagEditor2" class="card" style="padding:6px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;min-height:38px;cursor:text;">
            <input id="tagInput2" type="text" placeholder="Type and press Enter or ," style="border:none;outline:none;background:transparent;flex:1;min-width:160px;">
          </div>
        </label>
        <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">
          <button class="btn" type="submit">Save</button>
          <button class="btn btn-outline" type="button" onclick="closeExpertiseModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function(){
      const modal = document.getElementById('expModal');
      const nameEl = document.getElementById('expModalName');
      const idEl = document.getElementById('expModalId');
      const hidden = document.getElementById('expHidden2');
      const wrap = document.getElementById('tagEditor2');
      const input = document.getElementById('tagInput2');
      function tokens(){ return hidden.value.split(',').map(s=>s.trim()).filter(Boolean); }
      function render(){
        [...wrap.querySelectorAll('span[data-tag]')].forEach(el=>el.remove());
        tokens().forEach(t=>{
          const pill=document.createElement('span'); pill.setAttribute('data-tag',t); pill.className='pill sm'; pill.style.margin='2px'; pill.textContent=t;
          const x=document.createElement('button'); x.type='button'; x.textContent='×'; x.className='btn btn-outline btn-sm'; x.style.padding='0 6px'; x.style.marginLeft='6px'; x.onclick=()=>{ removeTag(t); };
          pill.appendChild(x); wrap.insertBefore(pill,input);
        });
      }
      function addTag(t){ t=t.trim(); if(!t) return; const set=new Set(tokens()); if(set.has(t)) return; set.add(t); hidden.value=[...set].join(', '); input.value=''; render(); }
      function removeTag(t){ const list=tokens().filter(x=>x!==t); hidden.value=list.join(', '); render(); }
      input.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===','){ e.preventDefault(); addTag(input.value.replace(/,$/,'')); } else if(e.key==='Backspace' && input.value===''){ const list=tokens(); if(list.length){ removeTag(list[list.length-1]); } } });
      wrap.addEventListener('click', ()=> input.focus());
      window.openExpertiseModalFromEl = function(btn){ const id=btn.getAttribute('data-exp-id'); const name=btn.getAttribute('data-exp-name'); const exp=btn.getAttribute('data-exp')||''; idEl.value=id; nameEl.textContent=name; hidden.value=exp; input.value=''; render(); modal.style.display='flex'; };
      window.closeExpertiseModal = function(){ modal.style.display='none'; };
    })();
  </script>
  <?php endif; ?>
</section>
