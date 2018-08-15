<?php
/**
 * Created by PhpStorm.
 * User: GAX
 * Date: 2018-07-31 14:50
 * 网络部-程序小组
 */

namespace plugins\bslform;
use cmf\lib\Plugin;

class BslformPlugin extends Plugin
{

    public $info = array(
        'name'        => 'Bslform',//Demo插件英文名，改成你的插件英文就行了
        'title'       => 'ThinkCMF5自定义表单系统',
        'description' => 'ThinkCMF5自定义表单系统',
        'status'      => 1,
        'author'      => '网络部-程序小组',
        'version'     => '0.1'
    );

    public $hasAdmin = 1;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    //实现的footer_start钩子方法
    public function footerStart($param)
    {

    }

}