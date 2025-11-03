<?php
  $moduleCount = count($modules);
  // Prefer summing explicit videoList minutes when available; otherwise fallback to estimatedDuration
  $totalMins = 0;
  foreach($modules as $m){
    if(!empty($m['videoList'])){
      foreach(($m['videoList'] ?? []) as $vv){ $totalMins += (int)($vv['mins'] ?? 0); }
    } else {
      $totalMins += (int)($m['estimatedDuration'] ?? 0);
    }
  }
  $hours = (int)floor($totalMins/60); $mins = (int)($totalMins%60);
?>

<section class="course-hero">
  <div class="container">
    <div class="hero-head">
      <div>
        <div class="muted" style="margin-bottom:6px;">Category: <?= e($course['category'] ?? 'General') ?></div>
        <h1 class="title"><?= e($course['courseTitle']) ?></h1>
        <p class="sub"><?= e($course['description'] ?? '') ?></p>
      </div>
      <div class="cta">
        <?php if(Auth::check() && Auth::user()['role']==='learner'): ?>
          <form method="post" action="<?= app_url('courses/enroll') ?>">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="courseID" value="<?= e($course['courseID']) ?>">
            <button class="btn">Enroll for free</button>
          </form>
        <?php else: ?>
          <a class="btn" href="<?= app_url('signup') ?>">Enroll for free</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="stats-bar">
      <div class="stat"><strong>Total length: <?= (int)$hours ?>h <?= (int)$mins ?>m</strong></div>
      <div class="stat"><strong>Modules: <?= (int)$moduleCount ?></strong></div>
      <div class="stat"><strong>Category: <?= e($course['category'] ?? 'General') ?></strong></div>
      <?php if(!empty($enrollCount ?? null)): ?>
        <div class="stat"><strong><?= (int)$enrollCount ?></strong><span>enrolled</span></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-bg"></div>
</section>

