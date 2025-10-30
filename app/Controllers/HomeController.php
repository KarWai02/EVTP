<?php
class HomeController {
  public function index(){
    $pdo = DB::conn();
    $q = $pdo->query("SELECT courseID, courseTitle, category, createdDate, description FROM Course ORDER BY createdDate DESC LIMIT 6");
    $courses = $q->fetchAll();
    return render('home/index', compact('courses'));
  }
}
