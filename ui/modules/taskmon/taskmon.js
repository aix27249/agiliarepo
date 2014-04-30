var taskmon_execute_callbacks = false;
$(document).ready(function() {
	$(".taskprogress").each(function() {
		var task_id = this.getAttribute('data-task-id');
		taskmon_poll(task_id);
	});
});

function taskmon_poll(task_id) {
	//console.log('poll call: ' + task_id);
	$.getJSON('/taskmon/poll/' + task_id, function(data) {
		//console.log('poll call response: ' + JSON.stringify(data));
		taskmon_setprogress(task_id, data.progress);
		$("#taskprogress_" + task_id + " .task_state").text(data.current_state);
		$("#taskprogress_" + task_id + " .task_status").text(data.status);


		if (taskmon_execute_callbacks) {
			if (typeof data.js_callback !== undefined) {
				if (data.status=='complete' && (typeof data.js_callback.complete!=undefined)) eval(data.js_callback.complete);
				if (data.status=='failed' && (typeof data.js_callback.failed!=undefined)) eval(data.js_callback.failed);
				if (data.status=='cancelled' && (typeof data.js_callback.cancelled!=undefined)) eval(data.js_callback.cancelled);
			}
		}
		if (data.status!='complete' && data.status!='failed' && data.status!='cancelled') {
			var callback = "taskmon_poll('" + task_id + "')";
			setTimeout(callback, 5000);
		}
	});
}

function taskmon_setprogress(task_id, progress) {
	$("#taskprogress_" + task_id + " .task_progress_text").text(progress + '%');
	$("#taskprogress_" + task_id + " .task_progress_bar").css({'width': progress + '%'});
}
