<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <h1 style="margin:0 0 10px;">Bulk Issue Certificates</h1>
  <form method="post" action="<?= app_url('admin/certificates/bulk-issue') ?>" class="card" style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Course
      <select name="courseID" required>
        <?php foreach(($courses ?? []) as $c): ?>
          <option value="<?= e($c['courseID']) ?>"><?= e($c['courseTitle']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Date Issued
      <input type="date" name="dateIssued" value="<?= e(date('Y-m-d')) ?>" required>
    </label>
    <label>Status
      <select name="certStatus">
        <?php foreach(($statuses ?? ['issued','pending','updated','deleted']) as $s): ?>
          <option value="<?= e($s) ?>"><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Grade (optional)
      <input type="text" name="grade" placeholder="e.g. A+">
    </label>
    <div style="grid-column:1/-1;">
      <label>Select Learners</label>
      <div class="card" style="padding:8px;max-height:320px;overflow:auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:6px;">
        <?php foreach(($learners ?? []) as $l): ?>
          <label class="card" style="display:flex;gap:8px;align-items:center;padding:8px;">
            <input type="checkbox" name="learnerIDs[]" value="<?= e($l['learnerID']) ?>">
            <span><strong><?= e($l['learnerName']) ?></strong><br><small class="muted"><?= e($l['learnerEmail']) ?></small></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="grid-column:1/-1;display:flex;gap:8px;">
      <button class="btn" type="submit">Issue Certificates</button>
      <a class="btn btn-outline" href="<?= app_url('admin/certificates') ?>">Cancel</a>
    </div>
  </form>
</section>
