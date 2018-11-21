<?php

class EmailNotifIppBehavior extends CBehavior implements EmailNotifInterface
{
    protected $_type;
    protected $param_data;
    protected $to_email;

    protected $_picEmail;
    protected $_marketerEmail;
    protected $_kmtEmail;
    protected $_mgrMarketerEmail;
    protected $_upperManagerEmail;
    protected $_keuEmail;
    protected $_pdgEmail;
    protected $_ramadhanEmail;
    protected $_bksEmail;
    protected $_userEmail;
    // protected $_authorEmail;
    protected $_qaqcEmail;

    /**
     * Send Email Notify to Database
     * @param  EmailQueueStatusEnum	$type  Enum
     * @return bool
     */
    public function notifyEmail($type)
    {
        if (!$this->owner instanceof Ipp) {
            return false;
        }

        $currency = $this->owner->getCurrencyCode('symbol');
        $this->_type = $type;
        $this->param_data = array(
            'id'        => $this->owner->id,
            'title'     => $this->owner->program_name,
            'ipp_no'    => $this->owner->ipp_no,
            'ipp_date'  => date_format(date_create($this->owner->ipp_date), "d-m-Y"),
            'exec_date' => $this->owner->getExecutionDate("d-m-Y"),
            'program'   => $this->owner->Program ? /*$this->owner->Program->program_name*/$this->owner->program_name : "-",
            'nilai'     => $this->owner->total_amount ? $currency . ". ".number_format($this->owner->total_amount) : "-",
            'donatur'   => $this->owner->Donor ? $this->owner->Donor->full_name : "-",
            'marketer'  => $this->owner->Marketer ? $this->owner->Marketer->full_name : "-",
            'pembuat'   => $this->owner->Creator ? $this->owner->Creator->full_name : "-",
            'lokasi'    => $this->owner->getLocation(),
        );

        // Set Email
        $this->_keuEmail      = "fithri@pkpu.or.id";
        $this->_pdgEmail      = "admin.pdg@pkpu.or.id,pdgpkpu@gmail.com,ferry.suranto@pkpu.or.id";
        $this->_ramadhanEmail = "admin.ramadhan@pkpu.or.id,pkpu.ramadhan@gmail.com";
        $this->_qaqcEmail     = "divisi.quality@pkpu.or.id";
        $this->_bksEmail      = "m.suharsono@pkpu.or.id";
        $this->_kmtEmail      = "";
        // $this->_pdgEmail2     = "ivansatria@pkpu.or.id,Osy.utami@pkpu.or.id,syakur@pkpu.or.id";

        $this->_marketerEmail = $this->owner->Marketer ? $this->owner->Marketer->getPkpuEmail() : null; //TODO
        if ($this->_marketerEmail && $this->owner->Marketer->managerModel) {
            $this->_mgrMarketerEmail  = $this->owner->Marketer->managerModel->getPkpuEmail(); //TODO
        }
        $this->_userEmail = $this->owner->Creator ? $this->owner->Creator->getPkpuEmail() : null;
        // End

        // To Email
        if ($this->owner->Creator) {
            if (($creator = $this->owner->Creator)) {
                $empId = $this->owner->created_by;
            } else {
                throw new CException("Tidak bisa mengirim Email", 500);
            }
            $emp = ViewEmployeeAll::model()->findByPk($empId);

            $employeeId = null;
            switch ($emp->division_id) {
                case DivisionEnum::Luar_Negeri:
                    $employeeId = 250619;
                    break;
                case DivisionEnum::CSR:
                    $employeeId = 38014;
                    break;
                case DivisionEnum::Retail_Funding:
                case DivisionEnum::Humanitarian_Funding:
                case DivisionEnum::Tabung_Peduli:
                    $employeeId = 96643;
                    break;
                case DivisionEnum::CRM:
                    $employeeId = 289481;
                    break;
            }

            if (in_array($emp->division_id, DivisionEnum::getAllKK())) { //CRM atau KK
                $employeeId = 289481;
                $this->to_email = $this->getToEmailByEmployeeId($employeeId);
            } elseif ($this->owner->fund_driven_id == FundDrivenEnum::RKAT) {
                $employeeId = 108087;
                $this->to_email = $this->getToEmailByEmployeeId($employeeId);
            } elseif ($type == EmailQueueStatusEnum::TYPE_IPP_APPROVE) {
                // } elseif ($emp->division_id == DivisionEnum::Keuangan) {
                $this->setToEmailAsUndefined();
            } elseif (!is_null($employeeId)) {
                $this->to_email = $this->getToEmailByEmployeeId($employeeId);
            } else {
                $this->setToEmailAsUndefined();
            }
        } else {
            $this->setToEmailAsUndefined();
        }

        switch ($type) {
            // CREATE
            case EmailQueueStatusEnum::TYPE_IPP_CREATE:
                return $this->notifyIppCreate();
                break;
            case EmailQueueStatusEnum::TYPE_IPP_APPROVE:
                return $this->notifyIppApproveByKeu();
                break;
            case EmailQueueStatusEnum::TYPE_IPP_REJECT:
                return $this->notifyIppRejectByKeu();
                break;
            case EmailQueueStatusEnum::TYPE_IPP_ACCEPT:
                return $this->notifyIppAcceptByPdg();
                break;
            case EmailQueueStatusEnum::TYPE_IPP_COMMENT:
                return $this->notifyIppComment();
        }
    }

