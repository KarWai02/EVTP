<section class="container py-6">
  <?php if(!empty($_SESSION['flash'])): ?>
    <div class="card" style="margin-bottom:10px; padding:10px; border-left:4px solid var(--primary); background:var(--bgElevated);">
      <?= e($_SESSION['flash']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>
  <h1 style="margin:0 0 12px;">Job search</h1>

  <form method="get" action="<?= app_url('jobs') ?>" class="card" style="padding:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" placeholder="Enter keywords" value="<?= e($q ?? '') ?>" style="flex:1;min-width:200px;">
    <input type="text" name="loc" placeholder="Enter suburb, city, or region" value="<?= e($loc ?? '') ?>" style="flex:1;min-width:220px;">
    <label>Posted
      <select name="posted">
        <option value="any" <?= ($posted ?? 'any')==='any'?'selected':'' ?>>Any time</option>
        <option value="24h" <?= ($posted ?? '')==='24h'?'selected':'' ?>>Last 24h</option>
        <option value="7d"  <?= ($posted ?? '')==='7d'?'selected':'' ?>>Last 7 days</option>
        <option value="30d" <?= ($posted ?? '')==='30d'?'selected':'' ?>>Last 30 days</option>
      </select>
    </label>
    <label style="display:flex;align-items:center;gap:6px;">
      <input type="checkbox" name="saved" value="1" <?= ($saved??'')==='1'?'checked':'' ?> onchange="this.form.submit()">
      Saved only
    </label>
    <button class="btn btn-outline" type="submit">Seek</button>
  </form>

  <?php if(!function_exists('___timeago')){ function ___timeago($d){ $t=strtotime($d?:''); if(!$t) return $d; $s=time()-$t; if($s<3600) return max(1,round($s/60)).'m ago'; if($s<86400) return round($s/3600).'h ago'; if($s<86400*30) return round($s/86400).'d ago'; return date('Y-m-d',$t);} }
        if(!function_exists('___salaryText')){ function ___salaryText($row){
          $sal = trim((string)($row['salary'] ?? ''));
          $min = isset($row['salaryMin']) ? (string)$row['salaryMin'] : '';
          $max = isset($row['salaryMax']) ? (string)$row['salaryMax'] : '';
          $fmt = function($v){ if($v==='') return ''; if(is_numeric($v)) return 'RM '.number_format((float)$v,0); $v=trim($v); return (stripos($v,'rm')===0? $v : ('RM '.$v)); };
          if($sal!=='') return $fmt($sal);
          if($min!=='' && $max!=='') return $fmt($min).' - '.$fmt($max);
          if($min!=='') return $fmt($min);
          if($max!=='') return $fmt($max);
          return '';
        } } ?>

  <div class="grid" style="display:grid; grid-template-columns:340px 1fr; gap:14px; margin-top:12px;">
    <div class="card" style="padding:0; max-height:70vh; overflow:auto;">
      <div class="muted" style="padding:8px 12px; border-bottom:1px solid var(--border);"><?= (int)count($jobs) ?> jobs</div>
      <?php if(!empty($jobs)): ?>
        <?php foreach($jobs as $j): ?>
          <?php $applied = in_array((int)$j['jobID'], ($appliedIds ?? []), true); $isSel = (string)($selected ?? '') === (string)$j['jobID']; $href = app_url('jobs').'?id='.urlencode($j['jobID']).'&q='.urlencode($q??'').'&loc='.urlencode($loc??'').'&posted='.urlencode($posted??'any'); ?>
          <div onclick="location.href='<?= e($href) ?>'" style="cursor:pointer; display:flex; gap:8px; justify-content:space-between; align-items:center; padding:12px; border-bottom:1px solid var(--border); <?php if($isSel): ?>background:rgba(100,179,255,.08); border-left:4px solid #64b3ff;<?php endif; ?>">
            <div style="min-width:0;">
              <!-- 1) Position -->
              <div style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">&lrm;<?= e($j['jobTitle']) ?></div>
              <!-- 2) Company name -->
              <div class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($j['companyName'] ?? '') ?></div>
              <!-- spacer -->
              <div style="height:6px;"></div>
              <!-- 3) Region/City -->
              <div class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($j['locations'] ?? '') ?></div>
              <!-- 4) Salary -->
              <?php $salaryText = ___salaryText($j); ?>
              <?php if($salaryText!==''): ?>
                <div class="muted" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($salaryText) ?></div>
              <?php endif; ?>
              <!-- spacer -->
              <div style="height:6px;"></div>
              <!-- Job type -->
              <?php if(!empty($j['jobType'])): ?>
                <div class="pill sm" title="Job type"><?= e($j['jobType']) ?></div>
              <?php endif; ?>
              <!-- Posted time -->
              <div class="muted" style="font-size:12px; margin-top:2px;">Posted <?= e(___timeago($j['postDate'] ?? '')) ?></div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
              <?php if($applied): ?>
                <span class="pill sm">Applied</span>
              <?php else: ?>
                <a class="btn btn-outline btn-sm" href="<?= e($href) ?>#detail" onclick="event.stopPropagation();">Apply now</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="padding:12px;" class="muted">No jobs match your filters.</div>
      <?php endif; ?>
    </div>

    <div id="detail">
      <?php if($detail): ?>
        <div class="card" style="padding:0;">
          <!-- sticky header -->
          <div style="position:sticky; top:0; z-index:5; padding:16px; background:var(--bgElevated); border-bottom:1px solid var(--border);">
            <div class="flex-between" style="align-items:center; gap:8px;">
              <div style="display:flex; align-items:center; gap:10px;">
                <?php if(!empty($detail['companyLogo'])): ?>
                  <img src="<?= e($detail['companyLogo']) ?>" alt="Logo" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                <?php endif; ?>
                <h2 style="margin:0 0 4px;"><?= e($detail['jobTitle']) ?></h2>
                <div class="muted"><?= e($detail['companyName'] ?? '') ?></div>
              </div>
              <div style="display:flex; gap:8px;">
                <button class="btn btn-outline btn-sm" type="button" onclick="(function(btn){ var href='<?= e(app_url('jobs')) ?>' + '?id=<?= e($detail['jobID']) ?>'; var abs; try{ abs = new URL(href, window.location.href).href; }catch(e){ abs = window.location.origin + '/' + href.replace(/^\//,''); } navigator.clipboard.writeText(abs).then(function(){ btn.textContent='Copied'; setTimeout(function(){ btn.textContent='Share'; },1500); }); })(this)" title="Share">Share</button>
                <?php $isSaved = in_array((string)($detail['jobID'] ?? ''), ($savedIds ?? []), true); ?>
                <form method="post" action="<?= app_url('jobs/save') ?>" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="job" value="<?= e($detail['jobID']) ?>">
                  <button class="btn btn-outline btn-sm" type="submit" title="Save/Unsave"><?= $isSaved ? 'Saved' : 'Save' ?></button>
                </form>
              </div>
            </div>
            <!-- badges row -->
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; font-size:13px;">
              <?php if(!empty($detail['locations'])): ?><span class="pill sm" title="Location">📍 <?= e($detail['locations']) ?></span><?php endif; ?>
              <?php if(!empty($detail['jobType'])): ?><span class="pill sm" title="Job type">🧭 <?= e($detail['jobType']) ?></span><?php endif; ?>
              <?php $salaryText = ___salaryText($detail); if($salaryText!==''): ?>
                <span class="pill sm" title="Salary">💰 <?= e($salaryText) ?></span>
              <?php endif; ?>
              <?php if(!empty($detail['postDate'])): ?><span class="pill sm" title="Posted">📅 Posted <?= e($detail['postDate']) ?></span><?php endif; ?>
              <?php if(!empty($detail['deadline'])): ?><span class="pill sm" title="Deadline">⏳ Apply by <?= e($detail['deadline']) ?></span><?php endif; ?>
            </div>
          </div>

          <div style="padding:16px;">
            <?php $desc = $detail['jobDesc'] ?? ($detail['jobDescription'] ?? ''); ?>
            <div style="white-space:pre-wrap;"><?= nl2br(e($desc)) ?></div>
            <?php if(!empty($detail['skills'])): ?>
              <h3 style="margin:16px 0 8px;">Skills</h3>
              <ul style="margin:0 0 12px 16px;">
                <?php foreach(array_filter(array_map('trim', explode(',', (string)$detail['skills']))) as $sk): ?>
                  <li><?= e($sk) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if(!empty($detail['educationReq'])): ?>
              <h3 style="margin:0 0 6px;">Education Requirements</h3>
              <div style="margin-bottom:14px;"><?= nl2br(e($detail['educationReq'])) ?></div>
            <?php endif; ?>
            <!-- soft divider instead of hr -->
            <div style="height:1px; background:var(--border); opacity:.6; margin:8px 0 12px;"></div>
          </div>
          <?php if(Auth::check() && Auth::user()['role']==='learner'): ?>
            <?php $already = in_array((int)($detail['jobID'] ?? 0), ($appliedIds ?? []), true); ?>
            <?php if($already): ?>
              <div class="pill">Applied</div>
            <?php else: ?>
              <form method="post" action="<?= app_url('jobs/apply') ?>" enctype="multipart/form-data" style="display:grid;gap:8px; padding:0 16px 16px;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="job" value="<?= e($detail['jobID']) ?>">
                <label>Resume (PDF, DOC, DOCX)
                  <input type="file" name="resume" accept="application/pdf,.doc,.docx">
                </label>
                <button class="btn" type="submit">Apply</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <a class="btn" href="<?= app_url('login') ?>">Log in to apply</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="card" style="padding:24px; text-align:center;">
          <h3 style="margin:0 0 6px;">Select a job</h3>
          <div class="muted">Display details here</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
