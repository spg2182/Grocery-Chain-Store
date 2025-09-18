<?php
function mds_date($format, $when = "now", $persianNumber = 0) {
    $TZhours = 3;
    $TZminute = 30;
    $result = "";

    if ($when == "now") {
        $year = date("Y");
        $month = date("m");
        $day = date("d");
        list($Dyear, $Dmonth, $Dday) = gregorian_to_mds($year, $month, $day);
        $when = mktime(date("H") + $TZhours, date("i") + $TZminute, date("s"), date("m"), date("d"), date("Y"));
    } else {
        $when += $TZhours * 3600 + $TZminute * 60;
        $date = date("Y-m-d", $when);
        list($year, $month, $day) = explode('-', $date);
        list($Dyear, $Dmonth, $Dday) = gregorian_to_mds($year, $month, $day);
    }
    $need = $when;

    $len = strlen($format);
    $escaped = false;

    for ($i = 0; $i < $len; $i++) {
        $subtype = $format[$i];

        if ($escaped) {
            $result .= $subtype;
            $escaped = false;
            continue;
        }
        if ($subtype == "\\") {
            $escaped = true;
            continue;
        }
        switch ($subtype) {
            case "A":
                $result1 = date("a", $need);
                $result .= ($result1 == "pm") ? "بعدازظهر" : "قبل از ظهر";
                break;
            case "a":
                $result1 = date("a", $need);
                $result .= ($result1 == "pm") ? "ب.ظ" : "ق.ظ";
                break;
            case "d":
                $result1 = ($Dday < 10) ? "0" . $Dday : $Dday;
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "D":
                $wday = date("D", $need);
                $map = ["Sat" => "ش", "Sun" => "ی", "Mon" => "د", "Tue" => "س", "Wed" => "چ", "Thu" => "پ", "Fri" => "ج"];
                $result .= $map[$wday] ?? $wday;
                break;
            case "F":
                $result .= monthname($Dmonth);
                break;
            case "g":
                $result1 = date("g", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "G":
                $result1 = date("G", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "h":
                $result1 = date("h", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "H":
                $result1 = date("H", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "i":
                $result1 = date("i", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "j":
                $result1 = $Dday;
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "l":
                $fullDay = date("l", $need);
                $mapFull = [
                    "Saturday" => "شنبه",
                    "Sunday" => "یکشنبه",
                    "Monday" => "دوشنبه",
                    "Tuesday" => "سه شنبه",
                    "Wednesday" => "چهارشنبه",
                    "Thursday" => "پنجشنبه",
                    "Friday" => "جمعه",
                ];
                $result .= $mapFull[$fullDay] ?? $fullDay;
                break;
            case "m":
                $result1 = ($Dmonth < 10) ? "0" . $Dmonth : $Dmonth;
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "M":
                $result .= short_monthname($Dmonth);
                break;
            case "n":
                $result1 = $Dmonth;
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "s":
                $result1 = date("s", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "S":
                $result .= "ام";
                break;
            case "t":
                $result .= lastday($month, $day, $year);
                break;
            case "w":
                $result1 = date("w", $need);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "y":
                $result1 = substr($Dyear, 2, 2);
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "Y":
                $result1 = $Dyear;
                $result .= ($persianNumber == 1) ? Convertnumber2farsi($result1) : $result1;
                break;
            case "U":
                $result .= $need;
                break;
            case "Z":
                $result .= days_of_year($Dmonth, $Dday, $Dyear);
                break;
            default:
                $result .= $subtype;
                break;
        }
    }
    return $result;
}

function monthname($month)
{
    $map = [
        1 => "فروردین",
        2 => "اردیبهشت",
        3 => "خرداد",
        4 => "تیر",
        5 => "مرداد",
        6 => "شهریور",
        7 => "مهر",
        8 => "آبان",
        9 => "آذر",
        10 => "دی",
        11 => "بهمن",
        12 => "اسفند",
    ];
    $month = (int)$month;
    return $map[$month] ?? $month;
}

function short_monthname($month)
{
    $map = [
        1 => "فرو",
        2 => "ارد",
        3 => "خرد",
        4 => "تیر",
        5 => "مرد",
        6 => "شهر",
        7 => "مهر",
        8 => "آبا",
        9 => "آذر",
        10 => "دی",
        11 => "بهمن",
        12 => "اسفند",
    ];
    $month = (int)$month;
    return $map[$month] ?? $month;
}

function Convertnumber2farsi($string)
{
    if (is_int($string)) {
        $string = (string)$string;
    }
    $fa_numbers = ["0" => "۰", "1" => "۱", "2" => "۲", "3" => "۳", "4" => "۴",
                   "5" => "۵", "6" => "۶", "7" => "۷", "8" => "۸", "9" => "۹"];
    $result = "";
    $len = strlen($string);
    for ($i = 0; $i < $len; $i++) {
        $char = $string[$i];
        $result .= $fa_numbers[$char] ?? $char;
    }
    return $result;
}

function div($a, $b) {
    return (int)($a / $b);
}

function gregorian_to_mds($g_y, $g_m, $g_d) {
    $g_days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
    $m_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;
    $g_day_no = 365 * $gy + div($gy+3,4) - div($gy+99,100) + div($gy+399,400);
    for ($i=0; $i < $gm; ++$i)
        $g_day_no += $g_days_in_month[$i];
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)))
        $g_day_no++;
    $g_day_no += $gd;
    $m_day_no = $g_day_no - 79;
    $j_np = div($m_day_no, 12053);
    $m_day_no %= 12053;
    $jy = 979 + 33*$j_np + 4*div($m_day_no,1461);
    $m_day_no %= 1461;
    if ($m_day_no >= 366) {
      $jy += div($m_day_no - 1, 365);
      $m_day_no = ($m_day_no - 1) % 365;
    }
    $i = 0;
    while ($i < 11 && $m_day_no >= $m_days_in_month[$i]) {
      $m_day_no -= $m_days_in_month[$i];
      $i++;
    }
    $jm = $i + 1;
    $jd = $m_day_no + 1;
    return [$jy, $jm, $jd];
}

?>
