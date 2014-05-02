var incoming = {
	filenames: [],
	import: function(filename) {
		$("[data-filename='" + filename + "'] input[type='button'].import_button").val('Importing...').attr('disabled', 'disabled');
		$.post('/incoming', {action: 'import', filename: filename}, function(data) {
			if (data=='OK') {
				$.post('/incoming', {action: 'pkginfo', filename: filename}, function(data) {
					$("[data-filename='" + filename + "']").replaceWith(data);
				});
			}
			else {
				alert(data);
				$("[data-filename='" + filename + "'] input[type='button'].import_button").val('Import failed');
			}

		});
		},
	delete: function(filename) {
		$.post('/incoming', {action: 'delete', filename: filename}, function(data) {
			if (data=='OK')	$("[data-filename='" + filename + "']").remove();
			else alert(data);
		});
		},
	add: function(filename) {
		this.filenames = [filename];
		this.addExec(1);
		},
	check: function(o, filename) {
		       this.showMultiButtons();
		},
	selectAll: function() {
		$('#incoming .package_select').each(function() {
			$(this).attr('checked', true);
		});
		incoming.showMultiButtons();

		},
	showMultiButtons: function() {
			var can_add = true, has_checked = false;
			$('#incoming .package').each(function() {
				var f = $(this).attr('data-filename');
				var cb = $("#incoming .package[data-filename='" + f + "'] .package_select");
				if (cb.is(':checked')) {
					has_checked = true;
					if ($(this).attr('data-imported')!='true') can_add = false;
				}
			});

			if (can_add) $("#multi_buttons .addMulti_button").show();
			else $("#multi_buttons .addMulti_button").hide();
			if (has_checked) $("#multi_buttons").show();
			else $("#multi_buttons").hide();
		},
	deleteMulti: function() {
			if (confirm('Delete selected packages?')) {
				$('#incoming .package').each(function() {
					var f = $(this).attr('data-filename');
					incoming.delete(f);
				});
			}
		},
	importMulti: function() {
		$('#incoming .package').each(function() {
			var f = $(this).attr('data-filename');
			incoming.import(f);

		});
	},

	addMulti: function(stage) {
		if (stage==undefined) stage=1;
		
		if (stage==1) {
			this.filenames = [];
			$('#incoming .package').each(function() {
				var f = $(this).attr('data-filename');
				var cb = $("#incoming .package[data-filename='" + f + "'] .package_select");
				if (cb.is(':checked')) {
					incoming.filenames.push(f);
				}
			});
		}
		this.addExec(stage);
	},
	addExec: function(stage) {
		$.post('/incoming', {action: 'addForm', stage: stage, filenames: incoming.filenames, repository: $("#repository").val(), osversion: $("#osversion").val(), branch: $("#branch").val(), subgroup: $("#subgroup").val(), autoposition: $("#autoposition").val()}, function(data) {
			removePopup();
			createPopup(data);
		});
		
	},


};



$(document).ready(function() {
	Dropzone.options.dropzone = {
		url: "/incoming/dropzone",
		accept: function(file, done) {
			if (file.name.split('.').pop()!='txz') {
				done('Only txz packages are allowed');
			}
			else {
				done();
			}
		},
		success: function() {
			$.post('/incoming', {action: 'reload_table'}, function(data) {
				$("#incoming").html(data);
			});
		}
	};
});
