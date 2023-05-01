<?php
/**
 * ***************************************************************
 * admin/user/phone_action.php (c) 2016 Jonathan Dieter
 *
 * Phone actions
 * ***************************************************************
 */

if ($is_admin) {
	if($_POST['phoneaction'] == "+") {
		$minindex = 0;
		foreach($_POST['phone'] as $phone) {
			if(intval($phone[0]) < $minindex)
				$minindex = $phone[0];
		}
		$newindex = $minindex-1;
		$_POST['phone'][] = array($newindex, "", 2, "");
	} else {
		foreach($_POST as $key => $value) {
			if(substr($key, 0, 12) == "phoneaction-") {
				$item = intval(substr($key, 12));
				if($value == "-") {
					if($item > -1)
						$_POST['phone_remove'][] = $item;
					foreach($_POST['phone'] as $aindex => $phone) {
						if($phone[0] == $item)
							unset($_POST['phone'][$aindex]);
					}
				} elseif($value == "▼") {
					unset($findex);
					foreach($_POST['phone'] as $aindex => $phone) {
						if($phone[0] == $item) {
							if(!isset($findex)) {
								$findex = $aindex;
							}
						} else {
							if(isset($findex)) {
								$temp = $_POST['phone'][$findex];
								$_POST['phone'][$findex] = $_POST['phone'][$aindex];
								$_POST['phone'][$aindex] = $temp;
								break;
							}
						}
					}
				} elseif($value == "▲") {
					unset($pindex);
					foreach($_POST['phone'] as $aindex => $phone) {
						if($phone[0] == $item) {
							if(isset($pindex)) {
								$temp = $_POST['phone'][$pindex];
								$_POST['phone'][$pindex] = $_POST['phone'][$aindex];
								$_POST['phone'][$aindex] = $temp;
							}
							break;
						} else {
							$pindex = $aindex;
						}
					}
				}
			}
		}
	}
	include "admin/user/modify.php";
} else { // User isn't authorized to view or change users.
	include "header.php"; // Show header
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
}