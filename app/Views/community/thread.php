<section class="container py-6">
  <?php if(empty($post)): ?><p class="muted">Thread not found.</p><?php return; endif; ?>
  <?php $isPinned = !empty($pinned); $isLocked = !empty($locked); ?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <h1 style="margin:0;"><?= e($post['forumTitle']) ?></h1>
    <div style="display:flex;gap:8px;align-items:center;">
      <?php if($isPinned): ?><span class="pill sm">Pinned</span><?php endif; ?>
      <a class="btn btn-outline btn-sm" href="<?= app_url('community/flag') ?>?type=thread&id=<?= e($post['postID']) ?>&reason=inappropriate">Report</a>
      <?php if(Auth::check() && (Auth::user()['role'] ?? '')==='admin'): ?>
        <form method="get" action="<?= app_url('community/mod') ?>" style="display:inline;">
          <input type="hidden" name="t" value="thread">
          <input type="hidden" name="i" value="<?= e($post['postID']) ?>">
          <select name="act">
            <option value="">Moderate…</option>
            <option value="p">Pin</option>
            <option value="up">Unpin</option>
            <option value="l">Lock</option>
            <option value="ul">Unlock</option>
            <option value="h">Hide</option>
            <option value="uh">Unhide</option>
            <option value="d">Delete</option>
          </select>
          <button class="btn btn-outline btn-sm" type="submit">Apply</button>
        </form>
        <?php if(!empty($showAll)): ?>
          <a class="btn btn-outline btn-sm" href="<?= app_url('community/thread') ?>?id=<?= e($post['postID']) ?>">Hide hidden</a>
        <?php else: ?>
          <a class="btn btn-outline btn-sm" href="<?= app_url('community/thread') ?>?id=<?= e($post['postID']) ?>&show=all">Show hidden</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="padding:16px;margin:12px 0;">
    <div class="muted" style="margin-bottom:8px;">Posted <?= e($post['timestamp']) ?> by <?= e(($post['name'] ?? '') !== '' ? $post['name'] : 'Anonymous') ?></div>
    <div><?= nl2br(e($post['postContent'])) ?></div>
  </div>

  <h3>Replies (<?= count($msgs ?? []) ?>)</h3>
  <?php if(!empty($msgs)): ?>
    <ul style="list-style:none;margin:8px 0 0;padding:0;display:flex;flex-direction:column;gap:8px;">
      <?php foreach($msgs as $m): ?>
        <li id="msg-<?= e($m['msgID']) ?>" class="card" style="padding:12px;display:flex;gap:10px;justify-content:space-between;align-items:flex-start;">
          <div style="min-width:0;">
            <div class="muted" style="margin-bottom:6px;"><?= e($m['msgTimestamp']) ?> · <?= e(($m['msgName'] ?? '') !== '' ? $m['msgName'] : 'Anonymous') ?></div>
            <div><?= nl2br(e($m['msgContent'])) ?></div>
          </div>
          <div style="display:flex;gap:6px;align-items:center;">
            <a class="btn btn-outline btn-sm" href="<?= app_url('community/flag') ?>?type=message&id=<?= e($m['msgID']) ?>&reason=inappropriate">Report</a>
            <?php if(Auth::check() && (Auth::user()['role'] ?? '')==='admin'): ?>
            <form method="get" action="<?= app_url('community/mod') ?>" style="display:inline;">
              <input type="hidden" name="t" value="message">
              <input type="hidden" name="i" value="<?= e($m['msgID']) ?>">
              <select name="act">
                <option value="">Moderate…</option>
                <option value="h">Hide</option>
                <option value="uh">Unhide</option>
                <option value="d">Delete</option>
              </select>
              <button class="btn btn-outline btn-sm" type="submit">Apply</button>
            </form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="muted">No replies yet.</p>
  <?php endif; ?>

  <div class="card" style="padding:16px;margin-top:12px;">
    <?php if($isLocked): ?>
      <p class="muted">This thread is locked. No new replies.</p>
    <?php else: ?>
      <h3 style="margin:0 0 8px;">Add a reply</h3>
      <form method="post" action="<?= app_url('community/reply') ?>" enctype="multipart/form-data" style="display:grid;gap:10px;max-width:820px;">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="post" value="<?= e($post['postID']) ?>">
        <label>Message
          <textarea name="body" rows="5" required></textarea>
        </label>
        <label>Image (optional)
          <input type="file" name="image" accept="image/png,image/jpeg">
          <small class="muted">PNG/JPG up to 2MB</small>
        </label>
        <?php if(!Auth::check()): ?>
          <div class="card" style="padding:12px;">
            <div class="muted" style="margin-bottom:8px;">Replying as guest (optional name/email)</div>
            <label>Name
              <input type="text" name="name" placeholder="Anonymous">
            </label>
            <label>Email
              <input type="email" name="email" placeholder="(optional)">
            </label>
          </div>
        <?php endif; ?>
        <div>
          <button class="btn" type="submit">Reply</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
