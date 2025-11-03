<?php
class LearnerCertificatesController {
  private function ensureLearner(){ Auth::requireRole(['learner']); }
  private function statuses(){ return ['issued','pending','updated','deleted']; }
  private function verifyCode($cert){
    $salt = ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? 'evtp');
    $data = ($cert['certID'] ?? '').'|'.($cert['learnerID'] ?? '').'|'.($cert['courseID'] ?? '').'|'.($cert['dateIssued'] ?? '');
    return substr(hash('sha256', $salt.'|'.$data), 0, 16);
  }

  public function index(){
    $this->ensureLearner();
    $pdo = DB::conn();
    $uid = Auth::user()['id'] ?? null; // assume maps to learnerID
    $q = trim($_GET['q'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $course = trim($_GET['course'] ?? '');
    $where = " WHERE cert.learnerID = ?"; $params = [$uid];
    if($q!==''){ $where .= " AND (c.courseTitle LIKE ?)"; $params[] = '%'.$q.'%'; }
    if($status!==''){ $where .= " AND cert.certStatus=?"; $params[]=$status; }
    if($course!==''){ $where .= " AND cert.courseID=?"; $params[]=$course; }
    $sql = "SELECT cert.*, c.courseTitle FROM certificate cert LEFT JOIN Course c ON c.courseID=cert.courseID $where ORDER BY cert.dateIssued DESC, cert.certID DESC";
    $st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();
    $courses = $pdo->prepare("SELECT DISTINCT c.courseID, c.courseTitle FROM certificate cert LEFT JOIN Course c ON c.courseID=cert.courseID WHERE cert.learnerID=? ORDER BY c.courseTitle ASC");
    $courses->execute([$uid]); $courses = $courses->fetchAll();
    return render('learner/certificates/index', ['rows'=>$rows,'q'=>$q,'status'=>$status,'course'=>$course,'courses'=>$courses,'statuses'=>$this->statuses()]);
  }

  public function print(){
    $this->ensureLearner();
    $pdo = DB::conn();
    $uid = Auth::user()['id'] ?? null;
    $id = trim($_GET['id'] ?? '');
    $st = $pdo->prepare("SELECT cert.*, c.courseTitle, l.learnerName, l.learnerEmail FROM certificate cert LEFT JOIN Course c ON c.courseID=cert.courseID LEFT JOIN Learners l ON l.learnerID=cert.learnerID WHERE certID=? AND cert.learnerID=?");
    $st->execute([$id,$uid]); $row = $st->fetch() ?: [];
    if(empty($row)){ $_SESSION['flash'] = 'Certificate not found'; return redirect('learner/certificates'); }
    $code = $this->verifyCode($row);
    $verifyUrl = app_url('verify/certificate').'?code='.rawurlencode($code).'&id='.rawurlencode($id);
    return render('admin/certificates/print', ['cert'=>$row,'code'=>$code,'verifyUrl'=>$verifyUrl]);
  }
}
