<?php

class UrlManager extends CUrlManager
{
    public function init()
    {
        $this->showScriptName = false;
        $this->urlFormat = 'path';
        $this->rules = $this->getDefaultRules();

        parent::init();
    }

    protected function getDefaultRules()
    {
        $projControllers = 'activity|beneficiary|budget|curves|resource|sheets|wbs|team|report|greport|file|ippsumber|qcpass|summary|eproc|photo';
        $reportControllers = 'progress|final';
        $postControllers = 'cerita|berita'; //PostController

        return CMap::mergeArray($this->rules, array(
            // Project
            'project/<pid:\d+>/' => 'project/fep/view/id/<pid>',
            "project/<pid:\d+>/<ctrl:({$projControllers})>" => 'project/<ctrl>/index', #/pid/<pid>
            "project/<pid:\d+>/<ctrl:({$projControllers})>/<action:\w+>" => 'project/<ctrl>/<action>',
            "project/<pid:\d+>/<ctrl:({$projControllers})>/<action:\w+>/<id:\d+>" => 'project/<ctrl>/<action>',
            'project/<pid:\d+>/<action:\w+>' => 'project/fep/<action>/id/<pid>',

            // Assist
            'assist/<pid:\d+>/' => 'assist/project/view/id/<pid>',
            'assist/<pid:\d+>/<action:\w+>' => 'assist/project/<action>/id/<pid>',
            "assist/<pid:\d+>/<ctrl:({$reportControllers})>" => 'assist/generalReport/index', #/pid/<pid>
            "assist/<pid:\d+>/<ctrl:({$reportControllers})>/<action:\w+>" => 'assist/generalReport/<action>',
            "assist/<pid:\d+>/<ctrl:({$reportControllers})>/<action:\w+>/<id:\d+>" => 'assist/<ctrl>/<action>',
            "assist/<pid:\d+>/<ctrl:({$postControllers})>" => 'assist/post/index',
            "assist/<pid:\d+>/<ctrl:({$postControllers})>/<action:\w+>" => 'assist/post/<action>',
            "assist/<pid:\d+>/<ctrl:({$postControllers})>/<action:\w+>/<id:\d+>" => 'assist/post/<action>',
            "assist/<pid:\d+>/<ctrl>" => 'assist/<ctrl>/index',
            "assist/<pid:\d+>/<ctrl>/<action:\w+>" => 'assist/<ctrl>/<action>',
            "assist/<pid:\d+>/<ctrl>/<action:\w+>/<id:\d+>" => 'assist/<ctrl>/<action>',

            // Default Route
            '<controller:\w+>/<id:\d+>' => '<controller>/view',
            '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
            '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
        ));
    }
}
