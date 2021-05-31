(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$("#store_city_id").select2()

	$('input[name="version_type"]').change(function(){
		if($(this).val()=="1")
			$(".toggle_version_type").show();
		else
			$(".toggle_version_type").hide();
	});

	$('input[name="is_unique_code"]').change(function(){
		if($(this).val()=="1")
			$(".toggle_is_unique_code").show();
		else
			$(".toggle_is_unique_code").hide();
	});

	$('input[name="is_added_cost_enable"]').change(function(){
		if($(this).val()=="1")
			$(".toggle_is_added_cost_enable").show();
		else
			$(".toggle_is_added_cost_enable").hide();
	});
})( jQuery );
