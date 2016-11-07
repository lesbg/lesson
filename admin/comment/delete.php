<?php
/**
 * ***************************************************************
 * admin/comment/delete.php (c) 2008 Jonathan Dieter
 *
 * Delete comment from database
 * ***************************************************************
 */

/* Get variables */
$commentindex = dbfuncInt2String($_GET['key']);
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

if ($_POST['action'] == "Yes, delete comment") {
    $title = "LESSON - Deleting comment #$commentindex";
    $noJS = true;
    $noHeaderLinks = true;

    include "header.php";

    /* Check whether current user is authorized to delete comments */
    if ($is_admin) {
        $res = &  $db->query(
                        "DELETE FROM commenttype " .
                         "WHERE CommentIndex  = $commentindex");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        $res = &  $db->query(
                    "DELETE FROM comment " .
                     "WHERE CommentIndex  = $commentindex");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        log_event($LOG_LEVEL_ADMIN, "admin/comment/delete.php", $LOG_ADMIN,
                "Deleted comment #$commentindex.");
        echo "      <p align=\"center\">Comment #$commentindex successfully deleted.</p>\n";
        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
    } else {
        log_event($LOG_LEVEL_ERROR, "admin/comment/delete.php",
                $LOG_DENIED_ACCESS, "Tried to delete comment #$commentindex.");
        echo "      <p>You do not have the authority to remove this comment.  <a href=\"$nextLink\">" .
             "Click here to continue</a>.</p>\n";
    }
} else {
    $title = "LESSON - Cancelling";
    $noJS = true;
    $noHeaderLinks = true;
    $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";

    include "header.php";

    echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
         "</p>\n";
}

include "footer.php";
?>
