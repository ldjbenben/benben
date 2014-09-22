function refreshVerifyCode(target, src)
{
	var date= new Date();
	jQuery(target).attr("src", src + '/' + date.valueOf());
}