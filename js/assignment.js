/*****************************************************************
 * assignment.js  (c) 2006, 2007 Jonathan Dieter
 *
 * Javascript to deal with assignment creation and modification
 *****************************************************************/

/* Recalculate average of username */
function recalc_avg(username, max, min, m, b) { 
	var avg   = document.getElementById('avg_' + username);
	var score = document.getElementById('score_' + username).value.toUpperCase();
	var cell_type = "";
	var new_class = "";
	var is_hidden = false;
	if(document.getElementById('row_' + username).className.indexOf("alt") > -1) {
		cell_type = "alt";
	} else if(document.getElementById('row_' + username).className.indexOf("std") > -1) {
		cell_type = "std";
	}
	new_class = cell_type;
	if(document.getElementById('hidden').checked == true) {
		is_hidden = true;
	}
	if(average_type == AVERAGE_TYPE_PERCENT) {
		var max_score = document.getElementById('max').value;

		if(document.getElementById('curve_type1').checked == true) {
			ct = 1;
		} else if(document.getElementById('curve_type2').checked == true) {
			ct = 2;
		} else {
			ct = 0;
		}
	
		if(score == 'A' || score == 'E') {
			avg.innerHTML = 'N/A';
		} else if(score == 'L') {
			if(!is_hidden) {
				new_class = "late-" + cell_type;
			}
			avg.innerHTML = '0%';
		} else if(score == '' || isNaN(parseFloat(score))) {
			if(!is_hidden) {
				new_class = "unmarked-" + cell_type;
			}
			avg.innerHTML = 'N/A';
		} else {
			if(score < 0) {
				if(!isNaN(parseFloat(max_score))) {
					score = Number(max_score) + Number(score);
					if(score < 0) {
						score = 0;
					} else {
						document.getElementById('score_' + username).value = score;
					}
				} else {
					score = 0;
				}
			}
			if(ct == 1) {
				if(!max) {
					max = 0;
					for(var i=0; i<document.assignment.length; i++) {
						if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
							if(!isNaN(parseFloat(document.assignment.elements[i].value))) {
								if(Number(document.assignment.elements[i].value) > max)
									max = Number(document.assignment.elements[i].value);
							}
						}
					}
					if(max == score) {
						recalc_all();
					} else {
						avg.innerHTML = String(parseInt(((score / max) * 100) + 0.5)) + '%';
					}
				} else {
					avg.innerHTML = String(parseInt(((score / max) * 100) + 0.5)) + '%';
				}
			} else if(ct == 2) {
				if(!max) {
					var top_mark    = 0;
					var bottom_mark = 0;
					max         = 0;
					min         = -1;
					m           = 0;
					b           = 0;
					for (var i = 0; i < document.assignment.length; i++) {
						if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
							if(!isNaN(parseFloat(document.assignment.elements[i].value)) && document.assignment.elements[i].value != '') {
								if(Number(document.assignment.elements[i].value) > max)
									max = Number(document.assignment.elements[i].value);
								if(Number(document.assignment.elements[i].value) < min || min == -1)
									min = Number(document.assignment.elements[i].value);
							}
						}
					}
					if(max == score || min == score) {
						recalc_all();
					} else {
						top_mark    = Number(document.getElementById('top_mark').value);
						bottom_mark = Number(document.getElementById('bottom_mark').value);
						m = (top_mark - bottom_mark) / (max - min);
						b = (top_mark * min - bottom_mark * max) / (min - max);
						if(!isNaN(parseFloat((m * score + b) + 0.49))) {
							avg.innerHTML = String(parseInt((m * score + b) + 0.5)) + '%';
						} else {
							avg.innerHTML = '0%';
						}
					}
				} else {
					if(!isNaN(parseFloat((m * score + b) + 0.5))) {
						avg.innerHTML = String(parseInt((m * score + b) + 0.5)) + '%';
					} else {
						avg.innerHTML = '0%';
					}
				}
			} else {
				if(isNaN(parseFloat(max_score)) || Number(max_score)==0) {
					avg.innerHTML = '0%';
				} else {
					avg.innerHTML = String(parseInt(((score / max_score) * 100) + 0.5)) + '%';
				}
			}
		}
	} else if(average_type == AVERAGE_TYPE_INDEX) {
		avg.innerHTML = 'N/A';
		new_class = "unmarked-" + cell_type;
		for(var x=0;x < average_input_array.length; x++) {
			if(average_input_array[x] == score) {
				avg.innerHTML = average_display_array[x];
				new_class = cell_type;
				break;
			}
		}
	}
	document.getElementById('row_' + username).className = new_class
}

