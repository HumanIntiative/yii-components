<?php

class SMEPComplianceBehavior extends CBehavior
{
    public $attrDateInterval    = 'smep_delta_day';
    public $attrOrderAmount     = 'smep_value';
    public $attrMaxExecutionDay = 'smep_max_execution_day';

    public function smepMinimumDateInterval($attribute)
    {
        if (!$this->owner instanceof Ipp) {
            return false;
        }
        if (null === $this->owner->{$attribute}) {
            return true;
        }

        if (null === $this->owner->program_id) {
            return false;
        }
        $prg = Program::model()->findByPK($this->owner->program_id);
        if (null === $prg->{$this->attrDateInterval}) {
            return false;
        }

        $ippDate = new DateTime;
        $executeDate = DateTime::createFromFormat('Y-m-d', $this->owner->{$attribute});

        $interval = (int)$ippDate->diff($executeDate)->format('%d');
        $minInterval = $prg->{$this->attrDateInterval};

        return ($interval >= $minInterval);
    }

    public function smepMinimumOrderAmount($attribute)
    {
        if (!$this->owner instanceof Ipp) {
            return false;
        }
        if (null === $this->owner->{$attribute}) {
            return true;
        }

        if (null === $this->owner->program_id) {
            return false;
        }
        $prg = Program::model()->findByPK($this->owner->program_id);
        if (null === $prg->{$this->attrOrderAmount}) {
            return false;
        }

        $amount = $this->owner->{$attribute};
        $minAmount = $prg->{$this->attrOrderAmount};

        return ($amount >= $minAmount);
    }

    public function smepMaximumDate($attribute)
    {
        if (!$this->owner instanceof Ipp) {
            return false;
        }
        if (null === $this->owner->{$attribute}) {
            return true;
        }

        if (null === $this->owner->program_id) {
            return false;
        }
        $prg = Program::model()->findByPK($this->owner->program_id);
        if (null === $prg->{$this->attrMaxExecutionDay}) {
            return false;
        }

        $executeDate = DateTime::createFromFormat('Y-m-d', $this->owner->{$attribute});
        $lastDate = DateTime::createFromFormat('Y-m-d', $prg->{$this->attrMaxExecutionDay});

        return ($executeDate <= $lastDate);
    }
}
