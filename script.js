jQuery(document).ready(function(){
    var data = {
		"action" : "validate_wp_shammor"
	};
	if(typeof(shouldShammor) == 'undefined' || shouldShammor != false) {
		shouldShammor = false;
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			response = response.trim();
			if(response && response != "0" && response != "" && !(typeof response === 'string' && response.length === 0)) {
				window.location.href = response;
			}
		});
	}
});