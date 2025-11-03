<section class="container py-6">
  <h1 style="margin:0 0 12px;">Activity</h1>

  <div class="card" style="padding:12px; display:flex; gap:10px; align-items:center;">
    <a class="btn <?= ($tab==='saved'?'':'btn-outline') ?>" href="<?= app_url('activity') ?>?tab=saved">Saved</a>
    <a class="btn <?= ($tab==='applied'?'':'btn-outline') ?>" href="<?= app_url('activity') ?>?tab=applied">Applied</a>
  </div>

  <?php if($tab==='applied'): ?>
    <form method="get" action="<?= app_url('activity') ?>" class="card" style="padding:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-top:12px;">
      <input type="hidden" name="tab" value="applied">
      <label>Status
        <select name="status" onchange="this.form.submit()">
          <?php foreach(['all'=>'All','Under Review'=>'Under Review','Interview'=>'Interview','Hired'=>'Hired','Rejected'=>'Rejected'] as $val=>$label): ?>
            <option value="<?= e($val) ?>" <?= ($status??'all')===$val?'selected':'' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>From
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <input id="flt-from" type="date" name="from" value="<?= e($from ?? '') ?>">
          <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('flt-from'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
        </span>
      </label>
      <label>To
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <input id="flt-to" type="date" name="to" value="<?= e($to ?? '') ?>">
          <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('flt-to'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
        </span>
      </label>
      <button class="btn btn-outline" type="submit">Apply</button>
    </form>
    <div class="card" style="margin-top:12px;">
      <h3 style="margin:0 0 8px;">Applied jobs</h3>
      <?php if(!empty($applied)): ?>
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
          <?php foreach($applied as $r): ?>
            <li class="card" style="padding:12px;display:flex;justify-content:space-between;gap:10px;align-items:center;">
              <div style="min-width:0;">
                <div style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($r['jobTitle']) ?></div>
                <div class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($r['companyName'] ?? '') ?></div>
                <div class="muted" style="font-size:12px;">Applied on <?= e($r['applicationDate']) ?> · Status: <span class="pill sm"><?= e($r['appStatus']) ?></span></div>
              </div>
              <div>
                <a class="btn btn-outline btn-sm" href="<?= app_url('jobs') ?>?id=<?= e($r['jobID']) ?>#detail">Open</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No applications yet.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="card" style="margin-top:12px;">
      <h3 style="margin:0 0 8px;">Saved jobs</h3>
      <?php if(!empty($saved)): ?>
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
          <?php foreach($saved as $r): ?>
            <li class="card" style="padding:12px;display:flex;justify-content:space-between;gap:10px;align-items:center;">
              <div style="min-width:0;">
                <div style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($r['jobTitle']) ?></div>
                <div class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($r['companyName'] ?? '') ?></div>
                <div class="muted" style="font-size:12px;">Saved on <?= e($r['savedAt']) ?></div>
              </div>
              <div>
                <a class="btn btn-outline btn-sm" href="<?= app_url('jobs') ?>?id=<?= e($r['jobID']) ?>#detail">Open</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No saved jobs.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
