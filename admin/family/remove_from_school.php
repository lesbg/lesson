<?php
/**
 * ***************************************************************
 * admin/family/remove_from_school.php (c) 2017 Jonathan Dieter
 *
 * Remove a student from school
 * ***************************************************************
 */

/* Get variables */
$delfamilyname = dbfuncInt2String($_GET['keyname']);
$delfcode = safe(dbfuncInt2String($_GET['key']));
$confirmed = 0;
if(!isset($_GET['next'])) {
    $nextLink = $backLink;
} else {
    $nextLink = dbfuncInt2String($_GET['next']);
}

if(isset($_POST) and isset($_POST['action'])) {
    if($_POST['action'] == "Yes, remove family from school") {
        $confirmed = 1;
    } else {
        redirect($nextLink);
        exit(0);
    }
}

if($confirmed) {
    $title = "LESSON - Removing the $delfamilyname family from school";
} else {
    $title = "LESSON - Confirm to remove the $delfamilyname family from school";
}
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to change scores */
if (!$is_admin) {
    log_event($LOG_LEVEL_ERROR, "admin/family/remove_from_school.php",
            $LOG_DENIED_ACCESS, "Tried to remove the $delfamilyname family from school.");
    echo "      <p>You do not have the authority to remove this family from the school.  <a href='$nextLink'>" .
         "Click here to continue</a>.</p>\n";
    exit(0);
}

if($yearindex != $currentyear) {
    echo "      <p align='center'>You cannot remove a family from a previous school year.  If this is really what you want to do, manually remove each student from their classes and the 'Active Student' group.</p>";
    echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";
    exit(0);
}

if(!$confirmed) {
    $link = "index.php?location=" . dbfuncString2Int("admin/family/remove_from_school.php") .
             "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
             "&amp;next=" . dbfuncString2Int($nextLink);

    $query =    "SELECT user.FirstName, user.Surname, user.Username, " .
                "       COUNT(DISTINCT subjectstudent.SubjectStudentIndex) AS subjects, " .
                "       class.ClassName, " .
                "       COUNT(DISTINCT discipline.DisciplineIndex) AS punishments " .
                "       FROM familylist INNER JOIN user " .
                "               ON (user.Username = familylist.Username " .
                "                   AND familylist.Guardian = 0) " .
                "          LEFT OUTER JOIN " .
                "            (class INNER JOIN classterm USING (ClassIndex) " .
                "                   INNER JOIN classlist USING (ClassTermIndex) " .
                "                   INNER JOIN currentterm ON classterm.TermIndex=currentterm.TermIndex) " .
                "               ON (user.Username=classlist.Username " .
                "                   AND class.YearIndex=$yearindex) " .
                "          LEFT OUTER JOIN subject " .
                "               ON subject.YearIndex=$yearindex " .
                "                  AND subject.TermIndex=classterm.TermIndex " .
                "          LEFT OUTER JOIN subjectstudent " .
                "               ON subject.SubjectIndex=subjectstudent.SubjectIndex " .
                "                  AND user.Username=subjectstudent.Username " .
                "          LEFT OUTER JOIN " .
                "            (discipline INNER JOIN disciplineweight USING (DisciplineWeightIndex)) " .
                "               ON (user.Username=discipline.Username " .
                "                   AND disciplineweight.YearIndex=$yearindex " .
                "                   AND disciplineweight.TermIndex=$termindex) " .
                "WHERE familylist.FamilyCode = '$delfcode' " .
                "GROUP BY user.Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query


    echo "      <p align='center'>Are you <b>sure</b> you want to remove the $delfamilyname family from the school?</p>\n";
    echo "      <div style='text-align: center'>\n";
    echo "         <div style='display: inline-block; text-align: left'>\n";
    echo "            <p>The following actions will occur:<br/>\n";
    echo "               <ul>\n";
    while($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $name = htmlspecialchars("{$row['FirstName']}", ENT_QUOTES);
        if(!is_null($row['ClassName'])) {
            $classname = htmlspecialchars($row['ClassName'], ENT_QUOTES);
            echo "                  <li>$name will be removed from the class {$row['ClassName']}</li>\n";
        }
        if(!is_null($row['subjects']) and $row['subjects'] > 0) {
            echo "                  <li>$name will be removed from {$row['subjects']} subjects for this term</li>\n";
        }
        if(!is_null($row['punishments']) and $row['punishments'] > 0) {
            echo "                  <li>$name will have {$row['punishments']} punishments removed from this term</li>\n";
        }
        echo "                  <li>$name's student status will be changed to inactive</li>\n";
    }
    echo "               </ul>\n";
    echo "            </p>\n";
    echo "         </div>\n";
    echo "      </div>\n";
    echo "      <form action='$link' method='post'>\n";
    echo "         <p align='center'>";
    echo "            <input type='submit' name='action' value='Yes, remove family from school' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
    echo "         </p>";
    echo "      </form>\n";

    include "footer.php";
    exit(0);
}

$query =    "SELECT user.FirstName, user.Surname, user.Username, classterm.TermIndex" .
            "       FROM familylist INNER JOIN user " .
            "               ON (user.Username = familylist.Username " .
            "                   AND familylist.Guardian = 0) " .
            "          LEFT OUTER JOIN " .
            "            (class INNER JOIN classterm USING (ClassIndex) " .
            "                   INNER JOIN classlist USING (ClassTermIndex) " .
            "               INNER JOIN currentterm ON classterm.TermIndex=currentterm.TermIndex) " .
            "               ON (user.Username=classlist.Username " .
            "                   AND class.YearIndex=$yearindex) " .
            "WHERE familylist.FamilyCode = '$delfcode' " .
            "GROUP BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

while($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    if(!is_null($row['TermIndex'])) {
        $remterm = $row['TermIndex'];
    } else {
        $remterm = $termindex;
    }
    school_remove_student($row['Username'], $yearindex, $remterm);

    $delfullname = htmlspecialchars("{$row['FirstName']} {$row['Surname']} ({$row['Username']})", ENT_QUOTES);
    log_event($LOG_LEVEL_ADMIN, "admin/family/remove_from_school", $LOG_ADMIN,
              "Removed $delfullname from school.");
    echo "      <p align='center'>$delfullname successfully removed from school.</p>\n";
}

echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

log_event($LOG_LEVEL_ADMIN, "admin/family/remove_from_school", $LOG_ADMIN,
          "Removed the $delfamilyname family from school.");

include "footer.php";
