<?php 
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$fullName = $_SESSION['FullName'] ?? '';
$role = $_SESSION['Role'] ?? '';
?>


<main class="container my-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card p-4 shadow-sm">
        <h1 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['FullName']); ?>!</h1>
        <p class="lead">Your role is: <strong><?php echo htmlspecialchars($_SESSION['Role']); ?></strong></p>
        <div class="mt-4">
          <a href="scm.php" class="btn btn-primary me-2">Go to SCM</a>
          <a href="erp.php" class="btn btn-secondary">Go to ERP</a>
        </div>
      </div>
    </div>
  </div>
</main>