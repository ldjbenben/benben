<?php

namespace benben\route;

use benben\web\HttpRequest;

/** 
 * Url路径解析器接口
 * 
 * @author benben
 * 
 */
interface IUrlAnalyzer 
{
	/**
	 * url路径中解析出以/为分隔的统一格式
	 *  框架会按以下方式进行路由：
	 * 	1、默认以/分隔的前两项当作"控制器/[默认]动作"来进行路由
	 *  2、上述不成功以"模块/[默认]控制器/[默认]动作"来进行路由
	 *  3、路由成功其它项按/分隔的对来当GET参数来使用
	 *  
	 * @param B_HttpRequest $request
	 * @return string 返回以/分隔的统一格式 如："news/view/id/100"
	 */
	function parseUrl(HttpRequest $request);
	
	/**
	 * 解析url GET参数
	 * 外部传过来指定格式的字符串，此方法解析出参数对，以数组键值对的形式返回
	 * 此方法，是在外部调用parseUrl后去除掉表示主路由信息后，调用此方法，获取参数信息
	 * 
	 * @param string $path 此参数是url中表示参数的部分
	 * @return array 如:外部传参"id-100-name-kitty",可能的返回结果为array('id'=>100,'name'=>'kitty')
	 */
	function parseParams($path);
	
	/**
	 * 创建一个有效的url链接
	 * 
	 * @param string $path 以/为分隔的字符串,格式为"[模块/]控制器[/动作]"
	 * @param array $params 传递的GET参数
	 * 
	 * @return string
	 */
	function createUrl($path, array $params = null);
}