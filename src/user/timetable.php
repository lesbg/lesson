<?php
/**
 * ***************************************************************
 * user/timetable.php (c) 2007, 2016, 2018 Jonathan Dieter
 *
 * Show user's timetable
 * ***************************************************************
 */

/* Get variables */
$ttusername = dbfuncInt2String($_GET['key']);
$ttname = dbfuncInt2String($_GET['keyname']);
if (isset($_GET['key2']))
    $type = dbfuncInt2String($_GET['key2']);
else
    $type = NULL;
$title = "Timetable for $ttname";

if ($type == "c")
    $query = $pdb->prepare(
        "SELECT DepartmentIndex FROM class WHERE ClassIndex=?"
    );
else
    $query = $pdb->prepare(
        "SELECT DepartmentIndex FROM user WHERE Username=?"
    );
$query->execute([$ttusername]);
$row = $query->fetch();

if ($row) {
    $depindex = $row['DepartmentIndex'];
} else {
    $query = $pdb->prepare(
        "SELECT class.DepartmentIndex FROM class, classterm, classlist " .
        "WHERE  classlist.Username = :ttusername " .
        "AND    classterm.ClassTermIndex = classlist.ClassTermIndex " .
        "AND    classterm.TermIndex = :termindex " .
        "AND    class.ClassIndex = classterm.ClassIndex " .
        "AND    class.YearIndex = :yearindex"
    );
    $query->execute(['ttusername' => $ttusername, 'termindex' => $termindex,
                     'yearindex' => $yearindex]);
    $row = $query->fetch();
    if($row) {
        $depindex = $row['DepartmentIndex'];
    } else {
        $query = $pdb->prepare(
            "SELECT DepartmentIndex FROM department " .
            "ORDER BY DepartmentIndex LIMIT 1"
        );
        $query->execute();
        $row = $query->fetch();
        $depindex = $row['DepartmentIndex'];
    }
}

$query = $pdb->prepare(
    "SELECT TimetableVersion FROM currentterm WHERE DepartmentIndex=:depindex"
);
$query->execute(['depindex' => $depindex]);
$row = $query->fetch();

if (! is_null($row['TimetableVersion']) and $row['TimetableVersion'] != "") {
    $subtitle = $row['TimetableVersion'];
}

$is_guardian = check_guardian($ttusername, $username);

include "header.php"; // Show header
include "core/settermandyear.php";

/* Check whether user is authorized to access this timetable */
if (($username == $ttusername or $is_admin or $is_guardian) and $type != "c") {
    /* Get student list */
    include "core/titletermyear.php";

    $query = $pdb->prepare(
        "SELECT Username FROM user INNER JOIN groupgenmem USING (Username) " .
        "     INNER JOIN groups USING (GroupID) " .
        "WHERE user.Username=:ttusername " .
        "AND   groups.GroupTypeID='activeteacher' " .
        "AND   groups.YearIndex=:yearindex "
    );
    $query->execute(['ttusername' => $ttusername, 'yearindex' => $yearindex]);
    $row = $query->fetch();
    if ($row) {
        if ($type == NULL)
            $type = "t";
        $tttype = $type;
        include "svg/timetable.svg.php";
    }
    $query = $pdb->prepare(
        "SELECT Username FROM user INNER JOIN groupgenmem USING (Username) " .
        "     INNER JOIN groups USING (GroupID) " .
        "WHERE user.Username='$ttusername' " .
        "AND   groups.GroupTypeID='activestudent' " .
        "AND   groups.YearIndex=$yearindex "
    );
    $query->execute(['ttusername' => $ttusername, 'yearindex' => $yearindex]);
    $row = $query->fetch();
    if ($row) {
        if ($type == NULL)
            $type = "s";
        $tttype = $type;
        include "svg/timetable.svg.php";
    }

    log_event($LOG_LEVEL_EVERYTHING, "user/timetable.php", $LOG_USER,
            "Looked at $username's timetable.");
} elseif ($type == "c") {
    /* Get student list */
    include "core/titletermyear.php";

    $type = dbfuncString2Int($type);
    echo "   <div align='center'><embed src='svg/timetable.svg.php?key={$_GET['key']}&amp;key2=$type' type='image/svg+xml' width='800' height='400' pluginspage='http://www.adobe.com/svg/viewer/install/' /></div>\n";

    log_event($LOG_LEVEL_EVERYTHING, "user/timetable.php", $LOG_USER,
            "Looked at $ttname's timetable.");
} else { // User isn't authorized to view or change scores.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "user/timetable.php", $LOG_DENIED_ACCESS,
            "Tried to access $username's timetable.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
