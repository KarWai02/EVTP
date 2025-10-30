<section class="container py-6">
  <?php Auth::requireRole(['employer']); ?>
  <h1 style="margin:0 0 12px;">Learner Profile</h1>

  <div class="card" style="padding:16px;">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
      <div>
        <div style="font-size:20px;font-weight:800;"><?= e($learner['learnerName']) ?></div>
        <div class="muted">Email: <?= e($learner['learnerEmail']) ?> • Phone: <?= e($learner['learnerPhone'] ?? '-') ?></div>
      </div>
      <div style="display:flex;gap:8px;">
        <form method="post" action="<?= app_url('employer/talent/shortlist') ?>" style="display:inline;">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="learnerID" value="<?= e($learner['learnerID']) ?>">
          <input type="hidden" name="action" value="<?= $inShort ? 'remove' : 'add' ?>">
          <button type="submit" class="btn btn-outline btn-sm"><?= $inShort ? 'Remove from shortlist' : 'Add to shortlist' ?></button>
        </form>
        <a class="btn btn-outline btn-sm" href="mailto:<?= e($learner['learnerEmail']) ?>?subject=Opportunity at <?= urlencode($_SESSION['user']['name'] ?? 'Employer') ?>">Message</a>
        <a class="btn btn-outline btn-sm" href="<?= app_url('employer/talent') ?>">Back</a>
      </div>
    </div>
  </div>

  <div class="card" style="padding:16px;margin-top:12px;">
    <h2 style="margin:0 0 8px;">Recent courses</h2>
    <?php if(!empty($courses)): ?>
      <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
        <?php foreach($courses as $c): ?>
          <li style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
            <div>
              <div style="font-weight:700;"><?= e($c['courseTitle']) ?></div>
              <small class="muted">Enrolled: <?= e($c['enrollDate']) ?> • <?= e($c['completionStatus']) ?></small>
            </div>
            <a class="btn btn-outline btn-sm" href="<?= app_url('courses/view') ?>?id=<?= e($c['courseID']) ?>">View</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted" style="margin:0;">No recent courses.</p>
    <?php endif; ?>
  </div>
</section>
