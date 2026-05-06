<?php 

//start session
session_start();


// login fail / login page defs
$loginFailed = (isset($_GET['login']) && $_GET['login'] == 'failed');
$LoginPage   = (!isset($_SESSION['UserID']) || $loginFailed);

$User     = isset($_SESSION['Username']) ? (string)$_SESSION['Username'] : '';
$Role     = isset($_SESSION['Role']) ? (string)$_SESSION['Role'] : '';
$FullName = isset($_SESSION['FullName']) ? (string)$_SESSION['FullName'] : '';

$DisplayName = ($FullName !== '') ? $FullName : $User;

?>

<!DOCTYPE html>
<html lang='en'>

    <?php
    // html head
    include 'head.php'; 
    ?>

    <body>
        <!-- top of page navbar -->
        <?php include 'navbar.php';?>

            <!-- BODY CONTENT -->
            <?php if (!$LoginPage): ?>
                <main class="container my-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card p-4 shadow-sm text-center">
                                <h1 class="mb-3">
                                    Welcome, <?php echo htmlspecialchars($DisplayName, ENT_QUOTES, 'UTF-8'); ?>!
                                </h1>
                                <p class="lead">
                                    Role: <strong><?php if ($Role==='SeniorManager'):
                                                            echo 'Senior Manager';
                                                        elseif ($Role==='SupplyChainManager'):
                                                            echo 'Supply Chain Manager';
                                                        else:
                                                            echo 'Unassigned';
                                                        endif;?></strong>
                                </p>

                                <div class="mt-4 d-grid gap-2 d-sm-flex justify-content-sm-center">
                                    <a href="scm.php" class="btn btn-primary btn-lg">Go to SCM</a>

                                    <?php if ($Role === 'SeniorManager'): ?>
                                        <a href="erp.php" class="btn btn-secondary btn-lg">Go to ERP</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            <?php else: ?>
                <main class="container my-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card p-4 shadow-sm text-center">
                                <h3 class="mb-2">Welcome!</h3>
                                <p class="mb-0">Please log in to continue.</p>
                            </div>
                        </div> 
                    </div>

                    <!-- login page -->
                    <div class="row justify-content-center mt-5">
                        <div class="col-lg-8">
                            <div class='card shadow-sm p-5'>
                                <h3 class="text-center">Log in</h3>
                                <!-- error display -->
                                <?php if (($_GET['error'])==='notloggedin'):?>
                                    <div class="alert alert-danger mt-3 text-center">
                                        <i>please login to continue</i>
                                    </div>
                                <?php elseif (isset($_GET['error'])): ?>
                                    <div class="alert alert-danger mt-3">
                                        <i>invalid username or password</i>
                                    </div>

                                <?php endif; ?>

                                <!-- login form -->
                                <form action='auth.php' method='POST' name='loginForm' onsubmit='return validateLogin();'>
                                    <!-- username input -->
                                    <div class='mb-3'>
                                        <label for='username' class='form-label'>
                                            <i class='fas fa-user'></i>
                                            Username
                                        </label>
                                        <input type='text' class='form-control' name='username' id='username' placeholder='Enter username' required>
                                    </div>

                                    <!-- password input -->
                                    <div class='mb-3'>
                                        <label for='password' class='form-label'>
                                            <i class='fas fa-lock'></i>
                                            Password
                                        </label>
                                        <input type='password' class='form-control' name='password' id='password' placeholder='Enter password' required>
                                    </div>

                                    <div class='d-grid gap-2'>
                                        <button type='submit' class='btn btn-primary btn-lg'>
                                            Login
                                        </button>
                                        <button type='reset' class='btn btn-secondary'>
                                            Clear
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div> <!-- end col-lg-8 -->
                    </div> <!-- end row -->

                </main>

            <?php endif; ?>

            <?php include 'demo.php';?>
            
            <!-- meet the team container -->
            <div class='container my-5' id='meettheteam' name='meettheteam'>
                <h1 class='text-center mb-4'>Meet Group 12</h1>

                <!-- row wrapper -->
                <div class="row g-4 justify-content-center">

                    <!-- adam -->
                    <div class="col-6 col-md-4 col-lg-2">
                        
                        <div class="card team-card h-100 text-center">
                            <img src="https://media.licdn.com/dms/image/v2/D5603AQE_WXyWxvz_bQ/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1714162777077?e=1766016000&v=beta&t=jlvbTftj_FNb358-ey08s_joAs_WgvOIScy43Q89gjU" class="card-img-top team-photo" alt="Adam Elsolh">
                            <div class="card-body">
                                <h5 class="card-title">Adam Elsolh</h5>
                                <a href="mailto:aelsolh@purdue.edu" class="card-link">Contact</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- derek -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card team-card h-100 text-center">
                            <img src="https://media.licdn.com/dms/image/v2/D5603AQFuKXc7EC5mWA/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1719209777062?e=1766620800&v=beta&t=S5Ruzo1WDu2NOLBZtD3yrJHKeeYRc1-eLWgEtpzhis4" class="card-img-top team-photo" alt="Derek Huang">
                            <div class="card-body">
                                <h5 class="card-title">Derek Huang</h5>
                                <a href="mailto:huan1803@purdue.edu" class="card-link">Contact</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- fernando -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card team-card h-100 text-center">
                            <img src="https://media.licdn.com/dms/image/v2/D4E03AQGaTgUl5r_S9A/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1695662396325?e=1766016000&v=beta&t=uW4PLYa7o_pBrKcykYD3GAcSzEF5zwYo97yTfWCtztM" class="card-img-top team-photo" alt="Fernando Canales">
                            <div class="card-body">
                                <h5 class="card-title">Fernando Canales</h5>
                                <a href="mailto:fcanale@purdue.edu" class="card-link">Contact</a>
                            </div>
                        </div>
                    </div>

                    <!-- jishnu -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card team-card h-100 text-center">
                            <img src="https://media.licdn.com/dms/image/v2/C4E03AQGalQVsH32_pw/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1658943306212?e=1766016000&v=beta&t=_Pk4xWTnZ-PZeQKvNdEZeqKah3iST_BbS8xqRyk-pT0" class="card-img-top team-photo" alt="Jishnu Ghosh">
                            <div class="card-body">
                                <h5 class="card-title">Jishnu Ghosh</h5>
                                <a href="mailto:jishnujag@gmail.com" class="card-link">Contact</a>
                            </div>
                        </div>
                    </div>

                    <!-- leo -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card team-card h-100 text-center">
                            <img src="https://media.licdn.com/dms/image/v2/D4E03AQFA8ihDgBzOVg/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1664311748104?e=1766016000&v=beta&t=d0xx7Um7H-OmVKQ_euqaFiRb6aVL-pVyn3sxtS0oQzs" class="card-img-top team-photo" alt="Leo Cavalier">
                            <div class="card-body">
                                <h5 class="card-title">Leo Cavalier</h5>
                                <a href="mailto:lcavalie@purdue.edu" class="card-link">Contact</a>
                            </div>
                        </div>
                    </div>

                    <!-- zihang -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card team-card h-100 text-center">
                            <img src='imgs/zihang.png' class="card-img-top team-photo" alt="Zihang Yang">
                            <div class="card-body">
                                <h5 class="card-title">Zihang Yang</h5>
                                <a href="mailto:yang2458@purdue.edu" class="card-link">Contact</a>
                            </div>
                        </div>
                    </div>

                </div> <!-- end row wrapper -->

            </div> <!-- end meet the team container -->

        <?php include 'footer.php';?>
        
    </body>
</html>