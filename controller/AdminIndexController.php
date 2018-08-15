<?php
/**
 * Created by PhpStorm.
 * User: GAX
 * Date: 2018-07-31 15:02
 * 网络部-程序小组
 */
namespace plugins\bslform\controller;
use cmf\controller\PluginAdminBaseController;
use think\Db;
use think\Session;
use think\Request;

class AdminIndexController extends PluginAdminBaseController
{


    protected function _initialize()
    {
        parent::_initialize();
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        }else {
            $this->error('未登录');
        }
        $result = Db::query("create table IF NOT EXISTS cmf_diyforms 
                            (
                            `diyid` int(10) unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(50) NOT NULL DEFAULT '',
                            `table` varchar(50) NOT NULL DEFAULT '',
                            `posttemplate` varchar(50) NOT NULL DEFAULT '',
                            `viewtemplate` varchar(50) NOT NULL DEFAULT '',
                            `listtemplate` varchar(50) NOT NULL DEFAULT '',
                            `info` text,
                            `public` tinyint(1) NOT NULL DEFAULT '1',
                            PRIMARY KEY (`diyid`)
                            );
                            ");
    }

    public function index()
    {
        $userName = Session::get("name");
        
        $diyforms = Db::name("diyforms")->select();

        $this->assign("name",$userName);
        $this->assign("diyforms",$diyforms);
        Session::delete('delfieldsucc');
        return $this->fetch('/admin_index');
    }

    public function addForm()
    {
        $result = Db::table('cmf_diyforms')
            ->order('diyid DESC')
            ->limit('1')
            ->select();

        if (sizeof($result) == 0){
            $diyid = 1;
            $this->assign('diyid',$diyid);
            return $this->fetch('/add_form');
        }else{

            $diyid = $result[0];
            $diyid = $diyid['diyid'] + 1;
            $this->assign('diyid',$diyid);
            return $this->fetch('/add_form');}

    }

    public function addFormDone()
    {
       $data = Request::instance()->param();
       unset($data['_plugin']);
       unset($data['_controller']);
       unset($data['_action']);
       if (null == $data){
           $this->error("新建表单错误！","{:cmf_plugin_url('bslform://AdminIndex/addForm')}");
       }
       Db::table('cmf_diyforms')->insert($data);

        $result = Db::query("create table IF NOT EXISTS cmf_diyform{$data['diyid']} ( id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id ));");
        if ($result == "empty"){
            $this->error("创建失败！","{:cmf_plugin_url('bslform://AdminIndex/addForm')}");
        }
       $this->success("新建成功","/plugin/bslform/admin_index/index.html");
    }

    public function deleteForm()
    {
        $deleteId = Request::instance()->param('deleteid');
        $iftrue = Db::table('cmf_diyforms')->where('diyid',$deleteId)->delete();
        $table = 'cmf_diyform'.$deleteId;
        $result = Db::query("DROP table {$table}");
        if (!$iftrue){
           return $this->error('删除失败！');
        }
        return $this->success('成功删除表单');
    }

    public function editForm()
    {
        $data = Request::instance()->param('editid');
        $editdata = Db::table('cmf_diyforms')->where('diyid',$data)->select();
        $fielddata = Db::query("select * from information_schema.columns where table_name='cmf_diyform{$data}'");
        $this->assign('fielddate',$fielddata);
        $this->assign('editdata',$editdata[0]);
        $this->assign('fieldid',$data);
        $this->assign('title','表单编辑页面');

        return $this->fetch('/edit_form');
    }

    public function deleteField()
    {
        $data = Request::instance()->param();
        $urlid = $data['editFormId'];
        $formId = 'cmf_diyform'.$data['editFormId'];
        $fiedId = $data['editFormStr'];
        $result = Db::query("ALTER TABLE $formId DROP $fiedId;");

        if ($result){
            Session::set('delfieldsucc','false');
            return $this->error('删除失败',"/plugin/bslform/admin_index/editform/editid/$urlid.html");
        }
        Session::set('delfieldsucc','true');
        return $this->success('删除成功',"/plugin/bslform/admin_index/editform/editid/$urlid.html");
    }

