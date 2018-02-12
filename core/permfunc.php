<?php
/**
 * ***************************************************************
 * core/permfunc.php (c) 2018 Jonathan Dieter
 *
 * Functions for getting permissions
 * ***************************************************************
 */

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
