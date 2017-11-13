<?php
/**
 * ***************************************************************
 * admin/makeuptype/modify.php (c) 2005, 2017 Jonathan Dieter
 *
 * Change information about makeup type
 * ***************************************************************
 */

/* Get variables */
if(isset($_GET['key'])) {
    $title = "Change type information for " . dbfuncInt2String($_GET['keyname']);
    $makeuptypeindex = intval(dbfuncInt2String($_GET['key']));
    $link = "index.php?location=" .
             dbfuncString2Int("admin/makeuptype/new_or_modify_action.php") .
             "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
             "&amp;next=" . dbfuncString2Int($backLink);
    $new = false;
} else {
    $title = "New makeup type";
    $link = "index.php?location=" .
             dbfuncString2Int("admin/makeuptype/new_or_modify_action.php") .
             "&amp;next=" . dbfuncString2Int($backLink);
    $new = true;
}

include "header.php"; // Show header

/* Check whether user is authorized to modify makeup type */
if (!$is_admin) {
    log_event($LOG_LEVEL_ERROR, "admin/makeuptype/modify.php",
            $LOG_DENIED_ACCESS,
            "Attempted to change information about a makeup type.");
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

$fRow = "";

if(!$new) {
    $query =    "SELECT MakeupType, Description, OriginalMax, TargetMax FROM makeup_type " .
                "WHERE MakeupTypeIndex = $makeuptypeindex";
    $fRes = & $db->query($query);
    if (DB::isError($fRes))
        die($fRes->getDebugInfo());

    /* If makeup type doesn't exist, give error message and bail */
    if (!$fRow = & $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo "      <p align='center'>Can't find makeup type.  Have you deleted it?</p>\n";
        echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
        include "footer.php";
        exit(0);
    }
}

/* If there were errors, print them, and reset fields */
if (isset($errorlist)) {
    echo $errorlist;
    $_POST['title'] = htmlspecialchars($_POST['title']);
    $_POST['descr'] = htmlspecialchars($_POST['descr']);
    $_POST['origmax'] = htmlspecialchars($_POST['origmax']);
    $_POST['targetmax'] = htmlspecialchars($_POST['targetmax']);
} else {
    if($new) {
        $_POST['title'] = "";
        $_POST['descr'] = "";
        $_POST['origmax'] = "";
        $_POST['targetmax'] = "";
    } else {
        $_POST['title'] = htmlspecialchars($fRow['MakeupType']);
        $_POST['descr'] = htmlspecialchars($fRow['Description']);
        $_POST['origmax'] = htmlspecialchars($fRow['OriginalMax']);
        $_POST['targetmax'] = htmlspecialchars($fRow['TargetMax']);
    }
}

echo "      <form action='$link' name='modSubj' method='post'>\n";
echo "         <table class='transparent' align='center'>\n";
echo "            <tr>\n";
echo "               <td>Name of makeup type</td>\n";
echo "               <td><input type='text' name='title' value='{$_POST['title']}' size=35></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td>Description</td>\n";
echo "               <td><input type='text' name='descr' value='{$_POST['descr']}' size=35></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td>Maximum original score that will get full benefit of makeup</td>\n";
echo "               <td><input type='text' name='origmax' value='{$_POST['origmax']}' size=35></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td>Target score that maximum original score that will receive if they get 100% on the makeup</td>\n";
echo "               <td><input type='text' name='targetmax' value='{$_POST['targetmax']}' size=35></td>\n";
echo "            </tr>\n";
echo "         </table>\n";
echo "         <p align='center'>\n";
if(!$new) {
    echo "            <input type='submit' name='action' value='Update' \>\n";
    echo "            <input type='submit' name='action' value='Delete' \>\n";
} else {
    echo "            <input type='submit' name='action' value='Save' \>\n";
}
echo "            <input type='submit' name='action' value='Cancel' \>\n";
echo "         </p>\n";
echo "      </form>\n";

log_event($LOG_LEVEL_EVERYTHING, "admin/makeuptype/modify.php", $LOG_ADMIN,
        "Opened a makeup type for editing.");

include "footer.php";
