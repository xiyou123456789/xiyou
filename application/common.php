<?php

/* 引用全局定义 */
require __DIR__ . '/common_global.php';
/* 商品相关调用 */
require __DIR__ . '/common_goods.php';
/* 图片上传、生成缩略图、删除等操作调用 */
require __DIR__ . '/common_upload.php';
/*
 * 更换数组的键值 为了应对 ->key
 */

function ds_change_arraykey($array, $key)
{
    $data = array();
    foreach ($array as $value) {
        $data[$value[$key]] = $value;
    }
    return $data;
}
/**
 * 
 * @param type $table 数据表
 * @param type $field 条件对应的字段
 * @param type $name  条件对应的值
 * @param type $value 数值
 * @return type
 */
function ds_getvalue_byname($table,$field,$name,$value){
    return db($table)->where($field,$name)->value($value);
}

/*
 * 编辑器内容
 */

function build_editor($params = array())
{
    $name = isset($params['name']) ? $params['name'] : null;
    $theme = isset($params['theme']) ? $params['theme'] : 'normal';
    $content = isset($params['content']) ? $params['content'] : null;
    //http://fex.baidu.com/ueditor/#start-toolbar
    /* 指定使用哪种主题 */
    $themes = array(
        'normal' => "[   
           'fullscreen', 'source', '|', 'undo', 'redo', '|',   
           'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',   
           'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',   
           'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',   
           'directionalityltr', 'directionalityrtl', 'indent', '|',   
           'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',   
           'link', 'unlink', 'anchor', '|', 'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',   
           'emotion',  'map', 'gmap',  'insertcode', 'template',  '|',   
           'horizontal', 'date', 'time', 'spechars', '|',   
           'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', 'charts', '|',   
           'searchreplace', 'help', 'drafts', 'charts'
       ]", 'simple' => " ['fullscreen', 'source', 'undo', 'redo', 'bold']",
    );
    switch ($theme) {
        case 'simple':
            $theme_config = $themes['simple'];
            break;
        case 'normal':
            $theme_config = $themes['normal'];
            break;
        default:
            $theme_config = $themes['normal'];
            break;
    }
    /* 配置界面语言 */
    switch (config('default_lang')) {
        case 'zh-cn':
            $lang = PLUGINS_SITE_ROOT . '/ueditor/lang/zh-cn/zh-cn.js';
            break;
        case 'en-us':
            $lang = PLUGINS_SITE_ROOT . '/ueditor/lang/en/en.js';
            break;
        default:
            $lang = PLUGINS_SITE_ROOT . '/ueditor/lang/zh-cn/zh-cn.js';
            break;
    }
    $include_js = '<script type="text/javascript" charset="utf-8" src="' . PLUGINS_SITE_ROOT . '/ueditor/ueditor.config.js"></script> <script type="text/javascript" charset="utf-8" src="' . PLUGINS_SITE_ROOT . '/ueditor/ueditor.all.min.js""> </script><script type="text/javascript" charset="utf-8" src="' . $lang . '"></script>';
	$content = json_encode($content);
    $str = <<<EOT
$include_js
<script type="text/javascript">
var ue = UE.getEditor('{$name}',{
    toolbars:[{$theme_config}],
        });
      ue.ready(function() {
       this.setContent($content);	
})
</script>
EOT;
    return $str;
}

/**
 * 
 * @param type $code   100000表示为正确,其他为错误代码
 * @param type $message  提示消息
 * @param type $result  返回数据
 */
function ds_json_encode($code, $message, $result = '')
{
    echo json_encode(array('code' => $code, 'message' => $message, 'result' => $result));
    exit;
}

/**
 * 规范数据返回函数
 * @param unknown $code
 * @param unknown $msg
 * @param unknown $data
 * @return multitype:unknown
 */
function ds_callback($code, $msg = '', $data = array())
{
    return array('code' => $code, 'msg' => $msg, 'data' => $data);
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++)
        $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 消息提示，主要适用于普通页面AJAX提交的情况
 *
 * @param string $message 消息内容
 * @param string $url 提示完后的URL去向
 * @param stting $alert_type 提示类型 error/succ/notice 分别为错误/成功/警示
 * @param string $extrajs 扩展JS
 * @param int $time 停留时间
 */
