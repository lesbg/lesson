<?php
/**
 * ***************************************************************
 * admin/makeuptype/modify_action.php (c) 2005, 2017 Jonathan Dieter
 *
 * Run query to modify a makeup type in the database.
 * ***************************************************************
 */

$makeuptypeindex = intval(dbfuncInt2String($_GET['key']));
$makeuptype = $_POST['title'];
$error = false;

/* Check whether user is authorized to modify a makeup type */
if (!$is_admin) {
    log_event($LOG_LEVEL_ERROR, "admin/makeuptype/modify_action.php",
            $LOG_DENIED_ACCESS,
            "Attempted to modify a makeup type $makeuptype.");
    echo "</p>\n      <p>You do not have permission to add a makeup type.</p>\n      <p>";
    $error = true;
    exit(0);
}

$query =    "UPDATE makeup_type SET MakeupType={$_POST['title']}, Description={$_POST['descr']}, " .
            "                       OriginalMax={$_POST['origmax']}, TargetMax={$_POST['targetmax']} " .
            "WHERE  MakeupTypeIndex = $makeuptypeindex";
$aRes = & $db->query($query);
if (DB::isError($aRes))
    die($aRes->getDebugInfo());
log_event($LOG_LEVEL_ADMIN, "admin/makeuptype/modify_action.php", $LOG_ADMIN,
    "Modified information about makeup type {$_POST['title']}.");
