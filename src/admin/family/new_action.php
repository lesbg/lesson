<?php
/**
 * ***************************************************************
 * admin/family/new_action.php (c) 2015-2017, 2019 Jonathan Dieter
 *
 * Run query to insert a new family code into the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors

if(isset($_GET['key']) && dbfuncInt2String($_GET['key']) == "1") {
    $set_session = True;
} else {
    $set_session = False;
}

/* Check whether user is authorized to change scores */
if ($is_admin) {
    $remove = array(" ", "-", "_", "=", "$", ".", ",", "/", "?", "<", ">", "{", "}", "[", "]", "\\", "'", ":", ";", "|");
    $codei = str_replace($remove, "", $_POST['fname']);

    $codei = strtoupper(substr($codei, 0, 4));
    if(strlen($codei) < 4)
        $codei = $codei . str_repeat("X", 4-strlen($codei));

    if ($_POST['autofcode'] == "Y") {
        $res = & $db->query(
                        "SELECT FamilyCode FROM family WHERE FamilyCode REGEXP '{$codei}.*' ORDER BY FamilyCode DESC LIMIT 1");
        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $num = intval(substr($row['FamilyCode'], 4)) + 1;
            $_POST['fcode'] = sprintf("{$codei}%04d", $num);
        } else {
            $_POST['fcode'] = "{$codei}0001";
        }
        echo "</p>\n      <p>The {$_POST['fname']} family's code is {$_POST['fcode']}.</p>\n      <p>";
    }
    $fcode = safe($_POST['fcode']);

    /* Check whether a user already exists with new username */
    $res = & $db->query(
                    "SELECT FamilyCode FROM family WHERE FamilyCode = '$fcode'");
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo "</p>\n      <p>There is already a family with that family code.  " .
             "Press \"Back\" to fix the problem.</p>\n      <p>";
        $error = true;
    } else {
        /* Add new user */
        $query = "INSERT INTO family (FamilyCode, FamilyName, Town, RegistrationNumber, Address, House) " .
                 "VALUES ('$fcode', '{$_POST['fname']}', {$_POST['town']}, " .
                 "        {$_POST['regnum']}, {$_POST['address']}, {$_POST['house']})";
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query

        if(!isset($_POST['show_users']) || $_POST['show_users'] != '1') {
            /* Remove family members that have been removed */
            foreach($_POST['remove_uname'] as $i => $uname) {
                $query =    "DELETE FROM familylist " .
                        "WHERE FamilyCode = '$fcode' " .
                        "AND   Username = '$uname' ";
                $aRes = & $db->query($query);
                if (DB::isError($aRes))
                    die($aRes->getDebugInfo()); // Check for errors in query
            }

            /* Add any family members we've added */
            foreach($_POST['uname'] as $val) {
                $uname = $val[0];
                $guardian = $val[1];
                $query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='$uname' AND FamilyCode='$fcode'";
                $aRes = & $db->query($query);
                if (DB::isError($aRes))
                    die($aRes->getDebugInfo()); // Check for errors in query
                if ($aRes->numRows() == 0) {
                    $query = "INSERT INTO familylist (Username, FamilyCode, Guardian) VALUES ('$uname', '$fcode', $guardian)";
                    $aRes = & $db->query($query);
                    if (DB::isError($aRes))
                        die($aRes->getDebugInfo()); // Check for errors in query
                } else {
                    $query = "UPDATE familylist SET Guardian=$guardian WHERE Username='$uname' AND FamilyCode='$fcode'";
                    $aRes = & $db->query($query);
                    if (DB::isError($aRes))
                        die($aRes->getDebugInfo()); // Check for errors in query
                }
            }
        } else {
            if(!isset($_SESSION['post'])) {
                $_SESSION['post'] = array();
            }
            if(!isset($_SESSION['post']['fcode'])) {
                $_SESSION['post']['fcode'] = array();
            }
            $_SESSION['post']['fcode'][] = array($_POST['fcode'], 0);
        }

        log_event($LOG_LEVEL_ADMIN, "admin/family/new_action.php", $LOG_ADMIN,
            "Added new code {$_POST['fcode']} for the {$_POST['fname']} family.");
    }
} else { // User isn't authorized to view or change scores.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/family/new_action.php", $LOG_DENIED_ACCESS,
            "Attempted to create family code for the {$_POST['$fname']} family.");
    echo "</p>\n      <p>You do not have permission to add a family code.</p>\n      <p>";
    $error = true;
}