    public function editFormDone()
    {
        $data = Request::instance()->param();
        $editId = $data['diyid'];
        $editTable = $data['table'];
        unset($data['_plugin'],$data['_controller'],$data['_action'],$data['table'],$data['diyid']);
        $ifEdit = Db::table('cmf_diyforms')->where('diyid',$editId)->update($data);

        $fieldcon = Session::get('delfieldsucc');
        if ($ifEdit || $fieldcon=="true"){
            return $this->success('成功修改表单信息','/plugin/bslform/admin_index/index.html');
        }
        return $this->error('未做操作或更新失败','/plugin/bslform/admin_index/index.html');
    }

    public function addField()
    {
        $diyid = Request::instance()->param('diyid');
        $this->assign('diyid',$diyid);
        $this->assign('title','字段添加页面');
        return $this->fetch('/add_field');
    }

    public function addFieldDone()
    {
        $data = Request::instance()->param();
        $diyid = 'cmf_diyform'.$data['diyid'];
        $urlid = $data['diyid'];
        unset($data['_plugin'],$data['_controller'],$data['_action']);
        $ziduanming = $data['ziduanming'];
        $tishi = $data['tishi'];
        $type = $data['dtype'].'('.$data['maxlength'].')';
        $default = $data['vdefault'];
        $result = Db::query("alter table {$diyid} add {$ziduanming} {$type} DEFAULT '{$default}' comment '{$tishi}';");
        if($result){
            Session::set('delfieldsucc','false');
           return $this->error('创建失败！请重试！');
        }
        Session::set('delfieldsucc','true');
        return $this->success('创建成功！',"/plugin/bslform/admin_index/editform/editid/$urlid.html");
    }

    public function editField()
    {
        $data = Request::instance()->param();

        $editFormStr = $data['editFormStr'];
        $editFormId = $data['editFormId'];
        $editForm = 'cmf_diyform'.$editFormId;
        $editFormField = Db::query("select * from information_schema.columns where table_name='$editForm' and COLUMN_NAME = '$editFormStr'" );
        $this->view->assign('title','字段修改页面');
        $this->view->assign('diyid',$editFormId);
        $this->assign('editFormField',$editFormField[0]);
        return $this->view->fetch('/edit_field');
    }

    public function editFieldDone()
    {
    $data = Request::instance()->param();
        $diyid = 'cmf_diyform'.$data['diyid'];
        $urlid = $data['diyid'];
        unset($data['_plugin'],$data['_controller'],$data['_action']);
        $ziduanming = $data['ziduanming'];
        $tishi = $data['tishi'];
        $type = $data['dtype'].'('.$data['maxlength'].')';
        $default = $data['vdefault'];
        $result = Db::query("alter table {$diyid} modify COLUMN {$ziduanming} {$type} DEFAULT '{$default}' comment '{$tishi}';");
        if($result){
            Session::set('delfieldsucc','false');
            return $this->error('修改失败！请重试！');
        }
        Session::set('delfieldsucc','true');
        return $this->success('修改成功！',"/plugin/bslform/admin_index/editform/editid/$urlid.html");
    }

    public function issueForm()
    {
        $data = Request::instance()->param();
        $diyId = $data['issueId'];
        $data = $data['issueTable'];
        $issueDate = Db::query("select COLUMN_COMMENT,COLUMN_NAME from information_schema.columns where table_name='$data'" );
        unset($issueDate[0]);
        $this->assign('title','表单预览发布页面');
        $this->assign('issueDate',$issueDate);
        $this->assign('diyid',$diyId);
        $this->assign('issueform',$data);
        return $this->fetch('/issue_form');
    }

    public function issuefielddone()
    {
        $data = Request::instance()->param();
        $urlid = $data['issueformid'];
        $issueForm = $data['issueform'];
        unset($data['_plugin'],$data['_action'],$data['_controller'],$data['issueform'],$data['issueformid']);
        $res = Db::table("{$issueForm}")->insert($data);
        if ($res){
            return $this->success('恭喜，插入成功！',"/plugin/bslform/admin_index/index.html");
        }
        return $this->error('插入数据是失败',"/plugin/bslform/admin_index/index.html");
    }

    public function showForm(){
        $data = Request::instance()->param('issueTable');
        $res = Db::table("$data")->select();
        $res2 = Db::query("select COLUMN_COMMENT,COLUMN_NAME from information_schema.columns where table_name='$data'" );
        unset($res2[0]);
        $this->assign('title','表单信息内容页！');
        $this->assign('showformkey',$res2);
        $this->assign('issueform',$data);
        $this->assign('showform',$res);
        return $this->fetch('/show_form');
    }

}