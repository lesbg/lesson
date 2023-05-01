<?php
/**
 * ***************************************************************
 * admin/makeuptype/delete.php (c) 2005, 2017 Jonathan Dieter
 *
 * Delete makeup type from database
 * ***************************************************************
 */

/* Get variables */
$makeup_typeindex = intval(dbfuncInt2String($_GET['key']));
$makeup_type = dbfuncInt2String($_GET['keyname']);
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

/* Check whether user is authorized to delete makeup type */
if (!$is_admin) {
    $title = "LESSON - Deleting makeup type";
    $noJS = true;
    $noHeaderLinks = true;

    include "header.php";

    log_event($LOG_LEVEL_ERROR, "admin/makeuptype/delete.php",
            $LOG_DENIED_ACCESS, "Tried to delete makeup type $makeup_type.");
    echo "      <p>You do not have the authority to remove this makeup type.  <a href='$nextLink'>" .
         "Click here to continue</a>.</p>\n";
    include "footer.php";
    exit(0);
}

/* If user doesn't want to delete makeup type, move on */
if ($_POST['action'] != "Yes, delete makeup type")
    redirect($nextLink);

$title = "LESSON - Deleting makeup type";
$noJS = true;
$noHeaderLinks = true;

include "header.php";

$errorname = "";
$iserror = False;

// Check whether makeuptype to be deleted has any assignments
$query =    "SELECT MakeupTypeIndex FROM assignment " .
            "WHERE MakeupTypeIndex = $makeup_typeindex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
    $errorname .=   "      <p align='center'>You cannot delete $makeup_type until you change all assignments " .
                            "so they aren't using this makeup type.</p>\n";
    $iserror = True;
    log_event($LOG_LEVEL_ADMIN, "admin/makeuptype/delete.php",
            $LOG_ERROR,
            "Attempted to delete makeup type $makeup_type, but there were still subjects of that type.");
}

if ($iserror) {      // Check whether there have been any errors during the
    echo $errorname; // sanity checks
} else {
    $query =    "DELETE FROM makeup_type " .
                "WHERE MakeupTypeIndex = $makeup_typeindex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());

    echo "      <p align='center'>$makeup_type successfully deleted.</p>\n";
    log_event($LOG_LEVEL_ADMIN, "admin/makeuptype/delete.php",
            $LOG_ADMIN, "Deleted makeup type $makeup_type.");
}
echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

include "footer.php";
