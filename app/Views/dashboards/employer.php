<section class="mt-6">
  <?php Auth::requireRole(['employer']); $pdo = DB::conn(); $eid = Auth::user()['id'];
    // Initialize vars to avoid undefined warnings
    $latestJobID = null; $latestJobTitle = '';
    $apps7 = 0; $iv7 = 0; $hi7 = 0; $apps30 = 0; $iv30 = 0; $hi30 = 0;
  ?>
  <?php
    // Company profile
    $emp = [];
    try{
      $stmt = $pdo->prepare("SELECT companyName, companyIndustry, contactPerson, employerEmail, companyPhone FROM Employers WHERE employerID=?");
      $stmt->execute([$eid]);
      $emp = $stmt->fetch() ?: [];
    }catch(Throwable $e){ $emp = []; }
  ?>
  <h2>Welcome, <?= e(($emp['companyName'] ?? '') ?: ($user['name'] ?? 'Employer')) ?> 👋</h2>

  <div class="card" style="padding:16px; margin:12px 0;">
    <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
      <?php
        $logoUrl = null;
        $base = app_url('uploads/employers/'.($eid));
        foreach(['png','jpg','jpeg','webp'] as $lx){
          $p = dirname(__DIR__,3).'/public/uploads/employers/'.preg_replace('/[^A-Za-z0-9_\-]/','',$eid).'/logo.'.$lx;
          if(file_exists($p)){ $v = @filemtime($p) ?: time(); $logoUrl = $base.'/logo.'.$lx.'?v='.$v; break; }
        }
      ?>
      <div style="width:56px;height:56px;border-radius:12px;background:var(--border);overflow:hidden;display:flex;align-items:center;justify-content:center;">
        <?php if($logoUrl): ?>
          <img src="<?= $logoUrl ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;" />
        <?php else: ?>
          <span class="muted" style="font-size:12px;">Logo</span>
        <?php endif; ?>
      </div>

  

  <?php $fmt = function($n){ $n=(int)$n; return ($n>0?'+':'').$n; }; ?>
      <div style="flex:1;min-width:260px;">
        <form method="post" action="<?= app_url('employer/profile/update') ?>" enctype="multipart/form-data" style="display:grid;gap:8px;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <h3 style="margin:0;">Company profile</h3>
            <?php if(!empty($_SESSION['flash'])): ?><small class="muted"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></small><?php endif; ?>
          </div>
          <label>Company name
            <input type="text" name="companyName" value="<?= e($emp['companyName'] ?? '') ?>" required>
          </label>
          <label>Industry
            <input type="text" name="companyIndustry" value="<?= e($emp['companyIndustry'] ?? '') ?>" required>
          </label>
          <label>Company logo
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" />
            <small class="muted" style="display:block;">PNG, JPG, or WebP. Max 2 MB.</small>
          </label>
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <label style="flex:1 1 200px;">Contact person
              <input type="text" name="contactPerson" value="<?= e($emp['contactPerson'] ?? ($user['name'] ?? '')) ?>" required>
            </label>
            <label style="flex:1 1 200px;">Phone
              <input type="text" name="companyPhone" value="<?= e($emp['companyPhone'] ?? '') ?>" pattern="[0-9+\-\s]{7,15}" title="7-15 characters: digits, spaces, + or - allowed" required>
            </label>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn" type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php
    // KPIs
    try{
      $qOpen = $pdo->prepare("SELECT COUNT(*) FROM JobPosting WHERE employerID=? AND (closedDate IS NULL OR closedDate='')");
      $qOpen->execute([$eid]);
      $open = (int)$qOpen->fetchColumn();
    }catch(Throwable $e){ $open = 0; }
    $new7 = $pdo->prepare("SELECT COUNT(*) FROM Application a JOIN JobPosting j ON j.jobID=a.jobID WHERE j.employerID=? AND a.applicationDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $new7->execute([$eid]); $newApplicants7 = (int)$new7->fetchColumn();
    $pipe = $pdo->prepare("SELECT 
                              SUM(a.appStatus='Under Review') ur,
                              SUM(a.appStatus='Interview') iv,
                              SUM(a.appStatus='Hired') hi
                            FROM Application a JOIN JobPosting j ON j.jobID=a.jobID
                            WHERE j.employerID=?");
    $pipe->execute([$eid]); $prow = $pipe->fetch() ?: ['ur'=>0,'iv'=>0,'hi'=>0];

    // 7-day trend by status
    $pipe7 = $pdo->prepare("SELECT 
                               SUM(a.appStatus='Under Review') ur,
                               SUM(a.appStatus='Interview') iv,
                               SUM(a.appStatus='Hired') hi
                             FROM Application a JOIN JobPosting j ON j.jobID=a.jobID
                             WHERE j.employerID=? AND a.applicationDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $pipe7->execute([$eid]); $p7 = $pipe7->fetch() ?: ['ur'=>0,'iv'=>0,'hi'=>0];

    // Recent applicants
    $ra = $pdo->prepare("SELECT a.appID, a.applicationDate, a.appStatus,
                                l.learnerName, l.learnerEmail,
                                j.jobID, j.jobTitle
                         FROM Application a
                         JOIN Learners l ON l.learnerID=a.learnerID
                         JOIN JobPosting j ON j.jobID=a.jobID
                         WHERE j.employerID=?
                         ORDER BY a.applicationDate DESC
                         LIMIT 8");
    $ra->execute([$eid]); $recent = $ra->fetchAll();

    // Latest job for quick filters
    $latestJobID = null; $latestJobTitle = '';
    try{
      $lj = $pdo->prepare("SELECT jobID, jobTitle, postDate FROM JobPosting WHERE employerID=? ORDER BY postDate DESC LIMIT 1");
      $lj->execute([$eid]); if($row=$lj->fetch()){ $latestJobID=$row['jobID']; $latestJobTitle=$row['jobTitle']; }
    }catch(Throwable $e){ $latestJobID = null; }

    // Conversion snapshot (last 7/30 days)
    $apps7 = 0; $iv7=0; $hi7=0; $apps30=0; $iv30=0; $hi30=0;
    try{
      $s7 = $pdo->prepare("SELECT 
                COUNT(*) apps,
                SUM(a.appStatus='Interview') iv,
                SUM(a.appStatus='Hired') hi
              FROM Application a JOIN JobPosting j ON j.jobID=a.jobID
              WHERE j.employerID=? AND a.applicationDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
      $s7->execute([$eid]); if($r=$s7->fetch()){ $apps7=(int)$r['apps']; $iv7=(int)$r['iv']; $hi7=(int)$r['hi']; }
      $s30 = $pdo->prepare("SELECT 
                COUNT(*) apps,
                SUM(a.appStatus='Interview') iv,
                SUM(a.appStatus='Hired') hi
              FROM Application a JOIN JobPosting j ON j.jobID=a.jobID
              WHERE j.employerID=? AND a.applicationDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
      $s30->execute([$eid]); if($r=$s30->fetch()){ $apps30=(int)$r['apps']; $iv30=(int)$r['iv']; $hi30=(int)$r['hi']; }
    }catch(Throwable $e){ /* fallback zeros */ }

    // Recent jobs
    $jobs = [];
    try{
      $jq = $pdo->prepare("SELECT j.jobID, j.jobTitle, j.createdDate, j.closedDate,
                                  (SELECT COUNT(*) FROM Application a WHERE a.jobID=j.jobID) AS apps
                            FROM JobPosting j
                            WHERE j.employerID=?
                            ORDER BY j.createdDate DESC
                            LIMIT 6");
      $jq->execute([$eid]);
      $jobs = $jq->fetchAll();
    }catch(Throwable $e){ $jobs = []; }
  ?>

  <?php // ensure latest job ID for quick links
    if($latestJobID===null){
      try{ $lj=$pdo->prepare("SELECT jobID, jobTitle, postDate FROM JobPosting WHERE employerID=? ORDER BY postDate DESC LIMIT 1"); $lj->execute([$eid]); if($row=$lj->fetch()){ $latestJobID=$row['jobID']; $latestJobTitle=$row['jobTitle']; } }catch(Throwable $e){ $latestJobID=null; }
    }
  ?>
  <div class="grid" style="gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <div class="card" style="padding:16px;">
      <div class="muted">Open positions</div>
      <div style="font-size:28px;font-weight:800;<?= $open>0?'color:#7dd3fc;':'' ?>"><?= $open ?></div>
      <div><a class="btn btn-outline btn-sm" href="<?= app_url('employer/jobs') ?>">Manage jobs</a></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">New applicants (7d)</div>
      <div style="font-size:28px;font-weight:800;<?= (int)$newApplicants7>0?'color:#7dd3fc;':'' ?>"><?= $newApplicants7 ?></div>
      <?php $toD = date('Y-m-d'); $fromD = date('Y-m-d', strtotime('-6 days')); ?>
      <div><a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?from=<?= $fromD ?>&to=<?= $toD ?>&sort=applied_newest">View</a></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">In pipeline — Under Review</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)($prow['ur'] ?? 0) ?></div>
      <div><a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Under+Review&sort=applied_newest">Filter</a></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">In pipeline — Interview</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)($prow['iv'] ?? 0) ?></div>
      <div><a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Interview&sort=applied_newest">Filter</a></div>
    </div>
    <div class="card" style="padding:16px;">
      <div class="muted">Hired (all-time)</div>
      <div style="font-size:28px;font-weight:800;"><?= (int)($prow['hi'] ?? 0) ?></div>
      <div><a class="btn btn-outline btn-sm" href="<?= app_url('employer/candidates') ?>?status=Hired&sort=applied_newest">Filter</a></div>
    </div>
  </div>

  <?php
    // Shortlisted candidates (compact panel)
    $shortRows = [];
    try{
      $sp = dirname(__DIR__,3).'/storage/shortlist_'.$eid.'.json';
      $ids = [];
      if(file_exists($sp)){
        $data = json_decode(file_get_contents($sp), true) ?: [];
        $ids = array_keys(array_filter($data));
      }
      if(!empty($ids)){
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $q = $pdo->prepare("SELECT learnerID, learnerName, learnerEmail FROM Learners WHERE learnerID IN ($ph)");
        $q->execute($ids);
        $shortRows = $q->fetchAll();
      }
    }catch(Throwable $e){ $shortRows = []; }
  ?>
  <div class="card" style="padding:16px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h3 style="margin:0;">Shortlisted candidates</h3>
      <a class="btn btn-outline btn-sm" href="<?= app_url('employer/talent') ?>?shortlisted=1">View all</a>
    </div>
    <?php if(!empty($shortRows)): ?>
      <div class="card" style="padding:0; overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Name</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach(array_slice($shortRows,0,5) as $s): ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><a href="<?= app_url('employer/talent/profile') ?>?learner=<?= e($s['learnerID']) ?>"><?= e($s['learnerName']) ?></a></strong></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($s['learnerEmail']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <form method="post" action="<?= app_url('employer/talent/shortlist') ?>" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="learnerID" value="<?= e($s['learnerID']) ?>">
                    <input type="hidden" name="action" value="remove">
                    <button class="btn btn-outline btn-sm" type="submit">Remove</button>
                  </form>
                  <a class="btn btn-outline btn-sm" href="mailto:<?= e($s['learnerEmail']) ?>?subject=Opportunity at <?= urlencode($_SESSION['user']['name'] ?? 'Employer') ?>">Message</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted" style="margin:0;">No shortlisted candidates yet.</p>
    <?php endif; ?>
  </div>

  <div class="card" style="padding:18px; margin-top:14px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h3 style="margin:0;">Recent applicants</h3>
      <a class="btn btn-outline" href="<?= app_url('employer/jobs') ?>">View all jobs</a>
    </div>
    <?php if(!empty($recent)): ?>
      <div class="card" style="padding:0; overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Candidate</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Job</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Applied</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
              <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($recent as $a): ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($a['learnerName']) ?></strong><br><small class="muted"><?= e($a['learnerEmail']) ?></small></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['jobTitle']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['applicationDate']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['appStatus']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                  <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                    <a class="btn btn-outline btn-sm" href="<?= app_url('employer/jobs/applicants') ?>?job=<?= e($a['jobID']) ?>">Review</a>
                    <?php 
                      $next = $a['appStatus']==='Under Review' ? 'Interview' : ($a['appStatus']==='Interview' ? 'Hired' : '');
                    ?>
                    <?php if($next!==''): ?>
                      <form method="post" action="<?= app_url('employer/applications/status') ?>" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="appID" value="<?= e($a['appID']) ?>">
                        <input type="hidden" name="job" value="<?= e($a['jobID']) ?>">
                        <input type="hidden" name="status" value="<?= e($next) ?>">
                        <button type="submit" class="btn btn-outline btn-sm">Advance</button>
                      </form>
                    <?php endif; ?>
                    <?php if($a['appStatus']!=='Hired'): ?>
                      <form method="post" action="<?= app_url('employer/applications/status') ?>" style="display:inline;" onsubmit="return confirm('Reject this applicant?');">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="appID" value="<?= e($a['appID']) ?>">
                        <input type="hidden" name="job" value="<?= e($a['jobID']) ?>">
                        <input type="hidden" name="status" value="Rejected">
                        <button type="submit" class="btn btn-outline btn-sm">Reject</button>
                      </form>
                    <?php endif; ?>
                    <a class="btn btn-outline btn-sm" href="mailto:<?= e($a['learnerEmail']) ?>?subject=Application: <?= urlencode($a['jobTitle']) ?>">Message</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted" style="margin:0;">No recent applicants yet.</p>
    <?php endif; ?>
  </div>
</section>

