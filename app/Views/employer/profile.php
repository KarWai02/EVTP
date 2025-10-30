<section class="container py-6">
  <?php Auth::requireRole(['employer']); ?>
  <h1 style="margin:0 0 12px;">Company Profile</h1>

  <div class="grid" style="display:grid;grid-template-columns:1.2fr 1fr;gap:16px;align-items:start;">
    <div class="card" style="padding:16px;">
      <h2 style="margin:0 0 10px;">Company details</h2>
      <div style="display:grid;gap:8px;">
        <div><strong>Name:</strong> <?= e($emp['companyName'] ?? '') ?></div>
        <div><strong>Industry:</strong> <?= e($emp['companyIndustry'] ?? '-') ?></div>
        <div><strong>Contact person:</strong> <?= e($emp['contactPerson'] ?? ($_SESSION['user']['name'] ?? '')) ?></div>
        <div><strong>Email:</strong> <?= e($emp['employerEmail'] ?? ($_SESSION['user']['email'] ?? '')) ?></div>
        <div><strong>Phone:</strong> <?= e($emp['companyPhone'] ?? '-') ?></div>
      </div>
      <div style="margin-top:12px;">
        <a class="btn btn-outline" href="<?= app_url('dashboard') ?>">Back to dashboard</a>
      </div>
    </div>

    <aside class="card" style="padding:16px;">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <h2 style="margin:0;">Shortlisted candidates</h2>
        <a class="btn btn-outline btn-sm" href="<?= app_url('employer/talent') ?>?shortlisted=1">Manage</a>
      </div>
      <?php if(!empty($shortRows)): ?>
        <div style="margin-top:10px; overflow:auto;">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Name</th>
                <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Email</th>
                <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($shortRows as $s): ?>
                <tr>
                  <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($s['learnerName']) ?></strong></td>
                  <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($s['learnerEmail']) ?></td>
                  <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
                    <form method="post" action="<?= app_url('employer/talent/shortlist') ?>" style="display:inline;">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="learnerID" value="<?= e($s['learnerID']) ?>">
                      <input type="hidden" name="action" value="remove">
                      <button class="btn btn-outline btn-sm" type="submit">Remove</button>
                    </form>
                    <a class="btn btn-outline btn-sm" href="mailto:<?= e($s['learnerEmail']) ?>?subject=Opportunity at <?= urlencode($_SESSION['user']['name'] ?? 'Employer') ?>">Message</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="muted" style="margin:10px 0 0;">No shortlisted candidates yet.</p>
      <?php endif; ?>
    </aside>
  </div>
</section>
