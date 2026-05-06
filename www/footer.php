<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div id="footer-container" class="mt-auto pt-3 footer-wrapper">

    <!-- Purdue Legal Bar -->
    <div class="legal-bar text-center small px-3">
        <p class="mb-1">Purdue University, 610 Purdue Mall, West Lafayette, IN, 47907, 765-494-4600</p>
        <p class="mb-1 legal-links">
            <a href="//www.purdue.edu/purdue/disclaimer.php">© 2025 Purdue University</a> |
            <a href="//www.purdue.edu/purdue/ea_eou_statement.html">An equal access/equal opportunity university</a> |
            <a href="//www.purdue.edu/purdue/about/integrity_statement.html">Integrity Statement</a> |
            <a href="//www.purdue.edu/home/free-speech/">Free Expression</a> |
            <a href="https://collegescorecard.ed.gov/school/fields/?243780-Purdue-University-Main-Campus">DOE Degree Scorecards</a> |
            <a href="//www.purdue.edu/securepurdue/security-programs/copyright-policies/reporting-alleged-copyright-infringement.php">Copyright Complaints</a> |
            <a href="https://marcom.purdue.edu/" target="_blank">Brand Toolkit</a> |
            <a href="//engineering.purdue.edu/ECN/">Maintained by the Engineering Computer Network</a>
        </p>
    </div>

    <hr class="footer-divider">

    <!-- Main Footer Columns -->
    <div class="container pb-3">
        <div class="row footer-row">

            <!-- LEFT: NAVIGATION -->
            <div class="col-md-4 footer-col text-start">
                <strong class="footer-heading">Navigation</strong><br>

                <a href="index.php">Home</a><br>

                <?php if (isset($_SESSION['Role']) && 
                         ($_SESSION['Role'] === 'SupplyChainManager' || $_SESSION['Role'] === 'SeniorManager')): ?>
                    <a href="scm.php">SCM Dashboard</a><br>
                <?php endif; ?>

                <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] === 'SeniorManager'): ?>
                    <a href="erp.php">ERP Dashboard</a><br>
                <?php endif; ?>
            </div>

            <!-- CENTER: COURSE + GROUP -->
            <div class="col-md-4 footer-center text-center">
                <div class="footer-title">IE332-001</div>
                <div class="footer-subtitle">Group 12</div>
            </div>

            <!-- RIGHT: TEAM -->
            <div class="col-md-4 footer-col text-end">
                <strong class="footer-heading">Team Members</strong><br>
                <a href="mailto:aelsolh@purdue.edu">Adam Elsolh</a><br>
                <a href="mailto:huan1803@purdue.edu">Derek Huang</a><br>
                <a href="mailto:fcanale@purdue.edu">Fernando Canales</a><br>
                <a href="mailto:jishnujag@gmail.com">Jishnu Ghosh</a><br>
                <a href="mailto:lcavalie@purdue.edu">Leo Cavalier</a><br>
                <a href="mailto:yang2458@purdue.edu">Zihang Yang</a>
            </div>

        </div>
    </div>
</div>
