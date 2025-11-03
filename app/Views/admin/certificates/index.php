<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <h1 style="margin:0 0 10px;">Manage Certificates</h1>
  <?php if(!empty($_SESSION['flash'])): ?><div class="alert"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>

  <form method="get" class="card" style="display:grid;grid-template-columns:1fr 200px 200px auto;gap:10px;align-items:end;">
    <label>Search
      <input type="text" name="q" placeholder="Name, email, course" value="<?= e($q ?? '') ?>">
    </label>
    <label>Status
      <select name="status">
        <option value="">All</option>
        <?php foreach(($statuses ?? []) as $s): ?>
          <option value="<?= e($s) ?>" <?= ($status??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Course
      <select name="course">
        <option value="">All</option>
        <?php foreach(($courses ?? []) as $c): ?>
          <option value="<?= e($c['courseID']) ?>" <?= ($course??'')===$c['courseID']?'selected':'' ?>><?= e($c['courseTitle']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="btn btn-outline" type="submit">Apply</button>
  </form>

  <div style="margin:10px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a class="btn" href="<?= app_url('admin/certificates/create') ?>">Issue Certificate</a>
    <a class="btn" href="<?= app_url('admin/certificates/bulk-issue') ?>">Bulk Issue</a>
  </div>

  <form method="post" id="bulkForm">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="card" style="padding:8px;display:flex;gap:8px;align-items:center;margin-bottom:6px;">
      <strong>Bulk actions:</strong>
      <select name="status" id="bulkStatus">
        <option value="">Set status…</option>
        <?php foreach(($statuses ?? []) as $s): ?>
          <option value="<?= e($s) ?>"><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline btn-sm" type="submit" formaction="<?= app_url('admin/certificates/bulk-status') ?>">Apply Status</button>
      <button class="btn btn-outline btn-sm" type="submit" formaction="<?= app_url('admin/certificates/bulk-revoke') ?>" onclick="return confirm('Revoke selected certificates?');">Revoke Selected</button>
    </div>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="padding:10px;border-bottom:1px solid var(--border)"><input type="checkbox" id="chkAll" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)"></th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Learner</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Date Issued</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Grade</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach(($rows ?? []) as $r): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><input class="rowchk" type="checkbox" name="ids[]" value="<?= e($r['certID']) ?>"></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)">
                <div><strong><?= e($r['learnerName'] ?? ('Learner #'.($r['learnerID']??''))) ?></strong></div>
                <div class="muted" style="font-size:12px;">&ZeroWidthSpace;<?= e($r['learnerEmail'] ?? '') ?></div>
              </td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['courseTitle'] ?? ('Course #'.($r['courseID']??''))) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['dateIssued'] ?? '') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><span class="pill sm"><?= ucfirst(e($r['certStatus'] ?? '')) ?></span></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['grade'] ?? '') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <?php if(!empty($r['file_path'])): ?><a class="btn btn-outline btn-sm" target="_blank" href="<?= e($r['file_path']) ?>">View</a><?php endif; ?>
                <a class="btn btn-outline btn-sm" href="<?= app_url('admin/certificates/print') ?>?id=<?= e($r['certID']) ?>" target="_blank">Print</a>
                <a class="btn btn-outline btn-sm" href="<?= app_url('admin/certificates/edit') ?>?id=<?= e($r['certID']) ?>">Edit</a>
                <form method="post" action="<?= app_url('admin/certificates/delete') ?>" style="display:inline;" onsubmit="return confirm('Revoke (mark deleted) this certificate?');">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="id" value="<?= e($r['certID']) ?>">
                  <button class="btn btn-outline btn-sm" type="submit">Revoke</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</section>
