<?php
// 加载 XS 入口文件
require_once '/var/www/html/xunsearch/lib/XS.php';
error_reporting(E_ALL ^ E_NOTICE);
try {
	$xs = new XS('demo');    // 建立 XS 对象，项目名称为：demo
	$search = $xs->search;		// 获取 搜索对象
	$search->setCharset('UTF-8');
	$query = '打开'; // 这里的搜索语句很简单，就一个短语
	$search->setQuery($query); // 设置搜索语句
	// $search->addWeight('subject', 'xunsearch'); // 增加附加条件：提升标题中包含 'xunsearch' 的记录的权重
	$search->setLimit(5, 10); // 设置返回结果最多为 5 条，并跳过前 10 条 
	$docs = $search->search(); // 执行搜索，将搜索结果文档保存在 $docs 数组中
	$count = $search->count(); // 获取搜索结果的匹配总数估算值

} catch (XSException $e) {
	$error = strval($e);
}
echo '<pre>';
print_r($docs);
foreach ($docs as $key => $v) {
	print_r($v->power."<br>");   //获取字段
	print_r($v->id."<br>");			//获取主键
}



