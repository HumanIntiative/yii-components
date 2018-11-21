<?php

class DateTimeHelper extends CComponent
{
    public static function getMonthNames()
    {
        return Yii::app()->locale->getMonthNames();
    }

    public static function getDayNames()
    {
        return [
            0 => 'Ahad',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
    }

    public static function getHolidays($year, $month)
    {
        $rows = Holidays::model()->findAll(array(
            'condition'=>'EXTRACT(YEAR FROM holiday_date)::INTEGER=:year AND EXTRACT(MONTH FROM holiday_date)::INTEGER=:month',
            'params'=>array(':year'=>$year,':month'=>$month),
        ));

        $days = array_map(function ($day) {
            return new DateTime($day->holiday_date);
        }, $rows);

        return $days;
    }

    private static function getWorkDays(DateTimeInterface $start, $days=14)
    {
        $interval = new DateInterval('P1D');
        $end = $start->add(new DateInterval("P{$days}D"));
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
            $dates[] = $date;
        }

        $year = $start->format('Y');
        $month = $start->format('n');
        $holidays = self::getHolidays($year, $month);
        if (($start->format('j') + $days) >= 30) {
            if ($month == 12) {
                $year++;
            } else {
                $month++;
            }
            array_merge($holidays, self::getHolidays($year, $month));
        }

        $workDays = array_filter($dates, function ($date) use ($holidays) {
            return !in_array($date, $holidays) && !in_array($date->format('N'), array(6, 7)); //sat, sun
        });
        $workDays = array_values($workDays);

        return $workDays;
    }

    /**
     * getDateFromNow description
     * @param  string $direction add/sub
     */
    public static function getDateFromNow($interval=1)
    {
        // TODO: WorkHour, Mon-Fri 8-5,
        // For Now Just 1D, 3D, 5D
        
        $today = new DateTimeImmutable;
        if (1 == $interval) { //Emergency 1d
            $resultDate = $today->add(new DateInterval('P1D'));
        } else {
            $workDays = self::getWorkDays($today);
            if ((in_array($today->format('N'), array(6, 7))) /*|| ($today->format('N')==5 && $today->format('G')>=17)*/) {
                $index = ($interval-1);
            } else {
                $index = $interval;
            }
            $resultDate = $workDays[$index];
        }

        return $resultDate->format('Y-m-d');
    }

    public static function getLocalFormat($data, $useDay=false)
    {
        $months = static::getMonthNames();
        $days   = static::getDayNames();
        $time   = strtotime($data);
        $nDay   = date('w', $time);
        $nMonth = date('n', $time);

        $day    = $days[$nDay];
        $date   = date('j', $time);
        $month  = $months[$nMonth];
        $year   = date('Y', $time);

        $formatted = "$date $month $year";
        if ($useDay) {
            $formatted = "$day, $formatted";
        }
        return $formatted;
    }

    public static function isValidDate($date, $dateformat='Y-m-d')
    {
        $date = DateTime::createFromFormat($dateformat, $date, new DateTimeZone('Asia/Jakarta'));
        return $date && DateTime::getLastErrors()["warning_count"] == 0 && DateTime::getLastErrors()["error_count"] == 0;
    }

    public static function generateYears($from, $to)
    {
        $range = range($from, $to);
        $combine = array_combine($range, $range);
        return $combine;
    }

    public static function getDateFormatString($date, $prepend = ' <b>pada</b> ')
    {
        if (is_null($date)) {
            return null;
        }
        return $prepend . date_format(date_create($date), "d M Y H:i:s");
    }
}
