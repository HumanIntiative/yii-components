<?php

class NewEmailNotifIppBehavior extends CBehavior implements EmailNotifInterface
{
    public $param_data;
    public $to_email;
    public $type;
    public $queue;

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
    protected $_qaqcEmail;

    protected $test = false;
    protected $year;

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

        $this->year = date('Y');
        $this->type = $type;
        $this->param_data = array(
            'id'        => $this->owner->id,
            'title'     => $this->owner->program_name,
            'ipp_no'    => $this->owner->ipp_no,
            'ipp_date'  => date_format(date_create($this->owner->ipp_date), "d-m-Y"),
            'exec_date' => $this->owner->getExecutionDate("d-m-Y"),
            'program'   => $this->owner->Program ? $this->owner->program_name : "-",
            'nilai'     => $this->owner->total_amount ? $this->owner->getCurrencyCode('symbol').". ".number_format($this->owner->total_amount) : "-",
            'donatur'   => $this->owner->Donor ? $this->owner->Donor->full_name : "-",
            'marketer'  => $this->owner->Marketer ? $this->owner->Marketer->full_name : "-",
            'pembuat'   => $this->owner->Creator ? $this->owner->Creator->full_name : "-",
            'approver'  => $this->owner->Approver ? $this->owner->Approver->full_name : "-",
            'lokasi'    => $this->owner->getLocation(),
            'company'	=> $this->owner->company_id,
        );

        $this->defineDefaultRecipients();
        $this->defineToEmail();

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
        if ($this->owner->company_id == CompanyEnum::PKPU) {
            $queue->from_email = 'project@pkpu.or.id';
            $queue->from_name  = 'PKPU Project System';
        } else {
            $queue->from_email = 'project@izi.or.id';
            $queue->from_name  = 'IZI Project System';
        }
        $queue->subject        = $subject;
        $queue->mapclass_name  = get_class($this->owner);
        $queue->mapclass_id    = $this->owner->id;
        $queue->notif_type     = $this->type;
        $queue->date_published = new CDbExpression('NOW()');

