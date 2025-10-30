<?php Auth::requireRole(['admin']); $pdo = DB::conn(); ?>
<section class="container py-6">
  <h1 style="margin:0 0 12px;">Admin Dashboard</h1>

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

  <div class="grid cards" style="display:grid;grid-template-columns:repeat(3,minmax(200px,1fr));gap:12px;margin-bottom:14px;">
    <div class="card" style="padding:16px;">
      <div class="muted">Learners</div>
      <div style="font-size:28px;font-weight:800;"><?= $counts['learners'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Trainers</div>
      <div style="font-size:28px;font-weight:800;"><?= $counts['trainers'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Employers</div>
      <div style="font-size:28px;font-weight:800;"><?= $counts['employers'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Admins</div>
      <div style="font-size:28px;font-weight:800;"><?= $counts['admins'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Courses</div>
      <div style="font-size:28px;font-weight:800;"><?= $counts['courses'] ?></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Enrollments</div>
      <div style="font-size:28px;font-weight:800;"><?= $counts['enrolls'] ?></div>
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
</section>