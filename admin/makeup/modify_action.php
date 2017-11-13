<?php
/**
 * ***************************************************************
 * admin/makeup/modify_action.php (c) 2016-2017 Jonathan Dieter
 *
 * Run query to add or change a makeup
 * ***************************************************************
 */

/* Get variables */
if(isset($_GET['next'])) {
    $backLink = dbfuncInt2String($_GET['next']);
    $nextLink = $backLink;
}

$makeup_index = safe(dbfuncInt2String($_GET['key']));
$error = false; // Boolean to store any errors
$error_list = array();

if (!$is_admin) {
    include "header.php";

    log_event($LOG_LEVEL_ERROR, "admin/makeup/modify_action.php",
        $LOG_DENIED_ACCESS, "Tried to modify makeup information.");
    echo "      <p>You do not have permission to change this makeup.</p>\n";

    include "footer.php";

    exit(0);
}

if (isset($_POST['category_index']) and $_POST['category_index'] != "") {
    $_POST['category_index'] = intval($_POST['category_index']);
} else {
    $_POST['category_index'] = -1;
}

if(!isset($_SESSION['makeup_assignment']))
    $_SESSION['makeup_assignment'] = array();

foreach($_POST as $key => $value) {
    if(substr($key, 0, 4) == "hid_") {
        $index = intval(substr($key, 4));
        if(array_key_exists("cbox_$index", $_POST)) {
            if(!in_array($index, $_SESSION['makeup_assignment']))
                $_SESSION['makeup_assignment'][] = $index;
        } else {
            if(($key = array_search($index, $_SESSION['makeup_assignment'])) !== False)
                unset($_SESSION['makeup_assignment'][$key]);
        }
    }
}

if ($_POST['action'] == "Update filters" or $_POST['action'] == "Select all" or $_POST['action'] == "Deselect all") {
    if ($_POST['action'] == "Select all")
        $check_all = True;
    if ($_POST['action'] == "Deselect all")
        $uncheck_all = True;

    include "admin/makeup/modify.php";
    exit(0);
}

if ($_POST['action'] == "Delete" or $_POST['action'] == "Update") {
    $makeup_index = intval($makeup_index);
    if($makeup_index == 0) {
        echo "      <p class='error'>Invalid makeup index.</p>\n";
        unset($_SESSION['makeup_assignment']);
        unset($_SESSION['post']);
        include "footer.php";
        exit(0);
    }
}

if ($_POST['action'] == "Delete") {
    $query = "DELETE makeup_user FROM makeup_user INNER JOIN makeup_assignment USING (MakeupAssignmentIndex) WHERE MakeupIndex=$makeup_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    $query = "DELETE FROM makeup_assignment WHERE MakeupIndex=$makeup_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    $query = "DELETE FROM makeup WHERE MakeupIndex=$makeup_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
}

if ($_POST['action'] != "Update" and $_POST['action'] != "Save") {
    unset($_SESSION['makeup_assignment']);
    unset($_SESSION['post']);
    redirect($nextLink);
    exit(0);
}

if(isset($_POST['open_date']) and $_POST['open_date'] != "") {
    $open_date = dbfuncCreateDate($_POST['open_date']);
    if($open_date === False) {
        $error = True;
        $error_list[] = "Registration open date is not a proper date";
    }
} else {
    $error = True;
    $error_list[] = "You must specify the date this makeup is open for registration";
}

if(isset($_POST['close_date']) and $_POST['close_date'] != "") {
    $close_date = dbfuncCreateDate($_POST['close_date']);
    if($close_date === False) {
        $error = True;
        $error_list[] = "Registration closed date is not a proper date";
    }
} else {
    $error = True;
    $error_list[] = "You must specify the date this makeup is closed for registration";
}

if(isset($_POST['makeup_date']) and $_POST['makeup_date'] != "") {
    $makeup_date = dbfuncCreateDate($_POST['makeup_date']);
    if($makeup_date === False) {
        $error = True;
        $error_list[] = "Makeup date is not a proper date";
    }
} else {
    $error = True;
    $error_list[] = "You must specify the date of this makeup";
}

