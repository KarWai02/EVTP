<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <h1 style="margin:0 0 10px;">Analytics & Reports</h1>

  <?php $range = $range ?? '30d'; $from=$from??date('Y-m-d'); $to=$to??date('Y-m-d'); $courseId=$courseId??''; $courses=$courses??[]; ?>
  <form method="get" action="<?= app_url('admin/reports') ?>" class="card" style="display:grid;gap:10px;grid-template-columns: auto minmax(220px,1fr) auto auto 1fr auto;align-items:end;">
    <label>Range
      <select name="range" onchange="this.form.submit()">
        <?php foreach(['7d'=>'Last 7 days','30d'=>'Last 30 days','90d'=>'Last 90 days','custom'=>'Custom'] as $val=>$label): ?>
          <option value="<?= $val ?>" <?= $range===$val?'selected':'' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Course
      <select name="course">
        <option value="">All courses</option>
        <?php foreach(($courses ?? []) as $c): ?>
          <option value="<?= e($c['courseID']) ?>" <?= ($courseId===$c['courseID'])?'selected':'' ?>><?= e($c['courseTitle']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>From
      <input type="date" name="from" value="<?= e($from) ?>">
    </label>
    <label>To
      <input type="date" name="to" value="<?= e($to) ?>">
    </label>
    <div></div>
    <button class="btn btn-outline" type="submit">Apply</button>
  </form>

  <div class="grid cards" style="display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin:12px 0;">
    <div class="card" style="padding:16px;">
      <div class="muted">Enrollments</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)$kpis['enrolls'] ?></div>
    </div>

  <?php if(!empty($performance)): ?>
  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h2 style="margin:0;">Course Performance</h2>
      <form method="post" action="<?= app_url('admin/reports/export-performance') ?>">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="range" value="<?= e($range) ?>">
        <input type="hidden" name="from" value="<?= e($from) ?>">
        <input type="hidden" name="to" value="<?= e($to) ?>">
        <input type="hidden" name="course" value="<?= e($courseId) ?>">
        <button class="btn btn-outline" type="submit">Export Detailed CSV</button>
      </form>
    </div>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Enrollments</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Completed</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Completion rate</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Avg progress</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">In progress</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Dropped</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($performance as $r): ?>
            <?php $en=(int)$r['enrollments']; $comp=(int)$r['completed']; $rate=$en>0?round(100.0*$comp/$en,1):0.0; $avgp=round((float)$r['avg_progress'],1); ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['courseTitle']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $en ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $comp ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $rate ?>%</td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $avgp ?>%</td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= (int)$r['in_progress'] ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= (int)$r['dropped'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
    <div class="card" style="padding:16px;">
      <div class="muted">New learners</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)$kpis['learners'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Active courses</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)$kpis['active_courses'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Completion rate</div>
      <div style="font-size:28px;font-weight:800;"><?= number_format((float)$kpis['completion_rate'],1) ?>%</div>
    </div>
  </div>

  <?php
    // helper for line chart
    function renderLine($series, $title){
      $max=0; foreach($series as $v){ if($v>$max) $max=$v; }
      $max=max(1,$max);
      $w=720; $h=180; $pad=28; $n=count($series); if($n<2){ $n=2; }
      $step=($w-$pad*2)/($n-1);
      $points=[];
      for($i=0;$i<count($series);$i++){
        $x=$pad+$i*$step; $y=($h-$pad) - ($h-$pad*2)*($series[$i]/$max);
        $points[]=$x.','.$y;
      }
      $svg='<svg viewBox="0 0 '.$w.' '.$h.'" width="100%" height="'.$h.'" role="img" aria-label="'.$title.'">';
      $svg.='<polyline fill="none" stroke="#64b3ff" stroke-width="2" points="'.implode(' ',$points).'" />';
      $svg.='</svg>';
      return $svg;
    }
  ?>

  <div class="card" style="padding:18px;">
    <h2 style="margin:0 0 8px;">Trends</h2>
    <div style="display:grid;grid-template-columns:1fr;gap:18px;">
      <div>
        <div class="muted" style="margin-bottom:4px;">Daily enrollments</div>
        <?= renderLine($tsEnroll ?? [], 'Daily enrollments') ?>
      </div>
      <div>
        <div class="muted" style="margin-bottom:4px;">Daily new learners</div>
        <?= renderLine($tsLearners ?? [], 'Daily new learners') ?>
      </div>
    </div>
  </div>

  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h2 style="margin:0;">Top courses</h2>
      <form method="post" action="<?= app_url('admin/reports/export') ?>">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="range" value="<?= e($range) ?>">
        <input type="hidden" name="from" value="<?= e($from) ?>">
        <input type="hidden" name="to" value="<?= e($to) ?>">
        <input type="hidden" name="course" value="<?= e($courseId) ?>">
        <button class="btn btn-outline" type="submit">Export CSV</button>
      </form>
    </div>
    <?php if(!empty($topCourses)): ?>
      <div class="card" style="padding:0; overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Enrollments</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($topCourses as $r): ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['courseTitle']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= (int)$r['cnt'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted">No enrollments in the selected range.</p>
    <?php endif; ?>
  </div>
</section>
