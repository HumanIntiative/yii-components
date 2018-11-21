<?php

class ModelExBehavior extends CActiveRecordBehavior
{
    public function beforeFind($event)
    {
        if (!Yii::app()->hasProperty('user')) {
            return;
        }

        if (($branchId = Yii::app()->user->branchId) != Branch::PUSAT && $this->owner->hasAttribute('branch_id')) {
            $fieldName = 't.branch_id';
            if (is_null($this->owner->dbCriteria->with)) {
                $fieldName = 'branch_id';
            }
            // Hack not found
            /*if (get_class($this->owner) == 'Ipp' && Yii::app()->user->branchId == 5)
                $fieldName = 't.branch_id';*/

            if (in_array($branchId, Branch::$KC_LIST)) {
                $this->owner->dbCriteria->addInCondition($fieldName, Branch::kcGroup($branchId));
            } else {
                $this->owner->dbCriteria->compare($fieldName, $branchId);
            }
        }
    }

    public function beforeCount($event)
    {
        if (!Yii::app()->hasProperty('user')) {
            return;
        }

        if (($branchId = Yii::app()->user->branchId) != Branch::PUSAT && $this->owner->hasAttribute('branch_id')) {
            $fieldName = 't.branch_id';
            if (is_null($this->owner->dbCriteria->with)) {
                $fieldName = 'branch_id';
            }
            // Hack not found
            /* if (get_class($this->owner) == 'Project' && Yii::app()->user->branchId == 5)
                $fieldName = 't.branch_id'; */

            if (in_array($branchId, Branch::$KC_LIST)) {
                $this->owner->dbCriteria->addInCondition($fieldName, Branch::kcGroup($branchId));
            } else {
                $this->owner->dbCriteria->compare($fieldName, $branchId);
            }
        }
    }

    public function countByBranch()
    {
        if (!Yii::app()->hasProperty('user')) {
            return;
        }

        $criteria = new CDbCriteria;
        if (($branchId = Yii::app()->user->branchId) != Branch::PUSAT && $this->owner->hasAttribute('branch_id')) {
            $fieldName = 't.branch_id';
            if (is_null($this->owner->dbCriteria->with)) {
                $fieldName = 'branch_id';
            }

            $criteria->compare($fieldName, $branchId);
        }

        return $this->owner->count($criteria);
    }
}
