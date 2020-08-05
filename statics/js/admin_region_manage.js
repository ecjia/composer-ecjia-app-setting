// JavaScript Document
;(function(admin, $) {
	admin.admin_region_manage = {
		init : function() {
			ecjia.admin.admin_region_manage.edit();
			ecjia.admin.admin_region_manage.add();
		},
		
		//添加
		add : function() {
			var $form = $('form[name="addArea"]');
			var option = {
				rules:{
					region_name : {required : true},
					region_id : {required : true},
				},
				messages:{
					region_name : {
						required : js_lang_admin_region_manage.please_enter_region_name,
					},
					region_id : {
						required : js_lang_admin_region_manage.please_enter_region_num,
					},
				},
				submitHandler : function() {
					$form.ajaxSubmit({
						dataType : "json",
						success : function(data) {
							$('#editArea').modal('hide');
							$('#addArea').modal('hide');
							$(".modal-backdrop").remove();
							ecjia.admin.showmessage(data); 
						}
					});
				}
			}
			var options = $.extend(ecjia.admin.defaultOptions.validate, option);
			$form.validate(options);
		},
		
		//编辑
		edit : function() {
			$('[href="#editArea"]').on('click', function() {
				var name		= $(this).attr('data-name');
				var region_id	= $(this).attr('value');
				var index_letter= $(this).attr('data-index-letter');
				$('#editArea .parent_name').text(name);
				$('#editArea input[name="region_id"]').val(region_id);
				$('#editArea input[name="index_letter"]').val(index_letter);
			});
			var $form = $('form[name="editArea"]');
			var option = {
				rules:{
					region_name : {required : true},
				},
				messages:{
					region_name : {
						required : js_lang_admin_region_manage.please_enter_region_name,
					}
				},
				submitHandler : function() {
					$form.ajaxSubmit({
						dataType : "json",
						success : function(data) {
							$('#editArea').modal('hide');
							$('#addArea').modal('hide');
							$(".modal-backdrop").remove();
							ecjia.admin.showmessage(data); 
						}
					});
				}
			}
			var options = $.extend(ecjia.admin.defaultOptions.validate, option);
			$form.validate(options);
		},
	}
})(ecjia.admin, $);

//end