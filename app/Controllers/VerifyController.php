<?php
class VerifyController {
  private function verifyCode($cert){
    $salt = ($GLOBALS['APP_CONFIG']['app']['base_url'] ?? 'evtp');
    $data = ($cert['certID'] ?? '').'|'.($cert['learnerID'] ?? '').'|'.($cert['courseID'] ?? '').'|'.($cert['dateIssued'] ?? '');
    return substr(hash('sha256', $salt.'|'.$data), 0, 16);
  }

  public function certificate(){
    $pdo = DB::conn();
    $id = trim($_GET['id'] ?? '');
    $code = trim($_GET['code'] ?? '');
    if($id===''){ return render('verify/certificate', ['state'=>'missing']); }
    $st = $pdo->prepare("SELECT cert.*, c.courseTitle, l.learnerName FROM certificate cert LEFT JOIN Course c ON c.courseID=cert.courseID LEFT JOIN Learners l ON l.learnerID=cert.learnerID WHERE certID=?");
    $st->execute([$id]); $row = $st->fetch();
    if(!$row){ return render('verify/certificate', ['state'=>'notfound','id'=>$id]); }
    $expected = $this->verifyCode($row);
    $valid = hash_equals($expected, $code ?? '');
    return render('verify/certificate', ['state'=>$valid?'valid':'invalid','cert'=>$row,'code'=>$code,'expected'=>$expected]);
  }
}
