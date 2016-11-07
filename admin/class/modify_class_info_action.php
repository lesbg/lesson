<?php
/**
 * ***************************************************************
 * admin/class/modify_class_info_action.php (c) 2016 Jonathan Dieter
 *
 * Change class information
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page
$classindex = dbfuncInt2String($_GET['key']); // Index of class to add and remove students from
$classname = dbfuncInt2String($_GET['keyname']);

include "core/settermandyear.php";

/* Check whether user is authorized to change class */
if ($is_admin) {
    /* Update class year */
    $res = & $db->query(
                    "SELECT YearIndex FROM class " .
                     "WHERE ClassIndex = $classindex");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $classyear = $row['YearIndex'];
    } else {
        die("Unable to find class with index $classindex!");
    }

    /* Get classterm */
    $res = & $db->query(
                    "SELECT ClassTermIndex FROM classterm " .
                     "WHERE ClassIndex = $classindex " .
                     "AND   TermIndex = $termindex");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $classterm = $row['ClassTermIndex'];
    } else {
        die("Unable to find class term with index $classindex!");
    }

    /* Check which button was pressed */
    if ($_POST["action"] == "Update") {
        $_POST['name'] = htmlspecialchars($_POST['name']);
        if (! is_null($_POST['grade']) and $_POST['grade'] != "NULL") {
            $_POST['grade'] = intval($_POST['grade']);
        } else {
            $errorlist .= "<p class='error'>You must specify a class</p>";
            $error = true;
        }

        if ($error) {
            include "admin/class/modify.php";
        } else {
            $title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
            $noHeaderLinks = true;
            $noJS = true;

            include "header.php"; // Print header

            echo "      <p align='center'>Saving changes...";

            $query =    "UPDATE class SET ClassName='{$_POST['name']}', " .
                        "                 Grade={$_POST['grade']}, " .
                        "                 DepartmentIndex=$depindex " .
                        "WHERE ClassIndex=$classindex";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
            $_GET['keyname'] = dbfuncString2Int($_POST['name']);

            if ($error) { // If we ran into any errors, print failed, otherwise print done
                echo "failed!</p>\n";
            } else {
                echo "done.</p>\n";
            }
            echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page

            include "footer.php";
            log_event($LOG_LEVEL_ADMIN, "admin/class/modify_class_info_action.php", $LOG_ADMIN,
                    "Modified information about {$_POST['name']}.");
        }
    } elseif ($_POST["action"] == "Cancel") {
        redirect($nextLink);
    } else {
        include "admin/class/modify_class_info.php";
    }
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/class/modify_class_info_action.php",
            $LOG_DENIED_ACCESS, "Attempted to modify class $classname.");

    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Unauthorized access!";

    include "header.php";

    echo "      <p align='center'>You do not have permission to access this page. <a href=" .
         "'$nextLink'>Click here to continue.</a></p>\n";

    include "footer.php";
}

?>
