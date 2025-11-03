<section class="container py-6">
  <?php Auth::requireRole(['learner']); ?>
  <h1 style="margin:0 0 10px;">My Certificates</h1>
  <?php if(!empty($_SESSION['flash'])): ?><div class="alert"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>

  <form method="get" class="card" style="display:grid;grid-template-columns:1fr 180px 220px auto;gap:10px;align-items:end;">
    <label>Search
      <input type="text" name="q" placeholder="Course title" value="<?= e($q ?? '') ?>">
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

  <div class="card" style="padding:0; overflow:auto; margin-top:10px;">
    <table style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Course</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Issued</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Grade</th>
          <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach(($rows ?? []) as $r): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($r['courseTitle'] ?? ('Course #'.($r['courseID']??''))) ?></strong></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['dateIssued'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><span class="pill sm"><?= ucfirst(e($r['certStatus'] ?? '')) ?></span></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($r['grade'] ?? '') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <?php if(!empty($r['file_path'])): ?><a class="btn btn-outline btn-sm" target="_blank" href="<?= e($r['file_path']) ?>">Download</a><?php endif; ?>
              <a class="btn btn-outline btn-sm" href="<?= app_url('learner/certificates/print') ?>?id=<?= e($r['certID']) ?>" target="_blank">View/Print</a>
              <?php $salt = ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? 'evtp'); $data=($r['certID']??'').'|'.($r['learnerID']??'').'|'.($r['courseID']??'').'|'.($r['dateIssued']??''); $code=substr(hash('sha256', $salt.'|'.$data),0,16); $verifyUrl = app_url('verify/certificate').'?code='.rawurlencode($code).'&id='.rawurlencode($r['certID']); ?>
              <a class="btn btn-outline btn-sm" href="<?= e($verifyUrl) ?>" target="_blank">Verify</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
