<?php


namespace Ecjia\App\Setting;


use admin_menu;
use Ecjia\Component\Config\Component\ComponentFactory;
use Ecjia\Component\Config\Component\ComponentNamespace;
use ecjia_admin;
use RC_Hook;
use RC_Object;
use RC_Uri;

class ShopConfigMenu extends RC_Object
{

    protected $menus;

    public function __construct()
    {
        $this->menus = $this->loadGroups();
    }

    /**
     * 获取菜单，并鉴权和排序
     * @return array
     */
    public function getMenus()
    {

        $menus = collect($this->menus)->map(function($admin_menu, $key) {

            /**
             * @var admin_menu $admin_menu
             */

            if ($this->checkAdminMenuPrivilege($admin_menu)) {

                if ($admin_menu->has_submenus) {

                    if ($admin_menu->has_submenus()) {
                        collect($admin_menu->submenus())->map(function($sub_menu) use ($admin_menu) {

                            if ($this->checkAdminMenuPrivilege($sub_menu)) {
                                return $sub_menu;
                            }
                            else {
                                $admin_menu->remove_submenu($sub_menu);
                            }

                            return $sub_menu;
                        });

                        return $admin_menu;
                    }
                    else {
                        return null;
                    }

                }
                else {
                    return $admin_menu;
                }

            }
            else {
                return null;
            }

        })->filter()->sort(array('ecjia_utility', 'admin_menu_by_sort'));

        return $menus;
    }

    /**
     * 加载菜单
     * @return array|mixed
     */
    protected function loadGroups()
    {
        $components = (new ComponentFactory(new ComponentNamespace()))->getComponents();

        $menus = collect($components)->map(function ($item) {

            if (! $item->isDisplayed()) {
                return null;
            }

            return ecjia_admin::make_admin_menu(
                $item->getCode(),
                $item->getName(),
                RC_Uri::url('setting/shop_config/init', array('code' => $item->getCode())),
                $item->getSort()
                )
                ->add_purview('shop_config');

        })->filter()->values();

        $menus = RC_Hook::apply_filters('append_admin_setting_group', $menus);

        return $menus;
    }

    /**
     * 检查管理员菜单权限
     */
    protected function checkAdminMenuPrivilege(admin_menu $admin_menu)
    {
        if ($admin_menu->has_purview()) {
            if (is_array($admin_menu->purview())) {
                $boole = false;
                foreach ($admin_menu->purview() as $action) {
                    $boole = $boole || $this->checkAdminSinglePrivilege($action);
                }

                return $boole;
            } else {
                if ($this->checkAdminSinglePrivilege($admin_menu->purview())) {
                    return true;
                }

                return false;
            }
        }
        return true;
    }

    /**
     * 判断管理员对某一个操作是否有权限。
     *
     * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
     * @param  string|array   $priv_str    操作对应的priv_str
     * @return bool
     */
    protected function checkAdminSinglePrivilege($priv_str)
    {
        if ($_SESSION['action_list'] == 'all') {
            return true;
        }

        if (strpos(',' . $_SESSION['action_list'] . ',', ',' . $priv_str . ',') === false) {
            return false;
        } else {
            return true;
        }
    }

}