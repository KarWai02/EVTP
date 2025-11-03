<?php Auth::requireRole(['admin']); $c=$cert??[]; $code=$code??''; $verifyUrl=$verifyUrl??'#'; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Certificate #<?= e($c['certID'] ?? '') ?></title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial;background:#fff;color:#111;margin:0;padding:24px;}
    .cert{border:6px double #333;padding:24px;max-width:900px;margin:0 auto;background:#fafafa;}
    .hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
    .qr{width:110px;height:110px;border:1px solid #ddd;display:flex;align-items:center;justify-content:center;background:#fff}
    .muted{color:#666}
    .title{font-size:28px;font-weight:800;margin:8px 0}
    .big{font-size:22px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
    .row{display:flex;justify-content:space-between}
    .btn{display:inline-block;padding:8px 12px;border:1px solid #333;text-decoration:none;color:#111;border-radius:8px}
    @media print{ .noprint{display:none} body{padding:0} .cert{border:none} }
  </style>
</head>
<body>
  <div class="noprint" style="display:flex;gap:8px;justify-content:flex-end;max-width:900px;margin:0 auto 10px;">
    <a class="btn" href="#" onclick="window.print();return false;">Print</a>
    <a class="btn" target="_blank" href="<?= e($verifyUrl) ?>">Verify Online</a>
  </div>
  <div class="cert">
    <div class="hdr">
      <div>
        <div class="muted">Certificate ID</div>
        <div class="big"><strong>#<?= e($c['certID'] ?? '') ?></strong></div>
      </div>
      <div class="qr">
        <?php $qrContent = urlencode($verifyUrl); $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=110x110&data='.$qrContent; ?>
        <img src="<?= $qrSrc ?>" alt="QR">
      </div>
    </div>
    <div class="title">Certificate of Completion</div>
    <p>This certifies that <strong><?= e($c['learnerName'] ?? ('Learner #'.($c['learnerID']??''))) ?></strong> has successfully completed the course <strong><?= e($c['courseTitle'] ?? ('Course #'.($c['courseID']??''))) ?></strong>.</p>
    <div class="grid">
      <div class="row"><span class="muted">Issued on</span><strong><?= e($c['dateIssued'] ?? '') ?></strong></div>
      <div class="row"><span class="muted">Status</span><strong><?= ucfirst(e($c['certStatus'] ?? '')) ?></strong></div>
      <div class="row"><span class="muted">Grade</span><strong><?= e($c['grade'] ?? '-') ?></strong></div>
      <div class="row"><span class="muted">Verification code</span><strong><?= e($code) ?></strong></div>
    </div>
    <?php if(!empty($c['file_path'])): ?>
      <p class="muted" style="margin-top:10px;">Original file: <a href="<?= e($c['file_path']) ?>" target="_blank">Download</a></p>
    <?php endif; ?>
    <p class="muted" style="margin-top:10px;">Verify online at: <?= e($verifyUrl) ?></p>
  </div>
</body>
</html>
