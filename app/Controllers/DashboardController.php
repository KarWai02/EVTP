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
}