function ds_show_dialog($message = '', $url = '', $alert_type = 'error', $extrajs = '', $time = 2)
{
    $message = str_replace("'", "\\'", strip_tags($message));

    $paramjs = null;
    if ($url == 'reload') {
        $paramjs = 'window.location.reload()';
    }
    elseif ($url != '') {
        $paramjs = 'window.location.href =\'' . $url . '\'';
    }
    if ($paramjs) {
        $paramjs = 'function (){' . $paramjs . '}';
    }
    else {
        $paramjs = 'null';
    }
    $modes = array('error' => 'alert', 'succ' => 'succ', 'notice' => 'notice', 'js' => 'js');
    $cover = $alert_type == 'error' ? 1 : 0;
    $extra = 'showDialog(\'' . $message . '\', \'' . $modes[$alert_type] . '\', null, ' . ($paramjs ? $paramjs : 'null') . ', ' . $cover . ', null, null, null, null, ' . (is_numeric($time) ? $time : 'null') . ', null);';
    $extra = '<script type="text/javascript" reload="1">' . $extra . '</script>';
    if ($extrajs != '' && substr(trim($extrajs), 0, 7) != '<script') {
        $extrajs = '<script type="text/javascript" reload="1">' . $extrajs . '</script>';
    }
    $extra .= $extrajs;
    ob_end_clean();
    @header("Expires: -1");
    @header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
    @header("Pragma: no-cache");
    @header("Content-type: text/xml; charset=utf-8");

    $string = '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
    $string .= '<root><![CDATA[' . $message . $extra . ']]></root>';
    echo $string;
    exit;
}

/**
 * 取上一步来源地址
 *
 * @param
 * @return string 字符串类型的返回结果
 */
function get_referer()
{
    return empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
}

/**
 * 加密函数
 *
 * @param string $txt 需要加密的字符串
 * @param string $key 密钥
 * @return string 返回加密结果
 */
function ds_encrypt($txt, $key = '')
{
    if (empty($txt))
        return $txt;
    if (empty($key))
        $key = md5(MD5_KEY);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $nh1 = rand(0, 64);
    $nh2 = rand(0, 64);
    $nh3 = rand(0, 64);
    $ch1 = $chars{$nh1};
    $ch2 = $chars{$nh2};
    $ch3 = $chars{$nh3};
    $nhnum = $nh1 + $nh2 + $nh3;
    $knum = 0;
    $i = 0;
    while (isset($key{$i}))
        $knum += ord($key{$i++});
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $txt = base64_encode(time() . '_' . $txt);
    $txt = str_replace(array('+', '/', '='), array('-', '_', '.'), $txt);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = strlen($txt);
    $klen = strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++) {
        $k = $k == $klen ? 0 : $k;
        $j = ($nhnum + strpos($chars, $txt{$i}) + ord($mdKey{$k++})) % 64;
        $tmp .= $chars{$j};
    }
    $tmplen = strlen($tmp);
    $tmp = substr_replace($tmp, $ch3, $nh2 % ++$tmplen, 0);
    $tmp = substr_replace($tmp, $ch2, $nh1 % ++$tmplen, 0);
    $tmp = substr_replace($tmp, $ch1, $knum % ++$tmplen, 0);
    return $tmp;
}

/**
 * 解密函数
 *
 * @param string $txt 需要解密的字符串
 * @param string $key 密匙
 * @return string 字符串类型的返回结果
 */
