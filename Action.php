<?php

class Export2Hugo_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 导出文章
     *
     * @access public
     * @return void
     */
    public function doExport() {
        date_default_timezone_set('Asia/Shanghai'); // 设置时区
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $sql=<<<TEXT
  select u.screenName author,url authorUrl,title,type,text,created,c.status status,password,t2.category,t1.tags,slug from {$prefix}contents c
  left join
  (select cid,CONCAT('"',group_concat(m.name SEPARATOR '","'),'"') tags from {$prefix}metas m,{$prefix}relationships r where m.mid=r.mid and m.type='tag' group by cid ) t1
  on c.cid=t1.cid
  left join
  (select cid,CONCAT('"',GROUP_CONCAT(m.name SEPARATOR '","'),'"') category from {$prefix}metas m,{$prefix}relationships r where m.mid=r.mid and m.type='category' group by cid) t2
  on c.cid=t2.cid
  left join ( select uid, screenName ,url from {$prefix}users)  as u
  on c.authorId = u.uid
  where c.type in ('post', 'page')
TEXT;

        $contents = $db->fetchAll($db->query($sql));

        $dir = sys_get_temp_dir()."/Export2Hugo";
        if(file_exists($dir)) {
            $this->deleteDirectory($dir); // 使用安全的删除方法
        }

        if (!mkdir($dir, 0755)) {
            throw new Typecho_Exception('无法创建临时目录');
        }

        $contentDir = $dir."/content/";
        if (!mkdir($contentDir, 0755, true)) {
            throw new Typecho_Exception('无法创建内容目录');
        }

        // 创建子目录
        $postDir = $contentDir."posts/";
        if (!mkdir($postDir, 0755, true)) {
            throw new Typecho_Exception('无法创建文章目录');
        }

        foreach($contents as $content) {
            // **-------  内容生成逻辑开始  -------**

            $title = $content['title']; // 获取文章标题

            // **重要:  生成 Hugo 格式的 Markdown 内容  (以下代码仅为示例，需要根据您的 Hugo 站点需求进行调整)**
            $hugo = "---\n";
            $hugo .= "title: \"".$title."\"\n";
            $hugo .= "date: ".date('Y-m-d H:i:s', $content['created'])."\n"; // 使用文章创建时间
            $hugo .= "author: \"".$content['author']."\"\n";
            if (!empty($content['tags'])) {
                $hugo .= "tags: [".$content['tags']."]\n"; // 添加标签
            }
            if (!empty($content['category'])) {
                $hugo .= "categories: [".$content['category']."]\n"; // 添加分类
            }
            $hugo .= "slug: \"".$content['slug']."\"\n"; // 添加 slug
            $hugo .= "---\n\n";
            $hugo .= $content['text']; // 文章内容


            // **重要:  生成文件名，并处理特殊字符，确保文件名合法**
            $filename = str_replace([" ","?","\\","/",":","|","*"], '-', $title).".md";


            if($content["type"] === "post") {
                $filePath = $postDir . $filename;
            } else {
                $filePath = $contentDir . $filename; // Page 类型文章放到 content 根目录
            }

            // **重要:  写入文件**
            if (file_put_contents($filePath, $hugo) === false) {
                throw new Typecho_Exception("文件写入失败: $filePath");
            }

             // **-------  内容生成逻辑结束  -------**
        }

        // 使用 ZipArchive 创建压缩包
        $zipFilename = "hugo.".date('Y-m-d').".zip";
        $zipPath = $dir."/".$zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Typecho_Exception("无法创建 ZIP 文件: $zipPath");
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($contentDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($contentDir));

                if (!$zip->addFile($filePath, $relativePath)) {
                    throw new Typecho_Exception("添加文件到 ZIP 失败: $filePath");
                }
            }
        }

        if (!$zip->close()) {
            throw new Typecho_Exception("ZIP 文件关闭失败");
        }

        // 验证文件存在
        if (!file_exists($zipPath)) {
            throw new Typecho_Exception("ZIP 文件未生成");
        }

        // 发送文件
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=" . basename($zipFilename));
        header("Content-Length: " . filesize($zipPath));
        header("Pragma: no-cache");
        header("Expires: 0");

        // 清空输出缓冲区
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($zipPath);
        exit;
    }

    /**
     * 安全删除目录
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action() {
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->is('export'))->doExport();
    }
}