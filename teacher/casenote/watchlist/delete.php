<?php
/**
 * ***************************************************************
 * teacher/casenote/watchlist/delete.php (c) 2006 Jonathan Dieter
 *
 * Delete student from teacher's watchlist
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$studentusername = safe(dbfuncInt2String($_GET['key']));

$nextLink = "index.php?location=" .
             dbfuncString2Int("teacher/casenote/watchlist/list.php");
if ($_POST['action'] == "Yes, remove from my watchlist") {
    $title = "LESSON - Removing from watchlist";
    $noJS = true;
    $noHeaderLinks = true;

    include "core/settermandyear.php";
    include "header.php";

    /* Check whether student is on current user's watchlist */
    $res = &  $db->query(
                    "SELECT WorkerUsername FROM casenotewatch " .
                     "WHERE WorkerUsername=\"$username\" " .
                     "AND   StudentUsername=\"$studentusername\"");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($res->numRows() > 0) {
        /* Remove student from watchlist */
        $res = &  $db->query(
                        "DELETE FROM casenotewatch " .
                             "WHERE CaseNoteWatchIndex=\"{$username}{$studentusername}\"");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        echo "      <p align=\"center\">Successfully removed $student from your watchlist.</p>\n";
        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
    } else {
        echo "      <p>$student is not in your watchlist.  " .
             "<a href=\"$nextLink\">Click here to continue</a>.</p>\n";
    }
} else {
    $title = "LESSON - Cancelling";
    $noJS = true;
    $noHeaderLinks = true;
    $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";

    include "header.php";

    echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
         "</p>\n";
}

include "footer.php";
?>
