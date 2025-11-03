<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <div class="flex-between" style="margin-bottom:8px;align-items:center;gap:10px;">
    <h1 style="margin:0;">Community Reports</h1>
    <form method="post" action="<?= app_url('community/reports/clean') ?>" style="display:inline;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <button class="btn btn-outline btn-sm" type="submit">Clean removed content</button>
    </form>
  </div>
  <p class="muted" style="margin:0 0 12px;">Newest first. Use Resolve to mark reviewed/dismissed. Use Moderate to take action on content.</p>

  <?php if(empty($items)): ?>
    <p class="muted">No reports.</p>
  <?php else: ?>
    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
      <?php foreach($items as $r): ?>
        <li class="card" style="padding:12px;display:grid;grid-template-columns: 1fr auto;gap:10px;align-items:center;">
          <div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <span class="pill sm"><?= e(strtoupper($r['type'] ?? '')) ?></span>
              <?php if(($r['status'] ?? 'open')!=='open'): ?>
                <span class="pill sm"><?= e(ucfirst($r['status'])) ?></span>
              <?php endif; ?>
              <span class="muted"><?= e($r['at'] ?? '') ?></span>
              <span class="muted">by <?= e(($r['prettyBy'] ?? '') ?: ($r['by'] ?? 'guest')) ?></span>
            </div>
            <div style="margin-top:6px;font-weight:600;">
              <?php if(($r['type'] ?? '')==='thread'): ?>
                <a href="<?= e($r['thread_link'] ?? (app_url('community/thread').'?id='.urlencode($r['id'] ?? ''))) ?>" target="_blank"><?= e($r['title'] ?? $r['id']) ?></a>
              <?php else: ?>
                <div>Reply: <?= e($r['snippet'] ?? ('Message '.$r['id'])) ?></div>
                <div class="muted">Thread: <a href="<?= e($r['thread_link'] ?? app_url('community')) ?>" target="_blank"><?= e($r['title'] ?? '') ?></a></div>
              <?php endif; ?>
            </div>
            <div class="muted" style="margin-top:4px;">Reason: <?= e($r['reason'] ?? 'inappropriate') ?></div>
          </div>
          <div style="display:flex;gap:6px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
            <form method="post" action="<?= app_url('community/reports/resolve') ?>">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="rid" value="<?= e($r['rid']) ?>">
              <select name="action">
                <option value="reviewed">Mark reviewed</option>
                <option value="dismiss">Dismiss</option>
              </select>
              <button class="btn btn-outline btn-sm" type="submit">Resolve</button>
            </form>
            <?php if(($r['type'] ?? '')==='thread'): ?>
              <form method="get" action="<?= app_url('community/mod') ?>">
                <input type="hidden" name="t" value="thread">
                <input type="hidden" name="i" value="<?= e($r['id']) ?>">
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
            <?php else: ?>
              <form method="get" action="<?= app_url('community/mod') ?>">
                <input type="hidden" name="t" value="message">
                <input type="hidden" name="i" value="<?= e($r['id']) ?>">
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
  <?php endif; ?>
</section>
