<?php
// Start session
session_start();

// -------------------------------------------------------
// PREVENT BROWSER FROM CACHING ANY LOGGED-IN PAGES
// -------------------------------------------------------
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// -------------------------------------------------------
// CLEAR ALL SESSION DATA
// -------------------------------------------------------
$_SESSION = [];

// Destroy session file
session_destroy();

// -------------------------------------------------------
// DELETE SESSION COOKIE (prevents ghost sessions)
// -------------------------------------------------------
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// -------------------------------------------------------
// REDIRECT TO LOGIN PAGE
// -------------------------------------------------------
header("Location: index.php?logout=success");
exit();
?>