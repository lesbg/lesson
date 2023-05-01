<?php
/**
 * ***************************************************************
 * teacher/punishment/request/new.php (c) 2006, 2018 Jonathan Dieter
 *
 * Create a punishment request
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$student_username = dbfuncInt2String($_GET['key']);

$title = "Request punishment for $student";

$link = "index.php?location=" .
         dbfuncString2Int("teacher/punishment/request/new_action.php") .
         "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
         "&amp;next=" . $_GET['next'];

$is_teacher = check_teacher_year($username, $currentyear);
$perm = get_punishment_permissions($username);

include "core/settermandyear.php";
include "header.php";

if (!$is_admin and ($perm < $PUN_PERM_REQUEST or !$is_teacher)) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/new.php",
            $LOG_DENIED_ACCESS,
            "Tried to create punishment request for $student.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php";
    exit(0);
}

log_event($LOG_LEVEL_EVERYTHING, "teacher/punishment/request/new.php",
        $LOG_TEACHER, "Starting new punishment request for $student.");
echo "      <form action='$link' method='post' name='punrequest'>\n"; // Form method
echo "         <table border='0' class='transparent' align='center' width='600px'>\n";
echo "            <tr>\n";
echo "               <td>\n";
echo "                  What punishment would you like to give $student?<br>\n";
$query = $pdb->prepare(
    "SELECT disciplinetype.DisciplineType, disciplinetype.DisciplineTypeIndex " .
    "       FROM disciplinetype, disciplineweight " .
    "WHERE  disciplinetype.DisciplineTypeIndex = disciplineweight.DisciplineTypeIndex " .
    "AND    disciplineweight.YearIndex = :yearindex " .
    "AND    disciplineweight.TermIndex = :termindex " .
    "AND    disciplineweight.DisciplineWeight > 0 " .
    "ORDER BY disciplineweight.DisciplineWeight"
);
$query->execute(['termindex' => $termindex, 'yearindex' => $yearindex]);

$count = 0;
while($row = $query->fetch()) {
    $count += 1;
    if ($count == 1) {
        $default = "checked";
    } else {
        $default = "";
    }
    echo "                  <label for='type{$row['DisciplineTypeIndex']}'>\n";
    echo "                  <input type='radio' name='type' value='{$row['DisciplineTypeIndex']}' id='type{$row['DisciplineTypeIndex']}' $default>\n";
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
$query = $pdb->query(
    "SELECT DisciplineReasonIndex, DisciplineReason FROM disciplinereason " .
    "ORDER BY DisciplineReasonIndex"
);
while($row = $query->fetch()) {
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
echo "         </table>\n";
echo "         <p align='center'>\n";
echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
echo "         </p>\n";
echo "      </form>\n";

include "footer.php";
