<section class="container py-6">
  <h1 style="margin:0 0 10px;">Community</h1>

  <form method="get" action="<?= app_url('community') ?>" class="card" style="padding:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <input type="text" name="q" placeholder="Search threads" value="<?= e($q ?? '') ?>" style="flex:1;min-width:240px;">
    <label>Group
      <?php $groups=[''=>'All','General'=>'General','Course help'=>'Course help','Career advice'=>'Career advice','Internships'=>'Internships','Announcements'=>'Announcements']; ?>
      <select name="group" onchange="this.form.submit()">
        <?php foreach($groups as $gval=>$gname): ?>
          <option value="<?= e($gval) ?>" <?= ($group ?? '')===$gval?'selected':'' ?>><?= e($gname) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <a class="btn" href="<?= app_url('community/create') ?>">Create thread</a>
    <?php if(Auth::check() && (Auth::user()['role'] ?? '')==='admin'): ?>
      <?php $showAll = !empty($showAll); ?>
      <?php if($showAll): ?>
        <a class="btn btn-outline" href="<?= app_url('community') ?>?q=<?= urlencode($q ?? '') ?>&group=<?= urlencode($group ?? '') ?>">Hide hidden</a>
      <?php else: ?>
        <a class="btn btn-outline" href="<?= app_url('community') ?>?q=<?= urlencode($q ?? '') ?>&group=<?= urlencode($group ?? '') ?>&show=all">Show hidden</a>
      <?php endif; ?>
    <?php endif; ?>
  </form>

  <?php if(!empty($posts)): ?>
    <ul style="list-style:none;margin:12px 0 0;padding:0;display:flex;flex-direction:column;gap:8px;">
      <?php foreach($posts as $p): $isPinned = in_array($p['postID'], ($pinned ?? []), true); $isHidden = in_array($p['postID'], ($hiddenIds ?? []), true); ?>
        <li class="card" style="padding:12px;display:flex;gap:10px;align-items:flex-start;justify-content:space-between;">
          <div style="min-width:0;">
            <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?php if($isPinned): ?><span class="pill sm">Pinned</span> <?php endif; ?>
              <?php if($isHidden): ?><span class="pill sm">Hidden</span> <?php endif; ?>
              <a href="<?= app_url('community/thread') ?>?id=<?= e($p['postID']) ?>"><?= e($p['forumTitle']) ?></a>
            </div>
            <div class="muted" style="font-size:12px;"><?= e($p['timestamp']) ?> · Replies <?= (int)($p['replies'] ?? 0) ?></div>
            <div class="muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:900px;"><?= e(mb_strimwidth(strip_tags((string)$p['postContent']),0,180,'…')) ?></div>
          </div>
          <div style="display:flex;gap:6px;align-items:center;">
            <a class="btn btn-outline btn-sm" href="<?= app_url('community/thread') ?>?id=<?= e($p['postID']) ?>">Open</a>
            <?php if(Auth::check() && (Auth::user()['role'] ?? '')==='admin' && $isHidden): ?>
              <a class="btn btn-outline btn-sm" href="<?= app_url('community/mod') ?>?t=thread&i=<?= e($p['postID']) ?>&act=uh">Unhide</a>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="muted" style="margin-top:12px;">No threads found.</p>
  <?php endif; ?>
</section>
