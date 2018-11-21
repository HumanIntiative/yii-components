<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
    /**
     * @var string the default layout for the controller view. Defaults to '//layouts/column1',
     * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
     */
    public $layout='//layouts/column1';
    /**
     * @var array context menu items. This property will be assigned to {@link CMenu::items}.
     */
    public $menu=array();
    public $user_menu=array();
    /**
     * @var array the breadcrumbs of the current page. The value of this property will
     * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
     * for more details on how to specify this property.
     */
    public $breadcrumbs=array();

    public function filters()
    {
        return array(
            'auth',
        );
    }

    public function filterAuth($filterChain)
    {
        if (Yii::app()->user->isGuest) {
            Yii::app()->user->loginRequired();
        }
        if (YII_ENV == 'dev') {
            $filter = new RightsFilter;
        } else {
            $filter = new SamlRightsFilter;
        }
        $filter->filter($filterChain);
    }

    public function init()
    {
        register_shutdown_function(array($this, 'onShutdownHandler'));
        return parent::init();
    }

    public function onShutdownHandler()
    {
        $e = error_get_last();
        $errorTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING);

        if ($e !== null && in_array($e['type'], $errorTypes)) {
            $msg = 'Fatal error: ' . $e['message'];
            Yii::app()->errorHandler->errorAction = null;
            Yii::app()->handleError($e['type'], $msg, $e['file'], $e['line']);
        }
    }

    public function allowedActions()
    {
        return '';
    }

    public function redirect($url, $terminate=true, $statusCode=302)
    {
        $returnUrl = Yii::app()->request->getQuery('returnUrl');
        if (isset($returnUrl)) {
            $url = $returnUrl;
        }
        parent::redirect($url, $terminate, $statusCode);
    }
}
