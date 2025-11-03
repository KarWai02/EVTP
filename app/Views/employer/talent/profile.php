<section class="container py-6">
  <?php Auth::requireRole(['employer']); ?>
  <h1 style="margin:0 0 12px;">Learner Profile</h1>

  <div class="card" style="padding:16px;">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
      <div style="display:flex;gap:12px;align-items:center;">
        <div style="width:44px;height:44px;border-radius:999px;background:var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;">
          <?php $nm = trim($learner['learnerName'] ?? ''); $ini = strtoupper(mb_substr($nm,0,1)); ?>
          <?= e($ini ?: 'L') ?>
        </div>
        <div>
          <div style="font-size:20px;font-weight:800;"><?= e($learner['learnerName']) ?></div>
          <div class="muted">Email: <?= e($learner['learnerEmail']) ?> • Phone: <?= e($learner['learnerPhone'] ?? '-') ?></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;">
        <form method="post" action="<?= app_url('employer/talent/shortlist') ?>" style="display:inline;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="learnerID" value="<?= e($learner['learnerID']) ?>">
          <input type="hidden" name="action" value="<?= $inShort ? 'remove' : 'add' ?>">
          <button type="submit" class="btn btn-outline btn-sm"><?= $inShort ? 'Remove from shortlist' : 'Add to shortlist' ?></button>
        </form>
        <a class="btn btn-outline btn-sm" href="mailto:<?= e($learner['learnerEmail']) ?>?subject=Opportunity at <?= urlencode($_SESSION['user']['name'] ?? 'Employer') ?>">Message</a>
        <a class="btn btn-outline btn-sm" href="<?= app_url('employer/talent') ?>">Back</a>
      </div>
    </div>
  </div>

  <div class="card" style="padding:16px;margin-top:12px;">
    <h2 style="margin:0 0 8px;border-left:4px solid var(--primary);padding-left:10px;">Recent courses</h2>
    <?php if(!empty($courses)): ?>
      <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
        <?php foreach($courses as $c): ?>
          <li style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
            <div>
              <div style="font-weight:700;"><?= e($c['courseTitle']) ?></div>
              <small class="muted">Enrolled: <?= e($c['enrollDate']) ?> • <?= e($c['completionStatus']) ?></small>
            </div>
            <a class="btn btn-outline btn-sm" href="<?= app_url('courses/view') ?>?id=<?= e($c['courseID']) ?>">View</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted" style="margin:0;">No recent courses.</p>
    <?php endif; ?>
  </div>

  <div class="card" style="padding:16px;margin-top:12px;">
    <h2 style="margin:0 0 8px;border-left:4px solid var(--primary);padding-left:10px;">Applications with your company</h2>
    <?php
      $apps = [];
      try{
        $pdo = DB::conn(); $eid = Auth::user()['id'];
        $st = $pdo->prepare("SELECT a.appID, a.appStatus, a.applicationDate, j.jobID, j.jobTitle
                             FROM Application a JOIN JobPosting j ON j.jobID=a.jobID
                             WHERE a.learnerID=? AND j.employerID=?");
        $st->execute([$learner['learnerID'], $eid]);
        $apps = $st->fetchAll();
      }catch(Throwable $e){ $apps = []; }
    ?>
    <?php if(!empty($apps)): ?>
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Job</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Applied</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Status</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid var(--border);">Resume</th>
            <th style="text-align:right; padding:8px; border-bottom:1px solid var(--border);">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($apps as $i=>$a): $st = (string)($a['appStatus'] ?? ''); $clr = ($st==='Hired'?'#22c55e':($st==='Interview'?'#60a5fa':($st==='Rejected'?'#ef4444':'#eab308'))); ?>
            <tr style="background: <?= ($i%2)?'transparent':'rgba(255,255,255,0.02)' ?>;">
              <td style="padding:8px; border-bottom:1px solid var(--border);">
                <?= e($a['jobTitle']) ?>
              </td>
              <td style="padding:8px; border-bottom:1px solid var(--border);"><?= e($a['applicationDate']) ?></td>
              <td style="padding:8px; border-bottom:1px solid var(--border);">
                <span class="pill sm" style="background:<?= $clr ?>22;color:<?= $clr ?>;border:1px solid <?= $clr ?>33;"><?= e($st) ?></span>
              </td>
              <td style="padding:8px; border-bottom:1px solid var(--border);">
                <?php
                  $resumeExists = false;
                  try{
                    $p = dirname(__DIR__,3).'/storage/applications/'.preg_replace('/[^A-Za-z0-9_\-]/','',$a['appID']).'/resume.pdf';
                    if(file_exists($p)) $resumeExists=true;
                    if(!$resumeExists){ $p = dirname(__DIR__,3).'/storage/applications/'.preg_replace('/[^A-Za-z0-9_\-]/','',$a['appID']).'/resume.doc'; if(file_exists($p)) $resumeExists=true; }
                    if(!$resumeExists){ $p = dirname(__DIR__,3).'/storage/applications/'.preg_replace('/[^A-Za-z0-9_\-]/','',$a['appID']).'/resume.docx'; if(file_exists($p)) $resumeExists=true; }
                  }catch(Throwable $e){ $resumeExists=false; }
                ?>
                <?php if($resumeExists): ?>
                  <a class="btn btn-outline btn-sm" href="<?= app_url('employer/applications/resume') ?>?app=<?= e($a['appID']) ?>">Download</a>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
              <td style="padding:8px; border-bottom:1px solid var(--border); text-align:right;"><a class="btn btn-outline btn-sm" href="<?= app_url('employer/jobs/applicants') ?>?job=<?= e($a['jobID']) ?>">Review</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="muted" style="margin:0;">No applications for your jobs yet.</p>
    <?php endif; ?>
  </div>
</section>
