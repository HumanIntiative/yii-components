<?php

class EmailNotifPMPBehavior extends CBehavior implements EmailNotifInterface
{
    protected $_type;
    protected $param_data;

    protected $_keuEmail;
    protected $_pdgEmail;
    protected $_ramadhanEmail;
    protected $_bksEmail;
    protected $_picEmail;
    // protected $_authorEmail;
    protected $_qaqcEmail;
    protected $_marketerEmail;
    protected $_kemitraanEmail;

    /**
     * Send Email Notify to Database
     * @param  EmailQueueStatusEnum	$type  Enum
     * @return bool
     */
    public function notifyEmail($type)
    {
        if (!$this->owner instanceof Project) {
            return;
        }

        $this->_type = $type;
        $this->param_data = array(
            'id'           => $this->owner->id,
            'no'   		   => $this->owner->project_no,
            'name'		   => $this->owner->project_name,
            'tgl_fep'      => date("d-m-Y", strtotime($this->owner->project_date)),
            'tgl_eksekusi' => date("d-m-Y", strtotime($this->owner->realize_date_start)),
            'program'  	   => $this->owner->Program ? /*$this->owner->Program->program_name*/$this->owner->project_name : "-",
            'mitra'        => $this->owner->CPM ? $this->owner->CPM->partner->partner_name : "-",
            'pic'          => $this->owner->Pic ? $this->owner->Pic->full_name : "-",
            'pembuat'      => $this->owner->Creator ? $this->owner->Creator->full_name : "-",
            'lokasi'	   => $this->owner->getLocation(),
        );

        if ($this->owner->Pic) {
            $this->_picEmail = $this->owner->Pic->email;
            $this->_picEmail .= ",{$this->owner->Pic->pkpuEmail}";
        }

        $this->_marketerEmail = $this->owner->getIppMarketerEmails();
        // $this->_kemitraanEmail = $this->owner->getIppKemitraanEmails();

        // Set Email
        $this->_keuEmail      = "khudaybiyah@pkpu.or.id,fithri@pkpu.or.id";
        $this->_pdgEmail      = "admin.pdg@pkpu.or.id,pdgpkpu@gmail.com,ferry.suranto@pkpu.or.id";
        $this->_ramadhanEmail = "admin.ramadhan@pkpu.or.id,pkpu.ramadhan@gmail.com";
        $this->_qaqcEmail     = "divisi.quality@pkpu.or.id,respati.oktaviani@pkpu.or.id";
        $this->_bksEmail      = "m.suharsono@pkpu.or.id";

        switch ($type) {
            case EmailQueueStatusEnum::TYPE_PMP_CREATE:
                return $this->notifyPMPCreate();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_VERIFY_QAQC:
                return $this->notifyPMPVerifyQAQC();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_VERIFY_BKS:
                return $this->notifyPMPVerifyBKS();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_RUNNING:
                return $this->notifyPMPRunning(); //Not Yet Implemented
                break;
            case EmailQueueStatusEnum::TYPE_PMP_FINISH:
                return $this->notifyPMPFinish();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_UPLOADMPP:
                return $this->notifyPMPUploadMpp(); //Not Yet Implemented
                break;
            case EmailQueueStatusEnum::TYPE_PMP_VERIFYFINISH:
                return $this->notifyPMPVerifyFinish();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_ALERT_EXECUTION_DATE:
                return $this->notifyPMPAlertExecutionDate();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_COMMENT:
                return $this->notifyPMPComment();
                break;
            case EmailQueueStatusEnum::TYPE_PMP_CANCEL:
                return $this->notifPMPCancel();
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

    /**
     * Create Queue for PMP Create
     * @return bool
     */
    protected function notifyPMPCreate()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah dibuat";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_qaqcEmail}";

        // $queue->cc_email = "{$this->_pdgEmail},{$this->_picEmail},{$this->_marketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan telah dibuat PMP dengan rincian:
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP Verifyed By Qaqc
     * @return bool
     */
    protected function notifyPMPVerifyQAQC()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah diverifikasi";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_picEmail}";
        $queue->cc_email = "{$this->_qaqcEmail}";
        // $queue->cc_email = "{$this->_pdgEmail},{$this->_marketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa PMP telah diverifikasi oleh QAQC<br/>
			dengan rincian :
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP Verifyied By BKS
     * @return bool
     */
    protected function notifyPMPVerifyBKS()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah diverifikasi oleh BKS";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_bksEmail}";

        // $queue->cc_email = "{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_marketerEmail},{$this->_picEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa PMP telah diverifikasi oleh BKS<br/>
			dengan rincian :
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP Running
     * @return bool
     */
    protected function notifyPMPRunning()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah berjalan";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_picEmail}";

        $queue->cc_email = "{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_marketerEmail},{$this->_keuEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa PMP telah berjalan dengan rincian :
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP Set to Finish
     * @return bool
     */
    protected function notifyPMPFinish()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah terlaksana";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_qaqcEmail}";

        // $queue->cc_email = "{$this->_pdgEmail},{$this->_picEmail},{$this->_marketerEmail}"; //,$this->_kemitraanEmail;

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Mohon diperiksa Laporan Proyek dengan rincian :
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat Laporan. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP Verifyied as Finish
     * @return bool
     */
    protected function notifyPMPVerifyFinish()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah diverifikasi dan dinyatakan Finish";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_picEmail}";

        $queue->cc_email = "{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_marketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa PMP telah diverifikasi dan dinyatakan Finish<br/>
			dengan rincian :
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP Alert Execution Date
     * @return bool
     */
    protected function notifyPMPAlertExecutionDate()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} akan dieksekusi tanggal {$this->param_data[tgl_eksekusi]}";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_picEmail}";

        $queue->cc_email = "{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_marketerEmail},{$this->_keuEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Insya Allah hari ini akan dilaksanakan aksi sebagai berikut:
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }

