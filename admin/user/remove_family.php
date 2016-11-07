<?php
/**
 * ***************************************************************
 * admin/user/remove_family.php (c) 2015 Jonathan Dieter
 *
 * Remove family code from user
 * ***************************************************************
 */

if ($is_admin) {
    if(!isset($_SESSION['post'])) {
        $_SESSION['post'] = array();
    }
    $pval = array();
    foreach($_POST as $key => $value) {
        $_SESSION['post'][$key] = $value;
    }

    if(isset($fremove) && isset($_SESSION['post']['fcode'])) {
        foreach($_SESSION['post']['fcode'] as $key => $fcode) {
            if($fcode[0] == $fremove) {
                unset($_SESSION['post']['fcode'][$key]);
            }
        }
    }
    redirect($backLink);
} else { // User isn't authorized to view or change users.
    include "header.php"; // Show header
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
}
