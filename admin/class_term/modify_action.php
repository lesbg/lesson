<?php
/**
 * ***************************************************************
 * admin/class_term/modify_action.php (c) 2008 Jonathan Dieter
 *
 * Run query to change class report options in database
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors
$classname = dbfuncInt2String($_GET['keyname']);
$classindex = safe(dbfuncInt2String($_GET['key']));
$termindex = safe(dbfuncInt2String($_GET['key2']));
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

include "core/settermandyear.php";

/* Check whether user is authorized to change class report options */
if (! $is_admin) {
    $title = "LESSON - Error saving changes";
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php";
    log_event($LOG_LEVEL_ERROR, "admin/class_term/modify_action.php",
            $LOG_DENIED_ACCESS,
            "Attempted to change report options for $classname.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

    include "footer.php";
    exit(0);
}

if ($_POST['action'] == "Update") {
    $title = "LESSON - Saving changes...";
    $noJS = true;
    $noHeaderLinks = true;
    $error = false;

    include "header.php";

    echo "      <p align='center'>Saving changes...";

    if (isset($_POST['report_enabled'])) {
        $_POST['report_enabled'] = intval($_POST['report_enabled']);
        if ($_POST['report_enabled'] > 1)
            $_POST['report_enabled'] = 0;
    } else {
        $_POST['report_enabled'] = NULL;
        ;
    }

    if (isset($_POST['ct_comment_type'])) {
        $_POST['ct_comment_type'] = intval($_POST['ct_comment_type']);
        if ($_POST['ct_comment_type'] >= $COMMENT_TYPE_MAX)
            $_POST['ct_comment_type'] = $COMMENT_TYPE_NONE;
    } else {
        $_POST['ct_comment_type'] = NULL;
    }

    if (isset($_POST['hod_comment_type'])) {
        $_POST['hod_comment_type'] = intval($_POST['hod_comment_type']);
        if ($_POST['hod_comment_type'] >= $COMMENT_TYPE_MAX)
            $_POST['hod_comment_type'] = $COMMENT_TYPE_NONE;
    } else {
        $_POST['hod_comment_type'] = NULL;
    }

    if (isset($_POST['pr_comment_type'])) {
        $_POST['pr_comment_type'] = intval($_POST['pr_comment_type']);
        if ($_POST['pr_comment_type'] >= $COMMENT_TYPE_MAX)
            $_POST['pr_comment_type'] = $COMMENT_TYPE_NONE;
    } else {
        $_POST['pr_comment_type'] = NULL;
    }

    if (isset($_POST['conduct_type'])) {
        $_POST['conduct_type'] = intval($_POST['conduct_type']);
        if ($_POST['conduct_type'] >= $CLASS_CONDUCT_TYPE_MAX)
            $_POST['conduct_type'] = $CLASS_CONDUCT_TYPE_NONE;
    } else {
        $_POST['conduct_type'] = NULL;
    }

    if (isset($_POST['effort_type'])) {
        $_POST['effort_type'] = intval($_POST['effort_type']);
        if ($_POST['effort_type'] >= $CLASS_EFFORT_TYPE_MAX)
            $_POST['effort_type'] = $CLASS_EFFORT_TYPE_NONE;
    } else {
        $_POST['effort_type'] = NULL;
    }

    if (isset($_POST['average_type'])) {
        $_POST['average_type'] = intval($_POST['average_type']);
        if ($_POST['average_type'] >= $CLASS_AVG_TYPE_MAX)
            $_POST['average_type'] = $CLASS_AVG_TYPE_NONE;
    } else {
        $_POST['average_type'] = NULL;
    }

    if (isset($_POST['absence_type'])) {
        $_POST['absence_type'] = intval($_POST['absence_type']);
        if ($_POST['absence_type'] >= $ABSENCE_TYPE_MAX)
            $_POST['absence_type'] = $ABSENCE_TYPE_NONE;
    } else {
        $_POST['absence_type'] = NULL;
    }

    if (isset($_POST['report_template'])) {
        if($_POST['report_template'] != "NULL")
            $_POST['report_template'] = intval($_POST['report_template']);
    } else {
        $_POST['report_template'] = NULL;
    }

    $query = "UPDATE classterm SET ";
    if (! is_null($_POST['report_enabled'])) {
        $query .= "       CanDoReport = {$_POST['report_enabled']}, ";
    }
    if (! is_null($_POST['ct_comment_type'])) {
        $query .= "       CTCommentType = {$_POST['ct_comment_type']}, ";
    }
    if (! is_null($_POST['hod_comment_type'])) {
        $query .= "       HODCommentType = {$_POST['hod_comment_type']}, ";
    }
    if (! is_null($_POST['pr_comment_type'])) {
        $query .= "       PrincipalCommentType = {$_POST['pr_comment_type']}, ";
    }
    if (! is_null($_POST['conduct_type'])) {
        $query .= "       ConductType = {$_POST['conduct_type']}, ";
    }
    if (! is_null($_POST['effort_type'])) {
        $query .= "       EffortType = {$_POST['effort_type']}, ";
    }
    if (! is_null($_POST['average_type'])) {
        $query .= "       AverageType = {$_POST['average_type']}, ";
    }
    if (! is_null($_POST['absence_type'])) {
        $query .= "       AbsenceType = {$_POST['absence_type']}, ";
    }
    if (! is_null($_POST['report_template'])) {
        $query .= "       ReportIndex = {$_POST['report_template']}, ";
    }

    $query = substr($query, 0, strlen($query) - 2); // Get rid of final comma
    $query .= " WHERE classterm.ClassIndex = $classindex " .
         " AND   classterm.TermIndex  = $termindex";
    $nres = & $db->query($query);
    if (DB::isError($nres))
        die($nres->getDebugInfo());

    if ($error) {
        echo "failed.</p>\n";
    } else {
        echo "done.</p>\n";
    }
    echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
    include "footer.php";
/* Not here anymore
} elseif ($_POST['action'] == "Upload") {
    if (! isset($_FILES['report_template']) or
             $_FILES['report_template']['error'] != UPLOAD_ERR_OK) {
        if (! isset($_FILES['report_template'])) {
            $error = "Error when attempting to upload file";
        } elseif ($_FILES['report_template']['error'] == UPLOAD_ERR_INI_SIZE or
                 $_FILES['report_template']['error'] == UPLOAD_ERR_FORM_SIZE) {
            $error = "You have attempted to upload a file that is too large";
        } elseif ($_FILES['report_template']['error'] == UPLOAD_ERR_PARTIAL) {
            $error = "Only part of the file was uploaded";
        } elseif ($_FILES['report_template']['error'] == UPLOAD_ERR_NO_FILE) {
            $error = "You must choose a file to be uploaded";
        } else {
            $error = "Error when attempting to upload file";
        }
        $errorlist = "<p align='center' class='error'>$error</p>\n";
    } else {
        $report_type = safe($_FILES['report_template']['type']);
        $report_file = $_FILES['report_template']['tmp_name'];

        $report_handle = fopen($report_file, "r");
        $data = fread($report_handle, filesize($report_file) * 2);
        print strlen($data);
        $data = addslashes($data);
        print strlen($data);

        $query = "UPDATE classterm SET " . "       ReportTemplate='$data', " .
                 "       ReportTemplateType='$report_type' " .
                 "WHERE  ClassIndex = $classindex " .
                 "AND    TermIndex  = $termindex";
        $nres = & $db->query($query);
        if (DB::isError($nres))
            die($nres->getUserInfo());
    }
    include "admin/class_term/modify.php";*/
} else {
    redirect($nextLink);
}
log_event($LOG_LEVEL_ADMIN, "admin/class_term/modify_action.php", $LOG_ADMIN,
        "Modified information about {$_POST['name']}.");
?>
