<?php Auth::requireRole(['admin']); $pdo = DB::conn(); ?>
<section class="container py-6">
  <h1 style="margin:0 0 12px;">Admin Dashboard</h1>

  <?php
    $range = $_GET['range'] ?? '30d';
    $from = $_GET['from'] ?? ($range==='custom' ? date('Y-m-01') : date('Y-m-d', strtotime('-30 days')));
    $to = $_GET['to'] ?? date('Y-m-d');
    if($range!=='custom'){
      if($range==='7d'){ $from = date('Y-m-d', strtotime('-7 days')); }
      elseif($range==='30d'){ $from = date('Y-m-d', strtotime('-30 days')); }
      elseif($range==='90d'){ $from = date('Y-m-d', strtotime('-90 days')); }
    }
  ?>
  <form method="get" class="card" style="display:grid;gap:10px;grid-template-columns: auto auto auto 1fr auto;align-items:end;margin-bottom:12px;">
    <label>Range
      <select name="range" onchange="this.form.submit()">
        <?php foreach(['7d'=>'Last 7 days','30d'=>'Last 30 days','90d'=>'Last 90 days','custom'=>'Custom'] as $val=>$label): ?>
          <option value="<?= $val ?>" <?= ($range??'')===$val?'selected':'' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>From
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <input id="adm-from" type="date" name="from" value="<?= e($from ?? '') ?>">
        <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('adm-from'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
      </span>
    </label>
    <label>To
      <span style="display:inline-flex;align-items:center;gap:6px;">
        <input id="adm-to" type="date" name="to" value="<?= e($to ?? '') ?>">
        <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('adm-to'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
      </span>
    </label>
    <div></div>
    <button class="btn btn-outline" type="submit">Apply</button>
  </form>

  <?php
    $ins = ['enrolls'=>0,'active_learners'=>0,'completed'=>0,'avg_progress'=>0,'drop'=>0];
    try{
      $stmt = $pdo->prepare("SELECT COUNT(*) enrolls,
            COUNT(DISTINCT learnerID) learners,
            SUM(CASE WHEN completionStatus='Completed' THEN 1 ELSE 0 END) completed,
            AVG(COALESCE(progress,0)) avgp,
            SUM(CASE WHEN completionStatus='Dropped' THEN 1 ELSE 0 END) dropped
          FROM Enroll WHERE enrollDate BETWEEN ? AND ?");
      $stmt->execute([$from,$to]); $r=$stmt->fetch();
      if($r){ $ins['enrolls']=(int)$r['enrolls']; $ins['active_learners']=(int)$r['learners']; $ins['completed']=(int)$r['completed']; $ins['avg_progress']=round((float)$r['avgp'],1); $ins['drop']=(int)$r['dropped']; }
    }catch(Throwable $e){}

    $top=[]; $maxEnroll=1; try{
      $q = $pdo->prepare("SELECT c.courseID, c.courseTitle,
               COUNT(*) AS enrolls,
               SUM(CASE WHEN e.completionStatus='Completed' THEN 1 ELSE 0 END) AS completed
             FROM Enroll e JOIN Course c ON c.courseID=e.courseID
             WHERE e.enrollDate BETWEEN ? AND ?
             GROUP BY c.courseID, c.courseTitle
             ORDER BY enrolls DESC
             LIMIT 5");
      $q->execute([$from,$to]); $top = $q->fetchAll();
      foreach($top as $x){ if((int)$x['enrolls']>$maxEnroll) $maxEnroll=(int)$x['enrolls']; }
      $maxEnroll=max(1,$maxEnroll);
    }catch(Throwable $e){ $top=[]; }
  ?>

  <?php
    // Category Mix (top 8 categories in selected period)
    $catMix=[]; try{
      $q=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(c.category),''),'Uncategorized') cat,
               COUNT(*) total,
               SUM(CASE WHEN e.completionStatus='Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN e.completionStatus='In Progress' THEN 1 ELSE 0 END) AS inprog,
               SUM(CASE WHEN e.completionStatus='Dropped' THEN 1 ELSE 0 END) AS dropped
             FROM Enroll e JOIN Course c ON c.courseID=e.courseID
             WHERE e.enrollDate BETWEEN ? AND ?
             GROUP BY cat
             ORDER BY total DESC
             LIMIT 8");
      $q->execute([$from,$to]); $catMix=$q->fetchAll();
    }catch(Throwable $e){ $catMix=[]; }

    if(!function_exists('renderCategoryStacked')){ function renderCategoryStacked($rows){
      $w=720; $h=220; $padL=120; $padR=20; $padT=20; $padB=20; $barGap=10; $barH=16;
      $colors=['completed'=>'#22c55e','inprog'=>'#60a5fa','dropped'=>'#ef4444'];
      $n=count($rows); $h = max($h, $padT+$padB+$n*($barH+$barGap));
      $max=1; foreach($rows as $r){ if((int)$r['total']>$max) $max=(int)$r['total']; }
      $svg='<svg viewBox="0 0 '.$w.' '.$h.'" width="100%" height="'.$h.'">';
      $y=$padT; foreach($rows as $r){ $tot=max(1,(int)$r['total']); $x=$padL;
        $parts=['completed','inprog','dropped'];
        $svg.='<text x="'.($padL-8).'" y="'.($y+$barH-3).'" text-anchor="end" font-size="12" fill="#9ab">'.htmlspecialchars(mb_strimwidth($r['cat'],0,18,'…')).'</text>';
        foreach($parts as $p){ $val=(int)($r[$p] ?? 0); $bw = floor(($w-$padL-$padR)*($val/$max));
          if($bw>0){ $svg.='<rect x="'.$x.'" y="'.$y.'" width="'.$bw.'" height="'.$barH.'" fill="'.$colors[$p].'" opacity="0.8"><title>'.$p.': '.$val.' / '.$tot.'</title></rect>'; $x+=$bw; }
        }
        $svg.='<text x="'.($w-$padR).'" y="'.($y+$barH-3).'" text-anchor="end" font-size="12" fill="#cbd5e1">'.((int)$r['total']).'</text>';
        $y += $barH + $barGap;
      }
      $svg.='</svg>'; return $svg; } }

    // Course Attractiveness (scatter): top N courses by enrolls, X=enrolls, Y=completion %
    $scatter=[]; $maxEn=1; try{
      $q=$pdo->prepare("SELECT c.courseTitle, COUNT(*) enrolls,
               SUM(CASE WHEN e.completionStatus='Completed' THEN 1 ELSE 0 END) completed
             FROM Enroll e JOIN Course c ON c.courseID=e.courseID
             WHERE e.enrollDate BETWEEN ? AND ?
             GROUP BY c.courseID, c.courseTitle
             ORDER BY enrolls DESC
             LIMIT 12");
      $q->execute([$from,$to]); $scatter=$q->fetchAll();
      foreach($scatter as $r){ if((int)$r['enrolls']>$maxEn) $maxEn=(int)$r['enrolls']; }
      $maxEn=max(1,$maxEn);
    }catch(Throwable $e){ $scatter=[]; }

    if(!function_exists('renderScatterAttractiveness')){ function renderScatterAttractiveness($rows,$maxEn){
      $w=720; $h=260; $padL=40; $padR=20; $padT=20; $padB=30;
      $svg='<svg viewBox="0 0 '.$w.' '.$h.'" width="100%" height="'.$h.'">';
      // axes
      $svg.='<line x1="'.$padL.'" y1="'.($h-$padB).'" x2="'.($w-$padR).'" y2="'.($h-$padB).'" stroke="#2a3750" />';
      $svg.='<line x1="'.$padL.'" y1="'.$padT.'" x2="'.$padL.'" y2="'.($h-$padB).'" stroke="#2a3750" />';
      // ticks
      for($i=0;$i<=5;$i++){ $x=$padL+($w-$padL-$padR)*($i/5); $v=round($maxEn*($i/5));
        $svg.='<text x="'.$x.'" y="'.($h-$padB+16).'" text-anchor="middle" font-size="11" fill="#8ea3c0">'.$v.'</text>'; }
      for($j=0;$j<=5;$j++){ $y=($h-$padB)-($h-$padT-$padB)*($j/5); $v=round(100*($j/5));
        $svg.='<text x="'.($padL-6).'" y="'.($y+4).'" text-anchor="end" font-size="11" fill="#8ea3c0">'.$v.'%</text>'; }
      // points
      foreach($rows as $r){ $en=(int)$r['enrolls']; $cr=$en>0? (100*((int)$r['completed'])/$en):0;
        $x=$padL + ($w-$padL-$padR)*($en/$maxEn);
        $y=($h-$padB) - ($h-$padT-$padB)*($cr/100);
        $svg.='<circle cx="'.$x.'" cy="'.$y.'" r="6" fill="#64b3ff" stroke="#1e3a8a"><title>'.htmlspecialchars($r['courseTitle'])."\nEnrolls: ".$en."\nCompletion: ".round($cr)."%".'</title></circle>';
      }
      $svg.='</svg>'; return $svg; } }
  ?>

  <?php
    // Certificate status summary in range
    $cs = ['issued'=>0,'pending'=>0,'updated'=>0,'deleted'=>0,'total'=>0];
    try{
      $q=$pdo->prepare("SELECT
          SUM(CASE WHEN certStatus='issued'  THEN 1 ELSE 0 END) AS issued,
          SUM(CASE WHEN certStatus='pending' THEN 1 ELSE 0 END) AS pending,
          SUM(CASE WHEN certStatus='updated' THEN 1 ELSE 0 END) AS updated,
          SUM(CASE WHEN certStatus='deleted' THEN 1 ELSE 0 END) AS deleted,
          COUNT(*) AS total
        FROM certificate WHERE dateIssued BETWEEN ? AND ?");
      $q->execute([$from,$to]); $row=$q->fetch(); if($row) $cs=array_merge($cs,array_map('intval',$row));
    }catch(Throwable $e){}
  ?>
  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h2 style="margin:0;">Certificate Status (<?= e($from) ?> → <?= e($to) ?>)</h2>
      <a class="btn btn-outline btn-sm" href="<?= app_url('admin/certificates') ?>?from=<?= e($from) ?>&to=<?= e($to) ?>">Manage</a>
    </div>
    <div class="card" style="padding:0;">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;padding:10px;">
        <?php foreach([
          ['Issued','issued'],['Pending','pending'],['Updated','updated'],['Deleted','deleted'],['Total','total']
        ] as $p): list($label,$key)=$p; ?>
          <div class="card" style="padding:12px;">
            <div class="muted"><?= e($label) ?></div>
            <div style="font-size:24px;font-weight:800;"><?= (int)$cs[$key] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php
    // Learner progress details table (low progress first)
    $lp = [];
    try{
      $q=$pdo->prepare("SELECT l.learnerID, l.learnerName, c.courseTitle, e.enrollDate, COALESCE(e.progress,0) progress, e.completionStatus
                         FROM Enroll e JOIN Learners l ON l.learnerID=e.learnerID JOIN Course c ON c.courseID=e.courseID
                         WHERE e.enrollDate BETWEEN ? AND ?
                         ORDER BY COALESCE(e.progress,0) ASC, e.enrollDate DESC
                         LIMIT 15");
      $q->execute([$from,$to]); $lp=$q->fetchAll();
    }catch(Throwable $e){ $lp=[]; }
  ?>
  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">Learner Progress</h2>
    <?php if(!empty($lp)): ?>
      <div class="card" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Learner</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Enrolled</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Progress</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($lp as $r): $p=(int)($r['progress'] ?? 0); ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['learnerName']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['courseTitle']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['enrollDate']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><span class="pill sm"><?= e($r['completionStatus']) ?></span></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <?= $p ?>%
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted">No enrollments in the selected period.</p>
    <?php endif; ?>
  </div>

  <?php
    // Inactive learners: no enrollments in selected range
    $inactive=[];
    try{
      $q=$pdo->prepare("SELECT l.learnerID, l.learnerName, l.learnerEmail,
               MAX(e.enrollDate) AS last_enroll
             FROM Learners l
             LEFT JOIN Enroll e ON e.learnerID=l.learnerID
             GROUP BY l.learnerID, l.learnerName, l.learnerEmail
             HAVING MAX(CASE WHEN e.enrollDate BETWEEN ? AND ? THEN 1 ELSE 0 END) = 0
             ORDER BY COALESCE(last_enroll,'1900-01-01') ASC
             LIMIT 10");
      $q->execute([$from,$to]); $inactive=$q->fetchAll();
    }catch(Throwable $e){ $inactive=[]; }
  ?>
  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">Inactive Learners</h2>
    <?php if(!empty($inactive)): ?>
      <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
        <?php foreach($inactive as $l): ?>
          <li class="flex-between">
            <div><strong><?= e($l['learnerName']) ?></strong><br><small class="muted"><?= e($l['learnerEmail']) ?> · Last enroll: <?= e($l['last_enroll'] ?? '—') ?></small></div>
            <a class="btn btn-outline btn-sm" href="<?= app_url('admin/users/edit') ?>?role=learner&id=<?= e($l['learnerID']) ?>">Contact</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">Great! No inactive learners in this period.</p>
    <?php endif; ?>
  </div>

  <?php
    // Charts: last 8 weeks
    // Courses per week
    $courseWeeks = $pdo->query("SELECT DATE_FORMAT(createdDate,'%x-%v') wk, COUNT(*) c
                                 FROM Course
                                 WHERE createdDate >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
                                 GROUP BY wk ORDER BY wk")->fetchAll();
    // Enrollments per week
    $enrollWeeks = $pdo->query("SELECT DATE_FORMAT(enrollDate,'%x-%v') wk, COUNT(*) c
                                 FROM Enroll
                                 WHERE enrollDate >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
                                 GROUP BY wk ORDER BY wk")->fetchAll();

    // Normalize buckets to 8 weeks window using PHP (fill missing weeks with 0)
    if(!function_exists('buildWeeklySeries')){ function buildWeeklySeries($rows){
      $map=[]; foreach($rows as $r){ $map[$r['wk']] = (int)$r['c']; }
      $out=[]; // last 8 ISO weeks including this week
      for($i=7;$i>=0;$i--){
        $wk = date('o-W', strtotime('-'.$i.' week'));
        $out[] = ['wk'=>$wk, 'c'=>$map[$wk] ?? 0];
      }
      return $out;
    } }
    $seriesCourses = buildWeeklySeries($courseWeeks);
    $seriesEnrolls = buildWeeklySeries($enrollWeeks);

    if(!function_exists('renderBarChart')){ function renderBarChart($series, $title){
      $max = 0; foreach($series as $p){ if($p['c']>$max) $max=$p['c']; }
      $max = max(1,$max);
      $w = 560; $h = 160; $pad = 26; $barGap = 8; $n = count($series);
      $barW = floor(($w - $pad*2 - $barGap*($n-1)) / max(1,$n));
      $svg = '<svg viewBox="0 0 '.$w.' '.$h.'" width="100%" height="'.$h.'" role="img" aria-label="'.$title.'">';
      // axes
      $svg .= '<line x1="'.$pad.'" y1="'.($h-$pad).'" x2="'.($w-$pad).'" y2="'.($h-$pad).'" stroke="#2a3750" />';
      $svg .= '<line x1="'.$pad.'" y1="'.$pad.'" x2="'.$pad.'" y2="'.($h-$pad).'" stroke="#2a3750" />';
      // bars
      $x = $pad;
      foreach($series as $p){
        $val = (int)$p['c'];
        $bh = ($h - $pad*2) * ($val / $max);
        $y = ($h - $pad) - $bh;
        $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$barW.'" height="'.$bh.'" rx="6" fill="rgba(124,192,255,.6)" stroke="#64b3ff" />';
        // week label (last 2 digits of week)
        $wkLabel = substr($p['wk'], -2);
        $svg .= '<text x="'.($x+$barW/2).'" y="'.($h-$pad+14).'" text-anchor="middle" font-size="11" fill="#8ea3c0">'.htmlspecialchars($wkLabel).'</text>';
        // value label
        $svg .= '<text x="'.($x+$barW/2).'" y="'.($y-4).'" text-anchor="middle" font-size="11" fill="#a9c8ff">'.($val).'</text>';
        $x += $barW + $barGap;
      }
      $svg .= '</svg>';
      return $svg;
    } }
  ?>

  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h2 style="margin:0;">Top Courses (<?= e($from) ?> → <?= e($to) ?>)</h2>
      <a class="btn btn-outline btn-sm" href="<?= app_url('admin/reports') ?>?range=custom&from=<?= e($from) ?>&to=<?= e($to) ?>">Open Reports</a>
    </div>
    <?php if(!empty($top)): ?>
      <div class="card" style="padding:0;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Enrolls</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Completion rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($top as $r): $val=(int)$r['enrolls']; $pctEnroll = round(100*$val/$maxEnroll); $cr = $val>0? round(100*((int)$r['completed'])/$val) : 0; ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)">
                  <div style="display:flex;flex-direction:column;gap:6px;">
                    <div><?= e($r['courseTitle']) ?></div>
                    <div style="height:8px;background:rgba(100,179,255,.25);border-radius:6px;overflow:hidden;">
                      <div style="height:100%;width:<?= $pctEnroll ?>%;background:#64b3ff;"></div>
                    </div>
                  </div>
                </td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $val ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;"><?= $cr ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted">No enrollments in the selected period.</p>
    <?php endif; ?>
  </div>

  <?php
    $counts = [
      'admins'    => 0,
      'trainers'  => 0,
      'employers' => 0,
      'learners'  => 0,
      'courses'   => 0,
      'enrolls'   => 0,
    ];
    foreach ([
      ['SELECT COUNT(*) c FROM Admin','admins'],
      ['SELECT COUNT(*) c FROM Trainers','trainers'],
      ['SELECT COUNT(*) c FROM Employers','employers'],
      ['SELECT COUNT(*) c FROM Learners','learners'],
      ['SELECT COUNT(*) c FROM Course','courses'],
      ['SELECT COUNT(*) c FROM Enroll','enrolls'],
    ] as $q){ $s=$pdo->query($q[0]); $r=$s?$s->fetch():['c'=>0]; $counts[$q[1]]=(int)($r['c']??0); }

    $recentCourses = $pdo->query("SELECT courseID, courseTitle, createdDate FROM Course ORDER BY createdDate DESC LIMIT 5")->fetchAll();
    $recentLearners = $pdo->query("SELECT learnerID, learnerName, learnerEmail FROM Learners ORDER BY learnerID DESC LIMIT 5")->fetchAll();
  ?>

  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">Platform Snapshot</h2>
    <div style="display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:12px;">
      <div class="card" style="padding:16px;"><div class="muted">Learners</div><div style="font-size:28px;font-weight:800;"><?= $counts['learners'] ?></div></div>
      <div class="card" style="padding:16px;"><div class="muted">Trainers</div><div style="font-size:28px;font-weight:800;"><?= $counts['trainers'] ?></div></div>
      <div class="card" style="padding:16px;"><div class="muted">Employers</div><div style="font-size:28px;font-weight:800;"><?= $counts['employers'] ?></div></div>
      <div class="card" style="padding:16px;"><div class="muted">Admins</div><div style="font-size:28px;font-weight:800;"><?= $counts['admins'] ?></div></div>
      <div class="card" style="padding:16px;"><div class="muted">Courses</div><div style="font-size:28px;font-weight:800;"><?= $counts['courses'] ?></div></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1.2fr .8fr;gap:16px;">
    <div class="card" style="padding:18px;">
      <div class="flex-between" style="margin-bottom:8px;">
        <h2 style="margin:0;">Quick actions</h2>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a class="btn" href="<?= app_url('admin/users') ?>?role=learner">Manage Learners</a>
        <a class="btn" href="<?= app_url('admin/users') ?>?role=trainer">Manage Trainers</a>
        <a class="btn" href="<?= app_url('admin/users') ?>?role=employer">Manage Employers</a>
        <a class="btn" href="<?= app_url('admin/users') ?>?role=admin">Manage Admins</a>
        <a class="btn" href="<?= app_url('admin/courses') ?>">Manage Courses</a>
        <a class="btn" href="<?= app_url('admin/certificates') ?>">Manage Certificates</a>
        <a class="btn btn-outline" href="<?= app_url('courses') ?>">Browse Courses</a>
        <a class="btn secondary" href="<?= app_url('admin/reports') ?>">View Reports</a>
      </div>
    </div>

    <div class="card" style="padding:18px;">
      <h2 style="margin:0 0 8px;">Recent courses</h2>
      <?php if(!empty($recentCourses)): ?>
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
          <?php foreach($recentCourses as $c): ?>
            <li class="flex-between">
              <div><strong><?= e($c['courseTitle']) ?></strong><br><small class="muted">Created: <?= e($c['createdDate']) ?></small></div>
              <a class="btn btn-outline btn-sm" href="<?= app_url('courses/view') ?>?id=<?= e($c['courseID']) ?>">View</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No courses yet.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">New learners</h2>
    <?php if(!empty($recentLearners)): ?>
      <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
        <?php foreach($recentLearners as $l): ?>
          <li class="flex-between">
            <div><strong><?= e($l['learnerName']) ?></strong><br><small class="muted"><?= e($l['learnerEmail']) ?></small></div>
            <a class="btn btn-outline btn-sm" href="<?= app_url('admin/users/edit') ?>?role=learner&id=<?= e($l['learnerID']) ?>">Edit</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">No learners yet.</p>
    <?php endif; ?>
  </div>

  <?php
    // Charts: last 8 weeks
    // Courses per week
    $courseWeeks = $pdo->query("SELECT DATE_FORMAT(createdDate,'%x-%v') wk, COUNT(*) c
                                 FROM Course
                                 WHERE createdDate >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
                                 GROUP BY wk ORDER BY wk")->fetchAll();
    // Enrollments per week
    $enrollWeeks = $pdo->query("SELECT DATE_FORMAT(enrollDate,'%x-%v') wk, COUNT(*) c
                                 FROM Enroll
                                 WHERE enrollDate >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
                                 GROUP BY wk ORDER BY wk")->fetchAll();

    // Normalize buckets to 8 weeks window using PHP (fill missing weeks with 0)
    function buildWeeklySeries($rows){
      $map=[]; foreach($rows as $r){ $map[$r['wk']] = (int)$r['c']; }
      $out=[]; // last 8 ISO weeks including this week
      for($i=7;$i>=0;$i--){
        $wk = date('o-W', strtotime('-'.$i.' week'));
        $out[] = ['wk'=>$wk, 'c'=>$map[$wk] ?? 0];
      }
      return $out;
    }
    $seriesCourses = buildWeeklySeries($courseWeeks);
    $seriesEnrolls = buildWeeklySeries($enrollWeeks);

    function renderBarChart($series, $title){
      $max = 0; foreach($series as $p){ if($p['c']>$max) $max=$p['c']; }
      $max = max(1,$max);
      $w = 560; $h = 160; $pad = 26; $barGap = 8; $n = count($series);
      $barW = floor(($w - $pad*2 - $barGap*($n-1)) / max(1,$n));
      $svg = '<svg viewBox="0 0 '.$w.' '.$h.'" width="100%" height="'.$h.'" role="img" aria-label="'.$title.'">';
      // axes
      $svg .= '<line x1="'.$pad.'" y1="'.($h-$pad).'" x2="'.($w-$pad).'" y2="'.($h-$pad).'" stroke="#2a3750" />';
      $svg .= '<line x1="'.$pad.'" y1="'.$pad.'" x2="'.$pad.'" y2="'.($h-$pad).'" stroke="#2a3750" />';
      // bars
      $x = $pad;
      foreach($series as $p){
        $val = (int)$p['c'];
        $bh = ($h - $pad*2) * ($val / $max);
        $y = ($h - $pad) - $bh;
        $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$barW.'" height="'.$bh.'" rx="6" fill="rgba(124,192,255,.6)" stroke="#64b3ff" />';
        // week label (last 2 digits of week)
        $wkLabel = substr($p['wk'], -2);
        $svg .= '<text x="'.($x+$barW/2).'" y="'.($h-$pad+14).'" text-anchor="middle" font-size="11" fill="#8ea3c0">'.htmlspecialchars($wkLabel).'</text>';
        // value label
        $svg .= '<text x="'.($x+$barW/2).'" y="'.($y-4).'" text-anchor="middle" font-size="11" fill="#a9c8ff">'.($val).'</text>';
        $x += $barW + $barGap;
      }
      $svg .= '</svg>';
      return $svg;
    }
  ?>

  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">Activity (last 8 weeks)</h2>
    <div style="display:grid;grid-template-columns:1fr;gap:16px;">
      <div>
        <div class="muted" style="margin-bottom:4px;">New courses per week</div>
        <?= renderBarChart($seriesCourses, 'New courses per week') ?>
      </div>
      <div>
        <div class="muted" style="margin-bottom:4px;">Enrollments per week</div>
        <?= renderBarChart($seriesEnrolls, 'Enrollments per week') ?>
      </div>
    </div>
  </div>

  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">Category Mix (<?= e($from) ?> → <?= e($to) ?>)</h2>
    <?php if(!empty($catMix)): ?>
      <?= renderCategoryStacked($catMix) ?>
    <?php else: ?>
      <p class="muted">No enrollments in the selected period.</p>
    <?php endif; ?>
  </div>

  <div class="card" style="padding:18px; margin-top:14px;">
    <h2 style="margin:0 0 8px;">Course Attractiveness (Enrolls vs Completion)</h2>
    <?php if(!empty($scatter)): ?>
      <?= renderScatterAttractiveness($scatter, $maxEn) ?>
    <?php else: ?>
      <p class="muted">No data for selected period.</p>
    <?php endif; ?>
  </div>
</section>