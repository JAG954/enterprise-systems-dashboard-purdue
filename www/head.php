<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    // header("Cache-Control: post-check=0, pre-check=0", false);
    // header("Pragma: no-cache");
    // header("Expires: 0");
    //header('Content-Type: text/html; charset=UTF-8');
} else {
    // header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    // header("Cache-Control: post-check=0, pre-check=0", false);
    // header("Pragma: no-cache");
    // header("Expires: 0");
    //header('Content-Type: text/html; charset=UTF-8');
};
?>

<head>
    <meta charset="us-ascii">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>A3_G24</title>
    
    <!--bootstrap styling & js-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!---stylesheet & js-->
    <link rel='stylesheet' href='style.css'>
    <!--script src='script.js'></script-->


</head>