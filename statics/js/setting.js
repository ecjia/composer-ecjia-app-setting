// JavaScript Document
;(function(app, $) {
	app.setting = {
		init : function() {
			$(".ajaxmenu").on('click', function(e){
				e.preventDefault();
				//$('.alert-error').removeClass('kind-notice');
				var $this = $(this);
				if ($this.hasClass('disabled')) {
					return false;
				}
				
				var info = '';
				var value = $(this).attr('data-value');
				info = js_lang_setting.get_region_info;
				
				var url = $(this).attr('data-url');
				var message = $(this).attr('data-msg');
				if (message) {
					smoke.confirm(message,function(e){
						if (e) {
							$this.html(js_lang_setting.getting).addClass('disabled');
							app.setting.get_userinfo(url);
						}
					}, {ok:js_lang_setting.ok, cancel:js_lang_setting.cancel});
				} else { 
					app.setting.get_userinfo(url);
				}
			});	
		},
		
		get_userinfo : function(url){
			$.ajax({
				type: "get",
				url: url,
				dataType: "json",
				success: function(data){
					ecjia.admin.showmessage(data);
					if (data.notice == 1) {
						var url = data.url;
						app.setting.get_userinfo(url + '&page=' + data.page + '&more=' + data.more);
					}
				}
			});
		}
	};
})(ecjia.admin, jQuery);

// end