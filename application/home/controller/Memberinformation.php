<?php
namespace app\home\controller;

use think\Lang;
class Memberinformation extends BaseMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/memberhome.lang.php');
    }

    /**
     * 我的资料【用户中心】
     *
     * @param
     * @return
     */
    public function index()
    {
        $member_model = model('member');

        if (request()->isPost()) {
            $member_array = array();
            $member_array['member_truename'] = input('post.member_truename');
            $member_array['member_sex'] = input('post.member_sex');
            $member_array['member_qq'] = input('post.member_qq');
            $member_array['member_ww'] = input('post.member_ww');
            $member_array['member_areaid'] = input('post.area_id');
            $member_array['member_cityid'] = input('post.city_id');
            $member_array['member_provinceid'] = input('post.province_id');
            $member_array['member_areainfo'] = input('post.area_info');
            if (strlen(input('post.birthday')) == 10) {
                $member_array['member_birthday'] = strtotime(input('post.birthday'));
            }
            $member_array['member_privacy'] = serialize($_POST['privacy']);
            $update = $member_model->editMember(array('member_id' => session('member_id')), $member_array);

            $message = $update ? lang('ds_common_save_succ') : lang('ds_common_save_fail');
            ds_show_dialog($message, 'reload', $update ? 'succ' : 'error');
        }

        if ($this->member_info['member_privacy'] != '') {
            $this->member_info['member_privacy'] = unserialize($this->member_info['member_privacy']);
        } else {
            $this->member_info['member_privacy'] = array();
        }
        $this->assign('member_info', $this->member_info);
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_information');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('member');
        $this->assign('menu_sign', 'profile');
        $this->assign('menu_sign_url', url('Memberinformation/index'));
        $this->assign('menu_sign1', 'baseinfo');
        return $this->fetch($this->template_dir.'index');
    }

    /**
     * 我的资料【更多个人资料】
     *
     * @param
     * @return
     */
    public function more()
    {
        if (request()->isPost()) {
            db('snsmtagmember')->where(array('member_id' => session('member_id')))->delete();
            if (!empty($_POST['mid'])) {
                $insert_array = array();
                foreach ($_POST['mid'] as $val) {
                    $insert_array[] = array(
                        'mtag_id' => $val,
                        'member_id' => session('member_id')
                    );
                }
                db('snsmtagmember')->insertAll($insert_array, '', true);
            }
            ds_show_dialog(lang('ds_common_op_succ'), '', 'succ');
        }

        // 用户标签列表
        $mtag_array = db('snsmembertag')->order('mtag_sort asc')->limit(1000)->select();

        // 用户已添加标签列表。
        $mtm_array = db('snsmtagmember')->where(array('member_id' => session('member_id')))->select();
        $mtag_list = array();
        $mtm_list = array();
        if (!empty($mtm_array) && is_array($mtm_array)) {
            // 整理
            $elect_array = array();
            foreach ($mtm_array as $val) {
                $elect_array[] = $val['mtag_id'];
            }
            foreach ((array)$mtag_array as $val) {
                if (in_array($val['mtag_id'], $elect_array)) {
                    $mtm_list[] = $val;
                } else {
                    $mtag_list[] = $val;
                }
            }
        } else {
            $mtag_list = $mtag_array;
        }
        $this->assign('mtag_list', $mtag_list);
        $this->assign('mtm_list', $mtm_list);

        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_information');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('more');

        $this->assign('menu_sign', 'profile');
        $this->assign('menu_sign_url', url('Memberinformation/index'));
        $this->assign('menu_sign1', 'baseinfo');
        return $this->fetch($this->template_dir.'more');
    }

    public function upload()
    {
        if (!request()->isPost()) {
            $this->redirect('memberinformation/avatar');
        }
        $member_id = session('member_id');

        //上传图片

        if (!empty($_FILES['pic']['tmp_name'])) {
            $file_object= request()->file('pic');
            $base_url=BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/';
            $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
            $file_name='avatar_'.$member_id.'_new'.".$ext";
            $info = $file_object->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($base_url,$file_name);
            if ($info) {
                $name_dir = BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/' . $info->getFilename();

                $imageinfo = getimagesize($name_dir);
                /* 设置买家当前菜单 */
                $this->setMemberCurMenu('member_information');
                /* 设置买家当前栏目 */
                $this->setMemberCurItem('avatar');
                $this->assign('menu_sign', 'profile');
                $this->assign('menu_sign_url', "{:url('Memberinformation/index')}");
                $this->assign('menu_sign1', 'avatar');
                $file_dir = UPLOAD_SITE_URL . '/' . ATTACH_AVATAR . '/' . $info->getFilename();

                $this->assign('newfile', $file_dir);
                $this->assign('height', $imageinfo[1]);
                $this->assign('width', $imageinfo[0]);
                return $this->fetch($this->template_dir . 'avatar');
            }else{
                $this->error($file_object->getError());
            }
        } else {
            $this->error(lang('upload_failed_replace_pictures'));
        }
        
    }

    /**
     * 裁剪
     *
     */
    public function cut()
    {
        if (request()->isPost()) {
            $x1 = input('post.x1');
            $y1 = input('post.y1');
            $x2 = input('post.x2');
            $y2 = input('post.y2');
            $w = input('post.w');
            $h = input('post.h');
            
            $newfile = str_replace(str_replace('/index.php', '', BASE_SITE_URL).'/uploads', BASE_UPLOAD_PATH, $_POST['newfile']);
            
            $avatarfile = BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/' . "avatar_".session('member_id').".jpg";
            $image_av = \think\Image::open($newfile);
            $image_av->crop($w,$h,$x1,$y1)->save($avatarfile);
            @unlink($newfile);
            model('member')->editMember(array('member_id' => session('member_id')), array('member_avatar' => 'avatar_' . session('member_id') . '.jpg'));
            $avatar_url ='avatar_' . session('member_id') . '.jpg';
            session('avatar',$avatar_url);
            $this->redirect('memberinformation/avatar');
        }
    }

    /**
     * 更换头像
     *
     * @param
     * @return
     */
    public function avatar()
    {
        $member_info = model('member')->getMemberInfoByID(session('member_id'));
        $this->assign('member_avatar', $member_info['member_avatar']);
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_information');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('avatar');

        $this->assign('menu_sign', 'profile');
        $this->assign('menu_sign_url', url('Memberinformation/index'));
        $this->assign('menu_sign1', 'avatar');
        $this->assign('newfile', '');
        return $this->fetch($this->template_dir.'avatar');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    public function getMemberItemList()
    {
                $menu_array = array(
                     array(
                        'name' => 'member',
                        'text' => lang('home_member_base_infomation'),
                        'url' => url('Memberinformation/index')
                    ),
                    array(
                        'name' => 'more',
                        'text' => lang('home_member_more'),
                        'url' => url('Memberinformation/more')
                    ),
                     array(
                        'name' => 'avatar',
                        'text' => lang('home_member_modify_avatar'),
                        'url' => url('Memberinformation/avatar')
                    )
                );

        return $menu_array;
    }
}