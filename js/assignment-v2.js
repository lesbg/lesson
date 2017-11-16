/*****************************************************************
 * assignment.js (c) 2006, 2007, 2017 Jonathan Dieter
 *
 * Javascript to deal with assignment creation and modification
 *****************************************************************/

/* Recalculate overall average of username */
function recalc_overall_avg(username) {
    var avg         = document.getElementById('avg_' + username);
    var makeup_avg  = document.getElementById('makeup_avg_' + username);
    var overall_avg = document.getElementById('overall_avg_' + username);
    var mult = 0;

    var makeup_type = document.getElementById('makeuptype').value;

    /* If makeups aren't enabled, set overall average and bail */
    if(makeup_type == 'NULL') {
        overall_avg.innerHTML = avg.innerHTML;
        return;
    }

    var original_max = makeup_dict[makeup_type][0];
    var target_max = makeup_dict[makeup_type][1];

    /* If either average is empty, set the other to be the overall average */
    if(avg.innerHTML == "N/A") {
        overall_avg.innerHTML = makeup_avg.innerHTML;
        return;
    } else if(makeup_avg.innerHTML == "N/A") {
        overall_avg.innerHTML = avg.innerHTML;
        return;
    }

    avg = parseInt(avg.innerHTML.substr(0, avg.innerHTML.length-1));
    makeup_avg = parseInt(makeup_avg.innerHTML.substr(0, makeup_avg.innerHTML.length-1));

    /* If makeup has no effect, set overall to original average to avoid divide-by-zero */
    if(original_max == target_max || avg >= target_max) {
        overall_avg.innerHTML = String(avg) + '%';
        return;
    }

    /* If we're below the original max, then apply straight function */
    if(avg <= original_max)
        mult = (target_max - 100) / (original_max - target_max);
    /* If we're above the original max, but below the target max, apply linear dropoff */
    else
        mult = (target_max - 100) / (avg - target_max);

    overall_avg.innerHTML = String(parseInt(((avg*mult + makeup_avg) / (mult + 1)) + 0.5)) + '%';
}

/* Set style of username row */
function set_style(username) {
    var new_class = "";
    var cell_type = "";

    var score = document.getElementById('score_' + username).value.toUpperCase();
    var makeup_score = document.getElementById('makeup_score_' + username).value.toUpperCase();
    var hidden = document.getElementById("hidden").checked;

    if(document.getElementById('row_' + username).className.indexOf("alt") > -1) {
        cell_type = "alt";
    } else if(document.getElementById('row_' + username).className.indexOf("std") > -1) {
        cell_type = "std";
    }

    new_class = cell_type;
    if(!hidden) {
        if(score == 'L' || makeup_score == 'L')
            new_class = "late-" + cell_type;
        else if(score == 'A' || makeup_score == 'A' || score == 'E' || makeup_score == 'E')
            new_class = cell_type;
        else if((score == '' || isNaN(parseFloat(score))) && (makeup_score == '' || isNaN(parseFloat(makeup_score))))
            new_class = "unmarked-" + cell_type;
        else
            new_class = cell_type;
    } else {
        new_class = cell_type;
    }

    document.getElementById('row_' + username).className = new_class
}

