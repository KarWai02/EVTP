<section class="mt-6">
  <div class="card" style="padding:16px; margin-bottom:12px;">
    <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
      <div style="display:flex;gap:12px;align-items:center;">
        <div style="width:44px;height:44px;border-radius:999px;background:var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;">
          <?php $nm = trim($user['name'] ?? ''); $ini = strtoupper(mb_substr($nm,0,1)); ?>
          <?= e($ini ?: 'L') ?>
        </div>
        <div>
          <h2 style="margin:0;">Welcome, <?=e($user['name'] ?? 'Learner')?> 👋</h2>
          <small class="muted">Good to see you back</small>
        </div>
      </div>
      <div style="display:flex;gap:8px;">
        <a class="btn btn-outline btn-sm" href="<?=app_url('courses')?>">Browse Courses</a>
        <a class="btn btn-outline btn-sm" href="<?=app_url('jobs')?>">Find Jobs</a>
        <a class="btn btn-outline btn-sm" href="<?=app_url('profile')?>">Profile</a>
      </div>
    </div>
  </div>
  <div class="grid">
    <div class="card">
      <div class="flex-between" style="margin-bottom:6px;">
        <h3 style="margin:0;">My Enrollments</h3>
        <a class="btn btn-outline btn-sm" href="<?= app_url('enrollments') ?>">View all</a>
      </div>
      <?php
        $enrs = [];
        try{
          $pdo = DB::conn(); $lid = Auth::user()['id'];
          $st = $pdo->prepare("SELECT e.enrollDate, e.progress, e.completionStatus,
                                       c.courseID, c.courseTitle, c.category
                                FROM Enroll e JOIN Course c ON c.courseID=e.courseID
                                WHERE e.learnerID=?
                                ORDER BY e.enrollDate DESC
                                LIMIT 5");
          $st->execute([$lid]); $enrs = $st->fetchAll();
        }catch(Throwable $e){ $enrs=[]; }
      ?>
      <?php if(!empty($enrs)): ?>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
          <?php foreach($enrs as $it): $prog = (int)($it['progress'] ?? 0); ?>
            <li class="card" style="padding:10px;display:flex;justify-content:space-between;gap:10px;align-items:center;">
              <div style="flex:1;min-width:200px;">
                <div style="font-weight:700;"><?= e($it['courseTitle']) ?></div>
                <small class="muted">Enrolled: <?= e($it['enrollDate']) ?> • <?= e($it['completionStatus']) ?></small>
                <div style="height:6px;background:var(--border);border-radius:4px;margin-top:6px;">
                  <div style="width:<?= max(0,min(100,$prog)) ?>%;background:#60a5fa;height:100%;border-radius:4px;"></div>
                </div>
              </div>
              <a class="btn btn-outline btn-sm" href="<?= app_url('courses/view') ?>?id=<?= e($it['courseID']) ?>&start=1">Resume</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No enrollments yet. <a href="<?= app_url('courses') ?>">Browse courses</a>.</p>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3>Quick Stats</h3>
      <?php
        $qsIn=0; $qsDone=0;
        try{
          $pdo = DB::conn(); $lid = Auth::user()['id'];
          $stc = $pdo->prepare("SELECT 
            SUM(CASE WHEN completionStatus='In Progress' THEN 1 ELSE 0 END) AS inprog,
            SUM(CASE WHEN completionStatus='Completed'   THEN 1 ELSE 0 END) AS done
          FROM Enroll WHERE learnerID=?");
          $stc->execute([$lid]); $row=$stc->fetch();
          if($row){ $qsIn=(int)$row['inprog']; $qsDone=(int)$row['done']; }
        }catch(Throwable $e){ }
      ?>
      <p>Courses in progress: <?= $qsIn ?></p>
      <p>Completed: <?= $qsDone ?></p>
    </div>
    <div class="card">
      <h3 style="margin:0 0 8px;">Continue learning</h3>
      <?php
        $inprog = [];
        try{
          $pdo = DB::conn(); $lid = Auth::user()['id'];
          $q = $pdo->prepare("SELECT e.enrollDate, e.progress, e.completionStatus, c.courseID, c.courseTitle
                               FROM Enroll e JOIN Course c ON c.courseID=e.courseID
                               WHERE e.learnerID=? AND (e.completionStatus='In Progress' OR COALESCE(e.progress,0)<100)
                               ORDER BY e.enrollDate DESC LIMIT 5");
          $q->execute([$lid]); $inprog = $q->fetchAll();
        }catch(Throwable $e){ $inprog=[]; }
      ?>
      <?php if(!empty($inprog)): ?>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
          <?php foreach($inprog as $c): ?>
            <li style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
              <div>
                <div style="font-weight:700;"><?= e($c['courseTitle']) ?></div>
                <small class="muted">Enrolled: <?= e($c['enrollDate']) ?> • <?= e($c['completionStatus']) ?><?= isset($c['progress'])? ' • '.(int)$c['progress'].'%':'' ?></small>
              </div>
              <a class="btn btn-outline btn-sm" href="<?= app_url('courses/view') ?>?id=<?= e($c['courseID']) ?>&start=1">Resume</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No active courses. <a href="<?= app_url('courses') ?>">Browse courses</a>.</p>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3 style="margin:0 0 8px;">Recommended jobs</h3>
      <?php
        $reco = []; $appliedIds=[];
        try{
          $pdo = DB::conn(); $lid = Auth::user()['id'];
          // hidden list
          $hidden = [];
          $hp = dirname(__DIR__,3).'/storage/hidden_jobs_'.$lid.'.json';
          if(file_exists($hp)){
            $h = json_decode(@file_get_contents($hp), true); if(is_array($h)) $hidden = $h;
          }
          $k = $pdo->prepare("SELECT DISTINCT TRIM(c.category) cat, c.courseTitle FROM Enroll e JOIN Course c ON c.courseID=e.courseID WHERE e.learnerID=? ORDER BY e.enrollDate DESC LIMIT 10");
          $k->execute([$lid]); $rowsK = $k->fetchAll();
          $terms=[]; foreach($rowsK as $rk){ if(!empty($rk['cat'])) $terms[]=$rk['cat']; $terms[]=$rk['courseTitle']; }
          $terms = array_values(array_filter(array_unique(array_map(function($s){ return trim(mb_substr($s,0,40)); }, $terms))));
          $ai = $pdo->prepare("SELECT jobID FROM Application WHERE learnerID=?"); $ai->execute([$lid]); $appliedIds = array_column($ai->fetchAll(),'jobID');
          if(!empty($terms)){
            $sql = "SELECT j.jobID, j.jobTitle, e.companyName FROM JobPosting j LEFT JOIN Employers e ON e.employerID=j.employerID WHERE (j.closedDate IS NULL OR j.closedDate='')";
            $params=[]; $first=true; foreach(array_slice($terms,0,5) as $t){ $sql .= $first?" AND (":" OR "; $first=false; $sql.="j.jobTitle LIKE ?"; $params[]='%'.$t.'%'; }
            if(!$first) $sql .= ")";
            $sql .= " ORDER BY j.postDate DESC LIMIT 6";
            $st = $pdo->prepare($sql); $st->execute($params); $reco = array_values(array_filter($st->fetchAll(), function($r) use($appliedIds,$hidden){ return !in_array($r['jobID'], $appliedIds, true) && empty($hidden[$r['jobID']]); }));
          }
        }catch(Throwable $e){ $reco=[]; }
      ?>
      <?php if(!empty($reco)): ?>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
          <?php foreach($reco as $j): ?>
            <li class="card" style="padding:10px;display:flex;justify-content:space-between;gap:10px;align-items:center;">
              <div>
                <div style="font-weight:700;"><?= e($j['jobTitle']) ?></div>
                <small class="muted"><?= e($j['companyName'] ?? '') ?></small>
              </div>
              <div style="display:flex;gap:8px;">
                <a class="btn btn-outline btn-sm" href="<?= app_url('jobs') ?>?id=<?= e($j['jobID']) ?>#detail">View</a>
                <form method="post" action="<?= app_url('dashboard/hide-recommendation') ?>" onsubmit="return confirm('Hide this recommendation?');">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="job" value="<?= e($j['jobID']) ?>">
                  <button type="submit" class="btn btn-outline btn-sm">Hide</button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">No recommendations yet. Explore more courses to get personalized matches.</p>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3 style="margin:0 0 8px;">Recent Applications</h3>
      <?php
        $apps = [];
        try{
          $pdo = DB::conn(); $lid = Auth::user()['id'];
          $st = $pdo->prepare("SELECT a.appID, a.appStatus, a.applicationDate, j.jobID, j.jobTitle FROM Application a JOIN JobPosting j ON j.jobID=a.jobID WHERE a.learnerID=? ORDER BY a.applicationDate DESC LIMIT 10");
          $st->execute([$lid]); $apps = $st->fetchAll();
        }catch(Throwable $e){ $apps = []; }
      ?>
      <?php if(!empty($apps)): ?>
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Job</th>
              <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Status</th>
              <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Applied</th>
              <th style="text-align:right; padding:8px; border-bottom:1px solid var(--border);">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($apps as $i=>$r): $st = (string)($r['appStatus'] ?? ''); $clr = ($st==='Hired'?'#22c55e':($st==='Interview'?'#60a5fa':($st==='Rejected'?'#ef4444':'#eab308'))); ?>
              <tr style="background: <?= ($i%2)?'transparent':'rgba(255,255,255,0.02)' ?>;">
                <td style="padding:8px; border-bottom:1px solid var(--border);">
                  <?= e($r['jobTitle']) ?>
                </td>
                <td style="padding:8px; border-bottom:1px solid var(--border);">
                  <span class="pill sm" style="background:<?= $clr ?>22;color:<?= $clr ?>;border:1px solid <?= $clr ?>33;"><?= e($st) ?></span>
                </td>
                <td style="padding:8px; border-bottom:1px solid var(--border);"><?= e($r['applicationDate']) ?></td>
                <td style="padding:8px; border-bottom:1px solid var(--border); text-align:right;"><a class="btn btn-outline btn-sm" href="<?= app_url('activity') ?>?tab=applied">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="muted">You haven't applied to any jobs yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

