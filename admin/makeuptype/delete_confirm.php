<?php
/**
 * ***************************************************************
 * admin/makeuptype/delete_confirm.php (c) 2005, 2017 Jonathan Dieter
 *
 * Confirm deletion of a makeup type from database
 * ***************************************************************
 */

/* Get variables */
$makeuptype = dbfuncInt2String($_GET['keyname']);

$title = "LESSON - Confirm to delete $makeuptype";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to delete makeup type */
if (!$is_admin) {
    log_event($LOG_LEVEL_ERROR, "admin/makeuptype/delete_confirm.php",
            $LOG_DENIED_ACCESS, "Tried to delete makeup type $makeuptype.");
    $nextLink = dbfuncInt2String($_GET['next']);
    echo "      <p>You do not have the authority to remove this subject.  <a href='$nextLink'>" .
         "Click here to continue</a>.</p>\n";
    include "footer.php";
    exit(0);
}

$link = "index.php?location=" .
         dbfuncString2Int("admin/makeuptype/delete.php") . "&amp;key=" .
         $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
         $_GET['next'];

echo "      <p align='center'>Are you <b>sure</b> you want to delete $makeuptype?  In order to succeed in " .
                "deleting it, you must make sure no assignments are using this type.</p>\n";
echo "      <form action='$link' method='post'>\n";
echo "         <p align='center'>";
echo "            <input type='submit' name='action' value='Yes, delete makeup type' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";

include "footer.php";
