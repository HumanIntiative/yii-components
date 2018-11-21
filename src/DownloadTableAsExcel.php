<?php

class DownloadTableAsExcel extends CComponent
{
    public $headers = [];
    public $data = [];
    public $options = [];

    public function init()
    {
        parent::init();

        // ini_set('memory_limit', '512M');
        //Yii::import('application.extensions.PHPExcel', true);
        //YIi::import('application.components.helper.*');
        Yii::import('application.vendor.phpoffice.phpexcel.Classes.PHPExcel', true);
        // Yii::import('application.extensions.PHPExcel.Style.Border');
        // Yii::import('application.extensions.PHPExcel.Style.Alignment');
        // Yii::import('application.extensions.PHPExcel.Worksheet.PageSetup');
        // Yii::import('application.extensions.PHPExcel.Worksheet.HeaderFooter');
    }

    public function create($saveAsFile=true, $filename=null)
    {
        /*if ($saveAsFile && is_null($filename))
            throw new CException("Please specify the filename", 22);*/

        $opt = $this->mergeOptions($this->options);

        $excel = new PHPExcel;

        // Set properties
        $excel->getProperties()
            ->setCreator($opt['creator'])
            ->setLastModifiedBy($opt['lastModifiedBy'])
            ->setTitle($opt['title'])
            ->setSubject($opt['subject'])
            ->setDescription($opt['desc'])
            ->setKeywords($opt['keywords'])
            ->setCategory($opt['category']);

        // Select ActiveSheet
        $worksheet = $excel->getActiveSheet();
        $excel->setActiveSheetIndex(0);

        // Page Setup
        $worksheet->getPageSetup()->setOrientation($opt['page_orientation']);
        $worksheet->getPageSetup()->setPaperSize($opt['page_size']);
        $worksheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);

        // Page Margins
        $worksheet->getPageMargins()->setRight($opt['margin_right']);
        $worksheet->getPageMargins()->setLeft($opt['margin_left']);

        // Header Footer
        $worksheet->getHeaderFooter()->addImage(new PHPExcel_Worksheet_HeaderFooterDrawing(), $opt['header_imagePosition']);
        $worksheet->getHeaderFooter()->setOddHeader("&L&G&C&H{$opt[company]}");
        $worksheet->getHeaderFooter()->setOddFooter("&L&B{$opt[title]}&RPage &P of &N");

        // Range Style
        $worksheet->getStyle($opt['style_fillRange'])->applyFromArray($opt['style_fontBold']);
        $worksheet->getStyle($opt['style_fillRange'])->getFill()->setFillType($opt['header_fillType']);
        $worksheet->getStyle($opt['style_fillRange'])->getFill()->getStartColor()->setRGB($opt['header_fillColor']);

        // Set Width
        foreach ($this->headers as $header) {
            $worksheet->getColumnDimension('A')->setWidth(4);
            $worksheet->getColumnDimension($header['column'])->setWidth($header['width']);
        }

        $excel->setActiveSheetIndex(0);
        foreach ($this->headers as $header) {
            $worksheet->setCellValue('A1', 'No');
            $worksheet->setCellValue($header['column'].'1', $header['title']);
        }

        // Freeze panes
        $worksheet->freezePane($opt['cell_freezePane']);

        $worksheet->getRowDimension($a)->setRowHeight($opt['data_rowHeight']);
        $worksheet->getStyle("A{$a}:{$opt[column_lastChar]}{$a}")->getAlignment()->setWrapText(true);
        $worksheet->getStyle("A{$a}:{$opt[column_lastChar]}{$a}")->getAlignment()->setVertical($opt['data_verticalAlignment']);
        $worksheet->getStyle("A{$a}:{$opt[column_lastChar]}{$a}")->applyFromArray($opt['style_borderBottom']);

        // Data
        $a=3;
        $no=1;
        foreach ($data as $row) {
            $worksheet->setCellValue('A'.$a, $no);
            foreach ($this->headers as $index => $header) {
                $worksheet->setCellValue($header['column'].$a, $row[$index][$header['fieldname']]);
            }
            $a++;
            $no++;
        }

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $excel->setActiveSheetIndex(0);
        $today = date($opt['date_format']);

        if (is_null($filename)) {
            $filename = "{$opt[filename_prepend]} {$today}.xls";
        }

        if (!$saveAsFile) {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment;filename=\"{$filename}\"");
            header("Cache-Control: max-age=0");
            $output = 'php://output';
        } else {
            $output = $filename;
        }

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($output);
    }

    protected function getDefaultOptions()
    {
        return array(
            // General Options
            'company'=>'PKPU - Lembaga Kemanusiaan Nasional.',
            'creator'=>'IT Dev',
            'title'=>'Download - Mulia Project',
            'category'=>'Download',
            'lastModifiedBy'=>'IT Dev Team',
            'filename_prepend'=>'Export Ipp',
            'date_format'=>'Y-m-d_his',
            // Component Options
            'style_fontBold'=>array('font'=>array('bold'=>true)),
            'style_borderBottom'=>array(
                'borders' => array(
                    'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'F555753')),
                ),
            ),
            'style_fillRange'=>'A1:BP1',
            'column_lastChar'=>'BP',
            'page_orientation'=>PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE,
            'page_size'=>PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4,
            'margin_left'=>0.3,
            'margin_right'=>0,
            'header_imagePosition'=>PHPExcel_Worksheet_HeaderFooter::IMAGE_HEADER_LEFT,
            'header_fillType'=>PHPExcel_Style_Fill::FILL_SOLID,
            'header_fillColor'=>'EAEAEA',
            'cell_freezePane'=>'A2',
            'data_rowHeight'=>20,
            'data_verticalAlignment'=>PHPExcel_Style_Alignment::VERTICAL_CENTER,
        );
    }

    protected function mergeOptions($options)
    {
        $defOptions = $this->getDefaultOptions();

        foreach ($options as $optK => $optV) {
            $defOptions[$optK] = $optV;
        }

        return $defOptions;
    }
}
