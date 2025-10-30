<section class="container py-6">
  <?php Auth::requireRole(['employer']); ?>
  <h1 style="margin:0 0 10px;">My Job Posts</h1>

  <form method="get" action="<?= app_url('employer/jobs') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    <input type="text" name="q" placeholder="Search title or location" value="<?= e($_GET['q'] ?? '') ?>" style="flex:1;min-width:260px;">
    <label class="muted">Per page
      <select name="pp">
        <?php $ppSel = (int)($perPage ?? 10); foreach([10,25,50] as $pp): ?>
          <option value="<?= $pp ?>" <?= $pp===$ppSel?'selected':'' ?>><?= $pp ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <a class="btn" href="<?= app_url('employer/jobs/create') ?>">New Job</a>
    <button class="btn btn-outline" type="submit">Search</button>
  </form>

  <?php if(!empty($rows)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Location</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Salary</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Posted</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Closed</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $j): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)">
              <a href="<?= app_url('employer/jobs/applicants') ?>?job=<?= e($j['jobID']) ?>"><?= e($j['jobTitle']) ?></a>
              <div class="muted" style="margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <?php $jt = trim((string)($j['jobType'] ?? '')); if($jt!==''): ?>
                  <span style="display:inline-block;padding:2px 8px;border-radius:999px;background:rgba(125,211,252,0.15);border:1px solid rgba(125,211,252,0.25);color:#7dd3fc;"><?= e($jt) ?></span>
                <?php endif; ?>
                <?php $dl = trim((string)($j['deadline'] ?? '')); if($dl!==''): $days = (int)floor((strtotime($dl) - strtotime(date('Y-m-d')))/86400); ?>
                  <span title="Deadline: <?= e($dl) ?>" style="display:inline-block;padding:2px 8px;border-radius:6px;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);color:#f87171;">D<?= $days>=0?'-'.$days:($days) ?></span>
                <?php endif; ?>
                <?php $skills = array_filter(array_map('trim', explode(',', (string)($j['skills'] ?? '')))); foreach(array_slice($skills,0,4) as $s): ?>
                  <span style="display:inline-block;padding:2px 6px;border-radius:6px;background:rgba(148,163,184,0.15);border:1px solid rgba(148,163,184,0.25);color:#cbd5e1;"><?= e($s) ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($j['location'] ?: '—') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <?php
                $smin = $j['salaryMin'] ?? null; $smax = $j['salaryMax'] ?? null; $slegacy = $j['salary'] ?? null;
                if($smin!==null || $smax!==null){
                  $parts=[]; if($smin!==null) $parts[] = number_format((float)$smin,2); if($smax!==null) $parts[] = number_format((float)$smax,2);
                  echo e(implode(' - ', $parts));
                } elseif($slegacy!==null) {
                  echo e(number_format((float)$slegacy,2));
                } else { echo '—'; }
              ?>
            </td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($j['postDate'] ?: '—') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($j['closedDate'] ?: '—') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <a class="btn btn-outline btn-sm" href="<?= app_url('employer/jobs/edit') ?>?id=<?= e($j['jobID']) ?>">Edit</a>
              <form method="post" action="<?= app_url('employer/jobs/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this job?');">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= e($j['jobID']) ?>">
                <button class="btn btn-outline btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if(($pages ?? 1) > 1): ?>
      <?php
        $current = (int)($page ?? 1); $last = (int)($pages ?? 1);
        $qs = ['q'=>($_GET['q'] ?? ''),'pp'=>(int)($perPage ?? 10)];
        $link = function($p) use ($qs){ $qs['page']=$p; return app_url('employer/jobs').'?'.http_build_query($qs); };
      ?>
      <nav class="pagination" aria-label="Pagination" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <span class="muted">Page <?= $current ?> of <?= $last ?> (<?= (int)($total ?? 0) ?>)</span>
        <a class="btn btn-outline btn-sm" href="<?= $link(1) ?>" <?= $current<=1?'aria-disabled="true" tabindex="-1"':'' ?>>« First</a>
        <a class="btn btn-outline btn-sm" href="<?= $link(max(1,$current-1)) ?>" <?= $current<=1?'aria-disabled="true" tabindex="-1"':'' ?>>‹ Prev</a>
        <?php
          $start = max(1, $current - 2); $end = min($last, $current + 2);
          if ($start > 1) echo '<span class="muted">…</span>';
          for($p=$start; $p<=$end; $p++){
            $cls = $p===$current ? 'btn btn-sm' : 'btn btn-outline btn-sm';
            echo '<a class="'.$cls.'" href="'.e($link($p)).'">'.$p.'</a>';
          }
          if ($end < $last) echo '<span class="muted">…</span>';
        ?>
        <a class="btn btn-outline btn-sm" href="<?= $link(min($last,$current+1)) ?>" <?= $current>=$last?'aria-disabled="true" tabindex="-1"':'' ?>>Next ›</a>
        <a class="btn btn-outline btn-sm" href="<?= $link($last) ?>" <?= $current>=$last?'aria-disabled="true" tabindex="-1"':'' ?>>Last »</a>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted">No jobs yet. Click New Job to post one.</p>
  <?php endif; ?>
</section>
