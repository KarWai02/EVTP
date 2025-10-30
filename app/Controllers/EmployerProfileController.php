<?php
class EmployerProfileController {
  public function index(){
    Auth::requireRole(['employer']);
    $pdo = DB::conn();
    $eid = Auth::user()['id'];
    // Employer company info
    $emp = [];
    try{
      $s = $pdo->prepare("SELECT employerID, employerEmail, companyName, companyIndustry, contactPerson, companyPhone FROM Employers WHERE employerID=?");
      $s->execute([$eid]);
      $emp = $s->fetch() ?: [];
    }catch(\Throwable $e){ $emp = []; }

    // Shortlist
    $shortlist = [];
    try{
      $path = __DIR__.'/../../storage/shortlist_'.$eid.'.json';
      if(file_exists($path)){
        $shortlist = json_decode(file_get_contents($path), true) ?: [];
      }
    }catch(\Throwable $e){}
    $shortIDs = array_keys(array_filter($shortlist));
    $shortRows = [];
    if(!empty($shortIDs)){
      $ph = implode(',', array_fill(0, count($shortIDs), '?'));
      try{
        $q = $pdo->prepare("SELECT learnerID, learnerName, learnerEmail FROM Learners WHERE learnerID IN ($ph)");
        $q->execute($shortIDs);
        $shortRows = $q->fetchAll();
      }catch(\Throwable $e){ $shortRows = []; }
    }

    return render('employer/profile', [
      'emp'=>$emp,
      'shortRows'=>$shortRows,
      'eid'=>$eid,
    ]);
  }
  public function update(){
    Auth::requireRole(['employer']);
    csrf_verify();
    $pdo = DB::conn();
    $eid = Auth::user()['id'];

    $companyName     = trim($_POST['companyName'] ?? '');
    $companyIndustry = trim($_POST['companyIndustry'] ?? '');
    $contactPerson   = trim($_POST['contactPerson'] ?? '');
    $companyPhone    = trim($_POST['companyPhone'] ?? '');

    $errors = [];
    if($companyName===''){ $errors['companyName'] = 'Company name is required'; }
    if($companyIndustry===''){ $errors['companyIndustry'] = 'Industry is required'; }
    if($contactPerson===''){ $errors['contactPerson'] = 'Contact person is required'; }
    if($companyPhone===''){ $errors['companyPhone'] = 'Phone is required'; }
    elseif(!preg_match('/^[0-9+\-\s]{7,15}$/', $companyPhone)){ $errors['companyPhone'] = 'Enter a valid phone number'; }

    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('dashboard'); }

    try{
      // attempt to update with company columns; fallback to contact/phone only if columns absent
      try{
        $pdo->query("SELECT companyName, companyIndustry FROM Employers LIMIT 1");
        $sql = "UPDATE Employers SET companyName=?, companyIndustry=?, contactPerson=?, companyPhone=? WHERE employerID=?";
        $stm = $pdo->prepare($sql);
        $stm->execute([$companyName,$companyIndustry,$contactPerson,$companyPhone,$eid]);
      }catch(Throwable $e){
        $sql = "UPDATE Employers SET contactPerson=?, companyPhone=? WHERE employerID=?";
        $stm = $pdo->prepare($sql);
        $stm->execute([$contactPerson,$companyPhone,$eid]);
      }

      // Handle logo removal
      $baseDir = __DIR__.'/../../public/uploads/employers/'.preg_replace('/[^A-Za-z0-9_\-]/','',$eid);
      if(!empty($_POST['remove_logo'])){
        foreach(['png','jpg','jpeg','webp'] as $e){ $old = $baseDir.'/logo.'.$e; if(file_exists($old)) @unlink($old); }
      }

      // Handle logo upload if present
      if(!empty($_FILES['logo']) && is_array($_FILES['logo']) && (int)($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
        $f = $_FILES['logo'];
        if(($f['error'] ?? 0) === UPLOAD_ERR_OK && ($f['size'] ?? 0) > 0){
          if(($f['size'] ?? 0) > 2*1024*1024){ throw new Exception('Logo too large'); }
          $tmp = $f['tmp_name'];
          if(!is_uploaded_file($tmp)) throw new Exception('Invalid upload');
          $mime = '';
          if(class_exists('finfo')){ try{ $fi = new finfo(FILEINFO_MIME_TYPE); $mime = $fi->file($tmp) ?: ''; }catch(\Throwable $e){ $mime=''; } }
          if($mime===''){
            $gi = @getimagesize($tmp); if(is_array($gi) && !empty($gi['mime'])) $mime = $gi['mime'];
          }
          if($mime===''){
            $extGuess = strtolower(pathinfo(($f['name'] ?? ''), PATHINFO_EXTENSION));
            $mime = $extGuess==='png' ? 'image/png' : ($extGuess==='jpg' || $extGuess==='jpeg' ? 'image/jpeg' : ($extGuess==='webp' ? 'image/webp' : ''));
          }
          $map = [ 'image/png'=>'png', 'image/jpeg'=>'jpg', 'image/webp'=>'webp' ];
          if(!isset($map[$mime])) throw new Exception('Unsupported logo format');
          $ext = $map[$mime];
          if(!is_dir($baseDir)) @mkdir($baseDir, 0777, true);
          // Remove previous logo files with known extensions
          foreach(['png','jpg','jpeg','webp'] as $e){ $old = $baseDir.'/logo.'.$e; if(file_exists($old)) @unlink($old); }
          $dest = $baseDir.'/logo.png';

          // Resize to max 256x256 using GD, keep aspect and transparency
          $max = 256;
          $w = $h = 0;
          [$w,$h] = getimagesize($tmp) ?: [0,0];
          $src = null; $saved = false;
          try{
            if($ext==='png')      $src = imagecreatefrompng($tmp);
            elseif($ext==='jpg')  $src = imagecreatefromjpeg($tmp);
            elseif($ext==='webp') $src = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($tmp) : null;
          }catch(Throwable $e){ $src = null; }
          if($src && $w>0 && $h>0){
            $scale = min(1.0, $max / max($w,$h));
            $nw = (int)floor($w*$scale); $nh = (int)floor($h*$scale);
            $dst = imagecreatetruecolor($nw,$nh);
            // preserve transparency
            if($ext==='png' || $ext==='webp'){
              imagealphablending($dst, false); imagesavealpha($dst, true);
              $transparent = imagecolorallocatealpha($dst, 0,0,0,127);
              imagefilledrectangle($dst,0,0,$nw,$nh,$transparent);
            }
            imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
            // Always save as PNG for consistent serving
            $saved = imagepng($dst,$dest,6);
            imagedestroy($dst); imagedestroy($src);
          }
          // Fallback: move original if resize failed (store with original extension)
          if(!$saved){
            $destFallback = $baseDir.'/logo.'.$ext;
            if(!@move_uploaded_file($tmp, $destFallback)) throw new Exception('Failed to save logo');
          }
        } else if(($f['error'] ?? 0) !== UPLOAD_ERR_NO_FILE){
          throw new Exception('Upload error');
        }
      }
      // Optionally refresh session display name with contact person
      $_SESSION['user']['name'] = $contactPerson ?: ($_SESSION['user']['name'] ?? '');
      $_SESSION['flash'] = 'Company profile updated';
    }catch(Throwable $e){
      $_SESSION['flash'] = 'Failed to update profile: '.$e->getMessage();
    }

    return redirect('dashboard');
  }
}
