<section class="container py-6">
  <h1 style="margin:0 0 10px;">Users</h1>
  <?php Auth::requireRole(['admin']); ?>
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
    <button class="btn btn-outline" type="submit">Search</button>
  </form>

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
              <?php endif; ?>
              <?= e($u['name']) ?>
            </td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($u['email']) ?></td>
            <?php if($role==='employer'): ?>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($u['companyName'] ?? '-') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?php $ind = trim((string)($u['companyIndustry'] ?? '')); if($ind!==''){ ?><span style="display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(125,211,252,0.15);border:1px solid rgba(125,211,252,0.25);color:#7dd3fc;"><?= e($ind) ?></span><?php } else { echo '-'; } ?></td>
            <?php endif; ?>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($u['phone']) ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <a class="btn btn-outline btn-sm" href="<?= app_url('admin/users/edit') ?>?id=<?= e($u['id']) ?>&role=<?= e($role) ?>">Edit</a>
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
</section>
