<section class="container py-6">
  <style>
    /* Scoped to filter card to avoid global impact */
    .filter-card .radio-list { display:flex; flex-direction:column; gap:10px; }
    .filter-card .radio-list .radio { display:flex; align-items:center; gap:10px; line-height:1.3; }
    .filter-card .radio-list .radio input[type="radio"] { width:16px; height:16px; margin:0; }
    .filter-card .radio-list .radio span { flex:1; }
  </style>
  <!-- Page title -->
  <header class="section-head" style="margin-bottom:12px;display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;justify-content:space-between;">
    <div>
      <h1 style="margin:0">Courses</h1>
      <?php if(!empty($activeCat)): ?>
        <span class="pill" style="margin-top:6px;display:inline-block">Category: <?= e($activeCat) ?></span>
      <?php endif; ?>
    </div>
    <div>
      <?php if(class_exists('Auth') && Auth::check()): ?>
        <?php $role = Auth::user()['role'] ?? ''; ?>
        <?php if($role==='trainer'): ?>
          <a class="btn btn-outline" href="<?= app_url('trainer/courses/create') ?>">Add Course</a>
        <?php elseif($role==='admin'): ?>
          <a class="btn btn-outline" href="<?= app_url('admin/courses/create') ?>">Add Course</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </header>

  <!-- Results bar: count + chips + sort -->
  <div class="results-bar">
    <div>
      <div class="count">
        <?= (int)($total ?? count($courses ?? [])) ?> result<?= ($total ?? 0) == 1 ? '' : 's' ?>
        <?php if(!empty($query)): ?> for “<?= e($query) ?>”<?php endif; ?>
        <?php if(!empty($activeCat)): ?> in <?= e($activeCat) ?><?php endif; ?>
      </div>

      <?php if(($activeDur ?? '') || ($activeLevel ?? '') || ($activeSector ?? '') || ($activeCat ?? '') || ($query ?? '')): ?>
        <div class="pills">
          <?php if($query): ?><span class="pill">Search: “<?= e($query) ?>”</span><?php endif; ?>
          <?php if($activeCat): ?><span class="pill">Category: <?= e($activeCat) ?></span><?php endif; ?>
          <?php if($activeSector): ?><span class="pill">Sector: <?= e($activeSector) ?></span><?php endif; ?>
          <?php if($activeLevel): ?><span class="pill">Level: <?= e($activeLevel) ?></span><?php endif; ?>
          <?php if($activeDur): ?><span class="pill">Duration: <?= e($activeDur) ?></span><?php endif; ?>
          <a class="btn btn-outline btn-sm" href="<?= app_url('courses') ?>">Clear all</a>
        </div>
      <?php endif; ?>
    </div>

    <form class="sort-row" method="get" action="<?= app_url('courses') ?>" aria-label="Sort courses">
      <!-- preserve filters -->
      <input type="hidden" name="q"     value="<?= e($query ?? '') ?>">
      <input type="hidden" name="cat"   value="<?= e($activeCat ?? '') ?>">
      <input type="hidden" name="sector" value="<?= e($activeSector ?? '') ?>">
      <input type="hidden" name="level" value="<?= e($activeLevel ?? '') ?>">
      <input type="hidden" name="dur"   value="<?= e($activeDur ?? '') ?>">
      <label for="sort" class="muted">Sort</label>
      <select id="sort" name="sort">
        <option value="new"   <?= (($sort ?? 'new')==='new'?'selected':'') ?>>Newest</option>
        <option value="title" <?= (($sort ?? '')==='title'?'selected':'') ?>>Title A–Z</option>
      </select>
      <button class="btn btn-outline btn-sm" type="submit">Apply</button>
    </form>
  </div>

  <div class="courses-layout">
    <!-- Sidebar filters -->
    <aside class="filter-card card" aria-label="Filter courses">
      <form method="get" action="<?= app_url('courses') ?>">
        <!-- always reset page when applying filters -->
        <input type="hidden" name="page" value="1">

        <h3 style="margin:0 0 10px;">Filter by</h3>

        <!-- Optional: quick search in sidebar -->
        <div class="filter-group">
          <div class="filter-title">Search</div>
          <input type="text" name="q" value="<?= e($query ?? '') ?>" placeholder="Search courses…">
        </div>

        <div class="filter-group">
          <div class="filter-title">Topic area</div>
          <select name="cat">
            <option value="">All categories</option>
            <?php $fixedCats = ['Farming','Digital Skills','Soft Skills']; ?>
            <?php foreach($fixedCats as $cat): ?>
              <option value="<?= e($cat) ?>" <?= (($activeCat ?? '')===$cat ? 'selected':'') ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if(!empty($sectors ?? []) && (($activeCat ?? '') === 'Farming')): ?>
        <div class="filter-group">
          <div class="filter-title">Course sector</div>
          <select name="sector">
            <option value="">All sectors</option>
            <?php foreach($sectors as $s): ?>
              <option value="<?= e($s) ?>" <?= (($activeSector ?? '')===$s ? 'selected':'') ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="filter-group">
          <div class="filter-title">Duration</div>
          <div class="radio-list">
            <label class="radio">
              <input type="radio" name="dur" value=""     <?= (($activeDur ?? '')==='')     ? 'checked' : '' ?>><span>Any</span>
            </label>
            <label class="radio">
              <input type="radio" name="dur" value="lt1h" <?= (($activeDur ?? '')==='lt1h') ? 'checked' : '' ?>><span>Less than 1 hour</span>
            </label>
            <label class="radio">
              <input type="radio" name="dur" value="1-3h" <?= (($activeDur ?? '')==='1-3h') ? 'checked' : '' ?>><span>1–3 hours</span>
            </label>
            <label class="radio">
              <input type="radio" name="dur" value="gt3h" <?= (($activeDur ?? '')==='gt3h') ? 'checked' : '' ?>><span>More than 3 hours</span>
            </label>
          </div>
        </div>

        <?php 
          $levelOptions = !empty($levels ?? []) ? $levels : ['Beginner','Intermediate','Advanced'];
        ?>
        <div class="filter-group">
          <div class="filter-title">Level</div>
          <select name="level">
            <option value="">All levels</option>
            <?php foreach($levelOptions as $lv): ?>
              <option value="<?= e($lv) ?>" <?= (($activeLevel ?? '')===$lv ? 'selected':'') ?>><?= e($lv) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-actions">
          <button class="btn" type="submit">Apply</button>
          <a class="btn btn-outline" href="<?= app_url('courses') ?>">Clear</a>
        </div>
      </form>
    </aside>

    <!-- Results -->
    <div class="courses-list">
      <!-- Consolidated Farming Pathway -->
      <?php
        // Featured pathway totals (minutes)
        $farmingMins = 360;   // 6h 0m
        $softMins    = 300;   // 5h 0m
        $dur = $activeDur ?? '';
        $lvl = $activeLevel ?? '';
        $inBucket = function($mins) use ($dur){
          if($dur==='') return true;                  // Any
          if($dur==='lt1h') return $mins < 60;        // < 1h
          if($dur==='1-3h') return $mins >= 60 && $mins <= 180; // 1–3h
          if($dur==='gt3h') return $mins > 180;       // > 3h
          return true;
        };
        $levelMatch = function($courseLevel) use ($lvl){
          if($lvl==='') return true; // All levels
          return strcasecmp($lvl, $courseLevel) === 0;
        };
        $cat = $activeCat ?? '';
        $catMatchF = ($cat === '' || $cat === 'Farming');
        $catMatchS = ($cat === '' || $cat === 'Soft Skills');
        $showFarming = $catMatchF && $inBucket($farmingMins) && $levelMatch('Advanced');
        $showSoft    = $catMatchS && $inBucket($softMins)    && $levelMatch('Intermediate');
      ?>
      <?php if((empty($query) || !empty($forceFeatured)) && ($showFarming || $showSoft)): ?>
      <section class="card" style="margin-bottom:14px;">
        <h2 style="margin:0 0 10px;">Featured Pathways</h2>
        <ul class="grid cards" style="list-style:none; padding:0; margin:0; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); display:grid; gap:12px;">
          <?php if($showFarming): ?>
          <li class="card course" style="display:flex;flex-direction:column;gap:10px;">
            <div class="course-chip">Farming</div>
            <?php $fimg = app_url('assets/img/farming.png'); ?>
            <div class="course-banner" style="background-image:url('<?= $fimg ?>'); background-size:cover; background-position:center; border-radius:10px; height:120px;"></div>
            <h3 style="margin:4px 0 6px;">Farming</h3>
            <p class="muted" style="margin:0 0 10px;">Learn sustainable agriculture, soil science, farm management, pest and disease management, and organic farming — a cohesive pathway into modern farming best practices.</p>
            <div class="meta" style="display:flex;gap:12px;align-items:center;justify-content:space-between;">
              <div class="muted" style="display:flex;gap:12px;">
                <small>5 modules</small>
                <small>6h 0m total</small>
              </div>
              <div>
                <a class="btn btn-outline" href="<?= app_url('courses/farming') ?>">View details</a>
              </div>
            </div>
          </li>
          <?php endif; ?>
          <?php if($showSoft): ?>
          <li class="card course" style="display:flex;flex-direction:column;gap:10px;">
            <div class="course-chip">Soft Skills</div>
            <?php $simg = app_url('assets/img/Softskill.jpg'); ?>
            <div class="course-banner" style="background-image:url('<?= $simg ?>'); background-size:cover; background-position:center; border-radius:10px; height:120px;"></div>
            <h3 style="margin:4px 0 6px;">Soft Skills</h3>
            <p class="muted" style="margin:0 0 10px;">Build core professional soft skills: communication, teamwork, problem-solving, time management, and leadership — a practical toolkit for the workplace.</p>
            <div class="meta" style="display:flex;gap:12px;align-items:center;justify-content:space-between;">
              <div class="muted" style="display:flex;gap:12px;">
                <small>5 modules</small>
                <small>5h 0m total</small>
              </div>
              <div>
                <a class="btn btn-outline" href="<?= app_url('courses/softskills') ?>">View details</a>
              </div>
            </div>
          </li>
          <?php endif; ?>
        </ul>
      </section>
      <?php endif; ?>

      <?php if(!empty($courses)): ?>
        <ul class="grid cards" style="margin-top:0; list-style:none; padding:0;">
          <?php foreach($courses as $c): ?>
            <li class="card course" style="display:flex;flex-direction:column;gap:10px;padding:16px;">
              <h3 style="margin:0;"><?= e($c['courseTitle']) ?></h3>
              <div style="display:flex;justify-content:flex-end;">
                <a class="btn btn-outline" href="<?= app_url('courses/view') ?>?id=<?= e($c['courseID']) ?>">View details</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

        <!-- Pagination -->
        <?php if(($pages ?? 1) > 1): ?>
          <?php
            // helper to build links keeping current filters
            $qs = $_GET;
            $current = (int)($page ?? 1);
            $last    = (int)$pages;
            $link = function(int $p) use ($qs){
              $qs['page'] = $p;
              return app_url('courses').'?' . http_build_query($qs);
            };
          ?>
          <nav class="pagination" aria-label="Pagination" style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
            <!-- First / Prev -->
            <a class="btn btn-outline" href="<?= $link(1) ?>"        <?= $current<=1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>« First</a>
            <a class="btn btn-outline" href="<?= $link(max(1,$current-1)) ?>" <?= $current<=1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>‹ Prev</a>

            <!-- Pages -->
            <?php
              // compact window around current
              $start = max(1, $current - 2);
              $end   = min($last, $current + 2);
              if ($start > 1) echo '<span class="muted" style="align-self:center">…</span>';
              for($p=$start; $p<=$end; $p++):
            ?>
              <a href="<?= $link($p) ?>" class="btn <?= $p===$current ? '' : 'btn-outline' ?>" aria-current="<?= $p===$current?'page':'false' ?>">
                <?= $p ?>
              </a>
            <?php endfor;
              if ($end < $last) echo '<span class="muted" style="align-self:center">…</span>';
            ?>

            <!-- Next / Last -->
            <a class="btn btn-outline" href="<?= $link(min($last,$current+1)) ?>" <?= $current>=$last ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next ›</a>
            <a class="btn btn-outline" href="<?= $link($last) ?>"      <?= $current>=$last ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Last »</a>
          </nav>
        <?php endif; ?>

      <?php else: ?>
        <?php if(!$showFarming && !$showSoft): ?>
          <p class="muted">No courses found.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
