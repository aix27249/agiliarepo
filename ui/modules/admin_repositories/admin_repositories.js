var admin_repositories = {
	create: function() {
			$.post('/admin/repositories', {'__submit_form_id': 'create_repository_form'}, function(data) {
				createPopup(data);
			});
		},
	createExec: function() {
			    var repname = $("#repository").val();
			$.post('/admin/repositories', {'__submit_form_id': 'create_repository', repository: repname}, function(data) {
				if (data==='OK') {
					window.location="/admin/repositories/" + repname + "/edit";
				}
				else alert(data);
			});
		},
};
