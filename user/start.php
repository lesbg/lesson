<?php
	/*****************************************************************
	 * user/start.php  (c) 2005 Jonathan Dieter
	 *
	 * Start page that redirects to main.  Used so that user can
	 * press back key to get to main without needing to repost
	 * data.
	 *****************************************************************/
	
	$nextLink      = "index.php?location=" . dbfuncString2Int("user/main.php");
	$noJS          = true;
	$noHeaderLinks = true;
	

	
	if($LOG_LEVEL >= $LOG_LEVEL_ACCESS) {
		$res =&  $db->query("SELECT log.Time FROM log, " .
							"      (SELECT Session FROM log " .
							"       WHERE Username=\"$username\" " .
							"       GROUP BY log.Session " .
							"       ORDER BY Time DESC " .
							"       LIMIT 2) AS query " .
							"WHERE log.Session = query.Session " .
							"AND   log.Code    = $LOG_LOGOUT");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($res->numRows() == 0) {
			$res =&  $db->query("SELECT Session FROM log " .
								"WHERE Username=\"$username\" " .
								"GROUP BY log.Session " .
								"ORDER BY Time DESC " .
								"LIMIT 2");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

			$title = "LESSON - Warning!";
			include "header.php";

			echo "      <table class=\"transparent\" width=\"100%\">\n";
			echo "         <tr>\n";
			echo "            <td width=\"120px\" class=\"logo\"><img height=\"73\" width=\"75\" alt=\"LESB&G Logo\" src=\"images/lesbg-small.gif\"></td>\n"; 
			echo "            <td class=\"title\">$title</td>\n";
			echo "            <td width=\"120px\" class=\"logo\">&nbsp;</td>\n"; 
			echo "         </tr>\n";
			echo "      </table>\n";

			$homebutton   = dbfuncGetDisabledButton("Home", "small", "home", "button");
			$logoutbutton = dbfuncGetDisabledButton("Logout", "small", "logout", "button");
			echo "      <table class=\"transparent\" width=\"400px\" align=\"center\">\n";
			echo "         <tr>\n";
			if($res->numRows() == 1) {
				echo "            <td class=\"error\">Welcome to LESSON!  Please make sure you log out when you finish.  If you don't log out, anybody else who accesses the computer after you can view your marks (or change marks if you are a teacher)!  To logout, first click on the $homebutton button in the top right corner, and then click on the $logoutbutton button in the top right corner.</td>\n";
			} else {
				log_event($LOG_LEVEL_ACCESS, "user/start.php", $LOG_USER,
						"Warned user that they didn't log out last time.");
				echo "            <td class=\"error\">Warning! Last time you logged in to LESSON, you forgot to log out.  If you don't log out, anybody else who accesses the computer after you can view your marks (or change marks if you are a teacher)!  To logout, first click on the $homebutton button in the top right corner, and then click on the $logoutbutton button in the top right corner.</td>\n";
			}
			echo "         </tr>\n";
			echo "      </table>\n";
			echo "      <p align=\"center\">To continue, <a href=\"$nextLink\">click here</a>.</p>\n";
		} else {
			$title     = "LESSON - Redirecting...";
			$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
			include "header.php";
			
//			echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a>.</p>\n";
		}
	} else {
		$title     = "LESSON - Redirecting...";
		$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		include "header.php";
		
//		echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a>.</p>\n";
	}
	include "footer.php";
?>