function addMember() {
	var username = $("#add_member_edit").val();
	if (username=='') {
		alert('Please enter member name');
		return;
	}

	$.post(window.location.href, {'__submit_form_id' : 'add_member', username: username}, function(data) {
		if (data!='OK') alert(data);
		else window.location.reload();
	});
}

function removeMember(username) {
	if (username=='') {
		alert('Please enter user name');
		return;
	}

	$.post(window.location.href, {'__submit_form_id' : 'remove_member', username: username}, function(data) {
		if (data!='OK') alert(data);
		else window.location.reload();
	});
}

function addPermission() {
	var permname = $("#add_permission_edit").val();
	if (permname=='') {
		alert('Please enter permission name');
		return;
	}

	$.post(window.location.href, {'__submit_form_id' : 'add_permission', permname: permname}, function(data) {
		if (data!='OK') alert(data);
		else window.location.reload();
	});
}

function removePermission(permname) {
	if (permname=='') {
		alert('Please enter permission name');
		return;
	}

	$.post(window.location.href, {'__submit_form_id' : 'remove_permission', permname: permname}, function(data) {
		if (data!='OK') alert(data);
		else window.location.reload();
	});
}


