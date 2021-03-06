<?php

/**
 * 地区设置
 */

namespace app\admin\controller;

use think\View;
use think\Url;
use think\Lang;
use think\Request;
use think\Db;
use think\Validate;

class Region extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/region.lang.php');
        $this->_Region_mod = model('Area');
        define('MAX_LAYER', 4);
    }

    public function index() {
        $region_list = $this->_Region_mod->get_list(0);
        /* 先根排序 */
        foreach ($region_list as $key => $val) {
            $region_list[$key]['switchs'] = 0;
            if ($this->_Region_mod->get_list($val['area_id'])) {
                $region_list[$key]['switchs'] = 1;
            }
        }
        $this->assign('region_list', $region_list);
        $this->setAdminCurItem('index');
        return $this->fetch();
    }

    function ajax_cate() {
        $cate_id = input('param.id');
        if (empty($cate_id)) {
            return;
        }

        $cate = $this->_Region_mod->get_list($cate_id);
        foreach ($cate as $key => $val) {
            $child = $this->_Region_mod->get_list($val['area_id']);
//            $lay = $this->_Region_mod->get_layer($val['cate_id']);
//            if ($lay >= MAX_LAYER) {
//                $cate[$key]['add_child'] = 0;
//            } else {
//                $cate[$key]['add_child'] = 1;
//            }
            if (!$child || empty($child)) {
                $cate[$key]['switchs'] = 0;
            } else {
                $cate[$key]['switchs'] = 1;
            }
        }
        echo json_encode(array_values($cate));
        return;
    }

    /**
     * ajax操作
     */
    public function ajax() {
        switch (input('param.branch')) {
            /**
             * 更新地区
             */
            case 'area_name':
                $area_model = model('area');
                $where = array('area_id' => intval(input('get.id')));
                $update_array = array();
                $update_array['area_name'] = trim(input('get.value'));
                $area_model->editArea($update_array, $where);
                echo 'true';
                exit;

                break;
            /**
             * 地区 排序 显示 设置
             */
            case 'area_sort':
                $area_model = model('area');
                $where = array('area_id' => intval(input('get.id')));
                $update_array = array();
                $update_array['area_sort'] = trim(input('get.value'));
                $area_model->editArea($update_array, $where);

                \areacache::deleteCacheFile();
                \areacache::updateAreaPhp();
                \areacache::updateAreaArrayJs();
                echo 'true';
                exit;

            case 'area_region':
                $area_model = model('area');
                $where = array('area_id' => intval(input('get.id')));
                $update_array = array();
                $update_array['area_region'] = trim(input('get.value'));
                $area_model->editArea($update_array, $where);
                
                \areacache::deleteCacheFile();
                \areacache::updateAreaArrayJs();
                \areacache::updateAreaPhp();
                echo 'true';
                exit;

            case 'area_index_show':
                $area_model = model('area');
                $where = array('area_id' => intval(input('get.id')));
                $update_array = array();
                $update_array[input('get.column')] = input('get.value');
                $area_model->editArea($update_array, $where);

                \areacache::deleteCacheFile();
                \areacache::updateAreaArrayJs();
                \areacache::updateAreaPhp();
                echo 'true';
                exit;
                break;
            /**
             * 添加、修改操作中 检测类别名称是否有重复
             */
            case 'check_class_name':
                $area_model = model('area');
                $condition['area_name'] = trim(input('param.area_name'));
                $condition['area_parent_id'] = intval(input('param.area_parent_id'));
                $condition['area_id'] = array('neq', intval(input('param.area_id')));
                $class_list = $area_model->getAreaList($condition);
                if (empty($class_list)) {
                    echo 'true';
                    exit;
                } else {
                    echo 'false';
                    exit;
                }
                break;
        }
    }

    public function add() {
        if (!request()->isPost()) {
            $area = array(
                'area_name' => '',
                'area_region' => '',
                'area_parent_id' => input('param.area_id'),
                'area_sort' => '0',
            );
            $this->assign('area', $area);
            $this->assign('parents', $this->_get_options());
            $this->setAdminCurItem('add');
            return $this->fetch('form');
        } else {
            $area_mod=model('area');
            $data = array(
                'area_name' => input('post.area_name'),
                'area_region' => input('post.area_region'),
                'area_parent_id' => input('param.area_parentid'),
                'area_sort' => input('post.area_sort'),
            );
            //验证数据  BEGIN
            $rule = [
                ['area_name', 'require', lang('area_name_error')],
                ['area_sort', 'between:0,255', lang('area_sort_error')],
                ['area_region', 'length:0,9', lang('area_region_error')]
            ];
            $validate = new Validate();
            $validate_result = $validate->check($data, $rule);
            if (!$validate_result) {
                $this->error($validate->getError());
            }
            //验证数据  END

            $result = $area_mod->addArea($data);
            if ($result) {
                \areacache::deleteCacheFile();
                \areacache::updateAreaArrayJs();
                \areacache::updateAreaPhp();
                $this->success(lang('ds_common_save_succ'), 'Region/index');
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    public function edit() {
        $area_id = intval(input('param.area_id'));
        if ($area_id<=0) {
            $this->error(lang('param_error'));
        }
        $area_mod=model('area');
        if (!request()->isPost()) {
            $area = $area_mod->getAreaInfo(array('area_id'=>$area_id));
            $this->assign('area', $area);
            $this->assign('parents', $this->_get_options());
            $this->setAdminCurItem('edit');
            return $this->fetch('form');
        } else {
            $data = array(
                'area_name' => input('post.area_name'),
                'area_region' => input('post.area_region'),
                'area_parent_id' => input('param.area_parentid'),
                'area_sort' => input('post.area_sort'),
            );
            //验证数据  BEGIN
            $rule = [
                ['area_name', 'require', lang('area_name_error')],
                ['area_sort', 'between:0,255', lang('area_sort_error')],
                ['area_region', 'length:0,9', lang('area_region_error')]
            ];
            $validate = new Validate();
            $validate_result = $validate->check($data, $rule);
            if (!$validate_result) {
                $this->error($validate->getError());
            }
            //验证数据  END
            $result = $area_mod->editArea($data,array('area_id'=>$area_id));
            if ($result>=0) {
                \areacache::deleteCacheFile();
                \areacache::updateAreaArrayJs();
                \areacache::updateAreaPhp();
                $this->success(lang('ds_common_op_succ'), 'Region/index');
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    public function drop() {
        $area_id = input('param.area_id');
        if (empty($area_id)) {
            $this->error(lang('param_error'));
        }
        //判断此分类下是否有子分类
        $area_mod=model('area');
        $result = $area_mod->getAreaInfo(array('area_parent_id'=>$area_id));
        if ($result) {
            $this->error('请先删除该分类下的子地区');
        }
        $result = $area_mod->delArea(array('area_id'=>$area_id));
        if ($result) {
            $this->success(lang('ds_common_op_succ'), 'Region/index');
        } else {
            $this->error(lang('error'));
        }
    }

    /* 取得可以作为上级的地区分类数据 */

    function _get_options($except = NULL) {
        $area = $this->_Region_mod->get_list();
        if (empty($area)) {
            return;
        }
        $tree = new \mall\Tree();
        $tree->setTree($area, 'area_id', 'area_parent_id', 'area_name');
        return $tree->getOptions(MAX_LAYER - 1, 0, $except);
    }

    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => '管理',
                'url' => url('Region/index')
            ),
        );

        if (request()->action() == 'add' || request()->action() == 'index') {
            $menu_array[] = array(
                'name' => 'add',
                'text' => '新增',
                'url' => url('Region/add')
            );
        }
        if (request()->action() == 'edit') {
            $menu_array[] = array(
                'name' => 'edit',
                'text' => '编辑',
                'url' => 'javascript:void(0)'
            );
        }
        return $menu_array;
    }

}