function ds_decrypt($txt, $key = '', $ttl = 0)
{
    if (empty($txt))
        return $txt;
    if (empty($key))
        $key = md5(MD5_KEY);

    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $knum = 0;
    $i = 0;
    $tlen = @strlen($txt);
    while (isset($key{$i}))
        $knum += ord($key{$i++});
    $ch1 = @$txt{$knum % $tlen};
    $nh1 = strpos($chars, $ch1);
    $txt = @substr_replace($txt, '', $knum % $tlen--, 1);
    $ch2 = @$txt{$nh1 % $tlen};
    $nh2 = @strpos($chars, $ch2);
    $txt = @substr_replace($txt, '', $nh1 % $tlen--, 1);
    $ch3 = @$txt{$nh2 % $tlen};
    $nh3 = @strpos($chars, $ch3);
    $txt = @substr_replace($txt, '', $nh2 % $tlen--, 1);
    $nhnum = $nh1 + $nh2 + $nh3;
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = @strlen($txt);
    $klen = @strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++) {
        $k = $k == $klen ? 0 : $k;
        $j = strpos($chars, $txt{$i}) - $nhnum - ord($mdKey{$k++});
        while ($j < 0)
            $j += 64;
        $tmp .= $chars{$j};
    }
    $tmp = str_replace(array('-', '_', '.'), array('+', '/', '='), $tmp);
    $tmp = trim(base64_decode($tmp));

    if (preg_match("/\d{10}_/s", substr($tmp, 0, 11))) {
        if ($ttl > 0 && (time() - substr($tmp, 0, 11) > $ttl)) {
            $tmp = null;
        }
        else {
            $tmp = substr($tmp, 11);
        }
    }
    return $tmp;
}


/**
 * 获取文件列表(所有子目录文件)
 *
 * @param string $path 目录
 * @param array $file_list 存放所有子文件的数组
 * @param array $ignore_dir 需要忽略的目录或文件
 * @return array 数据格式的返回结果
 */
