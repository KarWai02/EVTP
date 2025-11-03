<section class="container py-6">
  <?php Auth::requireRole(['trainer']); ?>
  <h1 style="margin:0 0 10px;">Learner Progress</h1>

  <form method="get" action="<?= app_url('trainer/progress') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    <label>Course
      <select name="course" onchange="this.form.submit()">
        <?php foreach(($courses ?? []) as $c): ?>
          <option value="<?= e($c['courseID']) ?>" <?= (($courseID ?? '')===$c['courseID'])?'selected':'' ?>><?= e($c['courseTitle']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Status
      <select name="status" onchange="this.form.submit()">
        <?php foreach([''=>'All','In Progress'=>'In Progress','Completed'=>'Completed'] as $k=>$v): ?>
          <option value="<?= e($k) ?>" <?= (($status ?? '')===$k)?'selected':'' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Sort
      <select name="sort" onchange="this.form.submit()">
        <?php foreach(['name'=>'Name','progress'=>'Progress','date'=>'Enroll date'] as $k=>$v): ?>
          <option value="<?= e($k) ?>" <?= (($sort ?? 'name')===$k)?'selected':'' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if(!empty($courseID)): ?>
      <form method="post" action="<?= app_url('trainer/progress/export') ?>" style="margin-left:auto;">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="course" value="<?= e($courseID) ?>">
        <button class="btn btn-outline" type="submit">Export CSV</button>
      </form>
    <?php endif; ?>
  </form>

  <?php if(!empty($courseID)): ?>
    <div class="grid" style="gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
      <div class="card" style="padding:16px;">
        <div class="muted">Enrolled</div>
        <div style="font-size:28px;font-weight:800;"><?= (int)($kpis['enrolled'] ?? 0) ?></div>
      </div>

    <?php if(!empty($trend)): ?>
      <div class="card" style="margin-top:12px;padding:12px;">
        <div class="muted" style="margin-bottom:6px;">Enrollments (last 14 days)</div>
        <div style="display:flex;gap:4px;align-items:flex-end;height:60px;">
          <?php $max=1; foreach($trend as $t){ if($t['c']>$max) $max=$t['c']; }
                foreach($trend as $t): $h = (int)round(($t['c']/$max)*56); ?>
            <div title="<?= e($t['d']) ?>: <?= (int)$t['c'] ?>" style="width:12px;background:var(--primary);height:<?= max(4,$h) ?>px;"></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
      <div class="card" style="padding:16px;">
        <div class="muted">Completed</div>
        <div style="font-size:28px;font-weight:800;"><?= (int)($kpis['completed'] ?? 0) ?></div>
      </div>
      <div class="card" style="padding:16px;">
        <div class="muted">Avg progress</div>
        <div style="font-size:28px;font-weight:800;"><?= number_format((float)($kpis['avg_progress'] ?? 0),1) ?>%</div>
      </div>
      <div class="card" style="padding:16px;">
        <div class="muted">In progress</div>
        <div style="font-size:28px;font-weight:800;"><?= (int)($kpis['in_progress'] ?? 0) ?></div>
      </div>
    </div>

    <div class="card" style="padding:0; overflow:auto; margin-top:14px;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Learner</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Progress</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Enroll date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach(($rows ?? []) as $r): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><a href="<?= app_url('trainer/progress/learner') ?>?course=<?= e($courseID) ?>&learner=<?= e($r['learnerID'] ?? '') ?>"><?= e($r['learnerName']) ?></a></strong></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['learnerEmail']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= number_format((float)$r['progress'],1) ?>%</td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['completionStatus'] ?: 'In Progress') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['enrollDate']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No courses available.</p>
  <?php endif; ?>
</section>
