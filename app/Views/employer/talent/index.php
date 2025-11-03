<section class="container py-6">
  <?php Auth::requireRole(['employer']); $eid = Auth::user()['id']; ?>
  <h1 style="margin:0 0 10px;">Talent Search</h1>

  <form method="get" action="<?= app_url('employer/talent') ?>" class="card" style="padding:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" placeholder="Name or email contains" value="<?= e($q ?? '') ?>" style="flex:1;min-width:220px;">
    <input type="text" name="course" placeholder="Course title contains" value="<?= e($course ?? '') ?>" style="flex:1;min-width:220px;">
    <label class="muted">Min completed
      <select name="min_completed">
        <?php $mc = (int)($minCompleted ?? 0); foreach([0,1,2,3,5,10] as $v): ?>
          <option value="<?= $v ?>" <?= $v===$mc?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="muted" style="display:flex;gap:6px;align-items:center;">
      <input type="checkbox" name="shortlisted" value="1" <?= !empty($onlyShortlisted)?'checked':'' ?>> Shortlisted only
    </label>
    <button class="btn btn-outline" type="submit">Search</button>
  </form>

  <?php if(!empty($rows)): ?>
    <div class="card" style="padding:0; overflow:auto;margin-top:10px;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Learner</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Completed</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $l): $lid = $l['learnerID']; $in = !empty($shortlist[$lid]); ?>
            <tr <?= $in ? 'style="background:rgba(34,197,94,0.06)"' : '' ?>>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><a href="<?= app_url('employer/talent/profile') ?>?learner=<?= e($lid) ?>"><?= e($l['learnerName']) ?></a></strong></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($l['learnerEmail']) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)($l['completed'] ?? 0) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                <form method="post" action="<?= app_url('employer/talent/shortlist') ?>" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="learnerID" value="<?= e($lid) ?>">
                  <input type="hidden" name="action" value="<?= $in ? 'remove' : 'add' ?>">
                  <button type="submit" class="btn btn-outline btn-sm"><?= $in ? 'Remove' : 'Shortlist' ?></button>
                </form>
                <a class="btn btn-outline btn-sm" href="mailto:<?= e($l['learnerEmail']) ?>?subject=Opportunity at <?= urlencode($_SESSION['user']['name'] ?? 'Employer') ?>">Message</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted" style="margin-top:10px;">No learners match your filters yet.</p>
  <?php endif; ?>
</section>