        return $queue;
    }

    /**
     * Set Default CC
     * @return void
     */
    protected function defineDefaultRecipients()
    {
        if ($this->owner->company_id == CompanyEnum::PKPU) {
            $this->_ramadhanEmail = "admin.ramadhan@pkpu.or.id,pkpu.ramadhan@gmail.com";
        } else {
            //
        }
        $this->_userEmail     = $this->owner->Creator ? $this->owner->Creator->pkpuEmail : null;
        $this->_marketerEmail = $this->owner->Marketer ? $this->owner->Marketer->getPkpuEmail() : null;
        if ($this->_marketerEmail && $this->owner->Marketer->managerModel) {
            $this->_mgrMarketerEmail  = $this->owner->Marketer->managerModel->getPkpuEmail();
        }
    }

    /**
     * Set To Email based on Branch
     * @return void
     */
    protected function defineToEmail()
    {
        if (($branchId = Yii::app()->user->branchId) == Branch::PUSAT) {
            $this->defineToEmailFromCenter();
        } else {
            $this->defineToEmailFromBranch($branchId);
        }
    }

    /**
     * Set To Email Non Branch
     * @return void
     */
    protected function defineToEmailFromCenter()
    {
        if ($this->owner->company_id == CompanyEnum::PKPU) {
            $this->_keuEmail = 'fithri@pkpu.or.id';
        } else {
            $this->_keuEmail = 'riyanto@izi.or.id';
        }

        if ($this->owner->company_id == CompanyEnum::PKPU) {
            $this->_pdgEmail = 'admin.pdg@pkpu.or.id,pdgpkpu@gmail.com,ferry.suranto@pkpu.or.id';
        } else {
            $this->_pdgEmail = 'admin.pdg@izi.or.id,pendayagunaan@izi.or.id';
        }

        if ($this->owner->company_id == CompanyEnum::PKPU) {
            $this->_ramadhanEmail = 'admin.ramadhan@pkpu.or.id,pkpu.ramadhan@gmail.com';
        } else {
            $this->_ramadhanEmail = 'admin.ramadhan@izi.or.id';
        }

        if ($this->owner->company_id == CompanyEnum::PKPU) {
            $this->_qaqcEmail = 'divisi.quality@pkpu.or.id';
        } else {
            $this->_qaqcEmail = 'divisi.quality@pkpu.or.id,haryono@izi.or.id';
        }

        if ($this->owner->company_id == CompanyEnum::IZI) {
            $this->_bksEmail = "m.suharsono@izi.or.id";
        }

        // To Email
        if ($this->owner->Creator && ($this->owner->company_id == CompanyEnum::PKPU)) {
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
                $this->to_email = $this->getToEmailByEmployeeId(289481);
            } elseif ($this->owner->fund_driven_id == FundDrivenEnum::RKAT) {
                $this->to_email = $this->getToEmailByEmployeeId(108087);
            } elseif ($type == EmailQueueStatusEnum::TYPE_IPP_APPROVE) {
                $this->to_email = $this->setToEmailAsUndefined();
            } elseif (!is_null($employeeId)) {
                $this->to_email = $this->getToEmailByEmployeeId($employeeId);
            } else {
                $this->to_email = $this->setToEmailAsUndefined();
            }
        } else {
            $this->to_email = $this->setToEmailAsUndefined();
        }
    }

    /**
     * Set To Email From Branch
     * @param  int $branchId Branch ID
     * @return void
     */
    protected function defineToEmailFromBranch($branchId)
    {
        $recipients = EmailRecipient::model()->findAllByAttributes(array('branch_id'=>$branchId));
        if (!is_array($recipients) || count($recipients)==0) {
            return null;
        }

        foreach ($recipients as $recipient) {
            $pkpuEmail = $recipient->Employee->getPkpuEmail();
            switch ($recipient->role_id) {
                case Role::Keuangan:
                    $this->_keuEmail .= $pkpuEmail;
                    break;
                case Role::Eksekutor: //Staff Pdg
                    $this->_pdgEmail .= $pkpuEmail;
                    break;
                case Role::Ramadhan:
                    break;
                case Role::QAQC:
                    $this->_qaqcEmail .= $pkpuEmail;
                    break;
                case Role::BKS:
                    $this->_bksEmail .= $pkpuEmail;
                    break;
                case Role::Kemitraan:
                    $this->_kmtEmail .= $pkpuEmail;
                    break;
                case Role::PDG: //Kabid Pdg
                    $this->to_email .= $pkpuEmail;
                    break;
            }
        }

        // if (!isset($this->to_email)) $this->to_email =
    }

    /**
     * Get PKPU Email From Employee ID
     * @param  int 		$id 	Employee ID
     * @return string  			Email
     */
    protected function getToEmailByEmployeeId($id)
    {
        $pic = SdmEmployee::model()->findByPk($id);
        $this->_picEmail = $pic->pkpuEmail;
        $this->_picEmail .= ",{$pic->email}";
        return $this->_picEmail;
    }

    /**
     * Set To Email By PIC Per Program
     */
    protected function setToEmailAsUndefined()
    {
        if (($Program = $this->owner->Program) && ($Pic = $this->owner->Program->Pic)) {
            $this->_picEmail = $Pic->pkpuEmail;
            if ($Program->parent_id != Program::DRM) {
                $this->_picEmail .= ",{$Pic->email}";
            }
            $this->to_email  = $this->_picEmail;

            if (null !== ($Manager = ViewEmployeeManager::model()->findManagerById($Pic->id))) {
                $this->to_email .= ",{$Manager->pkpuEmail}";
            }
        } else {
            $this->to_email = Yii::app()->user->email;
        }

        return $this->to_email;
    }

    /**
     * Create Queue for Ipp Create
     * @return bool
     */
    protected function notifyIppCreate()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-{$this->year}]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} telah dibuat";

        $this->queue = $this->queueFactory($subject);
        $this->queue->to_email = $this->to_email;
        $this->queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $this->queue->param_data = json_encode($this->param_data);
        $this->queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
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

        if ($this->test) {
            return;
        }
        return $this->queue->save();
    }

    /**
     * Create Queue for Ipp Approve By Keuangan
     * @return bool
     */
    protected function notifyIppApproveByKeu()
    {
        // $subject = "[RMD-1437H] IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} di-APPROVE Keuangan";
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-{$this->year}]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} di-APPROVE Keuangan";

        $this->queue = $this->queueFactory($subject);
        $this->queue->to_email = $this->to_email;
        $this->queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $this->queue->param_data = json_encode($this->param_data);
        $this->queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
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
			Dibuat oleh 			: ".$this->param_data['pembuat']."<br/>
			Diapprove oleh 			: ".$this->param_data['approver']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        if ($this->test) {
            return;
        }
        return $this->queue->save();
    }

    /**
     * Create Queue for Ipp Reject By Keuangan
     * @return bool
     */
    protected function notifyIppRejectByKeu()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-{$this->year}]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} DITOLAK Keuangan";

        $this->queue = $this->queueFactory($subject);
        $this->queue->to_email = $this->to_email;
        $this->queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $this->queue->param_data = json_encode($this->param_data);
        $this->queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
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

        if ($this->test) {
            return;
        }
        return $this->queue->save();
    }

    /**
     * Create Queue for Ipp Accept By PDG
     * @return bool
     */
    protected function notifyIppAcceptByPdg()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-{$this->year}]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} di-APPROVE Tim Pelaksana";

        $this->queue = $this->queueFactory($subject);
        $this->queue->to_email = $this->to_email;
        $this->queue->cc_email = "{$this->_marketerEmail},{$this->_userEmail},{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_keuEmail},{$this->_mgrMarketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        $this->queue->param_data = json_encode($this->param_data);
        $this->queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
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

        if ($this->test) {
            return;
        }
        return $this->queue->save();
    }

    /**
     * Create Queue for Ipp New Comment
     * @return bool
     */
    protected function notifyIppComment()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-{$this->year}]";
        $subject .= " IPP#{$this->param_data[ipp_no]} {$this->param_data[title]} Komentar terbaru";

        $this->queue = $this->queueFactory($subject);

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->queue->cc_email .= ",{$this->_ramadhanEmail}";
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

        $this->queue->to_email   = $comment->recipent->pkpuEmail;
        $this->queue->cc_email   = $comment->recipent->email;
        $this->queue->param_data = json_encode($this->param_data);
        $this->queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			".$comment->creator->full_name." telah menambahkan komentar di IPP yang Anda ikuti<br/><br/>
			<p>".$comment->content."</p><br/>
			Silakan klik <a href=\"http://project.c27g.com/ipp/view/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat IPP.
			Login dengan menggunakan username dan password intranet anda. 
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        if ($this->test) {
            return;
        }
        return $this->queue->save();
    }
}
