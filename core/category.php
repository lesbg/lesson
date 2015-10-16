<?php
/**
 * ***************************************************************
 * core/category.php (c) 2007 Jonathan Dieter
 *
 * Functions for dealing with categories
 * ***************************************************************
 */
$CAT_ERR_ALREADY_IN_SUBJECT = 1;
$CAT_ERR_UNAVAILABLE = 2;
$CAT_ERR_NOTHING = 3;
$CAT_ERR_BAD_SUBJECT = 4;
function recalc_weight($subjectindex) {
	global $db;
	
	/* Find total weight not including new category */
	$query = "SELECT SUM(Weight) AS TotalWeight FROM categorylist " .
			 "WHERE SubjectIndex=$subjectindex " . "GROUP BY SubjectIndex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$total_weight = floatval($row['TotalWeight']);
	} else {
		$total_weight = 0.0;
	}
	
	/* Update total weight for all categories */
	$query = "UPDATE categorylist SET TotalWeight=$total_weight " .
			 "WHERE SubjectIndex=$subjectindex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
}
function add_category_to_subject($subjectindex, $categoryindex, $weight) {
	global $db;
	global $CAT_ERR_BAD_SUBJECT;
	global $CAT_ERR_NOTHING;
	global $CAT_ERR_ALREADY_IN_SUBJECT;
	global $CAT_ERR_UNAVAILABLE;
	
	// Sanity checking
	if ($subjectindex == "")
		return $CAT_ERR_BAD_SUBJECT;
	if ($categoryindex == "")
		return $CAT_ERR_NOTHING;
	$subjectindex = intval($subjectindex);
	$categoryindex = intval($categoryindex);
	$weight = floatval($weight);
	
	$res = &  $db->query(
					"SELECT SubjectTypeIndex FROM subject " .
					 "WHERE SubjectIndex = $subjectindex");
	if (DB::isError($res))
		die($res->getDebugInfo());
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	$subjecttypeindex = $row['SubjectTypeIndex'];
	
	// Check whether category is available for this subject
	$query = "SELECT category.CategoryIndex, category.CategoryName " .
			 "       FROM category LEFT OUTER JOIN categorytype USING (CategoryIndex) " .
			 "WHERE  categorytype.SubjectTypeIndex IS NULL " .
			 "OR     categorytype.SubjectTypeIndex=$subjecttypeindex " .
			 "AND    category.CategoryIndex = $categoryindex ";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	if ($res->numRows() == 0) {
		return $CAT_ERR_UNAVAILABLE;
	}
	
	// Check whether category has already been used in this subject
	$query = "SELECT CategoryIndex FROM categorylist " .
			 "WHERE CategoryIndex=$categoryindex " .
			 "AND   SubjectIndex=$subjectindex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	if ($res->numRows() > 0) {
		return $CAT_ERR_ALREADY_IN_SUBJECT;
	}
	
	/* Insert new category */
	$query = "INSERT INTO categorylist (CategoryIndex, SubjectIndex, Weight) " .
			 "                  VALUES ($categoryindex, $subjectindex, " .
			 "                          $weight)";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	$query = "UPDATE assignment, categorylist " .
			 "SET assignment.CategoryListIndex = categorylist.CategoryListIndex " .
			 "WHERE assignment.CategoryListIndex IS NULL " .
			 "AND   assignment.SubjectIndex = $subjectindex " .
			 "AND   categorylist.SubjectIndex = $subjectindex ";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	recalc_weight($subjectindex);
	
	return 0;
}
function remove_category_from_subject($subjectindex, $categorylistindex) {
	global $db;
	
	$message_list = "";
	
	$query = "SELECT COUNT(AssignmentIndex) AS Count FROM assignment " .
			 "WHERE CategoryListIndex=$categorylistindex " .
			 "GROUP BY CategoryListIndex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$count = $row['Count'];
		
		$query = "SELECT categorylist.CategoryListIndex, category.CategoryName " .
				 "       FROM category, categorylist " .
				 "WHERE SubjectIndex=$subjectindex " .
				 "AND   CategoryListIndex != $categorylistindex " .
				 "AND   category.CategoryIndex = categorylist.CategoryIndex " .
				 "ORDER BY category.CategoryName, categorylist.CategoryListIndex " .
				 "LIMIT 1";
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo());
		
		if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$new_cat_index = $row['CategoryListIndex'];
			$new_category = $row['CategoryName'];
			$message_list = "All $count assignments in this category are now in the $new_category category.";
			
			$query = "UPDATE assignment SET CategoryListIndex=$new_cat_index " .
					 "WHERE CategoryListIndex=$categorylistindex ";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
		} else {
			$message_list = "All $count assignments in this category now have no category.";
			
			$query = "UPDATE assignment SET CategoryListIndex=NULL " .
					 "WHERE CategoryListIndex=$categorylistindex ";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
		}
	}
	
	/* Remove category */
	$query = "DELETE FROM categorylist " .
			 "WHERE CategoryListIndex=$categorylistindex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	recalc_weight($subjectindex);
	
	return $message_list;
}
?>