var iso = {
	remove: function(img_name) {
		$.post('/iso', {action: 'remove', name: img_name}, function(data) {
			if (data!=='OK') alert(data);
			else window.location.reload();
		});
	},
};
