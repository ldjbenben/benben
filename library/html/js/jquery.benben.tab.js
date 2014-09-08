;
(function($){

// 按钮触发的事件
	var buttonEvent = function(e){
			var data = e.data;
			// 隐藏其它tab页
			$(data.target).find("."+data.params.buttonClass).neq($(this).index()).removeClass(data.params.currentClass);
			$(data.target).find("."+data.params.contentClass).neq($(this).index()).removeClass(data.params.currentClass).hide();
			// 显示活动tab页
			$(data.target).find("."+data.params.buttonClass).eq($(this).index()).addClass(data.params.currentClass);
			$(data.target).find("."+data.params.contentClass).eq($(this).index()).show();
			return false;
		};

/* 插件扩展方法 */
$.fn.extend({
		
	// 主方法
	"benbenTab":function (options){
			// 初始化参数
			var params = $.extend({
						buttonClass:"", /* 触发事件的按钮class样式,程序会拥有此class样式的标签添加触发事件 */
						contentClass:"", /* 切换内容所对应的class样式 */
						triggerMethod:"click", /* 触发模式"hover" --> 鼠标滑动"click" --> 鼠标点击 */
						currentClass:"on"	/* 当前活动样式 */
					}, options);
					
			// 绑定事件		
			if("click" == params.triggerMethod || "hover" == params.triggerMethod)
			{
				$(this).find("."+params.buttonClass).live(params.triggerMethod,{target:this,params:params}, buttonEvent);
			}
			
		}
	
});
	
})(jQuery);