<?php


namespace Ecjia\App\Setting\Controllers;


use admin_nav_here;
use ecjia;
use ecjia_admin;
use ecjia_cloud;
use ecjia_config;
use ecjia_screen;
use RC_App;
use RC_DB;
use RC_Script;
use RC_Style;
use RC_Time;
use RC_Uri;

class AdminRegionController extends AdminBase
{
    public function __construct()
    {
        parent::__construct();

        /* 加载全局 js/css */
        RC_Script::enqueue_script('jquery-validate');
        RC_Script::enqueue_script('jquery-form');
        RC_Script::enqueue_script('smoke');

        RC_Style::enqueue_style('chosen');
        RC_Style::enqueue_style('uniform-aristo');
        RC_Script::enqueue_script('jquery-uniform');
        RC_Script::enqueue_script('jquery-chosen');
        RC_Script::enqueue_script('admin_region_manage', RC_App::apps_url('statics/js/admin_region_manage.js', $this->__FILE__), array(), false, 1);
        RC_Script::localize_script('admin_region_manage', 'js_lang_admin_region_manage', config('app-setting::jslang.admin_region_manage'));
        RC_Script::enqueue_script('setting', RC_App::apps_url('statics/js/setting.js', $this->__FILE__), array(), false, 1);
        RC_Script::localize_script('setting', 'js_lang_setting', config('app-setting::jslang.admin_region_page'));
    }