/* Recalculate average of username */
function recalc_student_avg(username, is_makeup=false, max, min, m, b) {
    var makeup_prefix = '';
    var score_type = 'score';
    if(is_makeup) {
        makeup_prefix = 'makeup_';
        score_type = 'makeup_score';
    }
    var avg   = document.getElementById(makeup_prefix + 'avg_' + username);
    var score = document.getElementById(makeup_prefix + 'score_' + username).value.toUpperCase();
    var ignore_zero = false;

    if(average_type == AVERAGE_TYPE_PERCENT || average_type == AVERAGE_TYPE_GRADE) {
        var max_score = document.getElementById('max').value;

        if(document.getElementById('curve_type1').checked == true) {
            ct = 1;
        } else if(document.getElementById('curve_type2').checked == true) {
            ct = 2;
            if(document.getElementById('ignore_zero').checked == true) {
                ignore_zero = true;
            }
        } else {
            ct = 0;
        }

        if(score == 'A' || score == 'E') {
            avg.innerHTML = 'N/A';
            if(ct == 2 && !max)
                recalc_all();
        } else if(score == 'L') {
            avg.innerHTML = '0%';
            if(ct == 2 && !max)
                recalc_all();
        } else if(score == '' || isNaN(parseFloat(score))) {
            avg.innerHTML = 'N/A';
        } else {
            if(score < 0) {
                if(!isNaN(parseFloat(max_score))) {
                    score = Number(max_score) + Number(score);
                    if(score < 0) {
                        score = 0;
                    } else {
                        document.getElementById(makeup_prefix + 'score_' + username).value = score;
                    }
                } else {
                    score = 0;
                }
            }
            if(ct == 1) {
                if(!max) {
                    max = 0;
                    for(var i=0; i<document.assignment.length; i++) {
                        if(document.assignment.elements[i].id.substring(0, score_type.length) == score_type) {
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

                    if(score == 0 && ignore_zero) {
                        recalc_all();
                        return;
                    }
                    for (var i = 0; i < document.assignment.length; i++) {
                        if(document.assignment.elements[i].id.substring(0, score_type.length) == score_type) {
                            if(!isNaN(parseFloat(document.assignment.elements[i].value)) && document.assignment.elements[i].value != '') {
                                if(Number(document.assignment.elements[i].value) > max)
                                    max = Number(document.assignment.elements[i].value);
                                if(Number(document.assignment.elements[i].value) == 0 && ignore_zero)
                                    continue;
                                if(Number(document.assignment.elements[i].value) < min || min == -1)
                                    min = Number(document.assignment.elements[i].value);
                            }
                        }
                    }
                    if(max == score || min == score) {
                        recalc_all();
                        return;
                    } else {
                        top_mark    = Number(document.getElementById('top_mark').value);
                        bottom_mark = Number(document.getElementById('bottom_mark').value);
                        m = (top_mark - bottom_mark) / (max - min);
                        b = (top_mark * min - bottom_mark * max) / (min - max);
                        if(!isNaN(parseFloat((m * score + b) + 0.5))) {
                            avg.innerHTML = String(parseInt((m * score + b) + 0.5)) + '%';
                        } else {
                            avg.innerHTML = '0%';
                        }
                    }
                } else {
                    if(!isNaN(parseFloat((m * score + b) + 0.5)) && (score > 0 || !ignore_zero)) {
                        avg.innerHTML = String(parseInt((m * score + b) + 0.5)) + '%';
                    } else {
                        avg.innerHTML = '0%';
                    }
                }
            } else {
                if(isNaN(parseFloat(max_score)) || Number(max_score)==0) {
                    avg.innerHTML = 'N/A';
                } else {
                    avg.innerHTML = String(parseInt(((score / max_score) * 100) + 0.5)) + '%';
                }
            }
        }
    } else if(average_type == AVERAGE_TYPE_INDEX) {
        avg.innerHTML = 'N/A';
        for(var x=0;x < average_input_array.length; x++) {
            if(average_input_array[x] == score) {
                avg.innerHTML = average_display_array[x];
                break;
            }
        }
    }
}

/* Recalculate student average */
function recalc_avg(username, is_makeup=false) {
    recalc_student_avg(username, is_makeup);
    recalc_overall_avg(username);
    set_style(username);
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
    var agenda = document.getElementById("agenda").value;

    if(hidden == true) {
        setLinkedStyleSheet('Hidden');
    } else {
        if(agenda == true) {
            setLinkedStyleSheet('Agenda');
        } else {
            setLinkedStyleSheet('Regular');
        }
    }
    for(var i=0; i<document.assignment.length; i++) {
        if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
            var username = document.assignment.elements[i].id.substring(6);
            set_style(username);
        }
    }
}

function recalc_all_run(is_makeup=false) {
    var max         = 0;
    var min         = -1;
    var top_mark    = 0;
    var bottom_mark = 0;
    var m           = 0;
    var b           = 0;
    var ignore_zero = false;
    var score_type  = 'score';
    if(is_makeup)
        score_type  = 'makeup_score';
    if(document.getElementById('curve_type1').checked == true) {
        ct = 1;
    } else if(document.getElementById('curve_type2').checked == true) {
        ct = 2;
        if(document.getElementById('ignore_zero').checked == true) {
            ignore_zero = true;
        }
    } else {
        ct = 0;
    }

    for (var i = 0; i < document.assignment.length; i++) {
        if(document.assignment.elements[i].id.substring(0, score_type.length) == score_type) {
            if(!isNaN(document.assignment.elements[i].value) && document.assignment.elements[i].value != '') {
                if(Number(document.assignment.elements[i].value) > max)
                    max = Number(document.assignment.elements[i].value);
                if(Number(document.assignment.elements[i].value) == 0 && ignore_zero)
                    continue;
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
        if(document.assignment.elements[i].id.substring(0, score_type.length) == score_type) {
            var username = document.assignment.elements[i].id.substring(score_type.length + 1);
            recalc_student_avg(username, is_makeup, max, min, m, b);
        }
    }
}

/* Recalculate all averages */
function recalc_all() {
    check_style();
    descr_check();

    if(average_type == AVERAGE_TYPE_PERCENT || average_type == AVERAGE_TYPE_GRADE) {
        mark_boxes_visible();
        recalc_all_run(false);
        recalc_all_run(true);
        for(var i=0; i<document.assignment.length; i++) {
            if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
                var username = document.assignment.elements[i].id.substring(6);
                recalc_overall_avg(username);
                set_style(username);
            }
        }
    } else if(average_type == AVERAGE_TYPE_INDEX) {
        for(var i=0; i<document.assignment.length; i++) {
            if(document.assignment.elements[i].id.substring(0, 5) == 'score') {
                var username = document.assignment.elements[i].id.substring(6);
                recalc_student_avg(username);
                set_style(username);
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
    document.getElementById('ignore_zero').style.visibility       = visible;
    document.getElementById('ignore_zero_label').style.visibility = visible;
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

/* Hide or show makeup info */
function makeup_check() {
    var makeup_type = document.getElementById('makeuptype').value;
    if(makeup_type == 'NULL') {
        visible = 'none';
    } else {
        visible = 'table-cell';
    }

    var makeup_objs = document.querySelectorAll('[id^=makeupObj]')
    for(var i in makeup_objs) {
        if(makeup_objs[i].id == undefined)
            continue;
        makeup_objs[i].style.display = visible;
    }
}
