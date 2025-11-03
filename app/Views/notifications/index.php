<section class="container py-6">
  <?php if(!Auth::check()) redirect('login'); ?>
  <div class="flex-between" style="margin-bottom:10px;align-items:center;gap:10px;">
    <h1 style="margin:0;">Notifications</h1>
    <form method="post" action="<?= app_url('notifications/read-all') ?>" style="display:inline;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <button class="btn btn-outline btn-sm" type="submit">Mark all as read</button>
    </form>
  </div>
  <div class="card" style="padding:0; overflow:auto;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Message</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Date</th>
          <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach(($rows ?? []) as $n): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($n['title']) ?></strong></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($n['body']) ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($n['created_at'] ?: date('Y-m-d H:i:s')) ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <?php if(!empty($n['url'])): ?><a class="btn btn-outline btn-sm" href="<?= e($n['url']) ?>">Open</a><?php endif; ?>
              <?php if(empty($n['read_at'])): ?>
              <form method="post" action="<?= app_url('notifications/read') ?>" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= e($n['id']) ?>">
                <button class="btn btn-outline btn-sm" type="submit">Mark as read</button>
              </form>
              <?php else: ?>
                <span class="muted">Read</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
