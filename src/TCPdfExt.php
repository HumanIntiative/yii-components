<?php
require_once(dirname(__FILE__).'/../extensions/tcpdf62/tcpdf.php');

class TCPdfExt
{
    private $tcPdf = null;

    /**
     * @param $orientacion: 0:Portlain, 1:Landscape
     * @param $unit: /cm/mm/
     * @param $format: /A3/A4/A5/Letter/Legal/array(w,h)
     */
    public function __construct($orientation, $unit, $format, $unicode, $encoding)
    {
        if ($orientation != 'P' && $orientation != 'L') {
            throw new CException(Yii::t('PKPUPdf', 'The orientation must be "P" or "L"'));
        }

        if (!in_array($unit, array('pt', 'mm', 'cm', 'in'))) {
            throw new CException(Yii::t('PKPUPdf', 'The unit must be "pt", "in", "cm" or "mm"'));
        }

        if (!is_string($format) && !is_array($format)) {
            throw new CException(Yii::t('PKPUPdf', 'The format must be string or array.'));
        }

        if (is_string($format)) {
            if (!in_array($format, array('A3', 'A4', 'A5', 'Letter', 'Legal'))) {
                throw new CException(Yii::t('PKPUPdf', 'The format must be one of A3, A4, A5, Letter or Legal'));
            } elseif (!is_numeric($format[0]) && !is_numeric($format[1])) {
                throw new CException(Yii::t('PKPUPdf', 'The format must be array(w, h)'));
            }
        }

        if (!is_bool($unicode)) {
            throw new CException(Yii::t('PKPUPdf', '"unicode" must be a boolean value'));
        }

        $this->tcPdf = new TCPdfCostume($orientation, $unit, $format, $unicode, $encoding);
        define("K_PATH_CACHE", Yii::app()->getRuntimePath());
    }

    public function __call($method, $params)
    {
        if (is_object($this->tcPdf) && get_class($this->tcPdf)==='TCPdfCostume') {
            return call_user_func_array(array($this->tcPdf, $method), $params);
        } else {
            throw new CException(Yii::t('PKPUPdf', 'Can not call a method of a non existent object'));
        }
    }

    public function __set($name, $value)
    {
        if (is_object($this->tcPdf) && get_class($this->tcPdf)==='TCPdfCostume') {
            $this->tcPdf->$name = $value;
        } else {
            throw new CException(Yii::t('PKPUPdf', 'Can not set a property of a non existent object'));
        }
    }

    public function __get($name)
    {
        if (is_object($this->tcPdf) && get_class($this->tcPdf)==='TCPdfCostume') {
            return $this->tcPdf->$name;
        } else {
            throw new CException(Yii::t('PKPUPdf', 'Can not access a property of a non existent object'));
        }
    }

    public function __sleep()
    {
    }

    public function __wakeup()
    {
    }
}

class TCPdfCostume extends TCPDF
{
    public function formatDate($t=null)
    {
        if ($t===null) {
            $t = time();
        }
        $n = date('n', $t);
        $w = date('w', $t);
        $idMonth = DateTimeHelper::getMonthNames()[$n];
        $idDay   = DateTimeHelper::getDayNames()[$w];

        return $idDay.date(", j ", $t).$idMonth.date(" Y H:i", $t);
    }

    public function DownloaderInfo()
    {
        if (!Yii::app()->user->isGuest) {
            return 'Diunduh oleh '.Yii::app()->user->name.' @ '.$this->formatDate();
        } else {
            return 'Diunduh @ '.$this->formatDate();
        }
    }

    public function Footer()
    {
        $this->SetFont('helvetica', 'I', 10);

        $cur_y = $this->y;
        $this->SetTextColor(0, 0, 0);
        //set style for cell border
        $line_width = 0.85 / $this->k;
        $this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        //print document barcode
        $barcode = $this->getBarcode();
        if (!empty($barcode)) {
            $this->Ln($line_width);
            $barcode_width = round(($this->w - $this->original_lMargin - $this->original_rMargin) / 3);
            $style = array(
                'position' => $this->rtl?'R':'L',
                'align' => $this->rtl?'R':'L',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitalign' => '',
                'border' => false,
                'padding' => 0,
                'fgcolor' => array(0,0,0),
                'bgcolor' => false,
                'text' => false
            );
            $this->write1DBarcode($barcode, 'C128', '', $cur_y + $line_width, '', (($this->footer_margin / 3) - $line_width), 0.3, $style, '');
        }
        if (empty($this->pagegroups)) {
            $pagenumtxt = $this->DownloaderInfo().' | '.$this->l['w_page'].' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
        } else {
            $pagenumtxt = $this->DownloaderInfo().' | '.$this->l['w_page'].' '.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
        }
        $this->SetY($cur_y);
        //Print page number
        if ($this->getRTL()) {
            $this->SetX($this->original_rMargin);
            $this->Cell(0, 0, $pagenumtxt, 'T', 0, 'L');
        } else {
            $this->SetX($this->original_lMargin);
            $this->Cell(0, 0, $this->getAliasRightShift().$pagenumtxt, 'T', 0, 'R');
        }
    }
}
