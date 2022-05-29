jQuery(document).ready(function(){
    var data = {
		"action" : "validate_wp_shammor"
	};
	if(typeof(shouldShammor) == 'undefined' || shouldShammor != false) {
		shouldShammor = false;
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			if(response && response != "0") {
				window.location.href = response;
			}
		});
	}
});