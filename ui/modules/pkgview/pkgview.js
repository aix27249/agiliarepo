function pkgMove(repository, osversion, branch, subgroup) {
	$.post(window.location, {'__submit_form_id' : 'pkgMoveFormInit', repository: repository, osversion: osversion, branch: branch, subgroup: subgroup}, function(data) {
		createPopup(data);
	});
}

function pkgDelete(repository, osversion, branch, subgroup) {
	if (confirm('Really delete package from ' + repository + '/' + osversion + '/' + branch + '/' + subgroup + '?')) {
		$.post(window.location, {'__submit_form_id' : 'pkgDeleteFormSave', repository: repository, osversion: osversion, branch: branch, subgroup: subgroup}, function(data) {
			if (data==='OK') window.location.reload();
			else createPopup(data);
		});

	}
}

function pkgCopy(repository, osversion, branch, subgroup) {
	$.post(window.location, {'__submit_form_id' : 'pkgCopyFormInit', repository: repository, osversion: osversion, branch: branch, subgroup: subgroup}, function(data) {
		createPopup(data);
	});

}


function pkgFormUpdate() {
	var o = {'__submit_form_id': 'getFormFieldOptions', 'permission' : 'write', 'repository' : $("#repository").val(), 'osversion' : $("#osversion").val(), 'branch' : $("#branch").val(), 'subgroup' : $("#subgroup").val()};

	$.post(window.location, o, function(data) {
		console.log(data);
		var ret = $.parseJSON(data);
		$.each(['osversion', 'branch', 'subgroup'], function(index, value) {
			var selected = "";
			var oldvalue = $("#" + value).val();
			$("#" + value).find('option').remove();
			$.each(ret[value], function(r_idx, r_value) {
				if (r_value==oldvalue) {
					selected = " selected";
					console.log(r_value + ' equals');
				}
				else {
					selected = '';
					console.log(value + ': ' + r_value + ' != ' + oldvalue);
				}
				var optString = '<option value="' + r_value + '"' + selected + '>' + r_value + '</option>';
				$("#" + value).find('option').end().append(optString);
			});
		});
	});
}

function createPopup(code) {
	$('body').append('<div class="popup" id="popup">' + code + '</div><div class="popup_shadow" id="popup_shadow" onclick="removePopup();"></div>');
}

function removePopup() {
	$('.popup, .popup_shadow').remove();
}



