<?php
namespace library;

use Exception;

class Import
{

    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * 程序安装安装
     */
    public function install()
    {
        if (file_exists(DATA_PATH . 'install.lock')) {
            exit("You had already installed");
        }
        if (! file_exists(DATA_PATH)) {
            mkdir(DATA_PATH, 0775, true);
        }
        if (! file_exists(TEP_PATH)) {
            mkdir(TEP_PATH, 0775, true);
        }
        if (! file_exists(LOG_PATH)) {
            mkdir(LOG_PATH, 0775, true);
        }
        touch(DATA_PATH . 'install.lock');
        echo "intall succeed !";
    }

    /**
     * 下载列表页面
     * 
     * @param int $siteId            
     */
    public function listWrite($siteId)
    {
        $row = $this->db->find('setting', array(
            'id' => $siteId
        ));
        echo "loader config file \n";
        if (empty($row['cur_page']) && empty($row['total_page'])) {
            throw new Exception('cur_page， total_page config');
            return false;
        }
        if ($row['total_page'] < $row['cur_page']) {
            echo "not data \n";
            return false;
        }
        for ($i = $row['cur_page']; $i <= $row['total_page']; $i ++) {
            $url = str_replace('[PAGE_NUM]', $i, $row['url']);
            $result = \library\Crawl::write($url, $row['project'] . '/list.txt');
            if (empty($result)) {
                continue;
            }
            $data[] = array(
                'url' => $url,
                'filesize' => $result,
                'site_id' => $row['id'],
                'type' => 1
            );
        }
        $this->db->insertAll('url', $data);
        return $this->db->update('setting', array(
            'id' => $row['id']
        ), array(
            'cur_page' => $i - 1
        ));
    }

    /**
     * 匹配列表页面内内容页面URL
     * 
     * @param int $siteId            
     * @return boolean
     */
    public function listRead($siteId)
    {
        $row = $this->db->find('setting', array(
            'id' => $siteId
        ));
        $filename = $row['project'] . '/list.txt';
        $callback = '\library\CrawlCallback::listWrite';
        return \library\Crawl::read($filename, $callback, $row, $this->db);
    }

    /**
     * 匹配内容页面相关内容
     * 
     * @param int $siteId            
     * @return boolean
     */
    public function detailRead($siteId)
    {
        $row = $this->db->find('setting', array(
            'id' => $siteId
        ));
        $row['data'] = $this->db->findAll('setting_content', array(
            'site_id' => $siteId
        ));
        $filename = $row['project'] . '/detail.txt';
        $callback = '\library\CrawlCallback::detailWrite';
        return \library\Crawl::read($filename, $callback, $row, $this->db);
    }

    /**
     * 清除项目数据
     * 
     * @param int $siteId            
     * @throws \Exception
     * @return boolean
     */
    public function clear($siteId)
    {
        $row = $this->db->find('setting', array(
            'id' => $siteId
        ));
        $this->db->delete('url', array(
            'site_id' => $siteId
        ));
        $this->db->delete('data', array(
            'site_id' => $siteId
        ));
        $this->db->delete('data_detail', array(
            'site_id' => $siteId
        ));
        $this->db->delete('image', array(
            'site_id' => $siteId
        ));
        $path = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'data/' . $row['project']);
        if (! $path) {
            throw new \Exception('dir not exists:' . ROOT_PATH . DIRECTORY_SEPARATOR . 'data/' . $row['project']);
            return false;
        }
        if (IS_WIN) {
            exec("rd /s /q {$path}", $output);
        } else {
            exec("rm -rf {$path}", $output);
        }
        echo "[succeed] clear {$row['project']} " . \var_export($output, true) . " \n";
    }
}

