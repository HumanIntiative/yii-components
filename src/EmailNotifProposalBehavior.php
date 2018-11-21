<?php

class EmailNotifProposalBehavior extends CBehavior implements EmailNotifInterface
{
    public $type;
    public $param_data;
    public $to_email;

    protected $_queue;

    protected $_picEmail;
    protected $_donaturEmail;
    protected $_marketerEmail;
    protected $_managerEmail;
    protected $_BODEmail;
    protected $_pdgEmail;
    protected $_ramadhanEmail;
    protected $_authorEmail;
    protected $_qaqcEmail;
    protected $_prodevEmail;

    public function getQueue()
    {
        return $this->_queue;
    }

    /**
     * Send Email Notify to Database
     * @param  EmailQueueStatusEnum	$type  Enum
     * @return bool
     */
    public function notifyEmail($type)
    {
        if (!$this->owner instanceof Proposal) {
            return false;
        }

        $this->type = $type;
        $this->param_data = array(
            'id'          => $this->owner->id,
            'title'       => $this->owner->title,
            'proposal_no' => substr($this->owner->proposal_no, 3),
            'program'     => $this->owner->program ? $this->owner->program->program_name : "-",
            'donatur'     => $this->owner->donatur ? $this->owner->donatur->full_name : "-",
            'marketer'    => $this->owner->marketer ? $this->owner->marketer->full_name : "-",
            'nilai'       => $this->owner->amount ? "Rp. ".number_format($this->owner->amount) : "-",
            'nilai_x'     => $this->owner->actual_amount ? "Rp. ".number_format($this->owner->actual_amount) : "-",
            'tanggal'     => date("d-m-Y"),
            'deadline'    => $this->owner->target_date ? date("d-m-Y", strtotime($this->owner->target_date)) : "-",
            'pembuat'     => $this->owner->creator ? $this->owner->creator->full_name : "-",
            'author'	  => $this->owner->author ? $this->owner->author->full_name : "-",
        );

        // Set Email
        $this->_pdgEmail      = "ivansatria@pkpu.or.id,Osy.utami@pkpu.or.id,syakur@pkpu.or.id";
        $this->_ramadhanEmail = "admin.ramadhan@pkpu.or.id,pkpu.ramadhan@gmail.com";
        $this->_qaqcEmail     = "divisi.quality@pkpu.or.id,deden.selamet@pkpu.or.id,respati.oktaviani@pkpu.or.id";
        $this->_BODEmail      = "rullybarlian@pkpu.or.id,nana.sudiana@pkpu.or.id";
        $this->_prodevEmail   = "ari.djanuar@pkpu.or.id";

        $this->_donaturEmail  = $this->owner->donatur->email;
        $this->_marketerEmail = $this->owner->marketer->getPkpuEmail();
        $this->_managerEmail  = $this->owner->marketer->managerModel ? $this->owner->marketer->managerModel->getPkpuEmail() : null; //TODO
        $this->_authorEmail   = $this->owner->author ? $this->owner->author->getPkpuEmail() : null;
        // End

        $this->to_email = $this->setToEmailAsPIC();
        if ($type == EmailQueueStatusEnum::TYPE_PROPOSAL_SENTTODONATUR) {
            $this->to_email = $this->_donaturEmail;
        }

        switch ($type) {
            // CREATE
            case EmailQueueStatusEnum::TYPE_PROPOSAL_CREATE:
                return $this->notifyProposalCreate();
                break;
            case EmailQueueStatusEnum::TYPE_PROPOSAL_TYPEEMERGENCY:
                return $this->notifyProposalCreateEmergency();
                break;
            // REVIEW
            case EmailQueueStatusEnum::TYPE_PROPOSAL_REVIEW_APPROVE:
                return $this->notifyProposalReviewApprove();
                break;
            case EmailQueueStatusEnum::TYPE_PROPOSAL_REVIEW_REJECTED:
                return $this->notifyProposalReviewReject();
                break;
            case EmailQueueStatusEnum::TYPE_PROPOSAL_REVIEW_REVISION:
                return $this->notifyProposalReviewRevision();
                break;
            // VERIFY
            case EmailQueueStatusEnum::TYPE_PROPOSAL_VERIFY_APPROVE:
                return $this->notifyProposalVerifyApprove();
                break;
            case EmailQueueStatusEnum::TYPE_PROPOSAL_VERIFY_REJECTED:
                return $this->notifyProposalVerifyReject();
                break;
            case EmailQueueStatusEnum::TYPE_PROPOSAL_VERIFY_REVISION:
                return $this->notifyProposalVerifyRevision();
                break;
            // DONOR
            case EmailQueueStatusEnum::TYPE_PROPOSAL_SENTTODONATUR:
                return $this->notifyProposalSentToDonor();
                break;
            // KOMENTAR
            case EmailQueueStatusEnum::TYPE_PROPOSAL_SENTCOMMENT:
                return $this->notifyProposalSentComment();
            // FEE MANAGEMENT
            case EmailQueueStatusEnum::TYPE_PROPOSAL_FEEMGMNT:
                return $this->notifyProposalFeeManagement();
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
        $queue->to_email       = $this->to_email;

        return $queue;
    }

    protected function setToEmailAsPIC()
    {
        if (($Program = $this->owner->program) && ($Pic = $this->owner->program->Pic)) {
            $this->_picEmail = $Pic->pkpuEmail;
            if ($Program->parent_id != Program::DRM) {
                $this->_picEmail .= ",{$Pic->email}";
            }
            $to_email  = $this->_picEmail;

            if (null !== ($Manager = ViewEmployeeManager::model()->findManagerById($Pic->id))) {
                $to_email .= ",{$Manager->pkpuEmail}";
            }
        } else {
            $to_email = Yii::app()->user->email;
        }

        return $to_email;
    }

    /**
     * Create Queue for Proposal Create
     * @param  EmailQueueStatusEnum $type  Enum
     * @return bool
     */
    protected function notifyProposalCreate()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah dibuat";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->to_email = 'deden.selamet@pkpu.or.id,respati.oktaviani@pkpu.or.id';
        $this->_queue->cc_email = 'divisi.quality@pkpu.or.id';

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/>
			Pemberitahuan Permintaan Proposal, dengan rincian:
			<br/><br/>
			Judul Proposal  : ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal Create Emergency
     * @return bool
     */
    protected function notifyProposalCreateEmergency()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal Emergency#{$this->param_data[proposal_no]} {$this->param_data[title]} telah dibuat";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Pemberitahuan Permintaan Proposal Emergency, dengan rincian: 
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melakukan proses review Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal After Review by PDG
     * @return bool
     */
    protected function notifyProposalReviewApprove()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah direview dan disetujui";

        $files = array();
        foreach ($this->owner->files as $file) { //ProposalFile
            if ($file->file_type == ProposalFile::TYPE_DOCUMENT) {
                $files[] = $file->file_id;
            }
        }

        /**
         * To Author / Pembuat Proposal
         */
        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";
        
        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah direview dan disetujui PDG.<br/>
			Anda telah ditunjuk sebagai Pembuat Proposal.<br/>
			Rincian proposal :
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";
        $this->_queue->list_files = json_encode($files);

        $result += $this->_queue->save() ? 1 : 0;
        unset($this->_queue);

        /**
         * To Marketer
         */
        $this->_queue = $this->queueFactory($subject);
        $this->_queue->to_email = $this->_marketerEmail;

        $this->_queue->param_data = json_encode($param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah direview dan disetujui PDG.<br/>
			Rincian proposal :
			<br/><br/>
			Judul Proposal	: ".$param_data['title']." <br/>
			Program			: ".$param_data['program']." <br/>
			Donatur			: ".$param_data['donatur']." <br/>
			Marketer		: ".$param_data['marketer']." <br/>
			Nilai Proyek	: ".$param_data['nilai']." <br/>
			Tgl deadline    : ".$param_data['deadline']." <br/>
			Tgl permintaan  : ".$param_data['tanggal']." <br/>
			Dibuat oleh		: ".$param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";
        $this->_queue->list_files = json_encode($files);

        $result += $this->_queue->save() ? 1 : 0;
        unset($this->_queue);

        /**
         * To Manager Marketer
         */
        $this->_queue = $this->queueFactory($subject);
        $this->_queue->to_email = $this->_managerEmail;

        $this->_queue->param_data = json_encode($param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah direview dan disetujui PDG.<br/>
			Rincian proposal :
			<br/><br/>
			Judul Proposal	: ".$param_data['title']." <br/>
			Program			: ".$param_data['program']." <br/>
			Donatur			: ".$param_data['donatur']." <br/>
			Marketer		: ".$param_data['marketer']." <br/>
			Nilai Proyek	: ".$param_data['nilai']." <br/>
			Tgl deadline    : ".$param_data['deadline']." <br/>
			Tgl permintaan  : ".$param_data['tanggal']." <br/>
			Dibuat oleh		: ".$param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";
        $this->_queue->list_files = json_encode($files);

        $result += $this->_queue->save() ? 1 : 0;
        unset($this->_queue);

        return ($result == 3);
    }

    /**
     * Create Queue for Proposal After Review Reject by PDG
     * @return bool
     */
    protected function notifyProposalReviewReject()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah direview dan ditolak";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}"; //,{$this->_BODEmail}
        
        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah direview dan ditolak PDG.<br/>
			Rincian proposal :
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal After Review Revisi by PDG
     * @return bool
     */
    protected function notifyProposalReviewRevision()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah direview dan diminta Revisi";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah direview oleh PDG.<br/>
			Silakan lakukan revisi terhadap permintaan proposal.<br/>
			Rincian proposal :
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal After Verify by Qaqc
     * @return bool
     */
    protected function notifyProposalVerifyApprove()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah diverifikasi dan disetujui";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah diverifikasi dan disetujui QAQC.<br/>
			Rincian Proposal:
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";
        $this->_queue->list_files = json_encode($files);

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal After Verify Reject by Qaqc
     * @return bool
     */
    protected function notifyProposalVerifyReject()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah diverifikasi dan ditolak";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah diverifikasi dan ditolak QAQC.<br/>
			Rincian Proposal:
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal After Verify Revision by Qaqc
     * @return bool
     */
    protected function notifyProposalVerifyRevision()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah diverifikasi dan diminta Revisi";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Permintaan proposal telah diverifikasi dan dimintakan revisi oleh QAQC.<br/>
			Silakan lakukan revisi terhadap permintaan proposal.<br/>
			Rincian Proposal:
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal Sent To Donor
     * @return bool
     */
    protected function notifyProposalSentToDonor()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} telah dibuat";

        $files = array();
        foreach ($this->owner->files as $file) { //ProposalFile
            // if ($file->file_type == ProposalFile::TYPE_DOCUMENT)
            if ($file->file_type == ProposalFile::TYPE_ATTACHMENT) {
                $files[] = $file->file_id;
            }
        }

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->to_email = $this->_donaturEmail;
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Proposal Anda telah dibuat.<br/>
			Rincian Proposal:
			<br/><br/>
			Judul Proposal	: ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']."
			<br/><br/>
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";
        $this->_queue->list_files = json_encode($files);

        return $this->_queue->save();
    }

