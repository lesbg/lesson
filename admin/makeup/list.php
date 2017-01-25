<?php
/**
 * ***************************************************************
 * admin/makeup/list.php (c) 2016-2017 Jonathan Dieter
 *
 * List all makeups
 * ***************************************************************
 */

$title = "Makeups";


include "header.php"; // Show header
$showyear = True;
$showterm = False;
$showdeps = False;
include "core/settermandyear.php";
include "core/titletermyear.php";

if (!$is_admin) {
   /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/makeup/list.php", $LOG_DENIED_ACCESS,
            "Attempted to view list of makeup exams.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    exit(0);
}

$newlink =  "index.php?location=" .
            dbfuncString2Int("admin/makeup/modify.php") . // link to create a new subject
            "&amp;next=" .
            dbfuncString2Int(
                        "index.php?location=" .
                         dbfuncString2Int("admin/makeup/list.php"));
$newbutton = dbfuncGetButton($newlink, "New makeup", "medium", "",
                            "Create new makeup");

echo "      <p align=\"center\">$newbutton</p>\n";

$query =    "SELECT * FROM " .
            "(SELECT makeup.MakeupIndex, makeup.OpenDate, makeup.CloseDate, " .
            "        makeup.MakeupDate, makeup.MandatoryLower, " .
            "        makeup.OptionalLower, user.Title, user.FirstName, " .
            "        user.Surname, user.Username, " .
            "        COUNT(DISTINCT makeup_assignment.AssignmentIndex) AS AssignmentCount, " .
            "        COUNT(DISTINCT CASE WHEN makeup_user.Requested=1 THEN makeup_user.Username END) AS StudentCount, " .
            "        COUNT(DISTINCT CASE WHEN makeup_user.Requested=1 THEN makeup_user.MakeupUserIndex END) AS MakeupCount " .
            " FROM makeup LEFT OUTER JOIN makeup_assignment USING (MakeupIndex) " .
            "             LEFT OUTER JOIN makeup_user ON " .
            "              (makeup_user.MakeupAssignmentIndex=makeup_assignment.MakeupAssignmentIndex) " .
            "             INNER JOIN user ON makeup.Username=user.Username " .
            " WHERE makeup.YearIndex=$yearindex " .
            " GROUP BY makeup.MakeupIndex " .
            ") AS subtable " .
            "WHERE MakeupIndex IS NOT NULL " .
            "ORDER BY MakeupDate DESC";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if($res->numRows() == 0) {
    echo "      <p align='center'>There are no makeups.</p>\n";
    include "footer.php";
    exit(0);
}

echo "      <table align='center' border='1'>\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Makeup date $nameAsc $nameDec</th>\n";
echo "            <th>Registration open $openAsc $openDec</th>\n";
echo "            <th>Registration closed $closeAsc $closeDec</th>\n";
echo "            <th><a title='Mandatory if average is less than this mark'>M&lt;</a></th>";
echo "            <th><a title='Optional if average is less than this mark'>O&lt;</a></th>";
echo "            <th>Created by $teacherAsc $teacherDec</th>\n";
echo "            <th>Assignments $assignAsc $assignDec</th>\n";
echo "            <th>Registered students $studentAsc $studentDec</th>\n";
echo "            <th>Makeups registered $makeupAsc $makeupDec</th>\n";
echo "         </tr>\n";

$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt = " class=\"alt\"";
    } else {
        $alt = " class=\"std\"";
    }

    $open_date = date($dateformat, strtotime($row['OpenDate']));
    $close_date = date($dateformat, strtotime($row['CloseDate']));
    $makeup_date = date($dateformat, strtotime($row['MakeupDate']));

    $viewlink =  "index.php?location=" .
                 dbfuncString2Int("admin/makeup/list_assignments.php") . // link to create a new subject
                 "&amp;key=" . dbfuncString2Int($row['MakeupIndex']) .
                 "&amp;keyname=" . dbfuncString2Int($row['MakeupDate']);

    $editlink =  "index.php?location=" .
                 dbfuncString2Int("admin/makeup/modify.php") . // link to create a new subject
                 "&amp;key=" . dbfuncString2Int($row['MakeupIndex']) .
                 "&amp;keyname=" . dbfuncString2Int($makeup_date) .
                 "&amp;next=" .
                 dbfuncString2Int(
                            "index.php?location=" .
                             dbfuncString2Int("admin/makeup/list.php"));

    $viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
                            "View assignments for makeup");
    $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                            "Edit makeup");

    echo "         <tr$alt>\n";
    echo "            <td>$viewbutton$editbutton</td>";
    echo "            <td>$makeup_date</td>\n";
    echo "            <td>$open_date</td>\n";
    echo "            <td>$close_date</td>\n";
    echo "            <td>{$row['MandatoryLower']}</td>\n";
    echo "            <td>{$row['OptionalLower']}</td>\n";
    echo "            <td>{$row['Title']} {$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
    echo "            <td>{$row['AssignmentCount']}</td>\n";
    echo "            <td>{$row['StudentCount']}</td>\n";
    echo "            <td>{$row['MakeupCount']}</td>\n";
    echo "         </tr>\n";
}

echo "         </table>\n";

include "footer.php";
