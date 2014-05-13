var setup_variants = {
	switchMode: function(mode) {
		$("#verbose_link").hide();
		$("#package_container").html("Loading...");
		$.post(window.location.href, {'__ajax': mode}, function(data) {
			$("#package_container").html(data);
		});
	},
};