    /**
     * Create Queue for Proposal Create
     * @param  EmailQueueStatusEnum $type  Enum
     * @return bool
     */
    protected function notifyProposalSentComment()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} Komentar terbaru";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->cc_email = "{$this->_marketerEmail},{$this->_managerEmail}";

        if (null !== $this->owner->author) {
            $this->_queue->to_email .= "," . $this->owner->author->getPkpuEmail();
        }

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        // Search Comment
        $criteria = new CDbCriteria;
        $criteria->compare('proposal_id', $this->owner->id);
        $criteria->order = 'id DESC';
        $criteria->limit = 1;
        $comment = ProposalComment::model()->find($criteria);

        if (null === $comment) {
            return false;
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			".$comment->creator->full_name." telah menambahkan komentar di Proposal yang Anda ikuti<br/><br/>
			<p>".$comment->content."</p><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $this->_queue->save();
    }

    protected function notifyProposalFeeManagement()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1437H]" : "[Proyek-2016]";
        $subject .= " Proposal#{$this->param_data[proposal_no]} {$this->param_data[title]} Permintaan Pembuatan Proposal";

        $this->_queue = $this->queueFactory($subject);
        $this->_queue->to_email = $this->_authorEmail;
        $this->_queue->cc_email = 'divisi.quality@pkpu.or.id';

        if (Program::isRamadhan($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        if (Program::isCustom($this->owner->program_id)) {
            $this->_queue->cc_email .= ",{$this->_prodevEmail}";
        }

        $this->_queue->param_data = json_encode($this->param_data);
        $this->_queue->message    = "Assalamu'alaikum Wr. Wb.<br/>
			Pemberitahuan Permintaan Proposal, dengan rincian:
			<br/><br/>
			Judul Proposal  : ".$this->param_data['title']." <br/>
			Program			: ".$this->param_data['program']." <br/>
			Donatur			: ".$this->param_data['donatur']." <br/>
			Marketer		: ".$this->param_data['marketer']." <br/>
			Nilai Proyek	: ".$this->param_data['nilai']." <br/>
			Tgl deadline    : ".$this->param_data['deadline']." <br/>
			Tgl permintaan  : ".$this->param_data['tanggal']." <br/>
			Dibuat oleh		: ".$this->param_data['pembuat']." <br/>
			Catatan dari QC : ".$this->param_data['qaqc_notes']."
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/proposal/".$this->param_data['id']."\" target=\"_blank\">disini</a> untuk melihat Proposal.
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.
			<br/><br/>";

        return $this->_queue->save();
    }
}