function read_file_list($path, &$file_list, $ignore_dir = array())
{
    $path = rtrim($path, '/');
    if (is_dir($path)) {
        $handle = @opendir($path);
        if ($handle) {
            while (false !== ($dir = readdir($handle))) {
                if ($dir != '.' && $dir != '..') {
                    if (!in_array($dir, $ignore_dir)) {
                        if (is_file($path . '/' . $dir)) {
                            $file_list[] = $path . '/' . $dir;
                        }
                        elseif (is_dir($path . '/' . $dir)) {
                            read_file_list($path . '/' . $dir, $file_list, $ignore_dir);
                        }
                    }
                }
            }
            @closedir($handle);
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }
}

/**
 * 价格格式化
 *
 * @param int $price
 * @return string    $price_format
 */
function ds_price_format($price)
{
    $price_format = number_format($price, 2, '.', '');
    return $price_format;
}

/**
 * 价格格式化
 *
 * @param int $price
 * @return string    $price_format
 */
function ds_price_format_forlist($price)
{
    if ($price >= 10000) {
        return number_format(floor($price / 100) / 100, 2, '.', '') . lang('ten_thousand');
    }
    else {
        return lang('currency') . $price;
    }
}


/**
 * 通知邮件/通知消息 内容转换函数
 *
 * @param string $message 内容模板
 * @param array $param 内容参数数组
 * @return string 通知内容
 */
function ds_replace_text($message, $param)
{
    if (!is_array($param))
        return false;
    foreach ($param as $k => $v) {
        $message = str_replace('{$' . $k . '}', $v, $message);
    }
    return $message;
}

/** @noinspection InconsistentLineSeparators */

/**
 * 字符串切割函数，一个字母算一个位置,一个字算2个位置
 *
 * @param string $string 待切割的字符串
 * @param int $length 切割长度
 * @param string $dot 尾缀
 */
function str_cut($string, $length, $dot = '')
{
    $string = str_replace(array(
                              '&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;',
                              '&middot;', '&hellip;'
                          ), array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
    $strlen = strlen($string);
    if ($strlen <= $length)
        return $string;
    $maxi = $length - strlen($dot);
    $strcut = '';

    $n = $tn = $noc = 0;
    while ($n < $strlen) {
        $t = ord($string[$n]);
        if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
            $tn = 1;
            $n++;
            $noc++;
        }
        elseif (194 <= $t && $t <= 223) {
            $tn = 2;
            $n += 2;
            $noc += 2;
        }
        elseif (224 <= $t && $t < 239) {
            $tn = 3;
            $n += 3;
            $noc += 2;
        }
        elseif (240 <= $t && $t <= 247) {
            $tn = 4;
            $n += 4;
            $noc += 2;
        }
        elseif (248 <= $t && $t <= 251) {
            $tn = 5;
            $n += 5;
            $noc += 2;
        }
        elseif ($t == 252 || $t == 253) {
            $tn = 6;
            $n += 6;
            $noc += 2;
        }
        else {
            $n++;
        }
        if ($noc >= $maxi)
            break;
    }
    if ($noc > $maxi)
        $n -= $tn;
    $strcut = substr($string, 0, $n);
    $strcut = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;'), $strcut);
    return $strcut . $dot;
}


/*
 * 重写$_SERVER['REQUREST_URI']
 */

function request_uri()
{
    if (isset($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
    }
    else {
        if (isset($_SERVER['argv'])) {
            $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
        }
        else {
            $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        }
    }
    return $uri;
}


/**
 * 取得商品默认大小图片
 *
 * @param string $key 图片大小 small
 * @return string
 */
function default_goodsimage($key='')
{
//     $file = str_ireplace('.', '_' . $key . '.', config('default_goods_image'));
    $file = config('default_goods_image');
    return ATTACH_COMMON . '/' . $file;
}

/**
 * 取得用户头像图片
 *
 * @param string $member_avatar
 * @return string
 */
function get_member_avatar($member_avatar)
{
    if (empty($member_avatar)) {
        return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . 'default_user_portrait.gif';
    }
    else {

        if (file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/' . $member_avatar)) {
            return UPLOAD_SITE_URL . '/' . ATTACH_AVATAR . '/' . $member_avatar;
        }
        else {
            return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . 'default_user_portrait.gif';
        }
    }
}

/**
 * 成员头像
 * @param string $member_id
 * @return string
 */
function get_member_avatar_for_id($id)
{
    if (file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/avatar_' . $id . '.jpg')) {
        return UPLOAD_SITE_URL . '/' . ATTACH_AVATAR . '/avatar_' . $id . '.jpg';
    }
    else {
        if (config('default_user_portrait')) {
            return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . config('default_user_portrait');
        }
        else {
            return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . 'default_user_portrait.gif';
        }

    }
}


/**
 * 取得店铺标志
 *
 * @param string $img 图片名
 * @param string $type 查询类型 store_logo/store_avatar
 * @return string
 */
function get_store_logo($img, $type = 'store_avatar')
{
    if ($type == 'store_avatar') {
        if (empty($img)) {
            return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . config('default_store_avatar');
        }
        else {
            $linfo = explode('_', $img);
            if (is_file(BASE_UPLOAD_PATH . '/' . ATTACH_STORE . '/' . $linfo['0'] . '/' . $img))
                return UPLOAD_SITE_URL . '/' . ATTACH_STORE . '/' . $linfo['0'] . '/' . $img;
            return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . config('default_store_avatar');
        }
    }
    elseif ($type == 'store_logo') {
        if (empty($img)) {
            return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . '/' . config('default_store_logo');
        }
        else {
            $linfo = explode('_', $img);
            return UPLOAD_SITE_URL . '/' . ATTACH_STORE . '/' . $linfo['0'] . '/' . $img;
        }
    }
}

/**
 * 获取用户相册图片
 * @param type $user_id
 * @param type $ap_cover
 * @return type
 */
function get_snsalbumpic($user_id,$ap_cover){
    if (!file_exists(BASE_UPLOAD_PATH.'/'.ATTACH_MALBUM.'/'.$user_id.'/'.$ap_cover)) {
        return UPLOAD_SITE_URL . '/' . default_goodsimage('normal');
    }else{
        return UPLOAD_SITE_URL . '/' . ATTACH_MALBUM . '/' . $user_id . '/' . $ap_cover;
    }
}


/**
 * 获取开店申请图片
 */
function get_store_joinin_imageurl($image_name = '')
{
    return UPLOAD_SITE_URL . '/' . ATTACH_STORE_JOININ . '/' . $image_name;
}


/**
 * 获取运单图片地址
 */
function get_waybill_imageurl($image_name = '')
{
    $image_path = '/' . ATTACH_WAYBILL . '/' . $image_name;
    if (is_file(BASE_UPLOAD_PATH . $image_path)) {
        return UPLOAD_SITE_URL . $image_path;
    }
    else {
        return UPLOAD_SITE_URL . '/' . default_goodsimage('240');
    }
}

/**
 * 取得随机数
 *
 * @param int $length 生成随机数的长度
 * @param int $numeric 是否只产生数字随机数 1是0否
 * @return string
 */
function random($length, $numeric = 0)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}


/**
 * sns表情标示符替换为html
 */
function parsesmiles($message)
{
    $file = EXTEND_PATH.'/smilies.php';
    if (file_exists($file)) {
        include $file;
        if (!empty($smilies_array) && is_array($smilies_array)) {
            $imagesurl = PLUGINS_SITE_ROOT .  '/js' .  '/smilies' . '/images' . '/';
            $replace_arr = array();
            foreach ($smilies_array['replacearray'] AS $key => $smiley) {
                $replace_arr[$key] = '<img src="' . $imagesurl . $smiley['imagename'] . '" title="' . $smiley['desc'] . '" border="0" alt="' . $imagesurl . $smiley['desc'] . '" />';
            }

            $message = preg_replace($smilies_array['searcharray'], $replace_arr, $message);
        }
    }
    return $message;
}


/**
 * 延时加载分页功能，判断是否有更多连接和limitstart值和经过验证修改的$delay_eachnum值
 * @param int $delay_eachnum 延时分页每页显示的条数
 * @param int $delay_page 延时分页当前页数
 * @param int $count 总记录数
 * @param bool $ispage 是否在分页模式中实现延时分页(前台显示的两种不同效果)
 * @param int $page_nowpage 分页当前页数
 * @param int $page_eachnum 分页每页显示条数
 * @param int $page_limitstart 分页初始limit值
 * @return array array('hasmore'=>'是否显示更多连接','limitstart'=>'加载的limit开始值','delay_eachnum'=>'经过验证修改的$delay_eachnum值');
 */
function lazypage($delay_eachnum, $delay_page, $count, $ispage = false, $page_nowpage = 1, $page_eachnum = 1, $page_limitstart = 1)
{
    //是否有多余
    $hasmore = true;
    $limitstart = 0;
    if ($ispage == true) {
        if ($delay_eachnum < $page_eachnum) {//当延时加载每页条数小于分页的每页条数时候实现延时加载，否则按照普通分页程序流程处理
            $page_totlepage = ceil($count / $page_eachnum);
            //计算limit的开始值
            $limitstart = $page_limitstart + ($delay_page - 1) * $delay_eachnum;
            if ($page_totlepage > $page_nowpage) {//当前不为最后一页
                if ($delay_page >= $page_eachnum / $delay_eachnum) {
                    $hasmore = false;
                }
                //判断如果分页的每页条数与延时加载每页的条数不能整除的处理
                if ($hasmore == false && $page_eachnum % $delay_eachnum > 0) {
                    $delay_eachnum = $page_eachnum % $delay_eachnum;
                }
            }
            else {//当前最后一页
                $showcount = ($page_totlepage - 1) * $page_eachnum + $delay_eachnum * $delay_page; //已经显示的记录总数
                if ($count <= $showcount) {
                    $hasmore = false;
                }
            }
        }
        else {
            $hasmore = false;
        }
    }
    else {
        if ($count <= $delay_page * $delay_eachnum) {
            $hasmore = false;
        }
        //计算limit的开始值
        $limitstart = ($delay_page - 1) * $delay_eachnum;
    }

    return array('hasmore' => $hasmore, 'limitstart' => $limitstart, 'delay_eachnum' => $delay_eachnum);
}





/**
 * 返回以原数组某个值为下标的新数据
 *
 * @param array $array
 * @param string $key
 * @param int $type 1一维数组2二维数组
 * @return array
 */
function array_under_reset($array, $key, $type = 1)
{
    if (is_array($array)) {
        $tmp = array();
        foreach ($array as $v) {
            if ($type === 1) {
                $tmp[$v[$key]] = $v;
            }
            elseif ($type === 2) {
                $tmp[$v[$key]][] = $v;
            }
        }
        return $tmp;
    }
    else {
        return $array;
    }
}

/**
 * KV缓存 读
 *
 * @param string $key 缓存名称
 * @param boolean $callback 缓存读取失败时是否使用回调 true代表使用cache.model中预定义的缓存项 默认不使用回调
 * @param callable $callback 传递非boolean值时 通过is_callable进行判断 失败抛出异常 成功则将$key作为参数进行回调
 * @return mixed
 */
function rkcache($key, $callback = false)
{
    $value = cache($key);
    if (empty($value) && $callback !== false) {
        if ($callback === true) {
            $callback = array(model('cache'), 'call');
        }

        if (!is_callable($callback)) {
            exception('Invalid rkcache callback!');
        }
        $value = call_user_func($callback, $key);
        wkcache($key, $value);
    }
    return $value;
}

/**
 * KV缓存 写
 *
 * @param string $key 缓存名称
 * @param mixed $value 缓存数据 若设为否 则下次读取该缓存时会触发回调（如果有）
 * @param int $expire 缓存时间 单位秒 null代表不过期
 * @return boolean
 */
function wkcache($key, $value, $expire = 7200)
{
    return cache($key, $value, $expire);
}

/**
 * KV缓存 删
 *
 * @param string $key 缓存名称
 * @return boolean
 */
function dkcache($key)
{
    return cache($key, NULL);
}

/**
 * 读取缓存信息
 *
 * @param string $key 要取得缓存键
 * @param string $prefix 键值前缀
 * @return array/bool
 */
function rcache($key = null, $prefix = '')
{
    if ($key === null || !config('cache_open'))
        return array();
    if (!empty($prefix)) {
        $name = $prefix . $key;
    }
    else {
        $name = $key;
    }
    $cache_info = cache($name);
    //如果name值不存在，则默认返回 false。
    return $cache_info;
}

/**
 * 写入缓存
 *
 * @param string $key 缓存键值
 * @param array $data 缓存数据
 * @param string $prefix 键值前缀
 * @param int $expire 缓存周期  单位分，0为永久缓存
 * @return bool 返回值
 */
function wcache($key = null, $data = array(), $prefix='', $expire = 3600)
{
    if ($key === null || !config('cache_open') || !is_array($data))
        return;

    if (!empty($prefix)) {
        $name = $prefix . $key;
    }
    else {
        $name = $key;
    }
    $cache_info = cache($name, $data, $expire);
    //如果设置成功返回true，否则返回false。
    return $cache_info;
}

/**
 * 删除缓存
 * @param string $key 缓存键值
 * @param string $prefix 键值前缀
 * @return boolean
 */
function dcache($key = null, $prefix = '')
{
    if ($key === null || !config('cache_open'))
        return true;
    if (!empty($prefix)) {
        $name = $prefix . $key;
    }
    else {
        $name = $key;
    }
    return cache($name, NULL);
}



/**
 * sns 来自
 * @param $param string $trace_from
 * @return string
 */
function sns_share_from($sign)
{
    switch ($sign) {
        case '1' :
        case '2' :
            return lang('sns_from') . '<a target="_black" href="' . HOME_SITE_URL . '">' . lang('sns_shop') . '</a>';
            break;
    }
}

/**
 * 输出聊天信息
 *
 * @return string
 */
function get_chat()
{
    return Chat::getChatHtml();
}


/**
 * 验证是否为平台店铺
 *
 * @return boolean
 */
function check_platform_store()
{
    return session('is_platform_store');
}

/**
 * 验证是否为平台店铺 并且绑定了全部商品类目
 *
 * @return boolean
 */
function check_platform_store_bindingall_goodsclass()
{

    return check_platform_store() && session('bind_all_gc');
}

/**
 * 生成20位编号(时间+微秒+随机数+会员ID%1000)，该值会传给第三方支付接口
 * 长度 =12位 + 3位 + 2位 + 3位  = 20位
 * 1000个会员同一微秒提订单，重复机率为1/100
 * @return string
 */

function makePaySn($member_id) {
    return date('ymdHis',  time()).sprintf('%03d', (float) microtime() * 1000) .mt_rand(10, 99).sprintf('%03d', intval($member_id) % 1000);
}

/**
 * 获得店铺状态样式名称
 * @param $param array $store_info
 * @return string
 */
function get_store_state_classname($store_info)
{
    $result = 'open';
    if (intval($store_info['store_state']) === 1) {
        $store_endtime = intval($store_info['store_endtime']);
        if ($store_endtime > 0) {
            if ($store_endtime < TIMESTAMP) {
                $result = 'expired';
            }
            elseif (($store_endtime - 864000) < TIMESTAMP) {
                //距离到期10天
                $result = 'expire';
            }
        }
    }
    else {
        $result = 'close';
    }
    return $result;
}

/**
 * 将字符部分加密并输出
 * @param unknown $str
 * @param unknown $start 从第几个位置开始加密(从1开始)
 * @param unknown $length 连续加密多少位
 * @return string
 */
function encrypt_show($str, $start, $length)
{
    $end = $start - 1 + $length;
    $array = str_split($str);
    foreach ($array as $k => $v) {
        if ($k >= $start - 1 && $k < $end) {
            $array[$k] = '*';
        }
    }
    return implode('', $array);
}

/**
 * CURL请求
 * @param $url 请求url地址
 * @param $method 请求方法 get post
 * @param null $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug 调试开启 默认false
 * @return mixed
 */
function http_request($url, $method = "GET", $postfields = null, $headers = array(), $debug = false)
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return $response;
}

