/*****************************************************************
 * js/class_report.js  (c) 2008, 2016 Jonathan Dieter
 *
 * Javascript to deal with class reports
 *****************************************************************/

/* Recalculate comments */
function recalc_comment(reptype) {
	var comment   = document.getElementById(reptype + '_comment');
	var old_comment = comment.value;

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
	
	comment.value = comment.value.replace("{Name}", firstname);
	comment.value = comment.value.replace("{NAME}", firstname);
	comment.value = comment.value.replace("{name}", firstname);
	comment.value = comment.value.replace("{FullName}", fullname);
	comment.value = comment.value.replace("{FULLNAME}", fullname);
	comment.value = comment.value.replace("{fullname}", fullname);
	comment.value = comment.value.replace("{Fullname}", fullname);
	comment.value = comment.value.replace("{him/her}", himher);
	comment.value = comment.value.replace("{Him/her}", Himher);
	comment.value = comment.value.replace("{Him/Her}", Himher);
	comment.value = comment.value.replace("{he/she}", heshe);
	comment.value = comment.value.replace("{He/she}", Heshe);
	comment.value = comment.value.replace("{He/She}", Heshe);
	comment.value = comment.value.replace("{his/her}", hisher);
	comment.value = comment.value.replace("{His/her}", Hisher);
	comment.value = comment.value.replace("{His/Her}", Hisher);
	comment.value = comment.value.replace("{Grade}", grade);
	comment.value = comment.value.replace("{grade}", grade);
	comment.value = comment.value.replace("{GRADE}", grade);
	comment.value = comment.value.replace("{NextGrade}", parseInt(grade)+1).toString();
	comment.value = comment.value.replace("{Nextgrade}", parseInt(grade)+1).toString();
	comment.value = comment.value.replace("{nextgrade}", parseInt(grade)+1).toString();
	comment.value = comment.value.replace("{NEXTGRADE}", parseInt(grade)+1).toString();
	
	comment.value = comment.value.replace("[Name]", firstname);
	comment.value = comment.value.replace("[NAME]", firstname);
	comment.value = comment.value.replace("[name]", firstname);
	comment.value = comment.value.replace("[FullName]", fullname);
	comment.value = comment.value.replace("[FULLNAME]", fullname);
	comment.value = comment.value.replace("[fullname]", fullname);
	comment.value = comment.value.replace("[Fullname]", fullname);
	comment.value = comment.value.replace("[him/her]", himher);
	comment.value = comment.value.replace("[Him/her]", Himher);
	comment.value = comment.value.replace("[Him/Her]", Himher);
	comment.value = comment.value.replace("[he/she]", heshe);
	comment.value = comment.value.replace("[He/she]", Heshe);
	comment.value = comment.value.replace("[He/She]", Heshe);
	comment.value = comment.value.replace("[his/her]", hisher);
	comment.value = comment.value.replace("[His/her]", Hisher);
	comment.value = comment.value.replace("[His/Her]", Hisher);
	comment.value = comment.value.replace("[Grade]", grade);
	comment.value = comment.value.replace("[grade]", grade);
	comment.value = comment.value.replace("[GRADE]", grade);
	comment.value = comment.value.replace("[NextGrade]", parseInt(grade)+1).toString();
	comment.value = comment.value.replace("[Nextgrade]", parseInt(grade)+1).toString();
	comment.value = comment.value.replace("[nextgrade]", parseInt(grade)+1).toString();
	comment.value = comment.value.replace("[NEXTGRADE]", parseInt(grade)+1).toString();
	
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

		var commentstr = comment_array[parseInt(replaceval)];

		comment.value = comment.value.replace('{' + replaceval + '}', commentstr);

		startloc = comment.value.indexOf('{');
	}

	comment.value = comment.value.replace('}', ')');
	if(comment.value != old_comment) {
		recalc_comment(reptype);
	}
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