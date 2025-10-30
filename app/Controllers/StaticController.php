<?php
class StaticController {
  public function about(){
    $stats = [
      'courses'  => 0,
      'learners' => 0,
      'trainers' => 0,
    ];
    try {
      $pdo = DB::conn();
      $stats['courses']  = (int)$pdo->query("SELECT COUNT(*) FROM Course")->fetchColumn();
      $stats['learners'] = (int)$pdo->query("SELECT COUNT(*) FROM Learners")->fetchColumn();
      $stats['trainers'] = (int)$pdo->query("SELECT COUNT(*) FROM Trainers")->fetchColumn();
    } catch (Throwable $e) {
      // silently ignore DB errors for static page
    }
    return render('static/about', compact('stats'));
  }
}
