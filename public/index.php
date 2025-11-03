<?php
require __DIR__.'/../bootstrap.php';
$router = new Router();

$router->get('/', [new HomeController,'index']);

// Learner: job search and apply
$router->get('/jobs', [new JobsController,'index']);
$router->post('/jobs/save', [new JobsController,'save']);
$router->post('/jobs/apply', [new JobsController,'apply']);

// Learner: activity (saved/applied jobs)
$router->get('/activity', [new LearnerActivityController,'index']);

$router->get('/login', [new AuthController,'showLogin']);
$router->post('/login', [new AuthController,'login']);
$router->get('/logout', [new AuthController,'logout']);

// Signup routes
$router->get('/signup', [new AuthController,'showSignup']);
$router->post('/signup', [new AuthController,'signup']);

// Password reset routes
$router->get('/forgot', [new AuthController,'showForgot']);
$router->post('/forgot', [new AuthController,'forgot']);
$router->get('/reset', [new AuthController,'showReset']); // expects ?token=...
$router->post('/reset', [new AuthController,'reset']);

// Profile (learner)
$router->get('/profile', [new ProfileController,'edit']);
$router->post('/profile', [new ProfileController,'update']);
$router->post('/profile/password', [new ProfileController,'updatePassword']);
$router->get('/enrollments', [new ProfileController,'enrollments']);

// Static
$router->get('/about', [new StaticController,'about']);

$router->get('/dashboard', [new DashboardController,'index']);
$router->get('/admin/profile', [new AdminProfileController,'edit']);
$router->post('/admin/profile', [new AdminProfileController,'update']);
$router->post('/admin/profile/password', [new AdminProfileController,'updatePassword']);
$router->post('/admin/profile/avatar', [new AdminProfileController,'uploadAvatar']);
$router->post('/dashboard/hide-recommendation', [new DashboardController,'hideRecommendation']);

// Community forum
$router->get('/community', [new CommunityController,'index']);
$router->get('/community/thread', [new CommunityController,'thread']); // ?id=POST0001
$router->get('/community/create', [new CommunityController,'create']);
$router->post('/community/create', [new CommunityController,'create']);
$router->post('/community/reply', [new CommunityController,'reply']);
$router->get('/community/report', [new CommunityController,'report']);
$router->post('/community/report', [new CommunityController,'report']);
$router->get('/community/flag', [new CommunityController,'report']);
$router->post('/community/flag', [new CommunityController,'report']);
$router->post('/community/mod', [new CommunityController,'mod']);
$router->get('/community/mod', [new CommunityController,'mod']);
$router->get('/community/reports', [new CommunityController,'reports']);
$router->post('/community/reports/resolve', [new CommunityController,'resolveReport']);
$router->post('/community/reports/clean', [new CommunityController,'cleanReports']);

// Notifications helpers
$router->post('/notifications/read-all', [new NotificationsController,'readAll']);

$router->get('/courses', [new CourseController,'index']);
$router->get('/courses/view', [new CourseController,'view']); // ?id=CRS00001
$router->post('/courses/enroll', [new CourseController,'enroll']);
// Extra course routes
$router->get('/courses/details', [new CourseController,'detailsByTitle']); // ?title=...&cat=...
$router->get('/courses/farming', [new CourseController,'farming']);
$router->get('/courses/softskills', [new CourseController,'softskills']);
// Admin utility
$router->post('/admin/courses/seed-pathways', [new CourseController,'seedPathways']);

