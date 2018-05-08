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
    if ($query->fetch()) {
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
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is class teacher for a student */
function check_class_teacher_student($username, $student_username, $yearindex,
                                     $termindex) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
        "WHERE class.ClassTeacherUsername = :username " .
        "AND   classlist.Username = :student_username " .
        "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
        "AND   classterm.TermIndex = :termindex " .
        "AND   class.ClassIndex = classterm.ClassIndex " .
        "AND   class.YearIndex = :yearindex"
    );
    $query->execute(['username' => $username, 'termindex' => $termindex,
                     'student_username' => $student_username,
                     'yearindex' => $yearindex]);
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is class teacher for a classterm */
function check_class_teacher_classterm($username, $classterm_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT class.ClassIndex FROM class, classterm " .
        "WHERE class.ClassIndex           = classterm.ClassIndex " .
        "AND   classterm.ClassTermIndex   = :classterm_index " .
        "AND   class.ClassTeacherUsername = :username"
    );
    $query->execute(['username' => $username,
                     'classterm_index' => $classterm_index]);
    if ($query->fetch()) {
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
        "ORDER BY user.Username"
    );
    $query->execute(['username' => $username, 'yearindex' => $yearindex]);
    if ($query->fetch()) {
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
    if ($query->fetch()) {
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
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user teaches a student */
function check_teacher_student($username, $student_username, $yearindex,
                               $termindex) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT subjectteacher.Username FROM subject, subjectstudent, subjectteacher " .
        "WHERE subjectstudent.Username = :student_username " .
        "AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
        "AND   subject.YearIndex = :yearindex " .
        "AND   subject.TermIndex = :termindex " .
        "AND   subjectteacher.SubjectIndex = subject.SubjectIndex " .
        "AND   subjectteacher.Username = :username"
    );
    $query->execute(['username' => $username, 'termindex' => $termindex,
                     'student_username' => $student_username,
                     'yearindex' => $yearindex]);
    if ($query->fetch()) {
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
    if ($query->fetch()) {
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
    if ($row = $query->fetch()) {
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
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check if user is the principal */
function check_principal($username) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT Username FROM principal " .
        "WHERE Username=:username AND Level=1"
    );
    $query->execute(['username' => $username]);
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is the head of department for a student */
function check_hod_student($username, $student_username, $yearindex, $termindex) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT hod.Username FROM hod, class, classterm, classlist " .
        "WHERE hod.Username = :username " .
        "AND   hod.DepartmentIndex = class.DepartmentIndex " .
        "AND   classlist.Username = :student_username " .
        "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
        "AND   classterm.TermIndex = :termindex " .
        "AND   class.ClassIndex = classterm.ClassIndex " .
        "AND   class.YearIndex = :yearindex "
    );
    $query->execute(['username' => $username, 'termindex' => $termindex,
                     'student_username' => $student_username,
                     'yearindex' => $yearindex]);
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is the head of department for a classterm */
function check_hod_classterm($username, $classterm_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT hod.Username FROM hod, class, classterm " .
        "WHERE hod.Username        = :username " .
        "AND   hod.DepartmentIndex = class.DepartmentIndex " .
        "AND   class.ClassIndex    = classterm.ClassIndex " .
        "AND   classterm.ClassTermIndex = :classterm_index"
    );
    $query->execute(['username' => $username,
                     'classterm_index' => $classterm_index]);
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is the head of department for a subject */
function check_hod_subject($username, $subject_index) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT hod.Username FROM hod, term, subject " .
        "WHERE hod.Username         = :username " .
        "AND   hod.DepartmentIndex  = term.DepartmentIndex " .
        "AND   term.TermIndex       = subject.TermIndex " .
        "AND   subject.SubjectIndex = :subject_index"
    );
    $query->execute(['username' => $username,
                     'subject_index' => $subject_index]);
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}

/* Check whether user is a counselor */
function check_counselor($username) {
    global $pdb;

    $query = $pdb->prepare(
        "SELECT Username FROM counselorlist " .
        "WHERE Username=:username"
    );
    $query->execute(['username' => $username]);
    if ($query->fetch()) {
        return true;
    } else {
        return false;
    }
}