    /**
     * 列出某地区下的所有地区列表
     */
    public function init()
    {
        $this->admin_priv('region_manage');

        $this->assign('ur_here', __('地区列表', 'setting'));

        ecjia_screen::get_current_screen()->add_help_tab(array(
            'id'      => 'overview',
            'title'   => __('概述', 'setting'),
            'content' => '<p>' . __('欢迎访问ECJia智能后台地区设置页面，用户可以在此进行设置地区。', 'setting') . '</p>'
        ));

        ecjia_screen::get_current_screen()->set_help_sidebar(
            '<p><strong>' . __('更多信息：', 'setting') . '</strong></p>' .
            '<p>' . __('<a href="https://ecjia.com/wiki/帮助:ECJia智能后台:系统设置#.E5.9C.B0.E5.8C.BA.E5.88.97.E8.A1.A8" target="_blank">关于地区设置帮助文档</a>', 'setting') . '</p>'
        );

        $id = isset($_GET['id']) ? trim($_GET['id']) : 'CN';

        $region_arr = RC_DB::connection(config('cashier.database_connection', 'default'))
                            ->table('regions')
                            ->where('parent_id', $id)
                            ->get()->toArray();

        if ($id != 'CN') {
            $p_info = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->where('region_id', $id)->first();
        }

        if ($id == 'CN') {
            $region_type = 1;
        } else {
            $region_type = $p_info['region_type'] + 1;
        }

        //面包屑显示当前地区
        $re_ids = array();
        if ($region_type == 2) {
            $re_id2 = $id;
            $re_ids = array($re_id2);
        } elseif ($region_type == 3) {
            $re_id2 = substr(trim($_GET['id']), 0, 4);
            $re_id3 = $id;
            $re_ids = array($re_id2, $re_id3);
        } elseif ($region_type == 4) {
            $re_id2 = substr(trim($_GET['id']), 0, 4);
            $re_id3 = substr(trim($_GET['id']), 0, 6);
            $re_id4 = $id;
            $re_ids = array($re_id2, $re_id3, $re_id4);
        } elseif ($region_type == 5) {
            $re_id2 = substr(trim($_GET['id']), 0, 4);
            $re_id3 = substr(trim($_GET['id']), 0, 6);
            $re_id4 = substr(trim($_GET['id']), 0, 8);
            $re_id5 = $id;
            $re_ids = array($re_id2, $re_id3, $re_id4, $re_id5);
        }

        $str_last = '';
        if ($region_type != 1) {
            $current_name = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->whereIn('region_id', $re_ids)->lists('region_name');
            if (count($current_name) >= 2) {
                $end              = array_slice($current_name, -1, 1);
                $current_name_new = array_diff($current_name, $end);
                foreach ($current_name_new as $key => $val) {
                    $str .= '<li>' . $val . '</li>';
                }
                $str_last .= $str . '<li class="last">' . $end['0'] . '</li>';

            } else {
                $end      = array_slice($current_name, -1, 1);
                $str_last .= '<li class="last">' . $end['0'] . '</li>';
            }
            ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(('<li>' . __('地区列表', 'setting') . '</li>' . $str_last)));
        } else {
            ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('地区列表', 'setting')));
        }

        $this->assign('region_arr', $region_arr);
        $this->assign('parent_id', $id);
        $this->assign('region_type', $region_type);

        if ($id != 'CN') {
            $this->assign('action_link', array('href' => RC_Uri::url('setting/admin_region/init', 'id=' . $p_info['parent_id']), 'text' => __('返回上级', 'setting')));
        }

        return $this->display('region_list.dwt');
    }


    /**
     * 添加新的地区
     */
    public function add_area()
    {
        $this->admin_priv('region_manage', ecjia::MSGTYPE_JSON);

        $parent_id        = trim($_POST['parent_id']);
        $region_name      = trim($_POST['region_name']);
        $region_type      = intval($_POST['region_type']);
        $index_letter     = trim($_POST['index_letter']);
        $region_id        = trim($_POST['region_id']);
        $index_letter     = strtoupper($index_letter);
        $region_id_length = strlen($region_id);

        if (($region_type === 4) || ($region_type === 5)) {
            if ($region_id_length != 3) {
                return $this->showmessage(__('当前级地区码只能填3位数字！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
            }
        } else {
            if ($region_id_length != 2) {
                return $this->showmessage(__('当前级地区码只能填2位数字！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
            }
        }

        $region_id = trim($parent_id . $region_id);

        if (empty($region_name)) {
            return $this->showmessage(__('区域名称不能为空！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
        }

        /* 查看地区码是否重复 */
        $is_only = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->where('region_id', $region_id)->where('region_type', $region_type)->count();
        if ($is_only) {
            return $this->showmessage(__('抱歉，当前级已经有相同的地区码存在！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
        } else {
            $data = array(
                'region_id'    => $region_id,
                'parent_id'    => $parent_id,
                'region_name'  => $region_name,
                'region_type'  => $region_type,
                'index_letter' => $index_letter,
                'country'      => 'CN'
            );

            $region_id = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->insert($data);
            if ($region_id) {
                $region_href = RC_Uri::url('setting/admin_region/drop_area', array('id' => $region_id));
                //日志
                ecjia_admin::admin_log($region_name, 'add', 'area');
                //更新地区版本
// 				$region_cn_version = ecjia::config('region_cn_version');
// 				$version = substr(trim($region_cn_version), -6) + 1;
// 				$new_version = sprintf("%06d", $version);
// 				$last_version = substr(trim($region_cn_version), 0, 9).$new_version;
// 				ecjia_config::instance()->write_config('region_cn_version', $last_version);
                if ($parent_id == 'CN') {
                    return $this->showmessage(__('添加新地区成功！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('setting/admin_region/init')));
                } else {
                    return $this->showmessage(__('添加新地区成功！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('setting/admin_region/init', array('id' => $parent_id))));
                }

            } else {
                return $this->showmessage(__('添加新地区失败！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
            }
        }
    }


    /**
     * 编辑区域名称
     */
    public function edit_area_name()
    {
        $this->admin_priv('region_manage', ecjia::MSGTYPE_JSON);

        $region_id    = trim($_POST['region_id']);
        $region_name  = trim($_POST['region_name']);
        $index_letter = trim($_POST['index_letter']);
        $index_letter = strtoupper($index_letter);

        if (empty($region_name)) {
            return $this->showmessage(__('区域名称不能为空！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
        }

        $old       = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->select('region_name', 'parent_id')->where('region_id', $region_id)->first();
        $parent_id = $old['parent_id'];
        $old_name  = $old['region_name'];

        $data      = array(
            'region_name'  => $region_name,
            'index_letter' => $index_letter,
        );
        $region_id = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->where('region_id', $region_id)->update($data);

        if ($region_id) {
            //日志
            ecjia_admin::admin_log(sprintf(__('更新地区名称为 %s', 'setting'), $region_name), 'edit', 'area');

            //更新地区版本
// 			$region_cn_version = ecjia::config('region_cn_version');
// 			$version = substr(trim($region_cn_version), -6) + 1;
// 			$new_version = sprintf("%06d", $version);
// 			$last_version = substr(trim($region_cn_version), 0, 9).$new_version;
// 			ecjia_config::instance()->write_config('region_cn_version', $last_version);

            return $this->showmessage(__('修改名称成功！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('setting/admin_region/init', array('id' => $parent_id))));
        } else {
            return $this->showmessage(__('修改名称失败！', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR);
        }

    }


    /**
     * 删除区域
     */
    public function drop_area()
    {
        $this->admin_priv('region_manage', ecjia::MSGTYPE_JSON);

        $id = trim($_GET['id']);

        $region     = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->where('region_id', $id)->first();
        $regionname = $region['region_name'];

        //含id自己
        $parent_ids = $this->GetIds($id);

        if (!empty($parent_ids) && is_array($parent_ids)) {
            $delete_region = array_merge(array($id), $parent_ids);
        }

        RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->whereIn('region_id', $delete_region)->delete();
        //日志
        ecjia_admin::admin_log(addslashes($regionname), 'remove', 'area');
        //更新地区版本
// 		$region_cn_version = ecjia::config('region_cn_version');
// 		$version = substr(trim($region_cn_version), -6) + 1;
// 		$new_version = sprintf("%06d", $version);
// 		$last_version = substr(trim($region_cn_version), 0, 9).$new_version;
// 		ecjia_config::instance()->write_config('region_cn_version', $last_version);
        return $this->showmessage(sprintf(__('成功删除地区 %s', 'setting'), $regionname), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS);
    }


    /**
     * 子集ids
     * @return array
     */
    public static function GetIds($parent_id)
    {
        if ($parent_id) {
            if (is_array($parent_id)) {
                $data = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->whereIn('parent_id', $parent_id)->lists('region_id');
            } else {
                $data = RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->where('parent_id', $parent_id)->lists('region_id');
            }

            if (!empty($data)) {
                $datas = self::GetIds($data) ? array_merge($data, self::GetIds($data)) : $data;
            } else {
                $datas = array('0' => $parent_id);
            }
        }
        return $datas;
    }


    /**
     * 获取地区信息
     */
    public function get_regioninfo()
    {
        $this->admin_priv('region_manage', ecjia::MSGTYPE_JSON);
        //本地当前版本和检测时间
        $region_cn_version     = ecjia::config('region_cn_version');
        $region_last_checktime = ecjia::config('region_last_checktime');
        $time                  = \RC_Time::gmtime();
        $time_last_format      = RC_Time::local_date(ecjia::config('time_format'), $region_last_checktime);

        $page   = !empty($_GET['page']) ? intval($_GET['page']) + 1 : 1;
        $params = array(
            'pagination'        => array('page' => $page, 'count' => 1500),
            'region_cn_version' => $region_cn_version,
        );

        //获取ecjia_cloud对象
        $cloud = ecjia_cloud::instance()->api('region/synchrony')->data($params)->run();

        //判断是否有错误返回
        if (is_ecjia_error($cloud->getError())) {
            return $this->showmessage($cloud->getError()->get_error_message(), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('setting/admin_region/init')));
        }

        //获取每页可更新数
        $data = $cloud->getReturnData();

        //获取分页信息
        $pageinfo = $cloud->getPaginated();

        if (!empty($data['last_regions'])) {
            $region_cn_version_new = $data['region_cn_version'];
            /*检测是否有数据*/
            if (count($data['last_regions']) > 0) {
                //同步检测时间间隔不能小于7天
                if ($time - $region_last_checktime < 7 * 24 * 60 * 60) {
                    //更新检测时间
                    ecjia_config::instance()->write_config('region_last_checktime', $time);
                    return $this->showmessage(sprintf(__('当前版本已是最新版本，同步更新时间间隔不能小于7天，上次更新时间是（%s）', 'setting'), $time_last_format), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_ERROR, array('pjaxurl' => RC_Uri::url('setting/admin_region/init')));
                }

                if ($pageinfo['more'] == 1) {
                    $count = count($data['last_regions']) * $page;
                } else {
                    $count = count($data['last_regions']) * ($page - 1) + count($data['last_regions']);
                }
                $update_data = $data['last_regions'];

                //首次先清空本地地区表
                $first_page = intval($_GET['page']);
                if ($first_page == 0) {
                    RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->where('country', 'CN')->delete();
                }

                //批量插入
                RC_DB::connection(config('cashier.database_connection', 'default'))->table('regions')->insert($update_data);
            }
        }

        if ($pageinfo['more'] > 0) {
            return $this->showmessage(sprintf(__('获取地区信息成功', 'setting'), $count), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('url' => RC_Uri::url("setting/admin_region/get_regioninfo"), 'notice' => 1, 'page' => $page, 'more' => $pageinfo['more']));
        } else {
            //更新地区表最后检查日期和本地版本
            ecjia_config::instance()->write_config('region_last_checktime', \RC_Time::gmtime());
            ecjia_config::instance()->write_config('region_cn_version', $region_cn_version_new);
            return $this->showmessage(__('获取地区信息成功', 'setting'), ecjia::MSGTYPE_JSON | ecjia::MSGSTAT_SUCCESS, array('pjaxurl' => RC_Uri::url('setting/admin_region/init')));
        }
    }


}