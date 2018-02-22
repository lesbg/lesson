<?php
/**
 * ***************************************************************
 * core/permfunc.php (c) 2018 Jonathan Dieter
 *
 * Functions for getting permissions
 * ***************************************************************
 */

/* Check whether $guardian_username is guardian of $student_username */
function check_guardian($student_username, $guardian_username) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT familylist.Username FROM " .
        "    familylist INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
        "WHERE familylist.Username         = :student_username " .
        "AND   familylist2.Username        = :guardian_username " .
        "AND   familylist2.Guardian        = 1 "
    );
    $query->execute(['student_username' => $student_username, 'guardian_username' => $guardian_username]);
    $row = $query->fetch();

    if ($row) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is the class teacher for a year */
function check_class_teacher_year($username, $yearindex) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT class.ClassIndex " .
        "  FROM class " .
        "WHERE class.ClassTeacherUsername = :username " .
        "AND   class.YearIndex = :yearindex"
    );
    $query->execute(['username' => $username, 'yearindex' => $yearindex]);
    $row = $query->fetch();
    if ($row) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user teaches at all during the year */
function check_teacher_year($username, $yearindex) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT user.FirstName, user.Surname, user.Username FROM " .
        "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
        "            INNER JOIN groups USING (GroupID) " .
        "WHERE user.Username=:username " .
        "AND   groups.GroupTypeID='activeteacher' " .
        "AND   groups.YearIndex=:yearindex " .
        "ORDER BY user.Username";
    );
    $query->execute(['username' => $username, 'yearindex' => $yearindex]);
    $row = $query->fetch();
    if ($row) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user teaches subject containing specified assignment */
function check_teacher_assignment($username, $assignment_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT subjectteacher.Username FROM subjectteacher, assignment " .
         "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
         "AND   assignment.AssignmentIndex  = :assignment_index " .
         "AND   subjectteacher.Username     = :username"
    );
    $query->execute(['username' => $username, 'assignment_index' => $assignment_index]);
    $row = $query->fetch();
    if ($row) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user teaches subject */
function check_teacher_subject($username, $subject_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT subjectteacher.Username FROM subjectteacher " .
         "WHERE subjectteacher.SubjectIndex = :subject_index " .
         "AND   subjectteacher.Username     = :username"
    );
    $query->execute(['username' => $username, 'subject_index' => $subject_index]);
    $row = $query->fetch();
    if ($row) {
        return true;
    } else {
        return false;
    }
}

/* Check whether teacher is a support teacher for a subject */
function check_support_teacher_subject($username, $subject_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT support_class.Username " .
        "         FROM subject " .
        "         INNER JOIN subjectstudent USING (SubjectIndex) " .
        "         INNER JOIN classlist USING (Username) " .
        "         INNER JOIN classterm ON " .
        "           (classterm.ClassTermIndex=classlist.ClassTermIndex " .
        "            AND classterm.TermIndex=subject.TermIndex) " .
        "         INNER JOIN class ON " .
        "           (class.ClassIndex=classterm.ClassIndex " .
        "            AND class.YearIndex=subject.YearIndex) " .
        "         INNER JOIN support_class ON " .
        "           (classterm.ClassTermIndex=support_class.ClassTermIndex) " .
        "         WHERE support_class.Username = :username " .
        "         AND subject.SubjectIndex = :subject_index"
    );
    $query->execute(['username' => $username, 'subject_index' => $subject_index]);
    $row = $query->fetch();
    if ($row) {
        return true;
    } else {
        return false;
    }
}

/* Get punishment permissions */
function get_punishment_permissions($username) {
    global $pdb;
    global $DEFAULT_PUN_PERM;

    $query = $pdb->prepare(
        "SELECT Permissions FROM disciplineperms WHERE Username=:username"
    );
    $query->execute(['username' => $username]);
    $row = $query->fetch();
    if ($row) {
        return $row['Permissions'];
    } else {
        return $DEFAULT_PUN_PERM;
    }
}

function check_attendance($username, $subject_index) {
    global $pdb;
    global $PUN_PERM_SUSPEND;

    if(check_teacher_subject($username, $subject_index))
        return true;

    $query = $pdb->prepare(
        "SELECT Username FROM disciplineperms " .
        "WHERE Permissions >= :suspended " .
        "AND Username = :username "
    );
    $query->execute(['suspended' => $PUN_PERM_SUSPEND, 'username' => $username]);
    $row = $query->fetch();
    if ($row) {
        return true;
    } else {
        return false;
    }
}
