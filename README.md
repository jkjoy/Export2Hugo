## Export2Hugo
Typecho 博客文章导出至 Hugo 插件

## 我修改了什么

- 使用 PHP 的 `ZipArchive` 拓展来创建压缩包,用以解决部分服务器or虚拟主机没有 `zip`.

## 如何使用

点击右侧的`Download ZIP`按钮，下载完成之后解压得到类似`typecho-export-hugo-master`文件夹，将文件夹重命名为`Export2Hugo`,上传到Typecho目录`usr/plugins`,然后在后台启用插件。

在后台界面，`控制台`菜单下会有一个`导出至Hugo`菜单，点击进入导出界面，点击按钮后获得导出的 Zip 文件，将解压后的 `content` 文件夹移动到 Hugo 目录下即可。

**注：**

1. Mac 下有可能无法解压该 zip 文件，可以在命令行使用 `unzip` 命令进行解压。

## LICENSE

[MIT LICENSE](https://github.com/lizheming/typecho-export-hugo/blob/master/LICENSE)
