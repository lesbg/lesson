<?php
/**
 * ***************************************************************
 * admin/counselor/modify_action.php (c) 2006 Jonathan Dieter
 *
 * Add or remove counselors.
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check whether user is authorized to change counselors */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    /* Check which button was pressed */
    if ($_POST["action"] == ">") { // If >> was pressed, remove students from
        foreach ( $_POST['remove'] as $remUserName ) { // subject
            $res = &  $db->query(
                    "DELETE FROM counselorlist " .
                         "WHERE Username     = \"$remUserName\"");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            log_event($LOG_LEVEL_ADMIN, "admin/counselor/modify_action.php", $LOG_ADMIN,
            "Removed $remUserName from being a counselor.");
        }
        include "admin/counselor/modify.php";
    } elseif ($_POST["action"] == "<") {
        foreach ( $_POST['add'] as $addUserName ) { // class
            $res = &  $db->query(
                    "SELECT Username FROM counselorlist " .
                             "WHERE Username     = \"$addUserName\"");
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            if ($res->numRows() == 0) {
                $res = & $db->query(
                                "INSERT INTO counselorlist (Username) VALUES " .
                         "                           (\"$addUserName\")");
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
            }
            log_event($LOG_LEVEL_ADMIN, "admin/counselor/modify_action.php",
                    $LOG_ADMIN, "Set $addUserName as a counselor.");
        }
        include "admin/counselor/modify.php";
    } elseif ($_POST["action"] == "Done") {
        $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
        $noJS = true;
        $noHeaderLinks = true;
        $title = "LESSON - Redirecting...";

        include "header.php";

        echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";

        include "footer.php";
    } else {
        include "admin/counselor/modify.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/counselor/modify_action.php",
            $LOG_DENIED_ACCESS, "Attempted to modify counselors.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
         "\"$nextLink\">Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
