<?php
/**
 * ***************************************************************
 * admin/subject/new_or_modify_action.php (c) 2005 Jonathan Dieter
 *
 * Change subject information
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check which button was pressed */
if ($_POST["action"] == "Save" or $_POST["action"] == "Update") { // If update or save were pressed, print
                                                                    // common info and go to the appropriate page.
    /* Check for input errors */
    $format_error = False;
    $errorlist = "";
    if (! isset($_POST['name']) or is_null($_POST['name']) or
         $_POST['name'] == "") { // Make sure name has been entered
        $errorlist .= "<p class='error' align='center'>You must specifiy a subject name!</p>\n";
        $format_error = True;
    }

    if (! $format_error) {
        $errorlist = ""; // Clear error list. This list will now only contain database errors.

        if ($_POST["action"] == "Save") { // Go to admin/subject/new_action.php if "Save" was pressed
            include "admin/subject/new_action.php";
        } else { // Go to admin/subject/modify_action.php if "Update" was pressed
            include "admin/subject/modify_action.php";
        }

        if ($error) { // If we ran into any errors, print failed, otherwise go to admin/subject/modify.php
            $title = "LESSON - Error saving changes";
            $noHeaderLinks = true;
            $noJS = true;

            include "header.php"; // Print header
            echo "      <p align='center'>Attempted to save changes, but:</p>\n";
            echo $errorlist; // Print errors that we ran into
            echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
            include "footer.php";
        } else {
            $_GET['key2'] = dbfuncString2Int(strval($_POST['class']));
            $_GET['key3'] = dbfuncString2Int(strval($_POST['grade']));
            include "admin/subject/modify_list.php";
        }
    } else {
        if ($_POST["action"] == "Save") {
            include "admin/subject/new.php";
        } else {
            include "admin/subject/modify.php";
        }
    }
} elseif ($_POST["action"] == 'Delete') { // If delete was pressed, confirm deletion
    include "admin/subject/delete_confirm.php";
} else {
    redirect($nextLink);
}
