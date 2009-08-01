/*****************************************************************
 * casenotes.js  (c) 2006 Jonathan Dieter
 *
 * Javascript to deal with casenote creation and modification
 *****************************************************************/

function check_counselor_list() {
	if(document.getElementById('level3').checked == true) {
		for(var x=0; x<document.getElementById('counselor_list').options.length; x++) {
			document.getElementById('counselor_list').options[x].selected = true;
		}
		document.getElementById('counselor_list').disabled = false;
	} else if(document.getElementById('level1').checked == true || document.getElementById('level2').checked == true) {
		for(var x=0; x<document.getElementById('counselor_list').options.length; x++) {
			document.getElementById('counselor_list').options[x].selected = true;
		}
		document.getElementById('counselor_list').disabled = true;
	} else {
		document.getElementById('counselor_list').selectedIndex = -1;
		document.getElementById('counselor_list').disabled = true;
	}
}