<?php

class ProjectNumber extends CComponent
{
    const IZI_TO_PKPU_NUMBER = 5; //formerly 2 circa 2017

    public $fullyear;
    public $year;

    public function __construct()
    {
        $this->fullyear = date('Y');
        $this->year     = date('y');
    }

    /**
     * Encode IPP
     * @param  string $number
     */
    public function generate($companyId=1)
    {
        $result        = 'n/a';
        $command       = Yii::app()->db->createCommand();
        $tableName     = Ipp::model()->tableName();
        $sql           = "SELECT COUNT(id) FROM {$tableName}
											WHERE EXTRACT(year FROM ipp_date)={$this->fullyear} 
											AND company_id={$companyId} AND ipp_no IS NOT NULL";
        $companyNumber = $companyId == Company::IZI ? self::IZI_TO_PKPU_NUMBER : '';
        $count         = $command->setText($sql)->queryScalar();
        while ($result) {
            $count++;

            if ($companyNumber == '') { //PKPU
                $number = ($this->year * 10000) + $count;
            } else { //IZI
                $number = (($this->year . $companyNumber) * 1000) + $count;
            }
            $command->reset();

            $sql = "SELECT ipp_no FROM {$tableName} WHERE ipp_no='{$number}'";
            $result = $command->setText($sql)->queryScalar();
        }

        return $number;
    }

    public function getSequence($ippId, $seq=0)
    {
        $count = ProjectIpp::model()->count("ipp_id={$ippId}");
        return ($count >= 1) ? '-'.chr($count + 65) : null;
    }

    public function proposal($divisionId, $companyId, $revision='00')
    {
        $divNames = array(
            0  => 'ZF',
            25 => 'CSR',
            21 => 'LN',
            89 => 'HF',
            90 => 'RF',
            43 => 'CRM'
        );

        $result      = 'n/a';
        $companyName = CompanyEnum::getName($companyId);
        $command     = Yii::app()->db->createCommand();
        $tableName   = Proposal::model()->tableName();
        $sql         = "SELECT COUNT(id) FROM {$tableName} WHERE EXTRACT(year FROM created_stamp)={$this->fullyear} AND company_id={$companyId} AND proposal_no IS NOT NULL";
        $count       = $command->setText($sql)->queryScalar();
        while ($result) {
            $count++;

            // PKPU-Prop/212.00/CSR/II/2016
            $number  = "{$companyName}-Prop/";
            $number .= "{$count}.{$revision}/";
            $number .= (!is_null($divNames[$divisionId]) ? $divNames[$divisionId] : $divNames[0]) . '/';
            $number .= RomanConverter::convert(date('n')) . '/';
            $number .= date('Y');

            $command->reset();

            $sql = "SELECT proposal_no FROM {$tableName} WHERE proposal_no='{$number}'";
            $result = $command->setText($sql)->queryScalar();
        }

        return $number;
    }
}
