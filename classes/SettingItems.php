<?php


namespace Ecjia\App\Setting;


use Ecjia\Component\Config\Component\ComponentFactory;
use Ecjia\Component\Config\Component\ComponentNamespace;
use RC_DB;
use RC_Hook;
use RC_Object;
use RC_Upload;

class SettingItems extends RC_Object
{

    /**
     * @var \Royalcms\Component\Support\Collection
     */
    protected $config;

    public function __construct()
    {

    }

    public function getItems($group)
    {
        $items = $this->loadItems($group);

        $items = $items->toArray();

        return RC_Hook::apply_filters('shop_config_filter_items', $items, $group);
    }

    protected function loadItems($group)
    {
        $queryItems = $this->queryItemsByDatabase($group);

        $items = $this->getSettingComponentConfigs($group);

        $queryItems = collect($queryItems)->map(function ($item) use ($items) {

            $config = $this->getSettingItemConfig($item['code'], $items);

            $item['name'] = $this->getCfgName($config, $item['code']);
            $item['desc'] = $this->getCfgDesc($config, '');

            if ($item['type']=='file' && !empty($item['value'])) {
                if ($item['code'] == 'icp_file') {
                    $value = explode('/', $item['value']);
                    $item['file_name'] = array_pop($value);
                }
                $item['value'] = RC_Upload::upload_url() .'/'. $item['value'];
            }


            if ($item['code'] == 'sms_shop_mobile') {
                $item['url'] = 1;
            }

            if ($item['store_range']) {
                $item['store_options'] = explode(',', $item['store_range']);

                foreach ($item['store_options'] as $k => $v) {
                    $item['display_options'][$k] = $this->getCfgRange($config, $v, $v);
                }
            }

            return $item;
        });

        return $queryItems;
    }

    protected function queryItemsByDatabase($group)
    {
        $parent_id = $this->getParentId($group);

        $item_list = RC_DB::table('shop_config')
            ->where('parent_id', $parent_id)
            ->where('type', '<>', 'hidden')
            ->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        return $item_list;
    }

    protected function getParentId($code)
    {
        $id = RC_DB::table('shop_config')->where('parent_id', 0)->where('type', 'group')->where('code', $code)->value('id');
        return $id;
    }

    /**
     * @param array $config
     * @param null $default
     * @return mixed
     */
    public function getCfgName($config, $default = null)
    {
        return array_get($config, 'cfg_name', $default);
    }

    /**
     * @param array $config
     * @param null $default
     * @return mixed
     */
    public function getCfgDesc($config, $default = null)
    {
        return array_get($config, 'cfg_desc', $default);
    }

    /**
     * @param array $config
     * @param string $subkey
     * @param null $default
     * @return mixed
     */
    public function getCfgRange($config, $subkey, $default = null)
    {
        return array_get($config, 'cfg_range'.'.'.$subkey, $default);
    }

    /**
     * @param $gorup
     * @return mixed|\Royalcms\Component\Support\Collection
     */
    public function getSettingComponentGroup($gorup)
    {
        try {
            $handler = (new ComponentFactory(new ComponentNamespace()))->component($gorup);

            return $handler;
        }
        catch (\InvalidArgumentException $e) {
            ecjia_log_error($e->getMessage(), $e);
            return null;
        }
    }

    public function getSettingItemConfig($code, $configs)
    {
        $config = $configs->where('cfg_code', $code)->first();

        return $config;
    }

    /**
     * @param $gorup
     * @return \Royalcms\Component\Support\Collection
     */
    public function getSettingComponentConfigs($gorup)
    {
        try {
            $handler = (new ComponentFactory(new ComponentNamespace()))->component($gorup);
            $configs = $handler->handle();

            return collect($configs);
        }
        catch (\InvalidArgumentException $e) {
            ecjia_log_error($e->getMessage(), $e);
            return collect();
        }
    }

    public function getSettingRangesByGroup($group)
    {
        $configs = $this->getSettingComponentConfigs($group);

        $rangs = $configs->mapWithKeys(function($item) {
            return [$item['cfg_code'] => $item['cfg_range']];
        })->all();

        return $rangs;
    }

}