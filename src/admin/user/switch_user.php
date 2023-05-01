<?php
/**
 * ***************************************************************
 * admin/user/switch_user.php (c) 2016 Jonathan Dieter
 *
 * Switch to a different user
 * ***************************************************************
 */


/* Get variables */
if(isset($_GET['key'])) {
    $uname = safe(dbfuncInt2String($_GET['key']));
} else {
    $uname = NULL;
}



if ($is_admin) {
    if(!is_null($uname)) {
        log_event($LOG_LEVEL_ADMIN, "admin/user/switch_user.php", $LOG_ADMIN,
        "Switched to user $uname.");
        session_unset();
        $_SESSION['username'] = $uname;
    }
    redirect('index.php');
} else { // User isn't authorized
    log_event($LOG_LEVEL_ERROR, "admin/user/switch_user.php", $LOG_DENIED_ACCESS,
            "Tried to switch to $uname.");

    include "header.php";
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

}

include "footer.php";
