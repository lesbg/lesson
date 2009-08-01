/*****************************************************************
 * js/class_report.js  (c) 2008 Jonathan Dieter
 *
 * Javascript to deal with class reports
 *****************************************************************/

/* Recalculate comments */
function recalc_comment(reptype) {
	var comment   = document.getElementById(reptype + '_comment');

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

		if(gender.toLowerCase() == 'm') {
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
		commentstr = commentstr.replace("[Name]", firstname);
		commentstr = commentstr.replace("[NAME]", firstname);
		commentstr = commentstr.replace("[name]", firstname);
		commentstr = commentstr.replace("[FullName]", fullname);
		commentstr = commentstr.replace("[FULLNAME]", fullname);
		commentstr = commentstr.replace("[fullname]", fullname);
		commentstr = commentstr.replace("[Fullname]", fullname);
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
		commentstr = commentstr.replace("[NextGrade]", parseInt(grade)+1).toString();
		commentstr = commentstr.replace("[Nextgrade]", parseInt(grade)+1).toString();
		commentstr = commentstr.replace("[nextgrade]", parseInt(grade)+1).toString();
		commentstr = commentstr.replace("[NEXTGRADE]", parseInt(grade)+1).toString();

		comment.value = comment.value.replace('{' + replaceval + '}', commentstr);

		startloc = comment.value.indexOf('{');
	}
	comment.value = comment.value.replace('}', ')');
}

/* Recalculate effort username */
function recalc_effort() {
	var indata = document.getElementById('effort').value.toUpperCase();
	var avg = document.getElementById('eavg');
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

/* Recalculate absences */
function recalc_absences() {
	var indata = document.getElementById('absences').value.toUpperCase();
	var avg = document.getElementById('abavg');
	if(absence_type == ABSENCE_TYPE_NUM) {
		if(indata == '' || isNaN(parseInt(indata))) {
			avg.innerHTML = 'N/A';
		} else if(indata < 0) {
			avg.innerHTML = '0';
		} else {
			avg.innerHTML = String(indata);
		}
	}
}

/* Recalculate conduct username */
function recalc_conduct() {
	var indata = document.getElementById('conduct').value.toUpperCase();
	var avg = document.getElementById('cavg');
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

/* Recalculate conduct username */
function recalc_avg() {
	var indata = document.getElementById('average').value.toUpperCase();
	var avg = document.getElementById('aavg');
	if(average_type == AVERAGE_TYPE_PERCENT) {
		if(indata == '' || isNaN(parseInt(indata))) {
			avg.innerHTML = 'N/A';
		} else if(indata < 0) {
			avg.innerHTML = '0%';
		} else if(indata > 100) {
			avg.innerHTML = '100%';
		} else {
			avg.innerHTML = String(indata) + "%";
		}
	} else if(average_type == AVERAGE_TYPE_INDEX) {
		avg.innerHTML = 'N/A';
		for(var x=0;x < average_input_array.length; x++) {
			if(average_input_array[x] == indata) {
				avg.innerHTML = average_display_array[x];
				break;
			}
		}
	}
}