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
