<?php
session_start();

// 1) config first, so Helpers can read base_url
require __DIR__ . '/config/config.php';

// optional composer autoload (PHPMailer, etc.)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require __DIR__ . '/vendor/autoload.php';
}

// 2) core
require __DIR__ . '/app/Core/DB.php';
require __DIR__ . '/app/Core/Helpers.php';
require __DIR__ . '/app/Core/Auth.php';
require __DIR__ . '/app/Core/Router.php';

// 3) controllers
require __DIR__ . '/app/Controllers/AuthController.php';
require __DIR__ . '/app/Controllers/DashboardController.php';
require __DIR__ . '/app/Controllers/CourseController.php';
require __DIR__ . '/app/Controllers/ProfileController.php';
require __DIR__ . '/app/Controllers/HomeController.php';
require __DIR__ . '/app/Controllers/StaticController.php';
require __DIR__ . '/app/Controllers/UsersController.php';
require __DIR__ . '/app/Controllers/AdminCoursesController.php';
require __DIR__ . '/app/Controllers/AdminReportsController.php';
require __DIR__ . '/app/Controllers/EmployerJobsController.php';
require __DIR__ . '/app/Controllers/EmployerProfileController.php';
require __DIR__ . '/app/Controllers/EmployerTalentController.php';
require __DIR__ . '/app/Controllers/TrainerMaterialsController.php';
require __DIR__ . '/app/Controllers/TrainerWorkshopsController.php';
require __DIR__ . '/app/Controllers/TrainerProgressController.php';
require __DIR__ . '/app/Controllers/TrainerCoursesController.php';
require __DIR__ . '/app/Controllers/TrainerAssessmentsController.php';