    /**
     * Create EmailQueue model instance
     * @param  string $subject Judul
     * @return EmailQueue      Model
     */
    public function queueFactory($subject)
    {
        $queue = new EmailQueue('create');
        $queue->from_email     = 'project@pkpu.or.id';
        $queue->from_name      = 'Project System';
        $queue->subject        = $subject;
        $queue->mapclass_name  = get_class($this->owner);
        $queue->mapclass_id    = $this->owner->id;
        $queue->notif_type     = $this->_type;
        $queue->date_published = new CDbExpression('NOW()');

        return $queue;
    }

    protected function getToEmailByEmployeeId($id)
    {
        $pic = SdmEmployee::model()->findByPk($id);
        $this->_picEmail = $pic->pkpuEmail;
        $this->_picEmail .= ",{$pic->email}";
        return $this->_picEmail;
    }

    protected function setToEmailAsUndefined()
    {
        if (($Program = $this->owner->Program) && ($Pic = $this->owner->Program->Pic)) {
            $this->_picEmail = $Pic->pkpuEmail;
            if ($Program->parent_id != Program::DRM) { //DRM hanya menerima Email pkpu.or.id
                $this->_picEmail .= ",{$Pic->email}";
            }
            $this->to_email  = $this->_picEmail;

            if (null !== ($Manager = ViewEmployeeManager::model()->findManagerById($Pic->id))) {
                $this->to_email .= ",{$Manager->pkpuEmail}";
            }
        } else {
            $this->to_email = Yii::app()->user->email;
        }
    }

    /**
     * Create Queue for Ipp Create
     * @return bool
     */
    protected function notifyIppCreate()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} telah dibuat";

        $queue = $this->queueFactory($subject);
        $queue->to_email = $this->to_email;
        $queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan telah dibuat IPP dengan rincian:
			<br/><br/>
			No IPP					: ".$this->param_data['ipp_no']."<br/>
			Tgl Permintaan Eksekusi : ".$this->param_data['exec_date']."<br/>
			Donatur     			: ".$this->param_data['donatur']."<br/>
			Nama Proyek				: ".$this->param_data['program']."<br/>
			Nilai Proyek			: ".$this->param_data['nilai']."<br/>
			Lokasi					: ".$this->param_data['lokasi']."<br/>
			Marketer    			: ".$this->param_data['marketer']."<br/>
			Dibuat oleh 			: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for Ipp Approve By Keuangan
     * @return bool
     */
    protected function notifyIppApproveByKeu()
    {
        // $subject = "[RMD-1436H] IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} di-APPROVE Keuangan";
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} di-APPROVE Keuangan";

        $queue = $this->queueFactory($subject);
        $queue->to_email = $this->to_email;
        $queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa IPP telah diapprove oleh keuangan<br/>
			dengan rincian :
			<br/><br/>
			No IPP					: ".$this->param_data['ipp_no']."<br/>
			Tgl Permintaan Eksekusi : ".$this->param_data['exec_date']."<br/>
			Donatur     			: ".$this->param_data['donatur']."<br/>
			Nama Proyek				: ".$this->param_data['program']." <br/>
			Nilai Proyek			: ".$this->param_data['nilai']." <br/>
			Lokasi					: ".$this->param_data['lokasi']."<br/>
			Marketer    			: ".$this->param_data['marketer']."<br/>
			Dibuat oleh 			: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for Ipp Reject By Keuangan
     * @return bool
     */
    protected function notifyIppRejectByKeu()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} DITOLAK Keuangan";

        $queue = $this->queueFactory($subject);
        $queue->to_email = $this->to_email;
        $queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa IPP telah ditolak oleh keuangan.<br/>
			Alasan: ".$this->param_data['reject_note'].".<br/>
			dengan rincian :
			<br/><br/>
			No IPP					: ".$this->param_data['ipp_no']."<br/>
			Tgl Permintaan Eksekusi : ".$this->param_data['exec_date']."<br/>
			Donatur     			: ".$this->param_data['donatur']."<br/>
			Nama Proyek				: ".$this->param_data['program']." <br/>
			Nilai Proyek			: ".$this->param_data['nilai']." <br/>
			Lokasi					: ".$this->param_data['lokasi']."<br/>
			Marketer    			: ".$this->param_data['marketer']."<br/>
			Dibuat oleh 			: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for Ipp Accept By PDG
     * @return bool
     */
    protected function notifyIppAcceptByPdg()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} di-APPROVE Tim Pelaksana";

        $queue = $this->queueFactory($subject);
        $queue->to_email = $this->to_email;
        $queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa IPP telah diterima oleh PDG<br/>
			dengan rincian :
			<br/><br/>
			No IPP					: ".$this->param_data['ipp_no']."<br/>
			Tgl Permintaan Eksekusi : ".$this->param_data['exec_date']."<br/>
			Donatur     			: ".$this->param_data['donatur']."<br/>
			Nama Proyek				: ".$this->param_data['program']." <br/>
			Nilai Proyek			: ".$this->param_data['nilai']." <br/>
			Lokasi					: ".$this->param_data['lokasi']."<br/>
			Marketer    			: ".$this->param_data['marketer']."<br/>
			Dibuat oleh 			: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for Ipp New Comment
     * @return bool
     */
    protected function notifyIppComment()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} Komentar terbaru";

        $queue = $this->queueFactory($subject);

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        // Search Comment
        $criteria = new CDbCriteria;
        $criteria->compare('ipp_id', $this->owner->id);
        $criteria->order = 'id DESC';
        $criteria->limit = 1;
        $ippComment = IppComment::model()->find($criteria);

        if (null === $ippComment) {
            return false;
        }
        $comment = $ippComment->comment;

        $queue->to_email   = $comment->recipent->pkpuEmail;
        $queue->cc_email   = $comment->recipent->email;
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			".$comment->creator->full_name." telah menambahkan komentar di IPP yang Anda ikuti<br/><br/>
			<p>".$comment->content."</p><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }
}
