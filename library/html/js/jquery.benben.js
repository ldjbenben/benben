;(function($){

/* 插件扩展方法 */
$.fn.extend({
		
	// 主方法
	"neq":function (index){
			var parent = this;
			this.each(function(){
				if(index == $(this).index())
				{
					$(parent).splice(index,1);
				}
			});
			return this;
		}
	
});
	
})(jQuery);