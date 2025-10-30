<section class="container py-6">
  <!-- Page title -->
  <header class="section-head" style="margin-bottom:12px;display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;justify-content:space-between;">
    <div>
      <h1 style="margin:0">Courses</h1>
      <?php if(!empty($activeCat)): ?>
        <span class="pill" style="margin-top:6px;display:inline-block">Category: <?= e($activeCat) ?></span>
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
            <?php foreach(($categories ?? []) as $cat): ?>
              <option value="<?= e($cat) ?>" <?= (($activeCat ?? '')===$cat ? 'selected':'') ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if(!empty($sectors ?? [])): ?>
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

        <?php if(!empty($levels ?? [])): ?>
        <div class="filter-group">
          <div class="filter-title">Level</div>
          <select name="level">
            <option value="">All levels</option>
            <?php foreach($levels as $lv): ?>
              <option value="<?= e($lv) ?>" <?= (($activeLevel ?? '')===$lv ? 'selected':'') ?>><?= e($lv) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="filter-actions">
          <button class="btn" type="submit">Apply</button>
          <a class="btn btn-outline" href="<?= app_url('courses') ?>">Clear</a>
        </div>
      </form>
    </aside>

    <!-- Results -->
    <div class="courses-list">
      <?php if(!empty($courses)): ?>
        <ul class="grid cards" style="margin-top:0; list-style:none; padding:0;">
          <?php foreach($courses as $c): ?>
            <li class="card course" style="display:flex;flex-direction:column;gap:10px;">
              <div class="course-chip"><?= e($c['category'] ?: 'General') ?></div>
              <div class="course-banner"></div>
              <h3 style="margin:4px 0 6px;"><?= e($c['courseTitle']) ?></h3>
              <p class="muted" style="margin:0 0 10px;"><?= e(mb_strimwidth($c['description'] ?? '',0,160,'…')) ?></p>
              <div class="meta">
                <small>Created: <?= e($c['createdDate'] ?? '') ?></small>
                <a class="btn btn-outline" href="<?= app_url('courses/view') ?>?id=<?= e($c['courseID']) ?>">View</a>
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
        <p class="muted">No courses found. Try adjusting filters or <a href="<?= app_url('courses') ?>">reset</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
