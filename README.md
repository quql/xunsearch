# XunSearch安装使用笔记

## 安装

1. 运行下面指令[下载](http://www.xunsearch.com/download/xunsearch-full-latest.tar.gz)、解压安装包

   ```
   wget http://www.xunsearch.com/download/xunsearch-full-latest.tar.bz2
   tar -xjf xunsearch-full-latest.tar.bz2
   ```

2. 执行安装脚本，根据提示进行操作，主要是输入 `xunsearch` 软件包的安装目录，强烈建议单独 规划一个目录，而不是混到别的软件目录中。

   ```
   cd xunsearch-full-1.3.0/
   sh setup.sh
   ```

3. 待命令运行结束后，如果没有出错中断，则表示顺利安装完成，然后就可以启动/重新启动 `xunsearch` 的后台服务，下面命令中的 `$prefix` 请务必替换为您的安装目录，而不是照抄。

   ```
   cd $prefix ; bin/xs-ctl.sh restart
   ```

   强烈建议您将此命令添加到开机启动脚本中，以便每次服务器重启后能自动启动搜索服务程序， 在 `Linux` 系统中您可以将脚本指令写进 `/etc/rc.local` 即可。

4. 有必要指出的是，关于搜索项目的数据目录规划。搜索系统将所有数据保存在 `$prefix/data` 目录中。 如果您希望数据目录另行安排或转移至其它分区，请将 `$prefix/data` 作为软链接指向真实目录。

> **Info:** 出于性能和多数需求考虑 `xunsearch` 服务端和 SDK API 通讯时没有加密和验证处理， 并且默认情况 `xs-ctl.sh` 启动的服务程序是绑定并监听在 `127.0.0.1` 上。
>
> 如果您的 SDK 调用和 `xunsearch` 服务端不在同一服务器，请使用 -b inet 方式启动脚本， 并注意借助类似 `iptables` 的防火墙来控制 `xunsearch` 的 `8383/8384` 两个端口的访问权限。 启动脚本用法举例如下，以下均为合法使用方式：
>
> ```shell
> bin/xs-ctl.sh -b local start    // 监听在本地回环地址 127.0.0.1 上
> bin/xs-ctl.sh -b inet start     // 监听在所有本地 IP 地址上
> bin/xs-ctl.sh -b a.b.c.d start  // 监听在指定 IP 上
> bin/xs-ctl.sh -b unix start     // 分别监听在 tmp/indexd.sock 和 tmp/searchd.sock
> 
> ```
>
>

## 使用

安装 PHP-SDK

安装成功后安装目录里面有个sdk文件就是

首先需要进行配置文件的配置

默认目录在app下，如下面的结构，每一个搜索项目都需要有一个ini文件进行相应的配置。

可以使用官方提供的工具生成   http://www.xunsearch.com/tools/iniconfig

```ini
project.name = demo
project.default_charset = utf-8
server.index = 8383
server.search = 8384
 
[novel_id]
type = id
 
[title]
type = title
 
[author_name]
 
[description]
type = body
 
[last_time]
type = date
 

```

## 导入mySql

 util/Indexer.php --rebuild --source=mysql://你的数据库用户名:你的数据库密码@你的数
据库IP/你的数据库名 --sql="你要执行的SQL语句" --filter=debug --project=你的项目名；

例如

```sql
 util/Indexer.php --rebuild --source=mysql://root:123456@127.0.0.1/ceshi --sql="SELECT * FROM ATRICLE" --filter=debug --project=demo；
```

保存数据文件的目录：$prefix/data/$project ；$prefix表示安装xunSearch的目录，$project表示不同的项目数据目录

若要实时更新请将以上命令写入定时任务

### 生成骨架文件

```shell
[root@localhost util]# ./SearchSkel.php demo
初始化项目对象 ...
解析字段，生成变量清单 ...
检测并创建输出目录 ...
正在复制 css ...
正在复制 img ...
正在生成 search.php ...
正在生成 search.tpl ...
正在生成 suggest.php ...
完成，请将 `./demo` 目录转移到 web 可达目录，然后访问 search.php 即可。
```

访问demo里面的search.php即可搜索你导入的数据



### 从数据库查询出需要的数据，并且将数据添加到xunsearch索引数据库

```php
<?php
//require '/data/xun/lib/XS.php';
require './lib/XS.php';
 
$XS = new XS('demo');// 建立 XS 对象，项目名称为：demo
$index = $XS->index; // 获取 索引对象
 
//创建pdo对象，并从表中读出需要的数据
$dsn = "mysql:dbname=ceshi;host=127.0.0.1";
$pdo = new PDO($dsn,'root','123456');
$sql = "select* from atricle";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(2);
 
//循环添加数据到XunSearch索引库
foreach ($data as $v){
    // 创建文档对象
    $doc = new XSDocument();
    $doc->setFields($v);
    // 添加到索引数据库中
    $res = $index->add($doc);
}
 
$res = $res ? 'success' : 'fail';
echo $res;

```

### 自定义搜索

```php
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
```

官方文档

http://www.xunsearch.com/doc/php