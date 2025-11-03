<section class="container py-6">
  <?php Auth::requireRole(['employer']); ?>
  <h1 style="margin:0 0 10px;">Applicants — <?= e($jobTitle) ?></h1>

  <form method="get" action="<?= app_url('employer/jobs/applicants') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    <input type="hidden" name="job" value="<?= e($jobID) ?>">
    <label>Status
      <select name="status" onchange="this.form.submit()">
        <?php foreach(['','Applied','Under Review','Interview','Hired','Rejected'] as $s): ?>
          <option value="<?= $s ?>" <?= ($status ?? '')===$s?'selected':'' ?>><?= $s===''?'All':$s ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <a class="btn btn-outline" href="<?= app_url('employer/jobs') ?>">Back to Jobs</a>
  </form>

  <?php if(!empty($apps)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Candidate</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Applied</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Status</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($apps as $a): ?>
            <tr>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($a['learnerName']) ?></strong></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['learnerEmail']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['applicationDate'] ?: '—') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($a['appStatus']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <form method="post" action="<?= app_url('employer/applications/status') ?>" style="display:inline-flex;gap:6px;align-items:center;">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="appID" value="<?= e($a['appID']) ?>">
                  <input type="hidden" name="job" value="<?= e($jobID) ?>">
                  <select name="status">
                    <?php foreach(['Applied','Under Review','Interview','Hired','Rejected'] as $s): ?>
                      <option value="<?= $s ?>" <?= $a['appStatus']===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline btn-sm" type="submit">Update</button>
                </form>
              </td>
            </tr>
            <?php if(!empty($a['coverText'])): ?>
              <tr>
                <td colspan="5" style="padding:10px 10px 14px;border-bottom:1px solid var(--border)"><small class="muted">Cover:</small><br><?= nl2br(e($a['coverText'])) ?></td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No applicants found.</p>
  <?php endif; ?>
</section>