// Admin: course management
$router->get('/admin/courses', [new AdminCoursesController,'index']);
$router->get('/admin/courses/create', [new AdminCoursesController,'create']);
$router->post('/admin/courses', [new AdminCoursesController,'store']);
$router->get('/admin/courses/edit', [new AdminCoursesController,'edit']); // ?id=CRS00001
$router->post('/admin/courses/update', [new AdminCoursesController,'update']);
$router->post('/admin/courses/delete', [new AdminCoursesController,'delete']);
$router->get('/admin/courses/modules', [new AdminCoursesController,'modules']); // ?courseID=CRS00001
$router->post('/admin/courses/modules/add', [new AdminCoursesController,'addModule']);
$router->post('/admin/courses/modules/update', [new AdminCoursesController,'updateModule']);
$router->post('/admin/courses/modules/delete', [new AdminCoursesController,'deleteModule']);
$router->post('/admin/courses/modules/reorder', [new AdminCoursesController,'reorderModules']);

// Admin: user management
$router->get('/admin/users', [new UsersController,'index']);
$router->get('/admin/users/export', [new UsersController,'export']);
$router->get('/admin/users/create', [new UsersController,'create']);
$router->post('/admin/users', [new UsersController,'store']);
$router->get('/admin/users/edit', [new UsersController,'edit']);
$router->post('/admin/users/update', [new UsersController,'update']);
$router->post('/admin/users/delete', [new UsersController,'delete']);
$router->post('/admin/users/update-expertise', [new UsersController,'updateExpertise']);
$router->get('/admin/users/seed-employers', [new UsersController,'seedEmployers']);

// Admin: courses management
$router->get('/admin/courses', [new AdminCoursesController,'index']);
$router->get('/admin/courses/create', [new AdminCoursesController,'create']);
$router->post('/admin/courses', [new AdminCoursesController,'store']);
$router->get('/admin/courses/edit', [new AdminCoursesController,'edit']);
$router->post('/admin/courses/update', [new AdminCoursesController,'update']);
$router->post('/admin/courses/delete', [new AdminCoursesController,'delete']);

// Admin: analytics / reports
$router->get('/admin/reports', [new AdminReportsController,'index']);
$router->post('/admin/reports/export', [new AdminReportsController,'export']);
$router->post('/admin/reports/export-performance', [new AdminReportsController,'exportPerformance']);
$router->post('/admin/reports/export-certificates', [new AdminReportsController,'exportCertificates']);

// Admin: certificates management
$router->get('/admin/certificates', [new AdminCertificatesController,'index']);
$router->get('/admin/certificates/create', [new AdminCertificatesController,'create']);
$router->post('/admin/certificates/store', [new AdminCertificatesController,'store']);
$router->get('/admin/certificates/edit', [new AdminCertificatesController,'edit']);
$router->post('/admin/certificates/update', [new AdminCertificatesController,'update']);
$router->post('/admin/certificates/delete', [new AdminCertificatesController,'delete']);
$router->get('/admin/certificates/bulk-issue', [new AdminCertificatesController,'bulkIssueForm']);
$router->post('/admin/certificates/bulk-issue', [new AdminCertificatesController,'bulkIssue']);
$router->post('/admin/certificates/bulk-revoke', [new AdminCertificatesController,'bulkRevoke']);
$router->post('/admin/certificates/bulk-status', [new AdminCertificatesController,'bulkStatus']);
$router->get('/admin/certificates/print', [new AdminCertificatesController,'print']);

// Learner: certificates
$router->get('/learner/certificates', [new LearnerCertificatesController,'index']);
$router->get('/learner/certificates/print', [new LearnerCertificatesController,'print']);

// Public: verification
$router->get('/verify/certificate', [new VerifyController,'certificate']);

// Notifications
$router->get('/notifications', [new NotificationsController,'index']);
$router->post('/notifications/read', [new NotificationsController,'read']);

// Employer: job postings & applicants
$router->get('/employer/jobs', [new EmployerJobsController,'index']);
$router->get('/employer/jobs/create', [new EmployerJobsController,'create']);
$router->post('/employer/jobs', [new EmployerJobsController,'store']);
$router->get('/employer/jobs/edit', [new EmployerJobsController,'edit']);
$router->post('/employer/jobs/update', [new EmployerJobsController,'update']);
$router->post('/employer/jobs/delete', [new EmployerJobsController,'delete']);
$router->get('/employer/jobs/applicants', [new EmployerJobsController,'applicants']);
$router->post('/employer/applications/status', [new EmployerJobsController,'updateStatus']);
$router->get('/employer/applications/resume', [new EmployerJobsController,'downloadResume']);

