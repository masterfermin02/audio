<?php

namespace Masterfermin02\Audio;

class Math
{
    public const ZERO_MODE = 0;

    public const ONE_MODE = 1;
    /**
     * // ************************************************************************
     * // longCalc calculates the decimal value of 4 bytes
     * // mode = 0 ... b1 is the byte with least value
     * // mode = 1 ... b1 is the byte with most value
     * // ************************************************************************
     */
    public static function longCalc($b1,$b2,$b3,$b4,$mode): float|int
    {
        $b1 = hexdec(bin2hex((string) $b1));
        $b2 = hexdec(bin2hex((string) $b2));
        $b3 = hexdec(bin2hex((string) $b3));
        $b4 = hexdec(bin2hex((string) $b4));
        if ($mode == 0) {
            return ($b1 + ($b2*256) + ($b3 * 65536) + ($b4 * 16_777_216));
        }

        return ($b4 + ($b3*256) + ($b2 * 65536) + ($b1 * 16_777_216));
    }

    /**
     *
     * // ************************************************************************
     * // shortCalc calculates the decimal value of 2 bytes
     * // mode = 0 ... b1 is the byte with least value
     * // mode = 1 ... b1 is the byte with most value
     * // ************************************************************************
     *
     * @param $b1
     * @param $b2
     * @param $mode
     * @return float|int
     */
    public static function shortCalc($b1,$b2,$mode): float|int
    {
        $b1 = hexdec(bin2hex((string) $b1));
        $b2 = hexdec(bin2hex((string) $b2));
        if ($mode == 0)
        {
            return ($b1 + ($b2*256));
        }

        return ($b2 + ($b1*256));
    }
}