/* Change linked stylesheet */
function setLinkedStyleSheet(title) {
	var linkNodes = document.getElementsByTagName("link");
	for(i=0; i<linkNodes.length; i++) {
		linkNode = linkNodes[i];
		relAttr = linkNode.getAttribute('rel');
		if(relAttr && relAttr == "StyleSheet" && linkNode.getAttribute("title")) {
			if(linkNode.getAttribute("title") != 'LES' && linkNode.getAttribute("title") != 'Basic') {
				linkNode.disabled = true;
				if(linkNode.getAttribute("title") == title)
					linkNode.disabled = false;
			} else {
				linkNode.disabled = false;
			}
		}
	}
}

/* Check which stylesheet to use */
function check_style() {
	var hidden = document.getElementById("hidden").checked;
	
	if(hidden == true) {
		setLinkedStyleSheet('Hidden');
	} else {
		setLinkedStyleSheet('Regular');
	}
}

/* Recalculate all averages */
function recalc_all() {
	check_style();
	descr_check();

	if(average_type == AVERAGE_TYPE_PERCENT) {
		mark_boxes_visible();
		var max         = 0;
		var min         = -1;
		var top_mark    = 0;
		var bottom_mark = 0;
		var m           = 0;
		var b           = 0;
		if(document.getElementById('curve_type1').checked == true) {
			ct = 1;
		} else if(document.getElementById('curve_type2').checked == true) {
			ct = 2;
		} else {
			ct = 0;
		}
		for (var i = 0; i < document.assignment.length; i++) {
			if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
				if(!isNaN(document.assignment.elements[i].value) && document.assignment.elements[i].value != '') {
					if(Number(document.assignment.elements[i].value) > max)
						max = Number(document.assignment.elements[i].value);
					if(Number(document.assignment.elements[i].value) < min || min == -1)
						min = Number(document.assignment.elements[i].value);
				}
			}
		}
		if(ct == 2) {
			top_mark    = Number(document.getElementById('top_mark').value);
			bottom_mark = Number(document.getElementById('bottom_mark').value);
			if(!isNaN(top_mark) && !isNaN(bottom_mark)) {
				m = (top_mark - bottom_mark) / (max - min);
				b = (top_mark * min - bottom_mark * max) / (min - max);
			}
		}
		for(var i=0; i<document.assignment.length; i++) {
			if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
				var username = document.assignment.elements[i].id.substring(6);
				recalc_avg(username, max, min, m, b);
			}
		}
	} else if(average_type == AVERAGE_TYPE_INDEX) {
		for(var i=0; i<document.assignment.length; i++) {
			if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
				var username = document.assignment.elements[i].id.substring(6);
				recalc_avg(username);
			}
		}
	}
}

/* Hide mark boxes */
function mark_boxes_visible() {
	if(document.getElementById('curve_type2').checked == true) {
		visible = 'visible';
	} else {
		visible = 'hidden';
	}
	document.getElementById('top_mark').style.visibility          = visible;
	document.getElementById('top_mark_label').style.visibility    = visible;
	document.getElementById('bottom_mark').style.visibility       = visible;
	document.getElementById('bottom_mark_label').style.visibility = visible;
}

function descr_check() {
	if(document.getElementById('descr_type0').checked == true) {
		txtdescr = false;
		datadescr = true;
	} else {
		txtdescr = true;
		datadescr = false;
	}
	document.getElementById('descr_upload').disabled = datadescr;
	document.getElementById('descr').disabled = txtdescr;
}