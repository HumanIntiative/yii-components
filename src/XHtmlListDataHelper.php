<?php

class XHtmlListDataHelper extends CComponent
{
    protected $expiration = 10800; // 3 hours

    public function program()
    {
        $cacheId = 'optProgram1234';
        // if (false !== ($listProgram = Yii::app()->cache->get($cacheId))) return $listProgram;

        $years = array(date('Y'), date('Y')-1);
        $skipCompany = Yii::app()->user->checkAccess('IZI To PKPU');
        $listProgram = CHtml::listData(Program::model()->findAllActive(null, $years, $skipCompany), 'id', 'program_name', 'group');
        Yii::app()->cache->set($cacheId, $listProgram, $this->expiration);

        return $listProgram;
    }

    public function budget()
    {
        $cacheId = 'optBudget1234';
        $year = '2016';
        // if (false !== ($listBudget = Yii::app()->cache->get($cacheId))) return $listBudget;

        $criteria =  array(
            'condition'=>"periode='{$year}'",
            'order'=>'code',
        );
        $listBudget = CHtml::listData(RKATBudget::model()->findAll($criteria), 'id', 'text');
        Yii::app()->cache->set($cacheId, $listBudget, $this->expiration);

        return $listBudget;
    }

    public function proposalStatus()
    {
        $cacheId = 'optPropStat1234';
        if (false !== ($listStatus = Yii::app()->cache->get($cacheId))) {
            return $listStatus;
        }

        $criteria =  array('order'=>'code');
        $listStatus = ProposalStatusEnum::toArrayEx();
        Yii::app()->cache->set($cacheId, $listStatus, $this->expiration);

        return $listStatus;
    }

    public function marketer()
    {
        $branchId = Yii::app()->user->branchId;
        $cacheId = 'optMarketer1234' . $branchId;
        //if (false !== ($listMarketer = Yii::app()->cache->get($cacheId))) return $listMarketer;

        $criteria = new CDbCriteria;
        $criteria->compare('is_marketer', 1);
        $criteria->compare('user_status', 1);
        // $criteria->compare('branch_id_financial', 1);
        if ($branchId != Branch::PUSAT) {
            $criteria->compare('branch_id', $branchId);
        }
        if (Yii::app()->user->companyId) {
            $criteria->compare('company_id', Yii::app()->user->companyId);
        }
        $criteria->order = 'full_name';

        $listMarketer = CHtml::listData(SdmEmployee::model()->findAll($criteria), 'id', 'full_name');
        Yii::app()->cache->set($cacheId, $listMarketer, $this->expiration);

        return $listMarketer;
    }

    public function cpm($program_id)
    {
        $branchId = Yii::app()->user->branchId;
        $cacheId = 'optCPM1234' . $branchId;
        if (false !== ($listCPM = Yii::app()->cache->get($cacheId))) {
            return $listCPM;
        }

        $criteria =  array('order'=>'code');
        $listCPM = CHtml::listData(CPM::model()->findAllByProgramId($program_id), 'id', 'text');
        Yii::app()->cache->set($cacheId, $listCPM, $this->expiration);

        return $listCPM;
    }

    public function mitra()
    {
        $branchId = Yii::app()->user->branchId;
        $cacheId = 'optMitra1234' . $branchId;
        // if (false !== ($listPartner = Yii::app()->cache->get($cacheId))) return $listPartner;

        $criteria = new CDbCriteria;
        $criteria->compare('status_id', Partner::APPROVED);
        if ($branchId != Branch::PUSAT) {
            $criteria->compare('branch_id', $branchId);
        }
        /*if ($companyId = Yii::app()->user->companyId)
            $criteria->compare('company_id', $companyId);*/
        $criteria->order = 'partner_name';

        $listPartner = CHtml::listData(Partner::model()->findAll($criteria), 'id', 'partner_name');
        Yii::app()->cache->set($cacheId, $listPartner, $this->expiration);

        return $listPartner;
    }

    public function employeePDG()
    {
        $branchId = Yii::app()->user->branchId;
        $cacheId = 'optPdg1234' . $branchId;
        //if (false !== ($listEmployee = Yii::app()->cache->get($cacheId))) return $listEmployee;

        $criteria = new CDbCriteria;
        if ($branchId != Branch::PUSAT) {
            $criteria->compare('branch_id', $branchId);
        }
        if (Yii::app()->user->companyId) {
            $criteria->compare('company_id', Yii::app()->user->companyId);
        }
        $criteria->order = 'full_name';

        $listEmployee = CHtml::listData(ViewEmployeePDG::model()->findAll($criteria), 'id', 'full_name');
        Yii::app()->cache->set($cacheId, $listEmployee, $this->expiration);

        return $listEmployee;
    }

    public function employee()
    {
        $branchId = Yii::app()->user->branchId;
        $cacheId = 'optPdg1234' . $branchId;
        //if (false !== ($listEmployee = Yii::app()->cache->get($cacheId))) return $listEmployee;

        $criteria = new CDbCriteria;
        $criteria->compare('user_status', 1);
        $criteria->compare('is_employee', 1);
        if ($branchId != Branch::PUSAT) {
            $criteria->compare('branch_id', $branchId);
        }
        if (Yii::app()->user->companyId) {
            $criteria->compare('company_id', Yii::app()->user->companyId);
        }
        $criteria->order = 'full_name';

        $listEmployee = CHtml::listData(SdmEmployee::model()->findAll($criteria), 'id', 'full_name');
        Yii::app()->cache->set($cacheId, $listEmployee, $this->expiration);

        return $listEmployee;
    }

    public function dateFilterWidget($model, $attribute)
    {
        return Yii::app()->controller->widget('bootstrap.widgets.TbDatePicker', array(
            'model'=>$model,
            'attribute' => $attribute,
            'value' => $model->$attribute,
            // additional javascript options for the date picker plugin
            'options'=>array(
                'language' => 'id',
                'format' => 'yyyy-mm-dd',
            ),
            'htmlOptions'=>array(
                'class'=>'form-control',
                'style'=>'width:90px;min-width:90px;',
                'placeholder'=>'',
            ),
        ), true);
    }

    public function priceFormatScript($selector)
    {
        $cs = Yii::app()->clientScript;
        $cs->registerPackage('priceFormat');
        $cs->registerScript('numFor', "
			jQuery('{$selector}').priceFormat({
				prefix: '',
				centsLimit: 0,
				thousandsSeparator: ','
			});
		", CClientScript::POS_READY);
    }

    public function getEnumToArray($name)
    {
        $cacheId = 'optEnums123_'.$name;
        if (is_null($name)) {
            return array();
        }
        if (false !== ($listEnum = Yii::app()->cache->get($cacheId))) {
            return $listEnum;
        }

        $sql   = "SELECT UNNEST(ENUM_RANGE(null::{$name})) as unnest";
        $types = Yii::app()->db->createCommand($sql)->queryAll();
        foreach ($types as $type) {
            $listEnum["$type[unnest]"] = $type['unnest'];
        }
        Yii::app()->cache->set($cacheId, $listEnum, $this->expiration);

        return $listEnum;
    }
}
