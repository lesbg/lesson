<?php
/**
 * ***************************************************************
 * admin/makeup/delete.php (c) 2016-2017 Jonathan Dieter
 *
 * Confirm that a user's makeup should be deleted, then delete it
 * ***************************************************************
 */

/* Get variables */
if(isset($_GET['next'])) {
    $backLink = dbfuncInt2String($_GET['next']);
}
$nextLink = $backLink;

if(isset($_GET['key'])) {
    $title = "Delete makeup for " . htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
    $makeup_user = htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
    $makeup_user_index = safe(dbfuncInt2String($_GET['key']));
} else {
    redirect($nextLink);
    exit(0);
}

if (!$is_admin) {
    include "header.php";

    log_event($LOG_LEVEL_ERROR, "admin/makeup/delete.php",
        $LOG_DENIED_ACCESS, "Tried to delete makeup.");
    echo "      <p>You do not have permission to delete this makeup.</p>\n";

    include "footer.php";

    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] == "Yes, delete makeup") {
        $noJS = true;
        $noHeaderLinks = true;

        include "core/settermandyear.php";
        include "header.php"; // Print header

        echo "      <p align='center'>Deleting makeup...";

        $query =    "SELECT MakeupUserIndex FROM makeup_user " .
                    "WHERE MakeupUserIndex=$makeup_user_index";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if($res->numRows() > 0) {
            $query =    "DELETE FROM makeup_user WHERE MakeupUserIndex=$makeup_user_index";
            $res = &  $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
        } else {
            echo "</p><p align='center'>Makeup doesn't exist</p><p align='center'>\n";
        }
        echo "done</p>\n";

        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page

        include "footer.php";
        exit(0);
    }

    redirect($nextLink);
}

$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

$link = "index.php?location=" .
        dbfuncString2Int("admin/makeup/delete.php") .
        "&amp;key=" . $_GET['key'] .
        "&amp;keyname=" . $_GET['keyname'] .
        "&amp;next=" . dbfuncString2Int($nextLink);

echo "      <p align='center'>Are you <b>sure</b> you want to delete the makeup for $makeup_user?</p>\n";
echo "      <form action='$link' method='post'>\n";
echo "         <p align='center'>";
echo "            <input type='submit' name='action' value='Yes, delete makeup' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";

include "footer.php";
