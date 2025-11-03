<?php
class DashboardController {
  public function index(){
    if(!Auth::check()) redirect('login');
    $role = Auth::user()['role'];
    $view = [
      'learner'=>'dashboards/learner',
      'trainer'=>'dashboards/trainer',
      'employer'=>'dashboards/employer',
      'admin'=>'dashboards/admin'
    ][$role] ?? 'dashboards/learner';
    return render($view, ['user'=>Auth::user()]);
  }

  public function hideRecommendation(){
    Auth::requireRole(['learner']); csrf_verify();
    $job = trim($_POST['job'] ?? '');
    if($job===''){ return redirect('dashboard'); }
    $lid = Auth::user()['id'];
    $path = __DIR__.'/../../storage/hidden_jobs_'.$lid.'.json';
    $list = [];
    if(file_exists($path)){
      $d = json_decode(@file_get_contents($path), true);
      if(is_array($d)) $list = $d;
    }
    $list[$job] = true;
    @file_put_contents($path, json_encode($list));
    $_SESSION['flash'] = 'Recommendation hidden';
    return redirect('dashboard');
  }
}
