<?php
/**
 * ***************************************************************
 * admin/sms/send_action.php (c) 2007, 2016-2017 Jonathan Dieter
 *
 * Actually send SMS to user
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$destusername = safe(dbfuncInt2String($_GET['key']));
$destfullname = dbfuncInt2String($_GET['keyname']);

if (! $is_admin ) {
    /* Print error message */
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "admin/sms/send_action.php",
            $LOG_DENIED_ACCESS,
            "Tried to send SMS to $destusername.");

    include "footer.php";
    exit(0);
}

/* Check which button was pressed */
if ($_POST["action"] == "Send") {
    $title = "LESSON - Sending SMS...";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header
    /* Get teacher list */
    $res = &  $db->query(
                    "SELECT user.PhoneNumber FROM user " .
                     "WHERE Username=\"$destusername\"");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) and
         ! is_null($row['PhoneNumber']) and $row['PhoneNumber'] != "") {
        if ($_POST['text'] != "") {
            $text = escapeshellarg($_POST['text']);
            $retval = 0;
            $retarray = array();
            echo "      <p align=\"center\">Sending SMS to $destfullname...";
            exec("/usr/bin/sms {$row['PhoneNumber']} $SMS_PASSWORD \"$text\"",
                $retarray, $retval);
            if ($retval == 0) {
                echo "done.<p>\n";
            } else {
                echo "failed!</p>\n";
                echo "<p align=\"center\">Return message: <pre>{$retarray[0]}</pre></p>\n";
            }
        } else {
            echo "      <p align=\"center\">The text is blank.</p>\n";
        }
    } else {
        echo "      <p align=\"center\">$destfullname has no phone number in the LESSON database!</p>\n";
    }
    echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page
} else {
    redirect($nextLink);
}
