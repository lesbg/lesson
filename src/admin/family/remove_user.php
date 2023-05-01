<?php
/**
 * ***************************************************************
 * admin/family/remove_user.php (c) 2016 Jonathan Dieter
 *
 * Remove family code from user
 * ***************************************************************
 */

if ($is_admin) {
	if(!isset($_SESSION['post_family'])) {
		$_SESSION['post_family'] = array();
	}
	foreach($_POST as $key => $value) {
		$_SESSION['post_family'][$key] = $value;
	}

	unset($_POST);
	
	if(isset($uremove) && isset($_SESSION['post_family']['uname'])) {
		foreach($_SESSION['post_family']['uname'] as $key => $user) {
			$uname = $user[0];
			if($uname == $uremove) {
				$fcodem = safe($_SESSION['post_family']['fcode']);
				$unamem = safe($uname);
				$query  =	"SELECT Username FROM familylist " .
							"WHERE FamilyCode = '$fcodem' " .
							"AND   Username = '$unamem'";
				$res = &  $db->query($query);
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				
				if($res->numRows() > 0) {
					if(!isset($_SESSION['post_family']['remove_uname'])) {
						$_SESSION['post_family']['remove_uname'] = array();
					}
					$_SESSION['post_family']['remove_uname'][] = $uname;
				}
				unset($_SESSION['post_family']['uname'][$key]);
			}
		}
	}
	
	include "admin/family/modify.php";
} else { // User isn't authorized to view or change users.
	include "header.php"; // Show header
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
}