<?php
namespace app\home\controller;
use app\mobile\controller\WechatApi;
use think\Lang;

class Memberinviter extends BaseMember {

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/memberinviter.lang.php');
        if(!config('inviter_open')){
            $this->error(lang('inviter_not_open'));
        }
    }

    public function add(){
        //判断是否已是分销员
        $inviter_model=model('inviter');
        $inviter_info=$inviter_model->getInviterInfo('i.inviter_id='.session('member_id'));
        
        if($inviter_info && $inviter_info['inviter_state']==0){
            $this->error(lang('inviter_view'));
        }
        if($inviter_info && $inviter_info['inviter_state']==2){
            $this->error(lang('inviter_close'));
        }
        if($inviter_info && $inviter_info['inviter_state']==1){
            $this->redirect('Home/Memberinviter/index');
        }
        //是否有分销门槛
        if(config('inviter_condition')){
            //检查消费金额
            $order_amount=db('order')->where('buyer_id='.session('member_id').' AND order_state='.ORDER_STATE_SUCCESS.' AND lock_state=0')->sum('order_amount-refund_amount');
            if($order_amount<config('inviter_condition_amount')){
                $this->error(sprintf(lang('inviter_condition_amount'),$order_amount,config('inviter_condition_amount')));
            }
            
        }
        $inviter_model->addInviter(array(
            'inviter_id'=>session('member_id'),
            'inviter_state'=>config('inviter_view')?0:1,
            'inviter_applytime'=>TIMESTAMP,
        ));
        if(config('inviter_view')){
            $this->redirect('Home/Memberinviter/add');
        }else{
            $this->redirect('Home/Memberinviter/index');
        }
        
    }
    /**
     * 推广海报【会员推广】
     *
     * @param
     * @return
     */
    public function index() {
        $member_info = $this->member_info;
        $this->assign('inviter_url',HOME_SITE_URL.'/Login/register.html?inviter_id=' . $member_info['member_id']);
        if(!file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_weixin.png')){
            $config = model('wechat')->getOneWxconfig();
            $wechat=new WechatApi($config);
            $expire_time = $config['expires_in'];
            if($expire_time > time()){
                //有效期内
                $wechat->access_token_= $config['access_token'];
            }else{
                $access_token=$wechat->checkAuth();
                $web_expires = time() + 7000; // 提前200秒过期
                db('wxconfig')->where(array('id'=>$config['id']))->update(array('access_token'=>$access_token,'expires_in'=>$web_expires));
            }
            $return=$wechat->getQRCode($member_info['member_id'], 1);
            if($return){
                $refer_qrcode_weixin=$wechat->getQRUrl($return['ticket']);
                copy($refer_qrcode_weixin,BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_weixin.png');
            }else{
                $refer_qrcode_weixin = '';
                $this->assign('wx_error_msg',$wechat->errMsg);
            }
        }else{
            $refer_qrcode_weixin=UPLOAD_SITE_URL. '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_weixin.png';
        }

        $this->assign('refer_qrcode_weixin',$refer_qrcode_weixin);
        //二维码
        $qrcode_path = BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '.png';
        $refer_qrcode_logo = BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_poster.png';
        if (!file_exists($qrcode_path)) {
            import('qrcode.phpqrcode', EXTEND_PATH);
            \QRcode::png(WAP_SITE_URL . '/member/register.html?inviter_id=' . $member_info['member_id'], $qrcode_path);
        }
        $qrcode = imagecreatefromstring(file_get_contents($qrcode_path));
        //背景图片
        $inviter_back = db('config')->where('code', 'inviter_back')->value('value');
        $inviter_back = imagecreatefromstring(file_get_contents(BASE_UPLOAD_PATH . DS . ATTACH_COMMON . DS . $inviter_back));


        $QR_width = imagesx($qrcode);
        $QR_height = imagesy($qrcode);
        imagecopyresampled($inviter_back, $qrcode, 65, 170, 0, 0, 190, 190, $QR_width, $QR_height);
        
        $portrait = imagecreatefromstring(file_get_contents(str_replace(UPLOAD_SITE_URL, BASE_UPLOAD_PATH, get_member_avatar($member_info['member_avatar']))));

        $QR_width2 = imagesx($portrait);
        $QR_height2 = imagesy($portrait);
        imagecopyresampled($inviter_back, $portrait, 20, 20, 0, 0, 80, 80, $QR_width2, $QR_height2);

        //此处是给图片载入文字
        $text = '我是'.$member_info['member_name'];
        $textcolor = imagecolorallocate($inviter_back, 255, 50, 37);
        imagefttext($inviter_back, 16, 0, 120, 50, $textcolor, PUBLIC_PATH . '/font/msyh.ttf', mb_convert_encoding($text, "html-entities", "utf-8"));


        imagepng($inviter_back, $refer_qrcode_logo);

        $this->assign('refer_qrcode_logo',UPLOAD_SITE_URL. '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_poster.png');
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('inviter_poster');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('poster');
        return $this->fetch($this->template_dir . 'index');
    }
    public function user(){
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('inviter_user');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('user');
        $member_model = model('member');
        $conditions=array('inviter_id'=>$this->member_info['member_id']);
        if(input('param.member_name')){
            $conditions['member_name']=array('like','%'.input('param.member_name').'%');
        }
        $member_list=$member_model->getMemberList($conditions, 'member_id,member_name,member_avatar,member_addtime,member_logintime', 10, 'member_id desc');
        if(is_array($member_list)){
            foreach($member_list as $key => $val){
                $member_list[$key]['member_addtime'] = $val['member_addtime'] ? date('Y-m-d H:i:s', $val['member_addtime']) : '';
                $member_list[$key]['member_logintime'] = $val['member_logintime'] ? date('Y-m-d H:i:s', $val['member_logintime']) : '';
                //该会员的2级内推荐会员
                $member_list[$key]['inviters']=array();
                $inviter_1=db('member')->where('inviter_id',$val['member_id'])->field('member_id,member_name')->find();
                if($inviter_1){
                    $member_list[$key]['inviters'][]=$inviter_1['member_name'];
                    $inviter_2=db('member')->where('inviter_id',$inviter_1['member_id'])->field('member_id,member_name')->find();
                    if($inviter_2){
                        $member_list[$key]['inviters'][]=$inviter_2['member_name'];
                    }
                }
                
            }
        }
        $this->assign('member_list', $member_list);
        $this->assign('show_page', $member_model->page_info->render());
        return $this->fetch($this->template_dir . 'user');
    }
    
    public function order(){
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('inviter_order');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('order');

        $conditions=array('orderinviter_member_id'=> $this->member_info['member_id']);
        if(input('param.orderinviter_order_sn')){
            $conditions['orderinviter_order_sn']=array('like','%'.input('param.orderinviter_order_sn').'%');
        }
        $orderinviter_list = db('orderinviter')->where($conditions)->order('orderinviter_id desc')->paginate(10);
        $page = $orderinviter_list->render();
        $this->assign('show_page', $page);
        $this->assign('orderinviter_list', $orderinviter_list);
        return $this->fetch($this->template_dir . 'order');
    }
    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    public function getMemberItemList() {
        $menu_array = array(
            array(
                'name' => 'poster',
                'text' => lang('inviter_poster'),
                'url' => url('Memberinviter/index')
            ),
            array(
                'name' => 'user',
                'text' => lang('inviter_user'),
                'url' => url('Memberinviter/user')
            ),
            array(
                'name' => 'order',
                'text' => lang('inviter_order'),
                'url' => url('Memberinviter/order')
            ),
        );

        return $menu_array;
    }

}
