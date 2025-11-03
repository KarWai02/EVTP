<section class="container py-6">
  <style>
    .table-zebra tbody tr:nth-child(even){ background: rgba(255,255,255,0.02); }
    .table-zebra tbody tr:hover{ background: rgba(255,255,255,0.04); }
  </style>
  <?php Auth::requireRole(['employer']); ?>
  <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
    <h1 style="margin:0;">Candidates</h1>
    <form method="get" action="<?= app_url('employer/candidates') ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <label class="muted">Job
        <select name="job" onchange="this.form.submit()">
          <option value="">All jobs</option>
          <?php foreach(($jobs ?? []) as $j): ?>
            <option value="<?= e($j['jobID']) ?>" <?= ($job ?? '')==$j['jobID']?'selected':'' ?>><?= e($j['jobTitle']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="muted">Status
        <select name="status" onchange="this.form.submit()">
          <?php foreach(['','Under Review','Interview','Hired','Rejected'] as $s): ?>
            <option value="<?= e($s) ?>" <?= ($status ?? '')===$s?'selected':'' ?>><?= $s===''? 'Any' : e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="muted">Sort
        <select name="sort" onchange="this.form.submit()">
          <?php $opts=['applied_newest'=>'Apply date (newest)','applied_oldest'=>'Apply date (oldest)','name_az'=>'Name A→Z','name_za'=>'Name Z→A','status'=>'Status']; foreach($opts as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($sort ?? 'applied_newest')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php $qs = http_build_query(['job'=>$job??'','status'=>$status??'','q'=>$q??'','from'=>$from??'','to'=>$to??'','location'=>$location??'','shortlist'=>$shortlistSel??'','sort'=>$sort??'']); ?>
      <a class="btn btn-outline" href="<?= app_url('employer/candidates/export') ?>?<?= $qs ?>">Export CSV</a>
    </form>

  
  </div>

  <form method="get" action="<?= app_url('employer/candidates') ?>" class="card" style="padding:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" placeholder="Search name or email" value="<?= e($q ?? '') ?>" style="flex:1;min-width:220px;">
    <input type="text" name="location" placeholder="Location contains" value="<?= e($location ?? '') ?>" style="flex:1;min-width:180px;">
    <label>Date
      <span style="display:inline-flex;gap:6px;align-items:center;flex-wrap:wrap;">
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <input id="cand-from" type="date" name="from" value="<?= e($from ?? '') ?>" style="width:140px;min-width:120px;">
          <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('cand-from'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
        </span>
        <span class="muted" style="padding:0 2px;">to</span>
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <input id="cand-to" type="date" name="to" value="<?= e($to ?? '') ?>" style="width:140px;min-width:120px;">
          <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('cand-to'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
        </span>
      </span>
    </label>
    <label>Shortlist
      <select name="shortlist">
        <option value="" <?= ($shortlistSel ?? '')===''?'selected':'' ?>>Any</option>
        <option value="yes" <?= ($shortlistSel ?? '')==='yes'?'selected':'' ?>>Yes</option>
        <option value="no"  <?= ($shortlistSel ?? '')==='no'?'selected':'' ?>>No</option>
      </select>
    </label>
    <button class="btn btn-outline" type="submit">Apply</button>
    <a class="btn btn-outline" href="<?= app_url('employer/candidates') ?>?clear=1">Clear</a>
  </form>

  <div class="card" style="padding:10px;margin-top:10px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Under+Review">Active (<?= (int)($counts['Under Review'] ?? 0) ?>)</a>
    <a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Under+Review">Awaiting review (<?= (int)($counts['Under Review'] ?? 0) ?>)</a>
    <a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Interview">Reviewed / Interview (<?= (int)($counts['Interview'] ?? 0) ?>)</a>
    <a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Hired">Hired (<?= (int)($counts['Hired'] ?? 0) ?>)</a>
    <a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Rejected">Rejected (<?= (int)($counts['Rejected'] ?? 0) ?>)</a>
  </div>

  <?php if(!empty($rows)): ?>
    <form method="post" action="<?= app_url('employer/candidates/bulk-shortlist') ?>">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div style="display:flex;gap:8px;align-items:center;margin:10px 0;">
        <button class="btn btn-outline btn-sm" name="action" value="add" type="submit">Shortlist Yes (selected)</button>
        <button class="btn btn-outline btn-sm" name="action" value="remove" type="submit">Shortlist No (selected)</button>
      </div>
      <div class="card" style="padding:0; overflow:auto;">
      <table class="table-zebra" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="padding:10px;border-bottom:1px solid var(--border)"><input type="checkbox" onclick="document.querySelectorAll('.pick').forEach(cb=>cb.checked=this.checked)"></th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Name</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Job</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Applied</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Location</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): $lid = $r['learnerID']; $in = !empty($shortlist[$lid]); ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><input class="pick" type="checkbox" name="ids[]" value="<?= e($lid) ?>"></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><a href="<?= app_url('employer/talent/profile') ?>?learner=<?= e($lid) ?>"><?= e($r['learnerName']) ?></a></strong></td>
              <?php $st = (string)($r['appStatus'] ?? ''); $clr = ($st==='Hired'?'#22c55e':($st==='Interview'?'#60a5fa':($st==='Rejected'?'#ef4444':'#eab308'))); ?>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><span class="pill sm" style="background:<?= $clr ?>22;color:<?= $clr ?>;border:1px solid <?= $clr ?>33;"><?= e($st) ?></span></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['jobTitle']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['applicationDate']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['learnerEmail']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['__location'] ?? '') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <form method="post" action="<?= app_url('employer/talent/shortlist') ?>" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="learnerID" value="<?= e($lid) ?>">
                  <input type="hidden" name="action" value="<?= $in ? 'remove' : 'add' ?>">
                  <button type="submit" class="btn btn-outline btn-sm"><?= $in ? 'Yes' : 'No' ?></button>
                </form>
                <a class="btn btn-outline btn-sm" href="mailto:<?= e($r['learnerEmail']) ?>?subject=Application: <?= urlencode($r['jobTitle']) ?>">Message</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </form>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:10px;">
      <div class="muted">Showing <?= count($rows) ?> of <?= (int)($total ?? 0) ?> candidates</div>
      <form method="get" action="<?= app_url('employer/candidates') ?>" style="display:flex;gap:8px;align-items:center;">
        <?php // preserve filters ?>
        <input type="hidden" name="job" value="<?= e($job ?? '') ?>">
        <input type="hidden" name="status" value="<?= e($status ?? '') ?>">
        <input type="hidden" name="q" value="<?= e($q ?? '') ?>">
        <input type="hidden" name="location" value="<?= e($location ?? '') ?>">
        <input type="hidden" name="from" value="<?= e($from ?? '') ?>">
        <input type="hidden" name="to" value="<?= e($to ?? '') ?>">
        <input type="hidden" name="shortlist" value="<?= e($shortlistSel ?? '') ?>">
        <input type="hidden" name="sort" value="<?= e($sort ?? 'applied_newest') ?>">
        <?php $p = (int)($page ?? 1); $pages = (int)($pages ?? 1); ?>
        <button class="btn btn-outline btn-sm" name="p" value="<?= max(1,$p-1) ?>" <?= $p<=1?'disabled':'' ?>>Prev</button>
        <span class="muted">Page <?= $p ?> / <?= max(1,$pages) ?></span>
        <button class="btn btn-outline btn-sm" name="p" value="<?= min(max(1,$pages),$p+1) ?>" <?= $p>=$pages?'disabled':'' ?>>Next</button>
        <label class="muted">per
          <select name="per" onchange="this.form.submit()">
            <?php foreach([10,20,30,50] as $n): ?>
              <option value="<?= $n ?>" <?= ((int)($per ?? 20))===$n?'selected':'' ?>><?= $n ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </form>
    </div>
  <?php else: ?>
    <p class="muted" style="margin-top:12px;">No candidates match the current filters.</p>
  <?php endif; ?>
</section>