    /**
     * Create Queue for PMP New Comment
     * @return bool
     */
    protected function notifyPMPComment()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} Komentar terbaru";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_picEmail}";

        $queue->cc_email = "{$this->_pdgEmail},{$this->_qaqcEmail},{$this->_marketerEmail},{$this->_keuEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }

        // Search Comment
        $criteria = new CDbCriteria;
        $criteria->compare('project_id', $this->owner->id);
        $criteria->order = 'id DESC';
        $criteria->limit = 1;
        $pmpComment = ProjectComment::model()->find($criteria);

        if (null === $pmpComment) {
            return false;
        }
        $comment = $pmpComment->comment;

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

    /**
     * Create Queue for PMP Create
     * @return bool
     */
    protected function notifyPMPCancel()
    {
        $subject = (Program::isRamadhan($this->owner->program_id)) ? "[RMD-1436H]" : "[Proyek-2015]";
        $subject .= " PMP#{$this->param_data[no]} {$this->param_data[name]} telah di BATAL kan";

        $queue = $this->queueFactory($subject);
        $queue->to_email = "{$this->_picEmail}";

        // $queue->cc_email = "{$this->_pdgEmail},{$this->_picEmail},{$this->_marketerEmail}";

        if (Program::isRamadhan($this->owner->program_id)) {
            $queue->cc_email .= ",{$this->_ramadhanEmail}";
        }
        
        $queue->param_data = json_encode($this->param_data);
        $queue->message    = "Assalamu'alaikum Wr. Wb.<br/><br/>
			Diberitahukan bahwa PMP dengan rincian sbb telah di BATAL kan: 
			<br/><br/>
			Nama Proyek		: ".$this->param_data['program']."<br/>
			Lokasi			: ".$this->param_data['lokasi']."<br/>
			Mitra           : ".$this->param_data['mitra']."<br/>
			Tgl eksekusi   	: ".$this->param_data['tgl_eksekusi']."<br/>
			Pelaksana 		: ".$this->param_data['pic']."<br/>
			Dibuat oleh     : ".$this->param_data['pembuat']."<br/>
			<br/><br/>
			Silakan klik <a href=\"http://project.c27g.com/project/".$this->param_data['id']."/\" target=\"_blank\">disini</a> untuk melihat PMP. 
			Login dengan menggunakan username dan password intranet anda.
			Terima kasih.
			<br/><br/>
			Wassalamu'alaikum Wr. Wb.";

        return $queue->save();
    }
}
