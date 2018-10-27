<?php

/**
 * Created by ds
 * 销售分析
 */

namespace app\admin\controller;

use think\Loader;
use think\Lang;

class Stattrade extends AdminControl {

    private $search_arr; //处理后的参数

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/stat.lang.php');
        Loader::import('mall.statistics');
        Loader::import('mall.datehelper');
    }

    /**
     * 销售收入统计
     */
    public function income() {
        $stat_model = model('stat');
        $search_year = input('param.search_year');
        $search_month = input('param.search_month');
        if ($search_year == '' || $search_month == '') {
            $now_year = date('Y', time());
            $now_month = date('m', time());
            if ($now_month == 1) {
                $search_year = $now_year - 1;
                $search_month = 12;
            } else {
                $search_year = $now_year;
                if ($now_month > 10) {
                    $search_month = $now_month - 1;
                } else {
                    $search_month = '0' . ($now_month - 1);
                }
            }
        }
        $year = intval($search_year);
        $month = trim($search_month);
        if (!in_array($month, array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'))) {
            $month = date('m', time());
            $search_month = $month;
        }
        $condition['os_month'] = $year . $month;
        if (input('param.exporttype') == 'excel') {
            //获取全部店铺结账数据
            $bill_list = $stat_model->getBillList($condition, 'ob', false);
            //导出Excel
            $excel_obj = new \excel\Excel();
            $excel_data = array();
            //设置样式
            $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
            //header
            $excel_data[0][0] = array('styleid' => 's_title', 'data' => '店铺名称');
            $excel_data[0][1] = array('styleid' => 's_title', 'data' => '卖家账号');
            $excel_data[0][2] = array('styleid' => 's_title', 'data' => '订单金额');
            $excel_data[0][3] = array('styleid' => 's_title', 'data' => '收取佣金');
            $excel_data[0][4] = array('styleid' => 's_title', 'data' => '退单金额');
            $excel_data[0][5] = array('styleid' => 's_title', 'data' => '退回佣金');
            $excel_data[0][6] = array('styleid' => 's_title', 'data' => '店铺费用');
            $excel_data[0][7] = array('styleid' => 's_title', 'data' => '结算金额');
            //data
            foreach ($bill_list as $k => $v) {
                $excel_data[$k + 1][0] = array('data' => $v['ob_store_name']);
                $excel_data[$k + 1][1] = array('data' => $v['member_name']);
                $excel_data[$k + 1][2] = array('data' => $v['ob_order_totals']);
                $excel_data[$k + 1][3] = array('data' => $v['ob_commis_totals']);
                $excel_data[$k + 1][4] = array('data' => $v['ob_order_return_totals']);
                $excel_data[$k + 1][5] = array('data' => $v['ob_commis_return_totals']);
                $excel_data[$k + 1][6] = array('data' => $v['ob_store_cost_totals']);
                $excel_data[$k + 1][7] = array('data' => $v['ob_result_totals']);
            }
            $excel_data = $excel_obj->charset($excel_data, CHARSET);
            $excel_obj->addArray($excel_data);
            $excel_obj->addWorksheet($excel_obj->charset('店铺佣金统计', CHARSET));
            $excel_obj->generateXML($excel_obj->charset('店铺佣金统计', CHARSET) . date('Y-m-d-H', time()));
            exit();
        } else {
            //获取平台总数据
            $plat_data = $stat_model->getBillList($condition, 'os');
            $this->assign('plat_data', $plat_data[0]);
            //店铺数据
            $this->assign('store_list', $stat_model->getBillList($condition, 'ob'));
            $this->assign('show_page',$stat_model->page_info->render());
            $this->setAdminCurItem('income');
            return $this->fetch('stat_income');
        }
    }

    /**
     * 预存款统计
     */
    public function predeposit() {
        $where = array();
        if (trim(input('param.pd_type')) == 'cash_pay') {
            $field = 'sum(lg_freeze_amount) as allnum';
        } else {
            $field = 'sum(lg_av_amount) as allnum';
        }
        $search_type = input('param.search_type');
        if (!$search_type) {
            $search_type = 'day';
        }
        $where['lg_type'] = trim(input('param.pd_type')) == '' ? 'recharge' : trim(input('param.pd_type'));
        //初始化时间
        //天
        $search_time = input('param.search_time');
        if (!$search_time) {
            $search_time = date('Y-m-d', time() - 86400);
        }
        $search_time = strtotime($search_time); //搜索的时间
        $this->assign('search_time', $search_time);
        //周
        $search_time_year = input('param.search_time_year');
        if (!$search_time_year) {
            $search_time_year = date('Y', time());
        }
        $search_time_month = input('param.search_time_month');
        if (!$search_time_month) {
            $search_time_month = date('m', time());
        }
        $search_time_week = input('param.search_time_week');
        if (!$search_time_week) {
            $search_time_week = implode('|', getWeek_SdateAndEdate(time()));
        }
        $current_year = $search_time_year;
        $current_month = $search_time_month;
        $current_week = $search_time_week;
        $year_arr = getSystemYearArr();
        $month_arr = getSystemMonthArr();
        $week_arr = getMonthWeekArr($current_year, $current_month);

        $this->assign('current_year', $current_year);
        $this->assign('current_month', $current_month);
        $this->assign('current_week', $current_week);
        $this->assign('year_arr', $year_arr);
        $this->assign('month_arr', $month_arr);
        $this->assign('week_arr', $week_arr);

        $stat_model = model('stat');
        $statlist = array(); //统计数据列表
        if ($search_type == 'day') {
            //构造横轴数据
            for ($i = 0; $i < 24; $i++) {
                //统计图数据
                $curr_arr[$i] = 0; //今天
                $up_arr[$i] = 0; //昨天
                //统计表数据
                $uplist_arr[$i]['timetext'] = $i;
                $currlist_arr[$i]['timetext'] = $i;
                $uplist_arr[$i]['val'] = 0;
                $currlist_arr[$i]['val'] = 0;
                //横轴
                $stat_arr['xAxis']['categories'][] = "$i";
            }
            $stime = $search_time - 86400; //昨天0点
            $etime = $search_time + 86400 - 1; //今天24点

            $today_day = @date('d', $search_time); //今天日期
            $yesterday_day = @date('d', $stime); //昨天日期

            $where['lg_addtime'] = array('between', array($stime, $etime));
            $field .= ' ,DAY(FROM_UNIXTIME(lg_addtime)) as dayval,HOUR(FROM_UNIXTIME(lg_addtime)) as hourval ';
            $memberlist = $stat_model->getPredepositInfo($where, $field, 0, '', 0, 'dayval,hourval');
            if ($memberlist) {
                foreach ($memberlist as $k => $v) {
                    if ($today_day == $v['dayval']) {
                        $curr_arr[$v['hourval']] = abs($v['allnum']);
                        $currlist_arr[$v['hourval']]['val'] = abs($v['allnum']);
                    }
                    if ($yesterday_day == $v['dayval']) {
                        $up_arr[$v['hourval']] = abs($v['allnum']);
                        $uplist_arr[$v['hourval']]['val'] = abs($v['allnum']);
                    }
                }
            }
            $stat_arr['series'][0]['name'] = '昨天';
            $stat_arr['series'][0]['data'] = array_values($up_arr);
            $stat_arr['series'][1]['name'] = '今天';
            $stat_arr['series'][1]['data'] = array_values($curr_arr);

            //统计数据标题
            $statlist['headertitle'] = array('小时', '昨天', '今天', '同比');
            $this->assign('actionurl', url('Stattrade/predeposit', ['search_type' => 'day', 'search_time' => date('Y-m-d', $search_time)]));
        }

        if ($search_type == 'week') {
            $current_weekarr = explode('|', $current_week);
            $stime = strtotime($current_weekarr[0]) - 86400 * 7;
            $etime = strtotime($current_weekarr[1]) + 86400 - 1;
            $up_week = @date('W', $stime); //上周
            $curr_week = @date('W', $etime); //本周
            //构造横轴数据
            for ($i = 1; $i <= 7; $i++) {
                //统计图数据
                $up_arr[$i] = 0;
                $curr_arr[$i] = 0;
                $tmp_weekarr = getSystemWeekArr();
                //统计表数据
                $uplist_arr[$i]['timetext'] = $tmp_weekarr[$i];
                $currlist_arr[$i]['timetext'] = $tmp_weekarr[$i];
                $uplist_arr[$i]['val'] = 0;
                $currlist_arr[$i]['val'] = 0;
                //横轴
                $stat_arr['xAxis']['categories'][] = $tmp_weekarr[$i];
                unset($tmp_weekarr);
            }
            $where['lg_addtime'] = array('between', array($stime, $etime));
            $field .= ',WEEKOFYEAR(FROM_UNIXTIME(lg_addtime)) as weekval,WEEKDAY(FROM_UNIXTIME(lg_addtime))+1 as dayofweekval ';
            $memberlist = $stat_model->getPredepositInfo($where, $field, 0, '', 0,'weekval,dayofweekval');
            if ($memberlist) {
                foreach ($memberlist as $k => $v) {
                    if ($up_week == $v['weekval']) {
                        $up_arr[$v['dayofweekval']] = abs($v['allnum']);
                        $uplist_arr[$v['dayofweekval']]['val'] = abs($v['allnum']);
                    }
                    if ($curr_week == $v['weekval']) {
                        $curr_arr[$v['dayofweekval']] = abs($v['allnum']);
                        $currlist_arr[$v['dayofweekval']]['val'] = abs($v['allnum']);
                    }
                }
            }
            $stat_arr['series'][0]['name'] = '上周';
            $stat_arr['series'][0]['data'] = array_values($up_arr);
            $stat_arr['series'][1]['name'] = '本周';
            $stat_arr['series'][1]['data'] = array_values($curr_arr);
            //统计数据标题
            $statlist['headertitle'] = array('星期', '上周', '本周', '同比');
            $this->assign('actionurl', url('Stattrade/predeposit', ['search_type' => 'week', 'search_time_year' => $current_year, 'search_time_month' => '$current_month', 'search_time_week' => $current_week]));
        }

        if ($search_type == 'month') {
            $stime = strtotime($current_year . '-' . $current_month . "-01 -1 month");
            $etime = getMonthLastDay($current_year, $current_month) + 86400 - 1;

            $up_month = date('m', $stime);
            $curr_month = date('m', $etime);
            //计算横轴的最大量（由于每个月的天数不同）
            $up_dayofmonth = date('t', $stime);
            $curr_dayofmonth = date('t', $etime);
            $x_max = $up_dayofmonth > $curr_dayofmonth ? $up_dayofmonth : $curr_dayofmonth;

            //构造横轴数据
            for ($i = 1; $i <= $x_max; $i++) {
                //统计图数据
                $up_arr[$i] = 0;
                $curr_arr[$i] = 0;
                //统计表数据
                $uplist_arr[$i]['timetext'] = $i;
                $currlist_arr[$i]['timetext'] = $i;
                $uplist_arr[$i]['val'] = 0;
                $currlist_arr[$i]['val'] = 0;
                //横轴
                $stat_arr['xAxis']['categories'][] = $i;
            }
            $where['lg_addtime'] = array('between', array($stime, $etime));
            $field .= ',MONTH(FROM_UNIXTIME(lg_addtime)) as monthval,day(FROM_UNIXTIME(lg_addtime)) as dayval ';
            $memberlist = $stat_model->getPredepositInfo($where, $field, 0, '', 0, 'monthval,dayval');
            if ($memberlist) {
                foreach ($memberlist as $k => $v) {
                    if ($up_month == $v['monthval']) {
                        $up_arr[$v['dayval']] = abs($v['allnum']);
                        $uplist_arr[$v['dayval']]['val'] = abs($v['allnum']);
                    }
                    if ($curr_month == $v['monthval']) {
                        $curr_arr[$v['dayval']] = abs($v['allnum']);
                        $currlist_arr[$v['dayval']]['val'] = abs($v['allnum']);
                    }
                }
            }
            $stat_arr['series'][0]['name'] = '上月';
            $stat_arr['series'][0]['data'] = array_values($up_arr);
            $stat_arr['series'][1]['name'] = '本月';
            $stat_arr['series'][1]['data'] = array_values($curr_arr);
            //统计数据标题
            $statlist['headertitle'] = array('日期', '上月', '本月', '同比');
            $this->assign('actionurl', url('Stattrade/predeposit', ['search_type' => 'month', 'search_time_year' => $current_year, 'search_time_month' => $current_month]));
        }

        //计算同比
        foreach ((array) $currlist_arr as $k => $v) {
            $tmp = array();
            $tmp['timetext'] = $v['timetext'];
            $tmp['currentdata'] = $v['val'];
            $tmp['updata'] = $uplist_arr[$k]['val'];
            $tmp['tbrate'] = getTb($tmp['updata'], $tmp['currentdata']);
            $statlist['data'][] = $tmp;
        }
        //导出Excel
        if (input('param.exporttype') == 'excel') {
            //获取数据
            $log_list = $stat_model->getPredepositInfo($where, '*', '');
            //导出Excel
            $excel_obj = new \excel\Excel();
            $excel_data = array();
            //设置样式
            $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
            //header
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '会员名称');
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '创建时间');
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '可用金额（元）');
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '冻结金额（元）');
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '管理员名称');
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '类型');
            $excel_data[0][] = array('styleid' => 's_title', 'data' => '描述');
            //data
            foreach ($log_list as $k => $v) {
                $excel_data[$k + 1][] = array('data' => $v['lg_member_name']);
                $excel_data[$k + 1][] = array('data' => date('Y-m-d H:i:s', $v['lg_addtime']));
                $excel_data[$k + 1][] = array('data' => $v['lg_av_amount']);
                $excel_data[$k + 1][] = array('data' => $v['lg_freeze_amount']);
                $excel_data[$k + 1][] = array('data' => $v['lg_admin_name']);
                switch ($v['lg_type']) {
                    case 'recharge':
                        $excel_data[$k + 1][] = array('data' => '充值');
                        break;
                    case 'order_pay':
                        $excel_data[$k + 1][] = array('data' => '消费');
                        break;
                    case 'cash_pay':
                        $excel_data[$k + 1][] = array('data' => '提现');
                        break;
                    case 'refund':
                        $excel_data[$k + 1][] = array('data' => '退款');
                        break;
                }
                $excel_data[$k + 1][] = array('data' => $v['lg_desc']);
            }
            $excel_data = $excel_obj->charset($excel_data, CHARSET);
            $excel_obj->addArray($excel_data);
            $excel_obj->addWorksheet($excel_obj->charset('预存款统计', CHARSET));
            $excel_obj->generateXML($excel_obj->charset('预存款统计', CHARSET) . date('Y-m-d-H', time()));
            exit();
        } else {
            $log_list = $stat_model->getPredepositInfo($where, '*', 15);
            $this->assign('log_list', $log_list);
            $this->assign('show_page', $stat_model->page_info->render());
            //总数统计部分
            $recharge_amount = $stat_model->getPredepositInfo(array('lg_type' => 'recharge', 'lg_addtime' => array('between', array($stime, $etime))), 'sum(lg_av_amount) as allnum');
            $order_amount = $stat_model->getPredepositInfo(array('lg_type' => 'order_pay', 'lg_addtime' => array('between', array($stime, $etime))), 'sum(lg_av_amount) as allnum');
            $cash_amount = $stat_model->getPredepositInfo(array('lg_type' => 'cash_pay', 'lg_addtime' => array('between', array($stime, $etime))), 'sum(lg_freeze_amount) as allnum');
            $this->assign('stat_array', array('recharge_amount' => $recharge_amount[0]['allnum'], 'order_amount' => abs($order_amount[0]['allnum']), 'cash_amount' => abs($cash_amount[0]['allnum'])));
            $user_amount = $stat_model->getPredepositInfo(true, 'distinct lg_member_id');
            $this->assign('user_amount', count($user_amount));
            $usable_amount = $stat_model->getPredepositInfo(true, 'sum(lg_av_amount+lg_freeze_amount) as allnum');
            $this->assign('usable_amount', $usable_amount[0]['allnum']);
            //得到统计图数据
            $stat_arr['title'] = '预存款统计';
            $stat_arr['yAxis'] = '金额';
            $stat_json = getStatData_LineLabels($stat_arr);
            $this->assign('stat_json', $stat_json);
            $this->assign('statlist', $statlist);
            $this->setAdminCurItem('predeposit');
            return $this->fetch('stat_predeposit');
        }
    }

    /**
     * 订单统计
     */
    public function sale() {
        $stat_model = model('stat');
        //存储参数
        $this->search_arr = input('param.');
        //处理搜索时间
        $this->search_arr = $stat_model->dealwithSearchTime($this->search_arr);
        //获得系统年份
        $year_arr = getSystemYearArr();
        //获得系统月份
        $month_arr = getSystemMonthArr();
        //获得本月的周时间段
        $week_arr = getMonthWeekArr($this->search_arr['week']['current_year'], $this->search_arr['week']['current_month']);
        $this->assign('year_arr', $year_arr);
        $this->assign('month_arr', $month_arr);
        $this->assign('week_arr', $week_arr);
        $this->assign('search_arr', $this->search_arr);

        //默认统计当前数据
        if (!isset($this->search_arr['search_type'])) {
            $this->search_arr['search_type'] = 'day';
        }
        //计算昨天和今天时间
        if ($this->search_arr['search_type'] == 'day') {
            $stime = $this->search_arr['day']['search_time'] - 86400; //昨天0点
            $etime = $this->search_arr['day']['search_time'] + 86400 - 1; //今天24点
            $curr_stime = $this->search_arr['day']['search_time']; //今天0点
        } elseif ($this->search_arr['search_type'] == 'week') {
            $current_weekarr = explode('|', $this->search_arr['week']['current_week']);
            $stime = strtotime($current_weekarr[0]) - 86400 * 7;
            $etime = strtotime($current_weekarr[1]) + 86400 - 1;
            $curr_stime = strtotime($current_weekarr[0]); //本周0点
        } elseif ($this->search_arr['search_type'] == 'month') {
            $stime = strtotime($this->search_arr['month']['current_year'] . '-' . $this->search_arr['month']['current_month'] . "-01 -1 month");
            $etime = getMonthLastDay($this->search_arr['month']['current_year'], $this->search_arr['month']['current_month']) + 86400 - 1;
            $curr_stime = strtotime($this->search_arr['month']['current_year'] . '-' . $this->search_arr['month']['current_month'] . "-01");
            //本月0点
        }

        $where = array();
        $where['order_add_time'] = array('between', array($curr_stime, $etime));
        if (trim(input('param.order_type')) != '') {
            $where['order_state'] = trim(input('param.order_type'));
        }
        if (trim(input('param.store_name')) != '') {
            $where['store_name'] = array('like', '%' . trim(input('param.store_name')) . '%');
        }
        if (input('param.exporttype') == 'excel') {
            $order_list = $stat_model->statByStatorder($where, '', 0, 0, 'order_id desc', '');
        } else {
            $order_list = $stat_model->statByStatorder($where, '', 10, 0, 'order_id desc', '');
        }
        //统计数据标题
        $statlist = array();
        $statlist['headertitle'] = array('订单号', '买家', '店铺名称', '下单时间', '订单总额', '订单状态');

        foreach ((array) $order_list as $k => $v) {
            switch ($v['order_state']) {
                case ORDER_STATE_CANCEL:
                    $v['order_statetext'] = '已取消';
                    break;
                case ORDER_STATE_NEW:
                    $v['order_statetext'] = '待付款';
                    break;
                case ORDER_STATE_PAY:
                    $v['order_statetext'] = '待发货';
                    break;
                case ORDER_STATE_SEND:
                    $v['order_statetext'] = '待收货';
                    break;
                case ORDER_STATE_SUCCESS:
                    $v['order_statetext'] = '交易完成';
                    break;
            }
            $statlist['data'][$k] = $v;
        }
        //导出Excel
        if (input('param.exporttype') == 'excel') {
            //导出Excel
            $excel_obj = new \excel\Excel();
            $excel_data = array();
            //设置样式
            $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
            //header
            foreach ($statlist['headertitle'] as $v) {
                $excel_data[0][] = array('styleid' => 's_title', 'data' => $v);
            }
            //data
            foreach ((array) @$statlist['data'] as $k => $v) {
                $excel_data[$k + 1][] = array('data' => $v['order_sn']);
                $excel_data[$k + 1][] = array('data' => $v['buyer_name']);
                $excel_data[$k + 1][] = array('data' => $v['store_name']);
                $excel_data[$k + 1][] = array('data' => date('Y-m-d H:i:s', $v['order_add_time']));
                $excel_data[$k + 1][] = array('data' => number_format(($v['order_amount']), 2));
                $excel_data[$k + 1][] = array('data' => $v['order_statetext']);
            }
            $excel_data = $excel_obj->charset($excel_data, CHARSET);
            $excel_obj->addArray($excel_data);
            $excel_obj->addWorksheet($excel_obj->charset('订单统计', CHARSET));
            $excel_obj->generateXML($excel_obj->charset('订单统计', CHARSET) . date('Y-m-d-H', time()));
            exit();
        } else {
            //总数统计
            $statcount_arr = $stat_model->getoneByStatorder($where, ' COUNT(*) as ordernum, SUM(order_amount) as orderamount');
            $this->assign('statcount_arr', $statcount_arr);
            $this->assign('searchtime', implode('|', array($stime, $etime)));
            $this->assign('statlist', $statlist);
            $this->assign('show_page', $stat_model->page_info->render());
            $this->setAdminCurItem('sale');
            return $this->fetch('stat_sale');
        }
    }

    /**
     * 订单走势
     */
    public function sale_trend() {
        $stat_model = model('stat');
        //存储参数
        $this->search_arr = $_REQUEST;
        //默认统计当前数据
        if (!$this->search_arr['search_type']) {
            $this->search_arr['search_type'] = 'day';
        }
        $where = array();
        $order_state = input('param.order_state');
        if (trim($order_state) != '') {
            $where['order_state'] = trim($order_state);
        }
        $store_name = input('param.store_name');
        if (trim($store_name) != '') {
            $where['store_name'] = array('like', '%' . trim($store_name) . '%');
        }
        $stattype = trim(input('param.type'));
        if ($stattype == 'ordernum') {
            $field = ' COUNT(*) as ordernum ';
            $stat_arr['title'] = '订单量统计';
            $stat_arr['yAxis'] = '订单量';
        } else {
            $stattype = 'orderamount';
            $field = ' SUM(order_amount) as orderamount ';
            $stat_arr['title'] = '订单销售额统计';
            $stat_arr['yAxis'] = '订单销售额';
        }
        $searchtime_arr_tmp = explode('|', $this->search_arr['t']);
        foreach ((array) $searchtime_arr_tmp as $k => $v) {
            $searchtime_arr[] = intval($v);
        }
        if ($this->search_arr['search_type'] == 'day') {
            //构造横轴数据
            for ($i = 0; $i < 24; $i++) {
                //统计图数据
                $curr_arr[$i] = 0; //今天
                $up_arr[$i] = 0; //昨天
                //统计表数据
                $currlist_arr[$i]['timetext'] = $i;

                $uplist_arr[$i]['val'] = 0;
                $currlist_arr[$i]['val'] = 0;
                //横轴
                $stat_arr['xAxis']['categories'][] = "$i";
            }

            $today_day = @date('d', $searchtime_arr[1]); //今天日期
            $yesterday_day = @date('d', $searchtime_arr[0]); //昨天日期

            $where['order_add_time'] = array('between', $searchtime_arr);
            $field .= ' ,DAY(FROM_UNIXTIME(order_add_time)) as dayval,HOUR(FROM_UNIXTIME(order_add_time)) as hourval ';
            $orderlist = $stat_model->statByStatorder($where, $field, 0, 0, '', 'DAY(FROM_UNIXTIME(order_add_time)),HOUR(FROM_UNIXTIME(order_add_time))');

            foreach ((array) $orderlist as $k => $v) {
                if ($today_day == $v['dayval']) {
                    $curr_arr[$v['hourval']] = intval($v[$stattype]);
                    $currlist_arr[$v['hourval']]['val'] = $v[$stattype];
                }
                if ($yesterday_day == $v['dayval']) {
                    $up_arr[$v['hourval']] = intval($v[$stattype]);
                    $uplist_arr[$v['hourval']]['val'] = $v[$stattype];
                }
            }
            $stat_arr['series'][0]['name'] = '昨天';
            $stat_arr['series'][0]['data'] = array_values($up_arr);
            $stat_arr['series'][1]['name'] = '今天';
            $stat_arr['series'][1]['data'] = array_values($curr_arr);
        }

        if ($this->search_arr['search_type'] == 'week') {
            $up_week = @date('W', $searchtime_arr[0]); //上周
            $curr_week = @date('W', $searchtime_arr[1]); //本周
            //构造横轴数据
            for ($i = 1; $i <= 7; $i++) {
                //统计图数据
                $up_arr[$i] = 0;
                $curr_arr[$i] = 0;
                $tmp_weekarr = getSystemWeekArr();
                //统计表数据
                $uplist_arr[$i]['timetext'] = $tmp_weekarr[$i];
                $currlist_arr[$i]['timetext'] = $tmp_weekarr[$i];
                $uplist_arr[$i]['val'] = 0;
                $currlist_arr[$i]['val'] = 0;
                //横轴
                $stat_arr['xAxis']['categories'][] = $tmp_weekarr[$i];
                unset($tmp_weekarr);
            }
            $where['order_add_time'] = array('between', $searchtime_arr);
            $field .= ',WEEKOFYEAR(FROM_UNIXTIME(order_add_time)) as weekval,WEEKDAY(FROM_UNIXTIME(order_add_time))+1 as dayofweekval ';
            $orderlist = $stat_model->statByStatorder($where, $field, 0, 0, '', 'weekval,dayofweekval');
            foreach ((array) $orderlist as $k => $v) {
                if ($up_week == $v['weekval']) {
                    $up_arr[$v['dayofweekval']] = intval($v[$stattype]);
                    $uplist_arr[$v['dayofweekval']]['val'] = intval($v[$stattype]);
                }
                if ($curr_week == $v['weekval']) {
                    $curr_arr[$v['dayofweekval']] = intval($v[$stattype]);
                    $currlist_arr[$v['dayofweekval']]['val'] = intval($v[$stattype]);
                }
            }
            $stat_arr['series'][0]['name'] = '上周';
            $stat_arr['series'][0]['data'] = array_values($up_arr);
            $stat_arr['series'][1]['name'] = '本周';
            $stat_arr['series'][1]['data'] = array_values($curr_arr);
        }

        if ($this->search_arr['search_type'] == 'month') {
            $up_month = date('m', $searchtime_arr[0]);
            $curr_month = date('m', $searchtime_arr[1]);
            //计算横轴的最大量（由于每个月的天数不同）
            $up_dayofmonth = date('t', $searchtime_arr[0]);
            $curr_dayofmonth = date('t', $searchtime_arr[1]);
            $x_max = $up_dayofmonth > $curr_dayofmonth ? $up_dayofmonth : $curr_dayofmonth;

            //构造横轴数据
            for ($i = 1; $i <= $x_max; $i++) {
                //统计图数据
                $up_arr[$i] = 0;
                $curr_arr[$i] = 0;
                //统计表数据
                $currlist_arr[$i]['timetext'] = $i;
                $uplist_arr[$i]['val'] = 0;
                $currlist_arr[$i]['val'] = 0;
                //横轴
                $stat_arr['xAxis']['categories'][] = $i;
            }
            $where['order_add_time'] = array('between', array($searchtime_arr[0], $searchtime_arr[1]));
            $field .= ',MONTH(FROM_UNIXTIME(order_add_time)) as monthval,day(FROM_UNIXTIME(order_add_time)) as dayval ';
            $orderlist = $stat_model->statByStatorder($where, $field, 0, 0, '', 'monthval,dayval');
            foreach ($orderlist as $k => $v) {
                if ($up_month == $v['monthval']) {
                    $up_arr[$v['dayval']] = intval($v[$stattype]);
                    $uplist_arr[$v['dayval']]['val'] = intval($v[$stattype]);
                }
                if ($curr_month == $v['monthval']) {
                    $curr_arr[$v['dayval']] = intval($v[$stattype]);
                    $currlist_arr[$v['dayval']]['val'] = intval($v[$stattype]);
                }
            }
            $stat_arr['series'][0]['name'] = '上月';
            $stat_arr['series'][0]['data'] = array_values($up_arr);
            $stat_arr['series'][1]['name'] = '本月';
            $stat_arr['series'][1]['data'] = array_values($curr_arr);
        }
        $stat_json = getStatData_LineLabels($stat_arr);
        $this->assign('stat_json', $stat_json);
        $this->assign('stattype', $stattype);
        echo $this->fetch('stat_linelabels');exit;
    }

    protected function getAdminItemList() {
        $menu_array = array(
            array('name' => 'income', 'text' => lang('stat_sale_income'), 'url' => url('Stattrade/income')),
            array('name' => 'predeposit', 'text' => lang('stat_predeposit'), 'url' => url('Stattrade/predeposit')),
            array('name' => 'sale', 'text' => lang('stat_sale'), 'url' => url('Stattrade/sale')),
        );
        return $menu_array;
    }

}