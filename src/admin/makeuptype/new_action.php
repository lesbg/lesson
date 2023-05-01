<?php
/**
 * ***************************************************************
 * admin/makeuptype/new_action.php (c) 2005, 2017 Jonathan Dieter
 *
 * Run query to insert a new makeup type into the database.
 * ***************************************************************
 */
$error = false; // Boolean to store any errors

/* Check whether user is authorized to add new makeup type */
if (!$is_admin) {
    log_event($LOG_LEVEL_ERROR, "admin/makeuptype/new_action.php",
            $LOG_DENIED_ACCESS,
            "Attempted to create new makeup type $makeuptype.");
    echo "</p>\n      <p>You do not have permission to add a makeup type.</p>\n      <p>";
    $error = true;
    exit(0);
}

/* Check whether a makeup type already exists with same name */
$query = "SELECT MakeupTypeIndex FROM makeup_type WHERE MakeupType = {$_POST['title']}";
$res = & $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    echo "</p>\n      <p>There is already a makeup type with that name.  " .
         "Press \"Back\" to fix the problem.</p>\n      <p>";
    $error = true;
} else {
    /* Add new makeup type */
    $query =    "INSERT INTO makeup_type (MakeupType, Description, OriginalMax, TargetMax) " .
                "VALUES ({$_POST['title']}, {$_POST['descr']}, {$_POST['origmax']}, {$_POST['targetmax']})";
    $aRes = & $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo());
    log_event($LOG_LEVEL_ADMIN, "admin/makeuptype/modify_action.php", $LOG_ADMIN,
        "Modified information about makeup type {$_POST['title']}.");
}