if(isset($_POST['mandatory_lower']) and $_POST['mandatory_lower'] != "") {
    $mandatory_lower = floatval($_POST['mandatory_lower']);
    if(strval($mandatory_lower) != $_POST['mandatory_lower']) {
        $error = True;
        $error_list[] = "'Mandatory if average is lower than' value must be a number";
    }
} else {
    echo "</p>\n<p align='center'>'Mandatory if average is lower than' value not specified, defaulting to 0.</p>\n<p align='center'>";
    $mandatory_lower = 0;
}

if(isset($_POST['optional_lower']) and $_POST['optional_lower'] != "") {
    $optional_lower = floatval($_POST['optional_lower']);
    if(strval($optional_lower) != $_POST['optional_lower']) {
        $error = True;
        $error_list[] = "'Optional if average is lower than' value must be a number";
    }
} else {
    echo "</p>\n<p align='center'>'Optional if average is lower than' value not specified, defaulting to 70.</p>\n<p align='center'>";
    $optional_lower = 70;
}

if($error) {
    include "admin/makeup/modify.php";
    exit(0);
}


$title = "LESSON - Saving changes...";
$noHeaderLinks = true;
$noJS = true;

include "header.php"; // Print header

echo "      <p align='center'>Saving changes...";

if($_POST['action'] == "Save") {
    $makeup_index = "NULL";
    $makeup_username = $username;
} else {
    $query = "SELECT Username FROM makeup WHERE MakeupIndex=$makeup_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if(!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo "</p><p align='center'>Error: Unable to find makeup!</p>\n";
        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
        include "footer.php";
        unset($_SESSION['makeup_assignment']);
        unset($_SESSION['post']);
        exit(0);
    }
    $makeup_username = $row['Username'];
}

$query =    "REPLACE INTO makeup (MakeupIndex, OpenDate, CloseDate, MakeupDate, " .
            "                     MandatoryLower, OptionalLower, Username, YearIndex) " .
            "             VALUES ($makeup_index, '$open_date', '$close_date', '$makeup_date', " .
            "                     $mandatory_lower, $optional_lower, '$makeup_username', $yearindex) ";

$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());
if($makeup_index == "NULL") {
    $query =    "SELECT MakeupIndex FROM makeup " .
                "WHERE OpenDate='$open_date' " .
                "AND   CloseDate='$close_date' " .
                "AND   MakeupDate='$makeup_date' " .
                "AND   MandatoryLower=$mandatory_lower " .
                "AND   OptionalLower=$optional_lower " .
                "AND   Username='$makeup_username'";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if(!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC))
        die("New makeup not created");

    $makeup_index = $row['MakeupIndex'];
}

// Add missing makeup assignments to makeup
foreach($_SESSION['makeup_assignment'] as $aidx) {
    $aidx = intval($aidx);
    $query = "SELECT AssignmentIndex FROM assignment WHERE AssignmentIndex=$aidx";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if($res->numRows() == 0)
        continue;

    $query =    "SELECT MakeupAssignmentIndex FROM makeup_assignment " .
                "WHERE AssignmentIndex=$aidx " .
                "AND   MakeupIndex=$makeup_index";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo());
    if(!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $query =    "INSERT INTO makeup_assignment (MakeupIndex, AssignmentIndex) " .
                    "                       VALUES ($makeup_index, $aidx)";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());

        $query =    "SELECT MakeupAssignmentIndex FROM makeup_assignment " .
                    "WHERE AssignmentIndex=$aidx " .
                    "AND   MakeupIndex=$makeup_index";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo());
        if(!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC))
            die("Unable to add assignment to makeup");
    }
    $makeup_assigment_index = $row['MakeupAssignmentIndex'];

    // Add mandatory students to makeup
    makeup_add_students($makeup_assigment_index, $aidx, $mandatory_lower, $optional_lower);
}

$query =    "SELECT MakeupAssignmentIndex, AssignmentIndex FROM makeup_assignment " .
            "WHERE  MakeupIndex=$makeup_index";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());

while($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    if(!in_array($row['AssignmentIndex'], $_SESSION['makeup_assignment']))
        makeup_remove_students($row['MakeupAssignmentIndex']);
}

echo "done.</p>\n";

echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page

unset($_SESSION['makeup_assignment']);
unset($_SESSION['post']);

include "footer.php";
