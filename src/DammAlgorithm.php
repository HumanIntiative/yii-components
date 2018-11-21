<?php
/**
 * The implementation of the damm algorithm based on the details on http://en.wikipedia.org/wiki/Damm_algorithm
 *
 * Class DammAlgorithm
 */
class DammAlgorithm
{
    /**
     * The quasigroup table from http://www.md-software.de/math/DAMM_Quasigruppen.txt
     *
     * @var $matrix array
     */
    protected $matrix = array(
        array(0, 3, 1, 7, 5, 9, 8, 6, 4, 2),
        array(7, 0, 9, 2, 1, 5, 4, 8, 6, 3),
        array(4, 2, 0, 6, 8, 7, 1, 3, 5, 9),
        array(1, 7, 5, 0, 9, 8, 3, 4, 2, 6),
        array(6, 1, 2, 3, 0, 4, 5, 9, 7, 8),
        array(3, 6, 7, 4, 2, 0, 9, 5, 8, 1),
        array(5, 8, 6, 9, 7, 2, 0, 1, 3, 4),
        array(8, 9, 4, 5, 3, 6, 2, 0, 1, 7),
        array(9, 4, 3, 8, 6, 1, 7, 2, 0, 5),
        array(2, 5, 8, 1, 4, 3, 6, 7, 9, 0),
    );
 
    /**
     * Calculate the checksum digit from provided number
     *
     * @param $number
     * @return int
     */
    public function encode($number)
    {
        /* @var $interim int */
        $interim = 0;
        /* @var $i int */
        for ($i=0; $i<strlen($number); $i++) {
            $interim = $this->matrix[$interim][$number[$i]];
        }
 
        return $interim;
    }
 
    /**
     * Checks the checksum digit from provided number
     *
     * @param $number
     * @return bool
     */
    public function check($number)
    {
        return (0 == $this->encode($number));
    }
}
