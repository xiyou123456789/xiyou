<?php

namespace app\home\controller;


use think\Lang;

class Pointcart extends BasePointShop
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH.'home/lang/'.config('default_lang').'/pointcart.lang.php');
        //判断系统是否开启积分和积分兑换功能
        if (config('pointprod_isuse') != 1){
            $this->error(lang('pointcart_unavailable'),HOME_SITE_URL);
        }
        if (session('is_login') != 1){
            $ref_url = request_uri();
            $this->redirect(HOME_SITE_URL.'/Login/login.html?ref_url='.urlencode($ref_url));
        }
    }

    /**
     * 积分礼品购物车首页
     */
    public function index() {
        $cart_goods	= array();
        $pointcart_model = model('pointcart');
        $data = $pointcart_model->getPCartListAndAmount(array('pmember_id'=>session('member_id')));
        $this->assign('pgoods_pointall',$data['data']['cartgoods_pointall']);
        $this->assign('cart_array',$data['data']['cartgoods_list']);
        return $this->fetch($this->template_dir.'pointcart_list');
    }

    /**
     * 购物车添加礼品
     */
    public function add() {
        $pgid	= intval(input('pgid'));
        $quantity	= intval(input('quantity'));
        if($pgid <= 0 || $quantity <= 0) {
            echo json_encode(array('done'=>false,'msg'=>lang('pointcart_cart_addcart_fail'))); die;
        }

        //验证积分礼品是否存在购物车中
        $pointcart_model = model('pointcart');
        $check_cart	= $pointcart_model->getPointcartInfo(array('pgoods_id'=>$pgid,'pmember_id'=>session('member_id')));
        if(!empty($check_cart)) {
            echo json_encode(array('done'=>true)); die;
        }
        //验证是否能兑换
        $data = $pointcart_model->checkExchange($pgid, $quantity, session('member_id'));
        if (!$data['state']){
            switch ($data['error']){
                case 'ParameterError':
                    echo json_encode(array('done'=>false,'msg'=>$data['msg'],'url'=>url('Pointprod/plist'))); die;
                    break;
                default:
                    echo json_encode(array('done'=>false,'msg'=>$data['msg'])); die;
                    break;
            }
        }
        $prod_info = $data['data']['prod_info'];

        $insert_arr	= array();
        $insert_arr['pmember_id']		= session('member_id');
        $insert_arr['pgoods_id']		= $prod_info['pgoods_id'];
        $insert_arr['pgoods_name']		= $prod_info['pgoods_name'];
        $insert_arr['pgoods_points']	= $prod_info['pgoods_points'];
        $insert_arr['pgoods_choosenum']	= $prod_info['quantity'];
        $insert_arr['pgoods_image']		= $prod_info['pgoods_image_old'];
        $cart_state = $pointcart_model->addPointcart($insert_arr);
        echo json_encode(array('done'=>true));
        die;
    }

    /**
     * 积分礼品购物车更新礼品数量
     */
    public function update() {
        $pcart_id	= intval(input('get.pc_id'));
        $quantity	= intval(input('get.quantity'));
        //兑换失败提示
        $msg = lang('pointcart_cart_modcart_fail');

        if($pcart_id <= 0 || $quantity <= 0) {
            echo json_encode(array('msg'=>$msg));
            die;
        }
        //验证礼品购物车信息是否存在
        $pointcart_model	= model('pointcart');
        $cart_info	= $pointcart_model->getPointcartInfo(array('pcart_id'=>$pcart_id,'pmember_id'=>session('member_id')));
        if (!$cart_info){
            echo json_encode(array('msg'=>$msg)); die;
        }

        //验证是否能兑换
        $data = $pointcart_model->checkExchange($cart_info['pgoods_id'], $quantity, session('member_id'));
        if (!$data['state']){
            echo json_encode(array('msg'=>$data['msg'])); die;
        }
        $prod_info = $data['data']['prod_info'];
        $quantity = $prod_info['quantity'];

        $cart_state = true;
        //如果数量发生变化则更新礼品购物车内单个礼品数量
        if ($cart_info['pgoods_choosenum'] != $quantity){
            $cart_state = $pointcart_model->editPointcart(array('pcart_id'=>$pcart_id,'pmember_id'=>session('member_id')),array('pgoods_choosenum'=>$quantity));
        }
        if ($cart_state) {
            //计算总金额
            $amount= $pointcart_model->getPointcartAmount(session('member_id'));
            echo json_encode(array('done'=>'true','subtotal'=>$prod_info['pointsamount'],'amount'=>$amount,'quantity'=>$quantity));
            die;
        }
    }

    /**
     * 积分礼品购物车删除单个礼品
     */
    public function drop() {
        $pcart_id	= intval(input('get.pc_id'));
        if($pcart_id <= 0) {
            echo json_encode(array('done'=>false,'msg'=> lang('ds_common_del_fail'))); die;
        }
        $pointcart_model = model('pointcart');
        $drop_state	= $pointcart_model->delPointcartById($pcart_id,session('member_id'));
        if ($drop_state){
            echo json_encode(array('done'=>true)); die;
        } else {
            echo json_encode(array('done'=>false,'msg'=>lang('ds_common_del_fail'))); die;
        }
    }

    /**
     * 兑换订单流程第一步
     */
    public function step1(){
        //获取符合条件的兑换礼品和总积分
        $data = model('pointcart')->getCartGoodsList(session('member_id'));
        if (!$data['state']){
            ds_show_dialog($data['msg'],url('Pointprod/index'),'error');
        }
        $this->assign('pointprod_arr',$data['data']);

        //实例化收货地址模型（不显示自提点地址）
        $address_list = model('address')->getAddressList(array('member_id'=>session('member_id'),'dlyp_id'=>0), 'address_is_default desc,address_id desc');
        $this->assign('address_list',$address_list);

        return $this->fetch($this->template_dir.'pointcart_step1');
    }
    /**
     * 兑换订单流程第二步
     */
    public function step2() {
        $pointcart_model = model('pointcart');
        //获取符合条件的兑换礼品和总积分
        $data = $pointcart_model->getCartGoodsList(session('member_id'));
        if (!$data['state']){
            ds_show_dialog($data['msg'],url('Pointcart/index'),'error');
        }
        $pointprod_arr = $data['data'];
        unset($data);

        //验证积分数是否足够
        $data = $pointcart_model->checkPointEnough($pointprod_arr['pgoods_pointall'], session('member_id'));
        if (!$data['state']){
            ds_show_dialog($data['msg'],url('Pointcart/index'),'error');
        }
        unset($data);

        //创建兑换订单
        $data = model('pointorder')->createOrder($_POST, $pointprod_arr, array('member_id'=>session('member_id'),'member_name'=>session('member_name'),'member_email'=>session('member_email')));
        if (!$data['state']){
            ds_show_dialog($data['msg'],url('Pointcart/step1'),'error');
        }
        $order_id = $data['data']['order_id'];
        $this->redirect('pointcart/step3',['order_id'=>$order_id]);
    }
    /**
     * 流程第三步
     */
    public function step3($order_arr=array()) {
        $order_id = intval(input('order_id'));
        if ($order_id <= 0){
            ds_show_dialog(lang('pointcart_record_error'),url('Index/index'),'error');
        }
        $where = array();
        $where['point_orderid'] = $order_id;
        $where['point_buyerid'] = session('member_id');
        $order_info = model('pointorder')->getPointorderInfo($where);
        if (!$order_info){
            ds_show_dialog(lang('pointcart_record_error'),url('Index/index'),'error');
        }
        $this->assign('order_info',$order_info);
        return $this->fetch($this->template_dir.'pointcart_step2');
    }
}