<?php
/**
 * ***************************************************************
 * teacher/report/class_action.php (c) 2008, 2018 Jonathan Dieter
 *
 * Confirm change report information for class at a time
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['next']))
    $_GET['next'] = dbfuncString2Int($backLink);
$class = dbfuncInt2String($_GET['keyname']);
$classterm_index = dbfuncInt2String($_GET['key']);
$nextLink = dbfuncInt2String($_GET['next']);

/* Check whether subject is open for report editing */
$query->prepare(
    "SELECT classterm.CTCommentType, class.DepartmentIndex, " .
    "       classterm.HODCommentType, classterm.PrincipalCommentType, " .
    "       classterm.CanDoReport, department.ProofreaderUsername " .
    "       FROM classterm, class, department " .
    "WHERE classterm.ClassTermIndex      = :classterm_index " .
    "AND   class.ClassIndex           = classterm.ClassIndex " .
    "AND   department.DepartmentIndex = class.DepartmentIndex "
);
$query->execute(['classterm_index' => $classterm_index]);

if (!$row = $query->fetch() or $row['CanDoReport'] == 0) {
    /* Print error message */
    include "header.php";
    echo "      <p>Reports for this class aren't open.</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/class_action.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

$ct_comment_type = $row['CTCommentType'];
$hod_comment_type = $row['HODCommentType'];
$pr_comment_type = $row['PrincipalCommentType'];
$can_do_report = $row['CanDoReport'];
$depindex = $row['DepartmentIndex'];
$proof_username = $row['ProofreaderUsername'];

$is_principal = check_principal($username);
$is_hod = check_hod_classterm($username, $classterm_index);
$is_ct = check_class_teacher_classterm($username, $classterm_index);

/* Check whether user is proofreader */
if ($proof_username == $username) {
    $is_proofreader = true;
} else {
    $is_proofreader = false;
}

include "core/settermandyear.php";

if (!$is_ct and !$is_hod and !$is_principal and !$is_admin and !$is_proofreader) {
    include "header.php"; // Show header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    log_event($LOG_LEVEL_ERROR, "teacher/report/class_action.php",
            $LOG_DENIED_ACCESS, "Tried to modify report for $subject.");

    include "footer.php";
    exit(0);
}

if ($_POST['action'] != "Yes, I'm finished" and
     $_POST['action'] != "Yes, close reports") {
    redirect($nextLink);
    exit(0);
}


$title = "LESSON - Saving changes...";
$noHeaderLinks = true;
$noJS = true;
$is_error = false;

include "header.php";

echo "      <p align='center'>Saving changes...";

