<section class="container py-6">
  <h1 style="margin:0 0 10px;">Certificate Verification</h1>
  <?php if(($state ?? '')==='missing'): ?>
    <div class="alert">Missing certificate ID.</div>
  <?php elseif(($state ?? '')==='notfound'): ?>
    <div class="alert">Certificate not found.</div>
  <?php elseif(($state ?? '')==='invalid'): ?>
    <div class="alert">Invalid verification code.</div>
  <?php elseif(($state ?? '')==='valid'): $c=$cert ?? []; ?>
    <div class="card" style="padding:16px;">
      <div><strong>Certificate #<?= e($c['certID']) ?></strong></div>
      <div class="muted">Learner: <?= e($c['learnerName'] ?? ('#'.$c['learnerID'])) ?></div>
      <div class="muted">Course: <?= e($c['courseTitle'] ?? ('#'.$c['courseID'])) ?></div>
      <div class="muted">Issued: <?= e($c['dateIssued']) ?></div>
      <div class="muted">Status: <?= ucfirst(e($c['certStatus'])) ?></div>
      <div style="margin-top:10px;">
        <a class="btn" href="<?= app_url('admin/certificates/print') ?>?id=<?= e($c['certID']) ?>" target="_blank">Print view</a>
      </div>
    </div>
  <?php endif; ?>
</section>
