<?php


namespace Ecjia\App\Setting\Subscribers;


use ecjia;
use Ecjia\App\Setting\ShopConfigMenu;
use Ecjia\Component\Region\Country;
use ecjia_admin;
use ecjia_config;
use RC_Package;
use Royalcms\Component\Hook\Dispatcher;

class AdminHookSubscriber
{
    /**
     * Handle events.
     */
    public function onDisplaySettingNavAction($code)
    {
        $menus = ShopConfigMenu::singleton()->getMenus();

        echo '<div class="setting-group">'.PHP_EOL;
        echo '<span class="setting-group-title"><i class="fontello-icon-cog"></i>商店设置</span>'.PHP_EOL;
        echo '<ul class="nav nav-list m_t10">'.PHP_EOL;

        foreach ($menus as $key => $group) {
            if ($group->action == 'divider') {
                echo '<li class="divider"></li>';
            } elseif ($group->action == 'nav-header') {
                echo '<li class="nav-header">' . $group->name . '</li>';
            } else {
                echo '<li><a class="setting-group-item'; //data-pjax

                if ($code == $group->action) {
                    echo ' llv-active';
                }

                echo '" href="' . $group->link . '">' . $group->name . '</a></li>'.PHP_EOL;
            }
        }

        echo '</ul>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
    }


    public function onFormConfigRegionSelectAction($item)
    {
        $countries = with(new Country)->getCountries();

        ecjia_admin::$controller->assign('countries', $countries);
        ecjia_admin::$controller->assign('var', $item);
        $shop_country = ecjia::config('shop_country');
        if (!empty($shop_country)) {
            ecjia_admin::$controller->assign('provinces', with(new \Ecjia\App\Setting\Region)->getSubarea(ecjia::config('shop_country')));
            $shop_province = ecjia::config('shop_province');
            if ($shop_province) {
                ecjia_admin::$controller->assign('cities', with(new \Ecjia\App\Setting\Region)->getSubarea(ecjia::config('shop_province')));
            }
        }

        echo ecjia_admin::$controller->fetch(
            RC_Package::package('app::setting')->loadTemplate('admin/library/widget_config_region_select.lbi', true)
        );
    }


    public function onFormConfigLangSelectAction($item)
    {
        /* 可选语言 */
        ecjia_admin::$controller->assign('lang_list', \Ecjia\App\Setting\AdminSettingAction::getSelectLangs());
        ecjia_admin::$controller->assign('var', $item);

        echo ecjia_admin::$controller->fetch(
            RC_Package::package('app::setting')->loadTemplate('admin/library/widget_config_lang_select.lbi', true)
        );
    }


    public function onFormConfigInvoiceTypeAction($item)
    {
        $invoice_type = ecjia::config('invoice_type');
        $invoice_type = unserialize($invoice_type);

        ecjia_admin::$controller->assign('invoice_type', $invoice_type);
        ecjia_admin::$controller->assign('var', $item);

        echo ecjia_admin::$controller->fetch(
            RC_Package::package('app::setting')->loadTemplate('admin/library/widget_config_invoice_type.lbi', true)
        );
    }


    public function onUpdateConfigInvoiceTypeAction($invoice_type, $invoice_rate)
    {
        /* 处理发票类型及税率 */
        if (!empty($invoice_rate)) {
            foreach ($invoice_rate as $key => $rate) {
                $rate = round(floatval($rate), 2);
                if ($rate < 0) {
                    $rate = 0;
                }
                $invoice_rate[$key] = $rate;
            }
            $invoice = array(
                'type' => $invoice_type,
                'rate' => $invoice_rate
            );
            ecjia_config::instance()->write_config('invoice_type', serialize($invoice));
        }
    }


    public function onShopConfigFilterItemsFilter($items, $code)
    {
        $disabled = config('app-setting::settings.disabled');

        foreach ($items as $key => $item) {
            if (in_array($item['code'], $disabled)) {
                unset($items[$key]);
            }
        }

        return $items;
    }


    public function onAddMaintainCommandFilter($factories)
    {
        $factories['setting_shop_config_sequence'] = 'Ecjia\App\Setting\Maintains\SettingShopConfigSequence';
        $factories['setting_shop_config_seeder'] = 'Ecjia\App\Setting\Maintains\SettingShopConfigSeeder';

        return $factories;
    }


    /**
     * Register the listeners for the subscriber.
     *
     * @param \Royalcms\Component\Hook\Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events)
    {

        $events->addAction(
            'admin_shop_config_nav',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onDisplaySettingNavAction'
        );


        $events->addAction(
            'config_form_shop_country',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onFormConfigRegionSelectAction'
        );
        $events->addAction(
            'config_form_shop_province',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onFormConfigRegionSelectAction'
        );
        $events->addAction(
            'config_form_shop_city',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onFormConfigRegionSelectAction'
        );
        $events->addAction(
            'config_form_lang',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onFormConfigLangSelectAction'
        );
        $events->addAction(
            'config_form_invoice_type',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onFormConfigInvoiceTypeAction'
        );
        $events->addAction(
            'update_config_invoice_type',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onUpdateConfigInvoiceTypeAction',
            10,
            2
        );

        $events->addFilter(
            'shop_config_filter_items',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onShopConfigFilterItemsFilter',
            10,
            2
        );
        $events->addFilter(
            'ecjia_maintain_command_filter',
            'Ecjia\App\Setting\Subscribers\AdminHookSubscriber@onAddMaintainCommandFilter'
        );

    }

}