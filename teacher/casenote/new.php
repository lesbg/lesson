<?php
/**
 * ***************************************************************
 * teacher/casenote/new.php (c) 2006, 2018 Jonathan Dieter
 *
 * Create new casenote
 * ***************************************************************
 */

/* Get variables */
$student = dbfuncInt2String($_GET['keyname']);
$student_username = dbfuncInt2String($_GET['key']);
$student_first_name = dbfuncInt2String($_GET['keyname2']);

$title = "New Casenote for $student";

$link = "index.php?location=" .
         dbfuncString2Int("teacher/casenote/new_action.php") . "&amp;key=" .
         $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;keyname2=" .
         $_GET['keyname2'];

include "core/settermandyear.php";

$is_principal = check_principal($username);
$is_hod = check_hod_student($username, $student_username, $currentyear, $currentterm);
$is_counselor = check_counselor($username);
$is_class_teacher = check_class_teacher_student($username, $student_username,
                                                $currentyear, $currentterm);
$is_support_teacher = false;
$is_teacher = check_teacher_student($username, $student_username, $currentyear,
                                    $currentterm);

$extra_js = "casenotes.js";

include "header.php"; // Show header

if (!$is_principal and !$is_hod and !$is_counselor and !$is_class_teacher and
    !$is_support_teacher and !$is_teacher) {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "teacher/casenote/new.php", $LOG_DENIED_ACCESS,
            "Tried to create\n casenote for $student.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php";

    exit(0);
}

log_event($LOG_LEVEL_EVERYTHING, "teacher/casenote/new.php", $LOG_TEACHER,
        "Starting new casenote for $student.");
if ($is_principal or $is_hod or $is_counselor) {
    echo "      <script language='JavaScript' type='text/javascript'>\n";
    echo "         window.onload = check_counselor_list;\n";
    echo "      </script>\n";
}
echo "      <form action='$link' method='post' name='casenote'>\n"; // Form method
echo "         <table border='0' class='transparent' align='center' width='600px'>\n";
echo "            <tr>\n";
echo "               <td>\n";
echo "                  Who should be allowed to see this casenote?<br>\n";
echo "                  <label for='level5'>\n";
echo "                  <input type='radio' name='level' value='5' id='level5' onchange='check_counselor_list();'>\n";
echo "                  <span class='cn-level5'>Myself and the principal (Level 5)</span>\n";
echo "                  </label><br>\n";
echo "                  <label for='level4'>\n";
echo "                  <input type='radio' name='level' value='4' id='level4' onchange='check_counselor_list();'>\n";
echo "                  <span class='cn-level4'>As above and the head of department (Level 4)</span>\n";
echo "                  </label><br>\n";
echo "                  <label for='level3'>\n";
echo "                  <input type='radio' name='level' value='3' id='level3' onchange='check_counselor_list();'>\n";
echo "                  <span class='cn-level3'>As above and specified counselors (Level 3)</span>\n";
echo "                  </label><br>\n";
echo "                  <label for='level2'>\n";
echo "                  <input type='radio' name='level' value='2' id='level2' onchange='check_counselor_list();' checked>\n";
echo "                  <span class='cn-level2'>As above and $student_first_name's class teacher (Level 2)</span>\n";
echo "                  </label><br>\n";
echo "                  <label for='level1'>\n";
echo "                  <input type='radio' name='level' value='1' id='level1' onchange='check_counselor_list();'>\n";
echo "                  <span class='cn-level1'>As above and all of $student_first_name's teachers (Level 1)</span>\n";
echo "                  </label><br>\n";
echo "               </td>\n";
echo "               <td>\n";
echo "                  Counselors:<br>\n";
echo "                  <select name='counselor_list[]' width='600px' multiple size=7 id='counselor_list' disabled='true'>\n";
$query = $pdb->query(
    "SELECT user.FirstName, user.Surname, user.Username FROM " .
    "       user, counselorlist " .
    "WHERE counselorlist.Username = user.Username " .
    "ORDER BY user.Username"
);
while ( $row = $query->fetch() ) {
    echo "                     <option value='{$row['Username']}' selected>{$row['FirstName']} " .
         "{$row['Surname']} ({$row['Username']})\n";
}
echo "                  </select>\n";
echo "               </td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td colspan='2'>\n";
echo "                  Casenote:<br>\n";
echo "                  <textarea rows='10' cols='78' name='note'>" .
     "</textarea>\n";
echo "               </td>\n";
echo "            </tr>\n";
echo "         </table>\n";
echo "         <p align='center'>\n";
echo "            <input type='submit' name='action' value='Save'>&nbsp;\n";
echo "            <input type='submit' name='action' value='Cancel'>&nbsp;\n";
echo "         </p>\n";
echo "         <p align='center'>WARNING: Once you have saved a casenote, there is no way to change or delete it.</p>\n";
echo "      </form>\n";

include "footer.php";
