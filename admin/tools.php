<?php
/**
 * ***************************************************************
 * admin/tools.php (c) 2005 Jonathan Dieter
 *
 * Administrative tools
 * ***************************************************************
 */
$title = "Admin Tools";

include "header.php"; // Show header

$showalldeps = true;
$showyear = false;
$showterm = false;
include "core/settermandyear.php";
include "core/titletermyear.php";

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) { // Make sure user has permissions
    log_event($LOG_LEVEL_ADMIN, "admin/tools.php", $LOG_ADMIN,
        "Accessed administrative tools.");

    /*
     * Show all $_SERVER variables
     * echo " <p>\n";
     * foreach($_SERVER as $key => $value) {
     * echo " $key = $value<br>\n";
     * }
     * echo " </p>\n";
     */

    $studentListLink = "index.php?location=" .
                     dbfuncString2Int("admin/studentlist.php");
    $familyListLink = "index.php?location=" .
                     dbfuncString2Int("admin/family/list.php");
    $teacherListLink = "index.php?location=" .
                     dbfuncString2Int("admin/teacherlist.php");
    $subjectListLink = "index.php?location=" .
                     dbfuncString2Int("admin/subject/list.php");
    $subtypeListLink = "index.php?location=" .
                     dbfuncString2Int("admin/subjecttype/list.php");
    $catListLink = "index.php?location=" .
                 dbfuncString2Int("admin/category/list.php");
    $commentLink = "index.php?location=" .
                 dbfuncString2Int("admin/comment/list.php");
    $classListLink = "index.php?location=" .
                     dbfuncString2Int("admin/class/list.php");
    $groupLink = "index.php?location=" .
                 dbfuncString2Int("admin/group/list.php");
    $ctermListLink = "index.php?location=" .
                     dbfuncString2Int("admin/class_term/list.php");
    $newUserLink = "index.php?location=" .
                 dbfuncString2Int("admin/user/modify.php");
    $newQuarterLink = "index.php?location=" .
                     dbfuncString2Int("admin/newquarter.php");
    $openreportLink = "index.php?location=" .
                     dbfuncString2Int("admin/open_reports.php");
    $viewLogLink = "index.php?location=" .
                 dbfuncString2Int("admin/viewlog.php");
    $counselorLink = "index.php?location=" .
                     dbfuncString2Int("admin/counselor/modify.php");
    $principalLink = "index.php?location=" .
                     dbfuncString2Int("admin/principal/modify.php");
    $bookLink = "index.php?location=" .
                 dbfuncString2Int("admin/book/title_list.php");
    $messageLink = "index.php?location=" .
                 dbfuncString2Int("user/messages.php");
    $lowmarksLink = "index.php?location=" .
                     dbfuncString2Int("admin/marks/low.php");
    $proofLink = "index.php?location=" .
                 dbfuncString2Int("admin/proofreader/list.php");
    $supportLink = "index.php?location=" .
                 dbfuncString2Int("admin/support/modify.php") . "&amp;next=" .
                 dbfuncString2Int(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/tools.php"));
    $customqueriesLink = "index.php?location=" .
                 dbfuncString2Int("admin/custom_queries/list.php");

    echo "      <div class='button' style='position: absolute; width: 200px; left: 50%; margin-left: -100px;'>\n";
    echo "      <p><a href='$lowmarksLink'>View low marks</a></p>\n";
    echo "      <p><a href='$studentListLink'>Student List</a></p>\n";
    echo "      <p><a href='$familyListLink'>Family List</a></p>\n";
    echo "      <p><a href='$teacherListLink'>Teacher List</a></p>\n";
    echo "      <p><a href='$classListLink'>Class List</a></p>\n";
    echo "      <p><a href='$customqueriesLink'>Custom Queries</a></p>\n";
    echo "      <p><a href='$groupLink'>Groups</a></p>\n";
    echo "      <p><a href='$ctermListLink'>Class Report Options</a></p>\n";
    echo "      <p><a href='$subjectListLink'>Subject List</a></p>\n";
    echo "      <p><a href='$counselorLink'>Counselor List</a></p>\n";
    echo "      <p><a href='$principalLink'>Principal List</a></p>\n";
    echo "      <p><a href='$subtypeListLink'>Subject Types List</a></p>\n";
    echo "      <p><a href='$catListLink'>Category Types List</a></p>\n";
    echo "      <p><a href='$commentLink'>Comment List</a></p>\n";
    echo "      <p><a href='$proofLink'>Proofreader List</a></p>\n";
    echo "      <p><a href='$bookLink'>Book List</a></p>\n";
    echo "      <p><a href='$newUserLink'>New user</a></p>\n";
    echo "      <p><a href='$newQuarterLink'>Generate new quarter</a></p>\n";
    echo "      <p><a href='$openreportLink'>Open reports</a></p>\n";
    echo "      <p><a href='$messageLink'>Messages</a></p>\n";
    /* echo " <p><a href='$stypePrintLink'>Print marks for a subject type</a></p>\n"; */
    echo "      <p><a href='$supportLink'>Support teachers\n";
    echo "      <p><a href='$viewLogLink'>View log</a></p>\n";
    echo "      </div>\n";
} else {
    log_event($LOG_LEVEL_ERROR, "admin/tools.php", $LOG_DENIED_ACCESS,
            "Tried to access administrative tools.");
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