<section class="container py-6">
  <div class="card" style="margin-bottom:14px;">
    <div class="tabs-head">
      <span class="tab active">Details</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:12px;">
      <div>
        <div class="muted">Created on</div>
        <div><strong><?= e($course['createdDate'] ?? '') ?></strong></div>
      </div>
      <div>
        <div class="muted">Sector</div>
        <div><strong><?= e($course['sector'] ?? '—') ?></strong></div>
      </div>
      <?php $lvl = ($course['level'] ?? '') ?: ($course['courseLevel'] ?? ($course['difficulty'] ?? '—')); ?>
      <div>
        <div class="muted">Level</div>
        <div><strong><?= e($lvl) ?></strong></div>
      </div>
      <div>
        <div class="muted">Estimated time</div>
        <div><strong><?= (int)$hours ?>h <?= (int)$mins ?> m</strong></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="tabs-head">
      <span class="tab active">Modules</span>
    </div>
    <div class="module-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:14px;align-items:start;">
      <?php foreach($modules as $idx=>$m): $mid = 'module_'.$idx; ?>
        <div class="module-card" style="border:1px solid var(--border);border-radius:10px;overflow:hidden;background:var(--surface);color:var(--text);display:flex;flex-direction:column;min-height:200px;">
          <div style="padding:12px;display:flex;flex-direction:column;gap:6px;">
            <div class="module-title" style="font-weight:700;line-height:1.25;min-height:40px;"><?= e($m['content']) ?></div>
            <div class="muted" style="font-size:13px;">Module <?= $idx+1 ?> • <?= e((int)($m['estimatedDuration'] ?? 0)) ?> min</div>
            <?php if(!empty($m['videoList'][0]['url'])): ?>
              <a class="btn btn-sm" target="_blank" rel="noopener" href="<?= e($m['videoList'][0]['url']) ?>">Watch first video</a>
            <?php endif; ?>
            <button class="btn btn-outline btn-sm" type="button" data-toggle="module" data-target="<?= $mid ?>" style="width:100%;margin-top:6px;">Details</button>
          </div>
          <div id="<?= $mid ?>" style="display:<?= !empty($m['videoList']) ? 'block' : 'none' ?>;padding:12px;border-top:1px solid var(--border);background:var(--surface);">
            <?php if(!empty(trim($m['description'] ?? ''))): ?>
              <div style="white-space:pre-wrap;<?= !empty($m['videoTopics'] ?? []) ? 'margin-bottom:10px;' : '' ?>"><?= e($m['description']) ?></div>
            <?php else: ?>
              <div class="muted" style="margin-bottom:10px;">No description available.</div>
            <?php endif; ?>
            <?php $vwrap = $mid.'_vlist'; ?>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:8px;">
              <button type="button" class="pill sm" style="display:inline-flex;align-items:center;gap:6px;background:var(--surface-2);border:1px solid var(--border);border-radius:999px;padding:6px 10px;color:var(--text);cursor:pointer;"
                data-toggle="video-pill" data-target="<?= $vwrap ?>">
                <span aria-hidden="true">🎥</span>
                <span><?= (int)($m['videoCount'] ?? 1) ?> video<?= ((int)($m['videoCount'] ?? 1))===1?'':'s' ?></span>
              </button>
              <span class="pill sm" style="display:inline-flex;align-items:center;gap:6px;">
                <span aria-hidden="true">📝</span>
                <span><?= (int)($m['taskCount'] ?? 1) ?> task<?= ((int)($m['taskCount'] ?? 1))===1?'':'s' ?></span>
              </span>
            </div>

            <div id="<?= $vwrap ?>" style="display:<?= !empty($m['videoList']) ? 'block' : 'none' ?>;">
              <?php if(!empty($m['videoTopics'] ?? []) && empty($m['videoList'] ?? [])): ?>
                <div class="muted" style="margin:6px 0 6px; font-weight:600;">Video topics</div>
                <ul style="margin:0;padding-left:18px;line-height:1.5;">
                  <?php foreach(($m['videoTopics'] ?? []) as $t): ?>
                    <li><?= e($t) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <?php if(!empty($m['videoList'] ?? [])): ?>
                <?php $totalM = 0; foreach(($m['videoList'] ?? []) as $vv){ $totalM += (int)($vv['mins'] ?? 0); } ?>
                <div class="muted" style="margin:10px 0 6px; font-weight:600; display:flex; align-items:center; gap:8px;">
                  <span>🎥 <?= (int)count($m['videoList']) ?> videos • total <?= (int)$totalM ?> min</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                  <?php foreach(($m['videoList'] ?? []) as $vIdx=>$vv): $vid = $mid.'_v_'.$vIdx; ?>
                    <div style="border:1px solid var(--border); border-radius:8px; overflow:hidden;">
                      <button type="button" class="btn btn-outline btn-sm" style="width:100%; text-align:left; display:flex; justify-content:space-between; align-items:center; gap:8px; padding:10px 12px; background:transparent;" data-toggle="video" data-target="<?= $vid ?>">
                        <span style="display:flex; align-items:center; gap:8px;">
                          <span aria-hidden="true">🎬</span>
                          <span><?= e($vv['title'] ?? 'Video') ?></span>
                        </span>
                        <span class="muted"><?= isset($vv['mins']) ? ((int)$vv['mins'].' min') : '' ?></span>
                      </button>
                      <div id="<?= $vid ?>" style="display:none; padding:10px 12px; border-top:1px solid var(--border); background:var(--surface);">
                        <?php $topics = $vv['topics'] ?? ($m['videoTopics'] ?? []); ?>
                        <?php if(!empty($topics)): ?>
                          <ul style="margin:0; padding-left:18px; line-height:1.5;">
                            <?php foreach($topics as $t): ?>
                              <li><?= e($t) ?></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <div class="muted">No topics listed.</div>
                        <?php endif; ?>
                        <?php if(!empty($vv['url'])): ?>
                          <?php
                            $u = trim((string)$vv['url']);
                            $embed = '';
                            if(preg_match('~^(https?:)?//(www\.)?youtu\.be/([\w-]{6,})~i',$u,$m)){
                              $embed = 'https://www.youtube.com/embed/'.e($m[3]);
                            } elseif(preg_match('~^(https?:)?//(www\.)?youtube\.com/watch\?v=([\w-]{6,})~i',$u,$m)){
                              $embed = 'https://www.youtube.com/embed/'.e($m[3]);
                            } elseif(preg_match('~^(https?:)?//(www\.)?youtube\.com/shorts/([\w-]{6,})~i',$u,$m)){
                              $embed = 'https://www.youtube.com/embed/'.e($m[3]);
                            } elseif(preg_match('~^(https?:)?//(www\.)?vimeo\.com/(\d+)~i',$u,$m)){
                              $embed = 'https://player.vimeo.com/video/'.e($m[3]);
                            }
                          ?>
                          <?php if($embed): ?>
                            <div style="margin-top:10px; aspect-ratio:16/9; width:100%;">
                              <iframe src="<?= $embed ?>" style="width:100%;height:100%;border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="no-referrer" allowfullscreen sandbox="allow-same-origin allow-scripts allow-presentation"></iframe>
                            </div>
                          <?php endif; ?>
                          <div style="margin-top:10px;">
                            <a class="btn btn-sm" target="_blank" rel="noopener" href="<?= e($u) ?>">Watch</a>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if(!empty($m['quizUrl'])): ?>
                <div style="margin-top:12px;">
                  <a class="btn" target="_blank" rel="noopener" href="<?= e($m['quizUrl']) ?>">Take Quiz</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <script>
  (function(){
    document.addEventListener('click', function(e){
      var t = e.target;
      if(t && t.matches('[data-toggle="module"]')){
        var id = t.getAttribute('data-target');
        var el = document.getElementById(id);
        if(el){ el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none'; }
      }
      if(t && t.matches('[data-toggle="video"]')){
        var idv = t.getAttribute('data-target');
        var elv = document.getElementById(idv);
        // Ensure the video list wrapper is visible when a specific video is clicked
        if(idv){
          var parts = idv.split('_v_');
          if(parts.length > 1){
            var prefix = parts[0]; // module_<idx>
            var vlist = document.getElementById(prefix + '_vlist');
            if(vlist && (vlist.style.display === 'none' || vlist.style.display === '')){
              vlist.style.display = 'block';
            }
          }
        }
        if(elv){ elv.style.display = (elv.style.display === 'none' || elv.style.display === '') ? 'block' : 'none'; }
      }
      if(t && t.matches('[data-toggle="video-pill"]')){
        var idp = t.getAttribute('data-target');
        var elp = document.getElementById(idp);
        if(elp){ elp.style.display = (elp.style.display === 'none' || elp.style.display === '') ? 'block' : 'none'; }
      }
      
    });
  })();
  </script>
</section>
