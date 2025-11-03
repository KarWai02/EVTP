<section class="container py-6">
  <?php Auth::requireRole(['admin']); $mode=$mode??'create'; $isEdit=$mode==='edit'; ?>
  <h1 style="margin:0 0 10px;"><?= $isEdit?'Edit':'Issue' ?> Certificate</h1>
  <form method="post" action="<?= $isEdit?app_url('admin/certificates/update'):app_url('admin/certificates/store') ?>" enctype="multipart/form-data" class="card" style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <?php if($isEdit): ?><input type="hidden" name="certID" value="<?= e($cert['certID'] ?? '') ?>"><?php endif; ?>

    <label>Learner
      <select name="learnerID" required>
        <?php foreach(($learners ?? []) as $l): ?>
          <option value="<?= e($l['learnerID']) ?>" <?= (($cert['learnerID'] ?? '')==$l['learnerID'])?'selected':'' ?>><?= e($l['learnerName']) ?> (<?= e($l['learnerEmail']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Course
      <select name="courseID" required>
        <?php foreach(($courses ?? []) as $c): ?>
          <option value="<?= e($c['courseID']) ?>" <?= (($cert['courseID'] ?? '')==$c['courseID'])?'selected':'' ?>><?= e($c['courseTitle']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Date Issued
      <input type="date" name="dateIssued" value="<?= e(($cert['dateIssued'] ?? date('Y-m-d'))) ?>" required>
    </label>

    <label>Status
      <select name="certStatus">
        <?php foreach(($statuses ?? ['issued','pending','updated','deleted']) as $s): ?>
          <option value="<?= e($s) ?>" <?= (($cert['certStatus'] ?? 'issued')===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Grade
      <input type="text" name="grade" value="<?= e($cert['grade'] ?? '') ?>">
    </label>

    <label>Upload Certificate (PDF/Image)
      <input type="file" name="certificate" accept=".pdf,.png,.jpg,.jpeg,.webp">
    </label>

    <div style="grid-column:1/-1;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('admin/certificates') ?>">Cancel</a>
    </div>
  </form>
</section>
