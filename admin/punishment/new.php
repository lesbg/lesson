<?php
/**
 * ***************************************************************
 * admin/punishment/new.php (c) 2006-2017 Jonathan Dieter
 *
 * Create a punishment
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$studentusername = dbfuncInt2String($_GET['key']);

$title = "Issue punishment for $student";

$link = "index.php?location=" .
         dbfuncString2Int("admin/punishment/new_action.php") . "&amp;key=" .
         $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
         $_GET['next'];

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
         "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
         "            INNER JOIN groups USING (GroupID) " .
         "WHERE user.Username='$username' " .
         "AND   groups.GroupTypeID='activeteacher' " .
         "AND   groups.YearIndex=$yearindex " .
         "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
    $is_teacher = true;
} else {
    $is_teacher = false;
}

$query = "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

include "header.php"; // Show header

if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
     ($perm >= $PUN_PERM_ISSUE and $is_teacher)) {
    log_event($LOG_LEVEL_EVERYTHING, "admin/punishment/new.php", $LOG_TEACHER,
            "Starting new punishment for $student.");
    echo "      <form action='$link' method='post' name='punishment'>\n"; // Form method
    echo "         <table border='0' class='transparent' align='center' width='600px'>\n";
    echo "            <tr>\n";
    echo "               <td>\n";
    echo "                  What punishment would you like to give $student?<br>\n";
    $query = "SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeightIndex " .
             "       FROM disciplinetype, disciplineweight " .
             "WHERE  disciplinetype.DisciplineTypeIndex = disciplineweight.DisciplineTypeIndex " .
             "AND    disciplineweight.YearIndex = $yearindex " .
             "AND    disciplineweight.TermIndex = $termindex " .
             "AND    disciplineweight.DisciplineWeight IS NOT NULL " .
             "AND    disciplinetype.PermLevel <= $perm " .
             "ORDER BY disciplineweight.DisciplineWeightIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $count = 0;
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $count += 1;
        if ($count == 1) {
            $default = "checked";
        } else {
            $default = "";
        }
        echo "                  <label for='type{$row['DisciplineWeightIndex']}'>\n";
        echo "                  <input type='radio' name='type' value='{$row['DisciplineWeightIndex']}' id='type{$row['DisciplineWeightIndex']}' $default>\n";
        echo "                     {$row['DisciplineType']}\n";
        echo "                  </label><br>\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>\n";
    echo "                  Reason for punishment?<br>\n";
    echo "                  <select name='reason'>\n";
    echo "                  <option value='other' selected>\n";
    echo "                     Other...\n";
    $query = "SELECT DisciplineReasonIndex, DisciplineReason FROM disciplinereason " .
             "ORDER BY DisciplineReasonIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        echo "                  <option value='{$row['DisciplineReasonIndex']}'>";
        echo "{$row['DisciplineReasonIndex']} - {$row['DisciplineReason']}\n";
    }
    echo "                  </select><br>\n";
    echo "                  Other: <input type='text' name='reasonother' id='reasonothertext'>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    $dateinfo = date($dateformat);
    echo "            <tr>\n";
    echo "               <td>\n";
    echo "                  Date of rules violation?<br>\n";
    echo "                  <input type='text' name='date' value='$dateinfo' id='datetext'>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>\n";
    echo "                  Extra comments (won't be seen by student):<br>\n";
    echo "                  <input type='text' name='comment' value='' id='commenttext'>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "         </table>\n";
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>\n";
} else { // User isn't authorized to create a punishment
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/new.php", $LOG_DENIED_ACCESS,
            "Tried to create punishment for $student.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