if ($_POST['action'] == "Yes, I'm finished") {
    $query = $pdb->prepare(
        "SELECT classlist.ClassTermIndex, user.Gender, user.FirstName, user.Surname, " .
        "       classlist.CTComment, classlist.HODComment, " .
        "       classlist.CTCommentDone, classlist.HODCommentDone, " .
        "       classlist.PrincipalComment, classlist.PrincipalCommentDone, " .
        "       classlist.PrincipalUsername, classlist.HODUsername, " .
        "       classlist.ReportDone " .
        "       FROM user, classlist " .
        "WHERE classlist.ClassTermIndex = :classterm_index " .
        "AND   classlist.Username       = user.Username "
    );
    $query->execute(['classterm_index' => $classterm_index]);

    while($row = $query->fetch()) {
        if ($row['ReportDone']) {
            if (($is_ct and $ct_comment_type != $COMMENT_TYPE_NONE and
                 ! $row['CTCommentDone']) or
                 ($is_hod and $hod_comment_type != $COMMENT_TYPE_NONE and
                 ! $row['HODCommentDone']) or
                 ($is_principal and $pr_comment_type != $COMMENT_TYPE_NONE and
                 ! $row['PrincipalCommentDone'])) {
                echo "</p><p align='center'>Error: Report for {$row['FirstName']} {$row['Surname']} is closed.  Please open it first, then try again.</p><p align='center'>";
                $is_error = true;
            }
            continue;
        }
        if($is_ct and $ct_comment_type != $COMMENT_TYPE_NONE and
           !$row['CTCommentDone']) {
            if(!is_null($row['CTComment']) or
               $ct_comment_type != $COMMENT_TYPE_MANDATORY) {
                $pdb->prepare(
                    "UPDATE classlist SET CTCommentDone=1 " .
                    "WHERE ClassTermIndex = :ctindex"
                )->execute(['ctindex' => $row['ClassTermIndex']]);
            } else {
                echo "</p><p align='center'>Error: You must write a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>";
                $is_error = true;
            }
        }
        if($is_hod and $hod_comment_type != $COMMENT_TYPE_NONE and
           !$row['HODCommentDone']) {
            if(!is_null($row['HODComment']) or
               $hod_comment_type != $COMMENT_TYPE_MANDATORY) {
                $pdb->prepare(
                    "UPDATE classlist SET HODCommentDone=1 " .
                    "WHERE ClassTermIndex = :ctindex"
                )->execute(['ctindex' => $row['ClassTermIndex']]);
            } else {
                echo "</p><p align='center'>Error: You must write a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>";
                $is_error = true;
            }
        }
        if($is_principal and $pr_comment_type != $COMMENT_TYPE_NONE and
           !$row['PrincipalCommentDone']) {
            if(!is_null($row['PrincipalComment']) or
               $pr_comment_type != $COMMENT_TYPE_MANDATORY) {
                $pdb->prepare(
                    "UPDATE classlist SET PrincipalCommentDone=1 " .
                    "WHERE ClassTermIndex = :ctindex"
                )->execute(['ctindex' => $row['ClassTermIndex']]);
            } else {
                echo "</p><p align='center'>Error: You must write a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>";
                $is_error = true;
            }
        }
    }
} elseif ($_POST['action'] == "Yes, close reports") {
    if (! $is_hod and ! $is_principal and ! $is_admin) {
        echo "</p><p align='center'>Error: You do not have permission to close these reports.</p><p align='center'>\n";
        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

        include "footer.php";
        exit(0);
    }

    $query = $pdb->prepare(
        "SELECT classlist.ClassTermIndex, user.Gender, user.FirstName, user.Surname, " .
        "       classlist.CTComment, classlist.HODComment, " .
        "       classlist.CTCommentDone, classlist.HODCommentDone, " .
        "       classlist.PrincipalComment, classlist.PrincipalCommentDone, " .
        "       classlist.PrincipalUsername, classlist.HODUsername, " .
        "       classlist.ReportDone " .
        "       FROM user, classlist " .
        "WHERE classlist.ClassTermIndex = :classterm_index " .
        "AND   classlist.Username       = user.Username " .
        "AND   classlist.ReportDone     = 0"
    );
    $query->execute(['classterm_index' => $classterm_index]);

    while($row = $query->fetch()) {
        $nquery = $pdb->prepare(
            "SELECT MIN(subjectstudent.ReportDone) AS ReportDone " .
            "       FROM subject, subjectstudent, class " .
            "WHERE subjectstudent.Username      = :susername " .
            "AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
            "AND   subject.TermIndex            = classterm.TermIndex " .
            "AND   subject.YearIndex            = class.YearIndex " .
            "AND   class.ClassIndex             = classterm.ClassIndex " .
            "AND   classterm.ClassTermIndex     = :classterm_index " .
            "GROUP BY subjectstudent.Username"
        );
        $nquery->execute(['classterm_index' => $classterm_index,
                          'susername' => $row['Username']]);
        $subject_report_done = 1;
        if ($nrow = $nquery->fetch())
            $subject_report_done = $nrow['ReportDone'];

        if((is_null($row['CTComment']) and $ct_comment_type == $COMMENT_TYPE_MANDATORY) or
           (!$row['CTCommentDone'] and $ct_comment_type != $COMMENT_TYPE_NONE) or
           (is_null($row['HODComment']) and $hod_comment_type == $COMMENT_TYPE_MANDATORY) or
           (!$row['HODCommentDone'] and $hod_comment_type != $COMMENT_TYPE_NONE) or
           (is_null($row['PrincipalComment']) and $pr_comment_type == $COMMENT_TYPE_MANDATORY) or
           (!$row['PrincipalCommentDone'] and $pr_comment_type != $COMMENT_TYPE_NONE) or
           !$subject_report_done) {
            if(is_null($row['CTComment']) and $ct_comment_type == $COMMENT_TYPE_MANDATORY) {
                echo "</p><p align='center'>Error: Class teacher must write a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>\n";
            }
            if(is_null($row['HODComment']) and $hod_comment_type == $COMMENT_TYPE_MANDATORY) {
                echo "</p><p align='center'>Error: Head of department must write a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>\n";
            }
            if(is_null($row['PrincipalComment']) and $pr_comment_type == $COMMENT_TYPE_MANDATORY) {
                echo "</p><p align='center'>Error: Principal must write a comment for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>\n";
            }
            if(!$row['CTCommentDone'] and $ct_comment_type != $COMMENT_TYPE_NONE) {
                echo "</p><p align='center'>Error: Class teacher must click &quot;Finished with comments&quot; button for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>\n";
            }
            if(!$row['HODCommentDone'] and $hod_comment_type != $COMMENT_TYPE_NONE) {
                echo "</p><p align='center'>Error: Head of Department must click &quot;Finished with comments&quot; button for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>\n";
            }
            if(!$row['PrincipalCommentDone'] and $pr_comment_type != $COMMENT_TYPE_NONE) {
                echo "</p><p align='center'>Error: Principal must click &quot;Finished with comments&quot; button for {$row['FirstName']} {$row['Surname']}.</p><p align='center'>\n";
            }
            if (!$subject_report_done) {
                echo "      <p align='center'>Error: All of {$row['FirstName']} {$row['Surname']}'s subjects must be finished.</p><p align='center'>\n";
            }
            continue;
            $is_error = true;
        }
        $nquery = "UPDATE classlist SET ";
        if (!is_null($proof_username)) {
            $nquery .= "       ReportProofread = 1, ";
        }
        $nquery .= "       ReportDone = 1 " .
                   " WHERE classlist.ClassTermIndex = :classterm_index ";
        $pdb->prepare($nquery)->execute(['classterm_index' => $classterm_index]);
    }
}
if ($is_error) {
    echo "failed.</p>\n";
} else {
    echo "done.</p>\n";
}
echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";

include "footer.php";
