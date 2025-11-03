<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <div class="flex-between" style="align-items:center;margin:0 0 10px;">
    <h1 style="margin:0;">Reports</h1>
    <div style="display:flex;gap:8px;">
      <a class="btn btn-outline" href="#" onclick="window.print();return false;">Print</a>
    </div>
  </div>

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
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <input id="rpt-from" type="date" name="from" value="<?= e($from) ?>">
        <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('rpt-from'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
      </span>
    </label>
    <label>To
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <input id="rpt-to" type="date" name="to" value="<?= e($to) ?>">
        <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('rpt-to'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
      </span>
    </label>
    <div></div>
    <button class="btn btn-outline" type="submit">Apply</button>
  </form>

  <div class="grid cards" style="display:grid;grid-template-columns:repeat(5,minmax(160px,1fr));gap:12px;margin:12px 0;">
    <div class="card" style="padding:16px;">
      <div class="muted">Enrollments</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)$kpis['enrolls'] ?></div>
    </div>
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
    <div class="card" style="padding:16px;">
      <div class="muted">Dropped</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)$kpis['dropped'] ?></div>
    </div>
  </div>

  <?php $viewPerf = $_GET['viewPerf'] ?? 'table'; if(!in_array($viewPerf,['cards','table'],true)) $viewPerf='table'; ?>
  <?php $qperf = trim($_GET['qperf'] ?? ''); $sortPerf = $_GET['sortPerf'] ?? 'enroll_desc'; ?>
  <?php if(!empty($performance)): ?>
  <?php
    // filter and sort locally
    $perfData = array_values(array_filter($performance, function($r) use ($qperf){
      if($qperf==='') return true;
      return stripos($r['courseTitle'] ?? '', $qperf) !== false;
    }));
    usort($perfData, function($a,$b) use($sortPerf){
      $enA=(int)$a['enrollments']; $enB=(int)$b['enrollments'];
      $rateA = $enA>0 ? (100.0*(int)$a['completed']/$enA) : 0.0;
      $rateB = $enB>0 ? (100.0*(int)$b['completed']/$enB) : 0.0;
      $avgA = (float)$a['avg_progress']; $avgB=(float)$b['avg_progress'];
      switch($sortPerf){
        case 'enroll_asc': return $enA <=> $enB;
        case 'rate_desc': return $rateB <=> $rateA;
        case 'rate_asc':  return $rateA <=> $rateB;
        case 'avg_desc':  return $avgB <=> $avgA;
        case 'avg_asc':   return $avgA <=> $avgB;
        default: /* enroll_desc */ return $enB <=> $enA;
      }
    });
  ?>
  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;gap:8px;align-items:center;">
      <h2 style="margin:0;">Course Performance</h2>
      <div style="display:flex;gap:8px;align-items:center;">
        <form method="get" action="<?= app_url('admin/reports') ?>" style="display:flex;gap:8px;align-items:center;">
          <?php foreach(['range','from','to','course'] as $k): ?><input type="hidden" name="<?= $k ?>" value="<?= e($_GET[$k] ?? '') ?>"><?php endforeach; ?>
          <input type="text" name="qperf" placeholder="Search course" value="<?= e($qperf) ?>" style="min-width:220px;">
          <select name="sortPerf">
            <option value="enroll_desc" <?= $sortPerf==='enroll_desc'?'selected':'' ?>>Enrollments ↓</option>
            <option value="enroll_asc" <?= $sortPerf==='enroll_asc'?'selected':'' ?>>Enrollments ↑</option>
            <option value="rate_desc" <?= $sortPerf==='rate_desc'?'selected':'' ?>>Completion rate ↓</option>
            <option value="rate_asc" <?= $sortPerf==='rate_asc'?'selected':'' ?>>Completion rate ↑</option>
            <option value="avg_desc" <?= $sortPerf==='avg_desc'?'selected':'' ?>>Avg progress ↓</option>
            <option value="avg_asc" <?= $sortPerf==='avg_asc'?'selected':'' ?>>Avg progress ↑</option>
          </select>
          <input type="hidden" name="viewPerf" value="<?= e($viewPerf) ?>">
          <button class="btn btn-outline btn-sm" type="submit">Apply</button>
        </form>
        <?php $qs=$_GET; $qs['viewPerf']='cards'; $cardsUrl=app_url('admin/reports').'?'.http_build_query($qs); $qs['viewPerf']='table'; $tableUrl=app_url('admin/reports').'?'.http_build_query($qs); ?>
        <a class="btn btn-sm <?= $viewPerf==='cards'?'':'btn-outline' ?>" href="<?= e($cardsUrl) ?>">Cards</a>
        <a class="btn btn-sm <?= $viewPerf==='table'?'':'btn-outline' ?>" href="<?= e($tableUrl) ?>">Table</a>
        <form method="post" action="<?= app_url('admin/reports/export-performance') ?>" style="display:inline;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="range" value="<?= e($range) ?>">
          <input type="hidden" name="from" value="<?= e($from) ?>">
          <input type="hidden" name="to" value="<?= e($to) ?>">
          <input type="hidden" name="course" value="<?= e($courseId) ?>">
          <button class="btn btn-outline btn-sm" type="submit">Export CSV</button>
        </form>
      </div>
    </div>
    <?php if($viewPerf==='cards'): ?>
      <div class="card" style="padding:0;">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;padding:10px;">
          <?php foreach($perfData as $r): ?>
            <?php $en=(int)$r['enrollments']; $comp=(int)$r['completed']; $rate=$en>0?round(100.0*$comp/$en,1):0.0; $avgp=round((float)$r['avg_progress'],1); ?>
            <div class="card" style="padding:12px;display:flex;flex-direction:column;gap:8px;">
              <div><strong><?= e($r['courseTitle']) ?></strong></div>
              <div style="display:flex;justify-content:space-between;"><span class="muted">Enrollments</span><strong><?= $en ?></strong></div>
              <div style="display:flex;justify-content:space-between;"><span class="muted">Completed</span><strong><?= $comp ?></strong></div>
              <div style="display:flex;justify-content:space-between;"><span class="muted">Completion rate</span><strong><?= $rate ?>%</strong></div>
              <div style="display:flex;justify-content:space-between;"><span class="muted">Avg progress</span><strong><?= $avgp ?>%</strong></div>
              <div style="display:flex;justify-content:space-between;"><span class="muted">In progress</span><strong><?= (int)$r['in_progress'] ?></strong></div>
              <div style="display:flex;justify-content:space-between;"><span class="muted">Dropped</span><strong><?= (int)$r['dropped'] ?></strong></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;table-layout:fixed;">
          <colgroup>
            <col style="width:36%"><col style="width:12%"><col style="width:12%"><col style="width:16%"><col style="width:16%"><col style="width:12%"><col style="width:12%">
          </colgroup>
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Enroll</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Done</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Completion</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Avg progress</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">In prog</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Dropped</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($perfData as $r): ?>
              <?php $en=(int)$r['enrollments']; $comp=(int)$r['completed']; $rate=$en>0?round(100.0*$comp/$en,1):0.0; $avgp=round((float)$r['avg_progress'],1); ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['courseTitle']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= $en ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= $comp ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= $rate ?>%</td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= $avgp ?>%</td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= (int)$r['in_progress'] ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= (int)$r['dropped'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
  <?php $___perfRendered = true; endif; ?>

  

  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h2 style="margin:0;">Certificate Summary</h2>
      <form method="post" action="<?= app_url('admin/reports/export-certificates') ?>">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="range" value="<?= e($range) ?>">
        <input type="hidden" name="from" value="<?= e($from) ?>">
        <input type="hidden" name="to" value="<?= e($to) ?>">
        <input type="hidden" name="course" value="<?= e($courseId) ?>">
        <button class="btn btn-outline" type="submit">Export CSV</button>
      </form>
    </div>
    <?php $cs = $certSummary ?? ['issued'=>0,'pending'=>0,'updated'=>0,'deleted'=>0,'total'=>0]; ?>
    <?php $base=app_url('admin/certificates'); $qs=['from'=>$from,'to'=>$to]; ?>
    <div class="card" style="padding:0;">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;padding:10px;">
        <?php foreach([
          ['Issued','issued'],['Pending','pending'],['Updated','updated'],['Deleted','deleted'],['Total','total']
        ] as $pair): list($label,$key)=$pair; $cnt=(int)($cs[$key] ?? 0); $href = $key!=='total' ? ($base.'?'.http_build_query(array_merge($qs,['status'=>$key]))) : ($base.'?'.http_build_query($qs)); ?>
          <a class="card" href="<?= e($href) ?>" style="padding:12px;text-decoration:none;color:inherit;">
            <div class="muted"><?= e($label) ?></div>
            <div style="font-size:24px;font-weight:800;"><?= $cnt ?></div>
            <div class="muted" style="font-size:12px;">View details →</div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- KPIs moved above Course Performance; redundant tiles removed -->

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
      <h2 style="margin:0;">Top 5 Most Popular Courses</h2>
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
      <div class="card" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Enrollments</th>
            </tr>
          </thead>
          <tbody>
            <?php $maxTop=0; foreach($topCourses as $x){ $v=(int)$x['cnt']; if($v>$maxTop) $maxTop=$v; } $maxTop=max(1,$maxTop); ?>
            <?php foreach($topCourses as $r): $val=(int)$r['cnt']; $pct = round(100*$val/$maxTop); ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)">
                  <div style="display:flex;flex-direction:column;gap:6px;">
                    <div><?= e($r['courseTitle']) ?></div>
                    <div style="height:8px;background:rgba(100,179,255,.25);border-radius:6px;overflow:hidden;">
                      <div style="height:100%;width:<?= $pct ?>%;background:#64b3ff;"></div>
                    </div>
                  </div>
                </td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $val ?></td>
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
