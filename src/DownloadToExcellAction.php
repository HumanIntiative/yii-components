<?php

class DownloadToExcellAction extends CAction
{
    public $title = '';

    protected $employeeId;
    protected $payrollMonth;
    protected $xl;
    protected $data;
    protected $date1;
    protected $date2;
    protected $name;

    protected function fetchData()
    {
        $this->data = DebtTransaction::model()->findAll(array(
            'with'=>array('employee'),
            'condition'=>'employee_id=:empId and transaction_date>=:date1 and transaction_date<=:date2',
            'params'=>array(':empId'=>$this->employeeId, ':date1'=>$this->date1, ':date2'=>$this->date2),
            'order'=>'employee.full_name,t.transaction_date desc',
        ));
    }

    protected function loadExtension()
    {
        // ini_set('memory_limit', '512M');
        date_default_timezone_set('Asia/Jakarta');
        // setlocale(LC_TIME, 'id_ID.UTF8', 'id.UTF8', 'id_ID.UTF-8', 'id.UTF-8');

        Yii::import('application.extensions.PHPExcel', true);
    }

    protected function createSpreadsheet()
    {
        $this->xl = new PHPExcel;
        // Set properties
        $this->xl
        ->getProperties()->setCreator("IT Dev")
        ->setLastModifiedBy("IT Development Team")
        ->setTitle($this->title)
        ->setSubject($this->title)
        ->setKeywords($this->title)
        ->setCategory("Download");
    }

    protected function formatingHeader()
    {
        $worksheet = $this->xl->getActiveSheet();

        //Page Setup
        $worksheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $worksheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        //Page Margin
        $worksheet->getPageMargins()->setRight(0);
        $worksheet->getPageMargins()->setLeft(0.3);

        //Header Footer
        $objDrawing = new PHPExcel_Worksheet_HeaderFooterDrawing();
        $worksheet->getHeaderFooter()->addImage($objDrawing, PHPExcel_Worksheet_HeaderFooter::IMAGE_HEADER_LEFT);
        $worksheet->getHeaderFooter()->setOddHeader('&L&G&C&HPKPU - Lembaga Kemanusiaan Nasional.');
        $worksheet->getHeaderFooter()->setOddFooter('&L&B' . $this->xl->getProperties()->getTitle() . '&RPage &P of &N');

        //Style
        $worksheet->getStyle('C1')->applyFromArray(array('font'=>array('bold'=>true)));
        $worksheet->getStyle('C2')->applyFromArray(array('font'=>array('bold'=>true)));
        $worksheet->getStyle('A6:H6')->applyFromArray(array('font'=>array('bold'=>true)));
        $worksheet->getStyle('A6:H6')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $worksheet->getStyle('A6:H6')->getFill()->getStartColor()->setRGB('EAEAEA');

        //Column setWidth
        $worksheet->getColumnDimension('A')->setWidth(4);
        $worksheet->getColumnDimension('B')->setWidth(28);
        $worksheet->getColumnDimension('C')->setWidth(15);
        $worksheet->getColumnDimension('D')->setWidth(14);
        $worksheet->getColumnDimension('E')->setWidth(14);
        $worksheet->getColumnDimension('F')->setWidth(14);
        $worksheet->getColumnDimension('G')->setWidth(35);
        $worksheet->getColumnDimension('H')->setWidth(17);

        //Set Logo PKPU
        $objDrawing = new PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('PHPExcel logo');
        $objDrawing->setDescription('PHPExcel logo');
        $objDrawing->setPath('/home/hrm/public_html/images/logo-pkpu.png'); // filesystem reference for the image file
        $objDrawing->setHeight(36);			// sets the image height to 36px (overriding the actual image height);
        $objDrawing->setCoordinates('B1');	// pins the top-left corner of the image to cell D24
        $objDrawing->setOffsetX(10);		// pins the top left corner of the image at an offset of 10 points horizontally to the right of the top-left corner of the cell
        $objDrawing->setWorksheet($worksheet);

        //Header Title
        $this->xl->setActiveSheetIndex(0);
        $worksheet->setCellValue('C1', "PKPU - LEMBAGA KEMANUSIAAN NASIONAL");
        $worksheet->setCellValue('C2', "LAPORAN PINJAMAN KARYAWAN");
        $worksheet->setCellValue('B4', "Tanggal   :  ".date_format(date_create($this->date1), "d M Y").' s/d '.
            date_format(date_create($this->date2), "d M Y"));
        
        //Row Header
        $worksheet->setCellValue('A6', "No");
        $worksheet->setCellValue('B6', "Nama Karyawan");
        $worksheet->setCellValue('C6', "Tanggal");
        $worksheet->setCellValue('D6', "Kredit");
        $worksheet->setCellValue('E6', "Debit");
        $worksheet->setCellValue('F6', "Saldo");
        $worksheet->setCellValue('G6', "Keterangan");
        $worksheet->setCellValue('H6', "Tipe Pembayaran");
            
        // Freeze panes
        $worksheet->freezePane('A7');

        // Rows to repeat at top
        $worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);
    }

    protected function generateRowData($rowData, $no)
    {
        $rowCell = ($no+6);
        $worksheet = $this->xl->getActiveSheet();

        $worksheet->getRowDimension($rowCell)->setRowHeight(20);
        $worksheet->getStyle('A'.$rowCell.':G'.$rowCell)->getAlignment()->setWrapText(true);
        $worksheet->getStyle('A'.$rowCell.':G'.$rowCell)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $worksheet->setCellValue('A'.$rowCell, $no);
        $worksheet->setCellValue('B'.$rowCell, $rowData->employee->full_name);
        $worksheet->setCellValue('C'.$rowCell, date_format(date_create($rowData->transaction_date), "d M Y"));
        $worksheet->setCellValue('D'.$rowCell, $rowData->credit);
        $worksheet->setCellValue('E'.$rowCell, $rowData->debit);
        $worksheet->setCellValue('F'.$rowCell, $rowData->balance);
        $worksheet->setCellValue('G'.$rowCell, $rowData->employeeDebt->type);
        $worksheet->setCellValue('H'.$rowCell, $rowData->paymentType);
    }

    protected function output($filename)
    {
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $this->xl->setActiveSheetIndex(0);
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($this->xl, 'Excel5');
        $writer->save('php://output');
    }

    protected function generateReport()
    {
        // Fetch Data From Database
        $this->fetchData();
        // Load Extension etc
        $this->loadExtension();
        // Create Excel Instance
        $this->createSpreadsheet();
        // Formating Worksheet Header
        $this->formatingHeader();
        // Generate Row Data
        foreach ($this->data as $row) {
            $this->generateRowData($row, ++$no);
        }
        // Output to Browser
        $this->output('Transaksi Pinjaman '.$this->name.' ('.
            date_format(date_create($this->date1), "d M Y").' s/d '.
            date_format(date_create($this->date2), "d M Y").').xls');
    }

    public function run()
    {
        if (null !== ($post = Yii::app()->request->getPost('EmployeeDebt'))) {
            // Variables
            $this->employeeId   = $post['employee_id'];
            //$this->payrollMonth = $post['debt_date'];
            $pecahTgl1 = explode(" - ", $post['debt_date']);

            // membaca bagian-bagian dari $pecahTgl1
            $this->date2 = $pecahTgl1[1];
            $this->date1 = $pecahTgl1[0];

            $employee=SdmEmployee::model()->findByPk($this->employeeId);
            $this->name = $employee->full_name;
            // To Excel
            $this->generateReport();
            Yii::app()->end();
        }

        $model = new EmployeeDebt;
        $this->controller->render('export', compact('model'));
    }
}
