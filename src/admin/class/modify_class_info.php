<?php
/**
 * ***************************************************************
 * admin/class/modify_class_info.php (c) 2016 Jonathan Dieter
 *
 * Change information about class
 * ***************************************************************
 */

/* Get variables */
if (isset($_GET['next']))
    $nextLink = dbfuncInt2String($_GET['next']);

if (! isset($nextLink))
    $nextLink = $backLink;

$title = "Modify " . dbfuncInt2String($_GET['keyname']);
$classindex = dbfuncInt2String($_GET['key']);
$link = "index.php?location=" . dbfuncString2Int("admin/class/modify_class_info_action.php") .
         "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
         "&amp;next=" . dbfuncString2Int($nextLink);

include "header.php"; // Show header

$showalldeps = true; // edit subjects
include "core/settermandyear.php";

/* Check whether user is authorized to change class */
if ($is_admin) {
    /* Get subject information */
    $query = "SELECT ClassName, DepartmentIndex, Grade FROM class " .
             "WHERE ClassIndex = $classindex";
    $fRes = & $db->query($query);
    if (DB::isError($fRes))
        die($fRes->getDebugInfo()); // Check for errors in query

    if ($fRow = & $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
        if (isset($errorlist)) { // If there were errors, print them, and reset fields
            echo $errorlist;
            $_POST['name'] = htmlspecialchars($_POST['name']);
            $_POST['depindex'] = intval($_POST['depindex']);
            $_POST['grade'] = intval($_POST['grade']);
        } else {
            $_POST['name'] = htmlspecialchars($fRow['ClassName']);
            $_POST['depindex'] = intval($_POST['DepartmentIndex']);
            $_POST['grade'] = intval($fRow['Grade']);
        }

        echo "            <form action='$link' name='modClass' method='post'>\n"; // Form method
        echo "                <table class='transparent' align='center'>\n"; // Table headers

        /* Show class name */
        echo "            <tr>\n";
        echo "               <td>Name</td>\n";
        echo "               <td><input type='text' name='name' value='{$_POST['name']}' size=35></td>\n";
        echo "            </tr>\n";

        /* Show list of grades */
        $res = &  $db->query(
                        "SELECT GradeName, Grade FROM grade WHERE DepartmentIndex=$depindex ORDER BY Grade");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        echo "            <tr>\n";
        echo "               <td>Grade</td>\n";
        echo "               <td><select name='grade'>\n";
        echo "                  <option value='NULL'";
        if (is_null($_POST['grade']))
            echo " selected";
        echo ">(No specific grade)\n";
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            echo "                  <option value='{$row['Grade']}'";
            if ($row['Grade'] == $_POST['grade'])
                echo " selected";
            echo ">{$row['GradeName']}\n";
        }
        echo "               </select></td>\n";
        echo "            </tr>\n";

        echo "         </table>\n"; // End of table
        echo "         <p align='center'>\n";
        echo "            <input type='submit' name='action' value='Update' \>\n";
        echo "            <input type='submit' name='action' value='Cancel' \>\n";
        echo "         </p>\n";
        echo "      </form>\n";
    } else { // Couldn't find $subjectindex in subject table
        echo "      <p align='center'>Can't find subject.  Have you deleted it?</p>\n";
        echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
    }
    log_event($LOG_LEVEL_EVERYTHING, "admin/class/modify_class_info.php", $LOG_ADMIN,
            "Opened class $title for editing.");
} else { // User isn't authorized to view or change scores.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/class/modify_class_info.php", $LOG_DENIED_ACCESS,
            "Attempted to change information about $title.");
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
