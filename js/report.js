/*****************************************************************
 * js/report.js  (c) 2008 Jonathan Dieter
 *****************************************************************/

/* Recalculate comments */
function recalc_comment(username) {
	var cval_count = 0
	var cval_total = 0.0

	var comment   = document.getElementById('comment_' + username);
	var cval      = document.getElementById('cval_' + username);
	var gender    = document.getElementById('gender_' + username);
	var firstname = document.getElementById('firstname_' + username);
	var fullname  = document.getElementById('fullname_' + username);
	var grade     = document.getElementById('grade_' + username);

	if(!isNaN(parseFloat(cval.value)) && comment.value.length > 10) {
		cval_count += 1;
		cval_total += parseFloat(cval.value);
	} else {
		cval.value = '';
	}
			
	startloc = comment.value.indexOf('{');
	while(startloc != -1) {
		endloc = comment.value.indexOf('}');
		if(endloc == -1) {
			comment.value = comment.value.replace('{', '(');
			startloc = comment.value.indexOf('{');
			continue;
		}
		if(endloc < startloc) {
			comment.value = comment.value.substr(0, endloc) + ')' + comment.value.substr(endloc+1);
			startloc = comment.value.indexOf('{');
			continue;
		}
		nextloc = comment.value.indexOf('{', startloc+1);
		if(nextloc != -1 && nextloc < endloc) {
			comment.value = comment.value.substr(0, startloc) + '(' + comment.value.substr(startloc+1);
			startloc = comment.value.indexOf('{');
			continue;
		}
		replaceval = comment.value.substr(startloc+1, endloc - (startloc+1));
		if(replaceval == '') {
			comment.value = comment.value.replace('{}', '()');
			startloc = comment.value.indexOf('{');
			continue;
		}
		if(isNaN(parseInt(replaceval)) || replaceval >= comment_array.length) {
			comment.value = comment.value.replace('{' + replaceval + '}', '(' + replaceval + ')');
			startloc = comment.value.indexOf('{');
			continue;
		}

		if(gender.value.toLowerCase() == 'm') {
			var heshe  = "he";
			var himher = "him";
			var hisher = "his";
			var Heshe  = "He";
			var Himher = "Him";
			var Hisher = "His";
		} else {
			var heshe  = "she";
			var himher = "her";
			var hisher = "her";
			var Heshe  = "She";
			var Himher = "Her";
			var Hisher = "Her";
		}

		var commentstr = comment_array[parseInt(replaceval)];
		commentstr = commentstr.replace("[Name]", firstname.value);
		commentstr = commentstr.replace("[NAME]", firstname.value);
		commentstr = commentstr.replace("[name]", firstname.value);
		commentstr = commentstr.replace("[FullName]", fullname.value);
		commentstr = commentstr.replace("[FULLNAME]", fullname.value);
		commentstr = commentstr.replace("[fullname]", fullname.value);
		commentstr = commentstr.replace("[Fullname]", fullname.value);
		commentstr = commentstr.replace("[him/her]", himher);
		commentstr = commentstr.replace("[Him/her]", Himher);
		commentstr = commentstr.replace("[Him/Her]", Himher);
		commentstr = commentstr.replace("[he/she]", heshe);
		commentstr = commentstr.replace("[He/she]", Heshe);
		commentstr = commentstr.replace("[He/She]", Heshe);
		commentstr = commentstr.replace("[his/her]", hisher);
		commentstr = commentstr.replace("[His/her]", Hisher);
		commentstr = commentstr.replace("[His/Her]", Hisher);
		commentstr = commentstr.replace("[Grade]", grade);
		commentstr = commentstr.replace("[grade]", grade);
		commentstr = commentstr.replace("[GRADE]", grade);
		commentstr = commentstr.replace("[NextGrade]", toString(parseInt(grade)+1));
		commentstr = commentstr.replace("[Nextgrade]", toString(parseInt(grade)+1));
		commentstr = commentstr.replace("[nextgrade]", toString(parseInt(grade)+1));
		commentstr = commentstr.replace("[NEXTGRADE]", toString(parseInt(grade)+1));

		comment.value = comment.value.replace('{' + replaceval + '}', commentstr);

		cval_count += 1;
		cval_total += parseFloat(cval_array[parseInt(replaceval)]);
		cval.value = String(parseFloat(cval_total) / parseFloat(cval_count));
		startloc = comment.value.indexOf('{');
	}
	comment.value = comment.value.replace('}', ')');
}

/* Recalculate effort username */
function recalc_effort(username) {
	var indata = document.getElementById('effort_' + username).value.toUpperCase();
	var avg = document.getElementById('eavg_' + username);
	if(effort_type == EFFORT_TYPE_PERCENT) {
		if(indata == '' || isNaN(parseInt(indata))) {
			avg.innerHTML = 'N/A';
		} else if(indata < 0) {
			avg.innerHTML = '0%';
		} else if(indata > 100) {
			avg.innerHTML = '100%';
		} else {
			avg.innerHTML = String(indata) + "%";
		}
	} else if(effort_type == EFFORT_TYPE_INDEX) {
		avg.innerHTML = 'N/A';
		for(var x=0;x < effort_input_array.length; x++) {
			if(effort_input_array[x] == indata) {
				avg.innerHTML = effort_display_array[x];
				break;
			}
		}
	}
}

/* Recalculate conduct username */
function recalc_conduct(username) {
	var indata = document.getElementById('conduct_' + username).value.toUpperCase();
	var avg = document.getElementById('cavg_' + username);
	if(conduct_type == CONDUCT_TYPE_PERCENT) {
		if(indata == '' || isNaN(parseInt(indata))) {
			avg.innerHTML = 'N/A';
		} else if(indata < 0) {
			avg.innerHTML = '0%';
		} else if(indata > 100) {
			avg.innerHTML = '100%';
		} else {
			avg.innerHTML = String(indata) + "%";
		}
	} else if(conduct_type == CONDUCT_TYPE_INDEX) {
		avg.innerHTML = 'N/A';
		for(var x=0;x < conduct_input_array.length; x++) {
			if(conduct_input_array[x] == indata) {
				avg.innerHTML = conduct_display_array[x];
				break;
			}
		}
	}
}
