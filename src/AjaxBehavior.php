<?php

class AjaxBehavior extends CBehavior
{
    protected function getEmployeesByKey($key, $is_pdg=false)
    {
        $criteria = new CDbCriteria;
        $criteria->compare('LOWER(full_name)', strtolower($key), true);
        $criteria->limit = 20;

        if ($is_pdg) {
            return ViewEmployeePDG::model()->findAll($criteria);
        } else {
            return SdmEmployee::model()->findAll($criteria);
        }
    }

    public function ajaxEmployee()
    {
        if (isset($_POST['ajax'])&& $_POST['ajax']=='getEmployees') {
            $pembina = array();
            $employees = $this->getEmployeesByKey($_POST['key'], $_POST['is_pdg']);

            foreach ($employees as $row) {
                $pembina[] = array('value'=>$row->full_name,'id'=>$row->id);
            }

            echo CJSON::encode($pembina);
            Yii::app()->end();
        }
    }

    public function performAjaxValidation($model, $formName)
    {
        if (isset($_POST['ajax']) && $_POST['ajax']===$formName) {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
