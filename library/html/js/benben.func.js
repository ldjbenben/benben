// JavaScript Document

/**
 * 设置层块跟随滚动条相随的效果
 * @param string id 作用的目标层块ID
 * @param int top 当滚动条距顶部多少像素时，目标层块开始跟随
 * @return void
 */
function toolbarShadow(id, top)
{
	var scrollTop = $("body").scrollTop();
	var target = jQuery("#"+id);
	if(scrollTop > top)
	{
		target.addClass("benben-fixed");
		target.css("top", scrollTop+"px");
		target.css("marginTop", "0px");
	}
	else
	{
		target.removeClass("benben-fixed");
	}	
}