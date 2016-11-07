<?php
/**
 * ***************************************************************
 * admin/sms/send_action.php (c) 2007 Jonathan Dieter
 *
 * Actually send SMS to user
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$destusername = safe(dbfuncInt2String($_GET['key']));
$destfullname = dbfuncInt2String($_GET['keyname']);

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
