function addGroup() {
	var groupname = $("#add_group_edit").val();
	if (groupname=='') {
		alert('Please enter group name');
		return;
	}

	$.post(window.location.href, {'__submit_form_id' : 'add_group', groupname: groupname}, function(data) {
		if (data!='OK') alert(data);
		else window.location.reload();
	});
}

function removeGroup(groupname) {
	if (groupname=='') {
		alert('Please enter group name');
		return;
	}

	$.post(window.location.href, {'__submit_form_id' : 'remove_group', groupname: groupname}, function(data) {
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

function enableUser(enable) {
	$.post(window.location.href, {'__submit_form_id' : 'enable_user', enable: enable}, function(data) {
		if (data!='OK') alert(data);
		else window.location.reload();
	});
}

$(document).ready(function() {
	$("#change_password_form").submit(function() {
		if ($("#password").val()!=$("#repeat_password").val()) {
			alert('Passwords does not match');
			return false;
		}
		var password = $("#password").val();
		if (password=='') {
			alert('Password cannot be empty');
			return false;
		}

		$.post(window.location.href, {'__submit_form_id' : 'change_password', password: password}, function(data) {
			if (data!='OK') alert(data);
			else {
				alert('Password changed successfully');
				window.location.reload();
			}

		});

		return false;
	});
});
