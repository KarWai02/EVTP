<?php
class AdminCertificatesController {
  private function ensureAdmin(){ Auth::requireRole(['admin']); }

  private function statuses(){ return ['issued','pending','updated','deleted']; }

  private function verifyCode($cert){
    // deterministic verification code based on cert fields and a salt
    $salt = ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? 'evtp');
    $data = ($cert['certID'] ?? '').'|'.($cert['learnerID'] ?? '').'|'.($cert['courseID'] ?? '').'|'.($cert['dateIssued'] ?? '');
    return substr(hash('sha256', $salt.'|'.$data), 0, 16);
  }

  private function createNotification($learnerID, $title, $body, $url=null){
    try{
      $pdo = DB::conn();
      $stmt = $pdo->prepare("INSERT INTO notifications (user_id, role, title, body, url, created_at, read_at) VALUES (?,?,?,?,?,NOW(),NULL)");
      $stmt->execute([$learnerID, 'learner', $title, $body, $url]);
    }catch(\Throwable $e){}
  }

  private function generatePdfIfPossible($certID){
    // Try to render printable HTML to PDF using Dompdf if present
    try{
      if(!class_exists('Dompdf\\Dompdf')){ return null; }
      $pdo = DB::conn();
      $st = $pdo->prepare("SELECT cert.*, l.learnerName, l.learnerEmail, c.courseTitle FROM certificate cert LEFT JOIN Learners l ON l.learnerID=cert.learnerID LEFT JOIN Course c ON c.courseID=cert.courseID WHERE certID=?");
      $st->execute([$certID]); $row = $st->fetch(); if(!$row){ return null; }
      $code = $this->verifyCode($row);
      $verifyUrl = app_url('verify/certificate').'?code='.rawurlencode($code).'&id='.rawurlencode($certID);

      ob_start();
      $cert=$row; $c=$row; $qrContent = urlencode($verifyUrl);
      $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=110x110&data='.$qrContent;
      ?>
      <!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans, sans-serif;color:#111} .wrap{border:6px double #333;padding:20px} .title{font-size:24px;font-weight:700;margin:10px 0}</style></head><body>
      <div class="wrap">
        <div class="title">Certificate of Completion</div>
        <p>Learner: <strong><?= htmlspecialchars($c['learnerName'] ?? ('#'.$c['learnerID'])) ?></strong></p>
        <p>Course: <strong><?= htmlspecialchars($c['courseTitle'] ?? ('#'.$c['courseID'])) ?></strong></p>
        <p>Issued on: <strong><?= htmlspecialchars($c['dateIssued'] ?? '') ?></strong></p>
        <p>Status: <strong><?= htmlspecialchars(ucfirst($c['certStatus'] ?? '')) ?></strong></p>
        <p>Verification: <strong><?= htmlspecialchars($code) ?></strong></p>
        <img src="<?= $qrSrc ?>" width="110" height="110" />
      </div>
      </body></html>
      <?php
      $html = ob_get_clean();
      $dompdf = new \Dompdf\Dompdf([ 'isRemoteEnabled'=>true ]);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4');
      $dompdf->render();
      $pdf = $dompdf->output();
      $dir = dirname(__DIR__,2)."/public/uploads/certificates/".preg_replace('/[^A-Za-z0-9_\-]/','',$certID);
      if(!is_dir($dir)) @mkdir($dir,0775,true);
      $file = $dir.'/certificate.pdf';
      file_put_contents($file, $pdf);
      $public = app_url('uploads/certificates/'.rawurlencode($certID).'/certificate.pdf');
      $pdo->prepare("UPDATE certificate SET file_path=? WHERE certID=?")->execute([$public,$certID]);
      return $public;
    }catch(\Throwable $e){ return null; }
  }

  private function sendEmailIfPossible($toEmail, $toName, $subject, $htmlBody, $attachPath=null){
    try{
      if(!class_exists('PHPMailer\\PHPMailer\\PHPMailer')){ return false; }
      $cfg = $GLOBALS['APP_CONFIG']['mail'] ?? [];
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
      if(($cfg['transport'] ?? 'smtp')==='smtp'){
        $mail->isSMTP();
        $mail->Host = $cfg['host'] ?? 'smtp.gmail.com';
        $mail->Port = $cfg['port'] ?? 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = $cfg['encryption'] ?? 'tls';
        $mail->Username = $cfg['username'] ?? '';
        $mail->Password = $cfg['password'] ?? '';
      }
      $mail->setFrom($cfg['from_address'] ?? 'no-reply@evtp.local', $cfg['from_name'] ?? 'EVTP');
      $mail->addAddress($toEmail ?: '', $toName ?: '');
      if($attachPath && is_string($attachPath) && str_starts_with($attachPath, app_url('uploads/'))){
        // translate public URL to local path
        $rel = parse_url($attachPath, PHP_URL_PATH);
        $rel = preg_replace('#^'.preg_quote(parse_url(app_url(''), PHP_URL_PATH), '#').'#','',$rel);
        $local = dirname(__DIR__,2).'/public/'.$rel;
        if(file_exists($local)) $mail->addAttachment($local);
      }
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $htmlBody;
      $mail->send();
      return true;
    }catch(\Throwable $e){ return false; }
  }

  public function index(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $q = trim($_GET['q'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $course = trim($_GET['course'] ?? '');
    $from = trim($_GET['from'] ?? '');
    $to   = trim($_GET['to'] ?? '');
    $where=''; $params=[];
    if($q!==''){ $where .= ($where===''?' WHERE ':' AND ')." (l.learnerName LIKE ? OR l.learnerEmail LIKE ? OR c.courseTitle LIKE ?)"; array_push($params,'%'.$q.'%','%'.$q.'%','%'.$q.'%'); }
    if($status!==''){ $where .= ($where===''?' WHERE ':' AND ')." cert.certStatus=?"; $params[]=$status; }
    if($course!==''){ $where .= ($where===''?' WHERE ':' AND ')." cert.courseID=?"; $params[]=$course; }
    if($from!=='' && $to!==''){
      $where .= ($where===''?' WHERE ':' AND ')." cert.dateIssued BETWEEN ? AND ?";
      $params[] = date('Y-m-d', strtotime($from));
      $params[] = date('Y-m-d', strtotime($to));
    }
    $sql = "SELECT cert.certID, cert.learnerID, cert.courseID, cert.dateIssued, cert.certStatus, cert.grade, cert.file_path,
                   l.learnerName, l.learnerEmail, c.courseTitle
            FROM certificate cert
            LEFT JOIN Learners l ON l.learnerID=cert.learnerID
            LEFT JOIN Course c ON c.courseID=cert.courseID
            $where
            ORDER BY cert.dateIssued DESC, cert.certID DESC";
    $rows = $pdo->prepare($sql); $rows->execute($params); $rows = $rows->fetchAll();
    $courses = $pdo->query("SELECT courseID, courseTitle FROM Course ORDER BY courseTitle ASC")->fetchAll();
    return render('admin/certificates/index', ['rows'=>$rows,'q'=>$q,'status'=>$status,'course'=>$course,'courses'=>$courses,'statuses'=>$this->statuses()]);
  }

  public function create(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $courses = $pdo->query("SELECT courseID, courseTitle FROM Course ORDER BY courseTitle ASC")->fetchAll();
    $learners = $pdo->query("SELECT learnerID, learnerName, learnerEmail FROM Learners ORDER BY learnerName ASC")->fetchAll();
    return render('admin/certificates/form', ['mode'=>'create','courses'=>$courses,'learners'=>$learners,'statuses'=>$this->statuses(),'cert'=>[],'errors'=>$_SESSION['errors']??[],'old'=>$_SESSION['old']??[]]);
  }

  public function store(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $learnerID = trim($_POST['learnerID'] ?? '');
    $courseID = trim($_POST['courseID'] ?? '');
    $dateIssued = trim($_POST['dateIssued'] ?? date('Y-m-d'));
    $certStatus = trim($_POST['certStatus'] ?? 'issued');
    $grade = trim($_POST['grade'] ?? '');
    $file_path = '';
    $ins = $pdo->prepare("INSERT INTO certificate (learnerID, courseID, dateIssued, certStatus, grade, file_path) VALUES (?,?,?,?,?,?)");
    $ins->execute([$learnerID,$courseID,$dateIssued,$certStatus,$grade,$file_path]);
    $id = $pdo->lastInsertId();
    if(!empty($_FILES['certificate']['name'])){
      $dir = dirname(__DIR__,2)."/public/uploads/certificates/".preg_replace('/[^A-Za-z0-9_\-]/','',$id);
      if(!is_dir($dir)) @mkdir($dir,0775,true);
      $ext = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
      $dest = $dir.'/certificate.'.strtolower($ext);
      @move_uploaded_file($_FILES['certificate']['tmp_name'], $dest);
      $public = app_url('uploads/certificates/'.rawurlencode($id).'/certificate.'.strtolower($ext));
      $up = $pdo->prepare("UPDATE certificate SET file_path=? WHERE certID=?");
      $up->execute([$public,$id]);
    }
    // generate PDF if possible (only if no uploaded file)
    if(empty($_FILES['certificate']['name'])){ $pdfUrl = $this->generatePdfIfPossible($id); if($pdfUrl){ $file_path=$pdfUrl; } }

    // email learner if possible
    try{
      $user = $pdo->prepare("SELECT learnerName, learnerEmail FROM Learners WHERE learnerID=?");
      $user->execute([$learnerID]); $L = $user->fetch() ?: [];
      $verifyCode = $this->verifyCode(['certID'=>$id,'learnerID'=>$learnerID,'courseID'=>$courseID,'dateIssued'=>$dateIssued]);
      $viewUrl = app_url('learner/certificates/print').'?id='.rawurlencode($id);
      $verifyUrl = app_url('verify/certificate').'?id='.rawurlencode($id).'&code='.rawurlencode($verifyCode);
      $body = '<p>Hi '.htmlspecialchars($L['learnerName'] ?? 'Learner').',</p>'
            . '<p>Your certificate has been issued.</p>'
            . '<p><a href="'.$viewUrl.'">View/Print Certificate</a> · <a href="'.$verifyUrl.'">Verify Online</a></p>';
      $this->sendEmailIfPossible($L['learnerEmail'] ?? '', $L['learnerName'] ?? '', 'Your certificate is issued', $body, $file_path ?: null);
    }catch(\Throwable $e){}

    // in-app notification
    $this->createNotification($learnerID, 'Certificate issued', 'Your certificate for course #'.($courseID).' has been issued.', app_url('learner/certificates/print').'?id='.rawurlencode($id));
    $_SESSION['flash'] = 'Certificate issued';
    return redirect('admin/certificates');
  }

  public function edit(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $id = trim($_GET['id'] ?? '');
    $row = $pdo->prepare("SELECT * FROM certificate WHERE certID=?"); $row->execute([$id]); $row=$row->fetch() ?: [];
    $courses = $pdo->query("SELECT courseID, courseTitle FROM Course ORDER BY courseTitle ASC")->fetchAll();
    $learners = $pdo->query("SELECT learnerID, learnerName, learnerEmail FROM Learners ORDER BY learnerName ASC")->fetchAll();
    return render('admin/certificates/form', ['mode'=>'edit','courses'=>$courses,'learners'=>$learners,'statuses'=>$this->statuses(),'cert'=>$row,'errors'=>$_SESSION['errors']??[],'old'=>$_SESSION['old']??[]]);
  }

  public function update(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $id = trim($_POST['certID'] ?? '');
    $learnerID = trim($_POST['learnerID'] ?? '');
    $courseID = trim($_POST['courseID'] ?? '');
    $dateIssued = trim($_POST['dateIssued'] ?? date('Y-m-d'));
    $certStatus = trim($_POST['certStatus'] ?? 'issued');
    $grade = trim($_POST['grade'] ?? '');
    $upd = $pdo->prepare("UPDATE certificate SET learnerID=?, courseID=?, dateIssued=?, certStatus=?, grade=? WHERE certID=?");
    $upd->execute([$learnerID,$courseID,$dateIssued,$certStatus,$grade,$id]);
    if(!empty($_FILES['certificate']['name'])){
      $dir = dirname(__DIR__,2)."/public/uploads/certificates/".preg_replace('/[^A-Za-z0-9_\-]/','',$id);
      if(!is_dir($dir)) @mkdir($dir,0775,true);
      $ext = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
      $dest = $dir.'/certificate.'.strtolower($ext);
      @move_uploaded_file($_FILES['certificate']['tmp_name'], $dest);
      $public = app_url('uploads/certificates/'.rawurlencode($id).'/certificate.'.strtolower($ext));
      $up = $pdo->prepare("UPDATE certificate SET file_path=? WHERE certID=?");
      $up->execute([$public,$id]);
    }
    // generate new PDF if no file uploaded and no file exists
    if(empty($_FILES['certificate']['name'])){ $this->generatePdfIfPossible($id); }

    // notify learner
    try{
      $user = $pdo->prepare("SELECT learnerName, learnerEmail FROM Learners WHERE learnerID=?");
      $user->execute([$learnerID]); $L = $user->fetch() ?: [];
      $verifyCode = $this->verifyCode(['certID'=>$id,'learnerID'=>$learnerID,'courseID'=>$courseID,'dateIssued'=>$dateIssued]);
      $viewUrl = app_url('learner/certificates/print').'?id='.rawurlencode($id);
      $verifyUrl = app_url('verify/certificate').'?id='.rawurlencode($id).'&code='.rawurlencode($verifyCode);
      $body = '<p>Hi '.htmlspecialchars($L['learnerName'] ?? 'Learner').',</p>'
            . '<p>Your certificate has been updated.</p>'
            . '<p><a href="'.$viewUrl.'">View/Print Certificate</a> · <a href="'.$verifyUrl.'">Verify Online</a></p>';
      // attempt attach current file if exists
      $cur = $pdo->prepare("SELECT file_path FROM certificate WHERE certID=?"); $cur->execute([$id]); $fp = ($cur->fetch()['file_path'] ?? null);
      $this->sendEmailIfPossible($L['learnerEmail'] ?? '', $L['learnerName'] ?? '', 'Your certificate was updated', $body, $fp ?: null);
    }catch(\Throwable $e){}

    $this->createNotification($learnerID, 'Certificate updated', 'Your certificate for course #'.($courseID).' has been updated.', app_url('learner/certificates/print').'?id='.rawurlencode($id));
    $_SESSION['flash'] = 'Certificate updated';
    return redirect('admin/certificates');
  }

  public function delete(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $id = trim($_POST['id'] ?? '');
    $upd = $pdo->prepare("UPDATE certificate SET certStatus='deleted' WHERE certID=?");
    $upd->execute([$id]);
    // notify learner of revocation
    try{
      $st = $pdo->prepare("SELECT learnerID, courseID, dateIssued FROM certificate WHERE certID=?"); $st->execute([$id]); $row=$st->fetch() ?: [];
      if($row){
        $user = $pdo->prepare("SELECT learnerName, learnerEmail FROM Learners WHERE learnerID=?");
        $user->execute([$row['learnerID'] ?? '']); $L = $user->fetch() ?: [];
        $body = '<p>Hi '.htmlspecialchars($L['learnerName'] ?? 'Learner').',</p><p>Your certificate has been revoked.</p>';
        $this->sendEmailIfPossible($L['learnerEmail'] ?? '', $L['learnerName'] ?? '', 'Your certificate was revoked', $body);
      }
    }catch(\Throwable $e){}

    if(!empty($row['learnerID'] ?? '')){ $this->createNotification($row['learnerID'], 'Certificate revoked', 'A certificate was revoked. If you have questions, contact support.', null); }
    $_SESSION['flash'] = 'Certificate revoked';
    return redirect('admin/certificates');
  }

  public function bulkRevoke(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $ids = $_POST['ids'] ?? [];
    if(!is_array($ids)) $ids = [];
    if(!empty($ids)){
      $ph = implode(',', array_fill(0, count($ids), '?'));
      $stmt = $pdo->prepare("UPDATE certificate SET certStatus='deleted' WHERE certID IN ($ph)");
      $stmt->execute($ids);
      $_SESSION['flash'] = 'Revoked '.count($ids).' certificate(s)';
    } else { $_SESSION['flash'] = 'No certificates selected'; }
    return redirect('admin/certificates');
  }

  public function bulkStatus(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $ids = $_POST['ids'] ?? [];
    $status = trim($_POST['status'] ?? 'issued');
    if(!in_array($status, $this->statuses(), true)) $status = 'issued';
    if(!is_array($ids)) $ids = [];
    if(!empty($ids)){
      $ph = implode(',', array_fill(0, count($ids), '?'));
      $stmt = $pdo->prepare("UPDATE certificate SET certStatus=? WHERE certID IN ($ph)");
      $stmt->execute(array_merge([$status], $ids));
      $_SESSION['flash'] = 'Updated status for '.count($ids).' certificate(s)';
    } else { $_SESSION['flash'] = 'No certificates selected'; }
    return redirect('admin/certificates');
  }

  public function bulkIssueForm(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $courses = $pdo->query("SELECT courseID, courseTitle FROM Course ORDER BY courseTitle ASC")->fetchAll();
    $learners = $pdo->query("SELECT learnerID, learnerName, learnerEmail FROM Learners ORDER BY learnerName ASC")->fetchAll();
    return render('admin/certificates/bulk_issue', ['courses'=>$courses,'learners'=>$learners,'statuses'=>$this->statuses()]);
  }

  public function bulkIssue(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    $courseID = trim($_POST['courseID'] ?? '');
    $dateIssued = trim($_POST['dateIssued'] ?? date('Y-m-d'));
    $certStatus = trim($_POST['certStatus'] ?? 'issued');
    if(!in_array($certStatus, $this->statuses(), true)) $certStatus='issued';
    $grade = trim($_POST['grade'] ?? '');
    $learnerIDs = $_POST['learnerIDs'] ?? [];
    if(!is_array($learnerIDs)) $learnerIDs=[];
    $count=0;
    $ins = $pdo->prepare("INSERT INTO certificate (learnerID, courseID, dateIssued, certStatus, grade, file_path) VALUES (?,?,?,?,?,?)");
    foreach($learnerIDs as $lid){
      $lid=trim($lid); if($lid==='') continue; $ins->execute([$lid,$courseID,$dateIssued,$certStatus,$grade,'']);
      $newId = $pdo->lastInsertId();
      $this->generatePdfIfPossible($newId);
      try{
        $user = $pdo->prepare("SELECT learnerName, learnerEmail FROM Learners WHERE learnerID=?"); $user->execute([$lid]); $L=$user->fetch() ?: [];
        $verifyCode = $this->verifyCode(['certID'=>$newId,'learnerID'=>$lid,'courseID'=>$courseID,'dateIssued'=>$dateIssued]);
        $viewUrl = app_url('learner/certificates/print').'?id='.rawurlencode($newId);
        $verifyUrl = app_url('verify/certificate').'?id='.rawurlencode($newId).'&code='.rawurlencode($verifyCode);
        $file = $pdo->prepare("SELECT file_path FROM certificate WHERE certID=?"); $file->execute([$newId]); $fp = ($file->fetch()['file_path'] ?? null);
        $body = '<p>Hi '.htmlspecialchars($L['learnerName'] ?? 'Learner').',</p><p>Your certificate has been issued.</p><p><a href="'.$viewUrl.'">View/Print Certificate</a> · <a href="'.$verifyUrl.'">Verify Online</a></p>';
        $this->sendEmailIfPossible(($L['learnerEmail'] ?? ''), ($L['learnerName'] ?? ''), 'Your certificate is issued', $body, $fp ?: null);
      }catch(\Throwable $e){}
      $this->createNotification($lid, 'Certificate issued', 'Your certificate for course #'.($courseID).' has been issued.', $viewUrl);
      $count++;
    }
    $_SESSION['flash'] = 'Issued '.$count.' certificate(s)';
    return redirect('admin/certificates');
  }

  public function print(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    $id = trim($_GET['id'] ?? '');
    $st = $pdo->prepare("SELECT cert.*, l.learnerName, l.learnerEmail, c.courseTitle FROM certificate cert LEFT JOIN Learners l ON l.learnerID=cert.learnerID LEFT JOIN Course c ON c.courseID=cert.courseID WHERE certID=?");
    $st->execute([$id]); $row = $st->fetch() ?: [];
    $code = $this->verifyCode($row);
    $verifyUrl = app_url('verify/certificate').'?code='.rawurlencode($code).'&id='.rawurlencode($id);
    return render('admin/certificates/print', ['cert'=>$row,'code'=>$code,'verifyUrl'=>$verifyUrl]);
  }
}