/**
 * Layer 提交成功返回函数
 * @param type $message
 */
function dsLayerOpenSuccess($msg = '',$url='') {
//    echo "<script>var index = parent.layer.getFrameIndex(window.name);parent.layer.close(index);parent.location.reload();</script>";
    $url_js = empty($url)?"parent.location.reload();":"parent.location.href='".$url."';";
            
    $str = "<script>";
    $str .= "parent.layer.alert('".$msg."',{yes:function(index, layero){".$url_js."},cancel:function(index, layero){".$url_js."}});";
    $str .= "</script>";
    echo $str;
    exit;
}

/**
 * 移除微信昵称中的emoji字符
 * @param type $nickname
 * @return type
 */
function removeEmoji($nickname) {
    $clean_text = "";
    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text = preg_replace($regexEmoticons, '', $nickname);
    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text = preg_replace($regexSymbols, '', $clean_text);
    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text = preg_replace($regexTransport, '', $clean_text);
    // Match Miscellaneous Symbols
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, '', $clean_text);
    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text = preg_replace($regexDingbats, '', $clean_text);
    //截取指定长度的昵称
    $clean_text = ds_substing($clean_text,0,20);
    return trim($clean_text);
}

/**
 * 截取指定长度的字符
 * @param type $string  内容
 * @param type $start 开始
 * @param type $length 长度
 * @return type
 */
function ds_substing($string, $start=0,$length=80) {
    $string = strip_tags($string);
    $string = preg_replace('/\s/', '', $string);
    return mb_substr($string, $start, $length);
}

/**
 * 针对批量删除进行处理  '1,2,3' 转换为数组批量删除
 * @param type $ids
 * @return boolean
 */
function ds_delete_param($ids){
    //转换为数组
    $ids_array = explode(',', $ids);
    //数组值转为整数型
    $ids_array = array_map("intval", $ids_array);
    if(empty($ids_array)||  in_array(0, $ids_array)){
        return FALSE;
    }else{
        return $ids_array;
    }
}