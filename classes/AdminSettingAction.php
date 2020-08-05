<?php


namespace Ecjia\App\Setting;


use RC_Storage;
use RC_Upload;

class AdminSettingAction
{

    /**
     * 获取语言列表
     * @return array
     */
    public static function getSelectLangs()
    {
        return array(
            'zh_CN'
        );
    }

    /**
     * 是否覆盖文件
     * @param string $code
     * @return boolean
     */
    public static function isReplaceFile($code)
    {
        //定义需要替换的文件
        $files = array(
            'shop_logo',
            'watermark',
            'wap_logo',
            'no_picture',
            'wap_app_download_img'
        );

        return in_array($code, $files);
    }

    /**
     * 删除需要覆盖的文件
     *
     * @param string $code
     * @param string $value
     */
    public static function replaceFile($code, $value)
    {
        //删除原有文件
        if (self::isReplaceFile($code)) {
            $disk = RC_Storage::disk();
            if ($disk->exists(RC_Upload::upload_path() . $value)) {
                $disk->delete(RC_Upload::upload_path() . $value);
            }
        }
    }

}