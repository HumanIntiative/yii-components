<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */

Yii::import('zii.widgets.CPortlet');
 
class UserMenuLTE extends CPortlet
{
    public function init()
    {
        parent::init();
    }
 
    protected function renderContent()
    {
        if (Yii::app()->user->checkAccess('jagoan')) {
            $menu = array(
                array('id'=>1,'has_child'=>0,'url'=>'/proposal/create','parent_id'=>0,'title'=>'Request Proposal'),
                array('id'=>2,'has_child'=>0,'url'=>'/proposal/index','parent_id'=>0,'title'=>'List Proposal'),
                array('id'=>3,'has_child'=>0,'url'=>'/repository/admin','parent_id'=>0,'title'=>'Repository Proposal'),
            );
        } elseif ((Yii::app()->user->checkAccess('Marketer')) or (Yii::app()->user->checkAccess('Admin KMT'))) {
            $menu = array(
                // array('id'=>1,'has_child'=>0,'url'=>'/repository/admin','parent_id'=>0,'title'=>'Repository Proposal'),
                array('id'=>2,'has_child'=>0,'url'=>'/proposal/create','parent_id'=>0,'title'=>'Request Proposal (Custom)'),
                array('id'=>3,'has_child'=>0,'url'=>'/proposal/index','parent_id'=>0,'title'=>'List Proposal'),
                /*array('id'=>4,'has_child'=>1,'url'=>'/proposal/index','parent_id'=>3,'title'=>'[All]'),
                // array('id'=>5,'has_child'=>0,'url'=>'/proposal/index?status=1','parent_id'=>3,'title'=>'Draft'),
                array('id'=>6,'has_child'=>0,'url'=>'/proposal/index?status=submitted','parent_id'=>3,'title'=>'Submitted'),
                array('id'=>7,'has_child'=>0,'url'=>'/proposal/index?status=donor','parent_id'=>3,'title'=>'Sent To Donor'),*/
            );
        } elseif (Yii::app()->user->checkAccess('Admin PDG')) {
            $menu = array(
                array('id'=>1,'has_child'=>0,'url'=>'#','parent_id'=>0,'title'=>'Create Proposal'),
                array('id'=>2,'has_child'=>1,'url'=>'/proposal/index','parent_id'=>0,'title'=>'List Proposal'),
                /*array('id'=>3,'has_child'=>0,'url'=>'/proposal/index','parent_id'=>2,'title'=>'[All]'),
                array('id'=>4,'has_child'=>0,'url'=>'/proposal/index?status=inbox','parent_id'=>2,'title'=>'Inbox'),
                array('id'=>5,'has_child'=>0,'url'=>'/proposal/index?status=sent','parent_id'=>2,'title'=>'Sent'),
                array('id'=>6,'has_child'=>0,'url'=>'/proposal/index?status=4','parent_id'=>2,'title'=>'Rejected'),*/
            );
        } elseif (Yii::app()->user->checkAccess('QAQC')) {
            $menu = array(
                array('id'=>1,'has_child'=>0,'url'=>'/repository/admin','parent_id'=>0,'title'=>'Repository Proposal'),
                array('id'=>2,'has_child'=>0,'url'=>'/proposal/index','parent_id'=>0,'title'=>'List Proposal'),
               /* array('id'=>3,'has_child'=>0,'url'=>'/proposal/index?status=inbox','parent_id'=>0,'title'=>'Inbox'),
                array('id'=>4,'has_child'=>0,'url'=>'/proposal/index?status=sent','parent_id'=>0,'title'=>'Sent'),
                array('id'=>5,'has_child'=>0,'url'=>'/proposal/index?status=2','parent_id'=>0,'title'=>'Rejected'),*/
            );
        } elseif (Yii::app()->user->checkAccess('Admin BKS')) {
            $menu = array(
                array('id'=>1,'has_child'=>0,'url'=>'/proposal/index','parent_id'=>0,'title'=>'[List All]'),
            );
        } else {
            $menu = array();
        }

        $this->render('userMenuLTE', array(
            'menus' => $menu,
        ));
    }

    public function KucingMenu($items, $class="treeview-menu")
    {
        $render = '<ul class="'.$class.' ">';

        foreach ($items as $item) {
            $render .= '<li><a href="'.$item->url.'"><i class="fa fa-plus-square"></i> ' . $item->title . '</a>';
            if (!empty($item->subs)) {
                $render .= $this->KucingMenu($item->subs, 'treeview-menu');
            }
            $render .= '</li>';
        }

        return $render . '</ul>';
    }

    public function generatePageTree($datas, $parent_id = 0, $limit=0)
    {
        if ($limit > 1000) {
            return '';
        }
        
        $tree = ($parent_id == 0) ? '<ul class="nav nav-second-level">' : '<ul class="nav nav-third-level">';
        for ($i=0, $ni=count($datas); $i < $ni; $i++) {
            if ($datas[$i]['parent_id'] == $parent_id) {
                $tree .= '<li><a href="'.$datas[$i]['url'].'">';
                $tree .= $datas[$i]['title'];
                if ($datas[$i]['has_child'] == 1) {
                    $tree .= '<span class="fa arrow"></span>';
                }
                $tree .='</a>';
                $tree .= $this->generatePageTree($datas, $datas[$i]['id'], $limit++);
                $tree .= '</li>';
            }
        }
        $tree .= '</ul>';
        return $tree;
    }
}