// Employer: inline company profile update
$router->get('/employer/profile', [new EmployerProfileController,'index']);
$router->post('/employer/profile/update', [new EmployerProfileController,'update']);

// Employer: talent search and shortlist
$router->get('/employer/talent', [new EmployerTalentController,'index']);
$router->get('/employer/talent/profile', [new EmployerTalentController,'profile']);
$router->post('/employer/talent/shortlist', [new EmployerTalentController,'shortlist']);

// Employer: unified candidates manager
$router->get('/employer/candidates', [new EmployerCandidatesController,'index']);
$router->post('/employer/candidates/bulk-shortlist', [new EmployerCandidatesController,'bulkShortlist']);
$router->get('/employer/candidates/export', [new EmployerCandidatesController,'export']);

// Trainer: materials management
$router->get('/trainer/courses', [new TrainerMaterialsController,'courses']);
$router->get('/trainer/materials/modules', [new TrainerMaterialsController,'modules']);
$router->post('/trainer/modules', [new TrainerMaterialsController,'createModule']);
$router->get('/trainer/materials/list', [new TrainerMaterialsController,'materials']);
$router->post('/trainer/materials', [new TrainerMaterialsController,'storeMaterial']);
$router->get('/trainer/materials/download', [new TrainerMaterialsController,'downloadMaterial']);
$router->post('/trainer/materials/replace', [new TrainerMaterialsController,'replaceMaterial']);

// Trainer: workshops
$router->get('/trainer/workshops', [new TrainerWorkshopsController,'index']);
$router->get('/trainer/workshops/create', [new TrainerWorkshopsController,'create']);
$router->post('/trainer/workshops', [new TrainerWorkshopsController,'store']);
$router->get('/trainer/workshops/edit', [new TrainerWorkshopsController,'edit']);
$router->post('/trainer/workshops/update', [new TrainerWorkshopsController,'update']);
$router->post('/trainer/workshops/delete', [new TrainerWorkshopsController,'delete']);

// Cron: workshop reminders (headless)
$router->get('/cron/workshops/reminders', [new TrainerWorkshopsController,'remindersCron']);

// Trainer: progress
$router->get('/trainer/progress', [new TrainerProgressController,'index']);
$router->post('/trainer/progress/export', [new TrainerProgressController,'export']);
$router->get('/trainer/progress/learner', [new TrainerProgressController,'learner']);

// Trainer: create course
$router->get('/trainer/courses/create', [new TrainerCoursesController,'create']);
$router->post('/trainer/courses', [new TrainerCoursesController,'store']);
// Trainer: edit course
$router->get('/trainer/courses/edit', [new TrainerCoursesController,'edit']);
$router->post('/trainer/courses/update', [new TrainerCoursesController,'update']);

// Trainer: profile
$router->get('/trainer/profile', [new TrainerProfileController,'edit']);
$router->post('/trainer/profile', [new TrainerProfileController,'update']);
$router->post('/trainer/profile/password', [new TrainerProfileController,'updatePassword']);

// Trainer: assessments (per module)
$router->get('/trainer/assessments', [new TrainerAssessmentsController,'index']);
$router->get('/trainer/assessments/create', [new TrainerAssessmentsController,'create']);
$router->post('/trainer/assessments', [new TrainerAssessmentsController,'store']);
$router->get('/trainer/assessments/edit', [new TrainerAssessmentsController,'edit']);
$router->post('/trainer/assessments/update', [new TrainerAssessmentsController,'update']);
$router->post('/trainer/assessments/delete', [new TrainerAssessmentsController,'delete']);

$router->dispatch();

