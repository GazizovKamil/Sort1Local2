<?php

namespace Sort1API\Components;


class CompareStrings {
	

	private static $replacement_words = array("OE","OEM");
	
	
	public static function clean_brand($txt) {
        $patterns = array();
        $reps = array();
        foreach (self::$replacement_words as $a) {
             $patterns[] = "/^".$a."\s/ui";
             $reps[] = "";
             $patterns[] = "/\s".$a."$/ui";
             $reps[] = "";
             $patterns[] = "/(\W)".$a."(\W)/ui";
             $reps[] = "$1$2";
        }      
        return preg_replace(array("/\/\s+/","/\s+\//"),"/", trim(preg_replace($patterns, $reps, $txt)));
	}
	
	public static function is_similar(&$str, $arr) {
		global $DEBUG_BRANDS;
		
		$str = self::clean_brand($str);
		
		
		$ret = false;
		foreach ($arr as $br) {
			if (self::bitap($br, $str)) {
				$ret = true;
				break;			
			} 			
			if (self::abbr($br, $str)) {
				$ret = true;
				break;			
			} 			
/*			
			if (self::tanimoto($br, $str)) {
				$ret = true;
				break;			
			} 			
*/			
/*			if (self::soundex_php($br, $str)) {
				$ret = true;
				break;			
			} 			
*/
			if (self::similar_php($br, $str)) {
				$ret = true;
				break;			
			} 			
			if (self::levenstein($br, $str)) {
				$ret = true;
				break;			
			}
		}
		
		
		// Попытка №2. Разбиваем по словам и пробуем сравнить:		
		if (!$ret) {
			$new_array = array();
			foreach ($arr as $a) {
				preg_match_all('/[^\W\d][\w]*/ui',$a, $matches);
				$new_array = array_merge($new_array, $matches[0]);
			}
			foreach ($new_array as $i => $a) { 
				if (mb_strlen(trim($a))<=3) unset($new_array[$i]); else $new_array[$i] = mb_strtoupper(trim($a));
			}
			$new_array = array_unique($new_array);
			
			$new_str = array();
			preg_match_all('/[^\W\d][\w]*/ui',$str, $m);
			$new_str = $m[0];
			foreach ($new_str as $i=>$ns) if (mb_strlen(trim($ns))<=3) unset($new_str[$i]); else $new_str[$i] = mb_strtoupper(trim($ns));
			$new_str = array_unique($new_str);
			
			if (!empty($new_array) && !empty($new_str)) {
				foreach ($new_str as $ns) {				
					foreach ($new_array as $na) {
						if (self::bitap($ns, $na)) {
							$ret = true;
							break 2;			
						} 			
						if (self::abbr($ns, $na)) {
							$ret = true;
							break 2;			
						} 			
			/*			
						if (self::tanimoto($ns, $na)) {
							$ret = true;
							break 2;			
						} 			
			*/			
			/*			if (self::soundex_php($ns, $na)) {
							$ret = true;
							break 2;			
						} 			
			*/
						if (self::similar_php($ns, $na)) {
							$ret = true;
							break 2;			
						} 			
						if (self::levenstein($ns, $na)) {
							$ret = true;
							break 2;			
						}
					}				
					
					
					
				}
			
			}
		}		
		
		
		
		if (!$ret && $DEBUG_BRANDS) {
			$date = date("Y-m-d H:i:s");
			//file_put_contents("/var/log/sort1/brands_rejected.log",$date." Search array is: ".print_r($arr,true)." \nCompare to brand from site: ".$str."\n", FILE_APPEND|LOCK_EX);
			
		}	
		
				
		return $ret;	
		
	}
	
	
	
	//Алгоритм Bitap, на входе 2 строки и погрешшность (в символах)- по умолчанию погрешность 25% от длины первой строки
	// На выходе bool	
	public static function bitap($needle, $haystack, int $threshold = null) 
	{
		// strtoupper
		$needle = strtoupper($needle);
		$haystack = strtoupper($haystack);
		
		//поменяем значения переменных, если иголка больше стога
		if (strlen($needle) > strlen($haystack)) list($needle,  $haystack) = array($haystack, $needle);		
		
		$needleLen    = strlen($needle);
		$haystackLen  = strlen($haystack);
		
		
		$patternMask  = [];
		$row          = [];
		$threshold    = $threshold === null ? floor($needleLen * 0.25) : (int) abs($threshold);
		// Empty needle or exact match
		if ( $needle === '' || $needle === $haystack ) {
			return true;
		}
		// Empty hay stack
		if ( $haystack === '' ) {
			return false;
		}
		// Initialise table
		for ( $i = 0; $i <= $threshold + 1; $i++ ) {
			$row[$i] = 1;
		}
		// Initialise pattern mask (255 gives us the full extended ASCII range)
		for ( $i = 0; $i < 256; $i++ ) {
			$patternMask[$i] = 0;
		}
		// Initialise needle bit masks. e.g., the mask for 'o' in 'foo' is 110:
		// 1. foo -> original text
		// 2. 011 -> 1 where letter appears, 0 where it doesn't
		// 3. 110 -> swap bit order
		for ( $i = 0; $i < $needleLen; ++$i ) {
			$patternMask[ord($needle[$i])] |= 1 << $i;
		}
		// Loop through hay-stack chars
		for ( $i = 0; $i < $haystackLen; $i++ ) {
			$oldCol     = 0;
			$nextOldCol = 0;
			// Test for each level of errors
			for ( $d = 0; $d <= $threshold; ++$d ) {
				$replace = ($oldCol | ($row[$d] & $patternMask[ord($haystack[$i])])) << 1;
				$insert  = $oldCol | (($row[$d] & $patternMask[ord($haystack[$i])]) << 1);
				$delete  = ($nextOldCol | ($row[$d] & $patternMask[ord($haystack[$i])])) << 1;
				$oldCol     = $row[$d];
				$row[$d]    = $replace | $insert | $delete | 1;
				$nextOldCol = $row[$d];
			}
			// If we've got a match, we're done
			if ( 0 < ($row[$threshold] & (1 << $needleLen)) ) {
				return true;
			}
		}
		return false;		
		
		
	}
	
	public static function similar_php($a ,$b) {
		similar_text(strtoupper($a),strtoupper($b), $p);		
		return ($p>=70)?true:false;		
	}
	
	public static function soundex_php($a ,$b) {
		$c1 =  preg_split('//', soundex($a), -1, PREG_SPLIT_NO_EMPTY);
		$c2 =  preg_split('//', soundex($b), -1, PREG_SPLIT_NO_EMPTY);

		$k=0;
		for ($i=0;$i<4;$i++) {
		 if ($c1[$i] === $c2[$i]) $k++;
		}
		
		return ($k>=3)?true:false;
		
	}
	
	public static function tanimoto($a ,$b) {
		$c1 =  preg_split('//', strtoupper($a), -1, PREG_SPLIT_NO_EMPTY);
		$c2 =  preg_split('//', strtoupper($b), -1, PREG_SPLIT_NO_EMPTY);
		
		$c3 = array_intersect($c1,$c2);
		$c4 = array_intersect($c2,$c1);
		
		return ((100*max((count($c3)/(count($c1)+count($c2)-count($c3))),(count($c4)/(count($c1)+count($c2)-count($c4)))))>=70)?true:false;
	}
	
	public static function levenstein($a ,$b) {
		$l = levenshtein(strtoupper($a),strtoupper($b));
		return (((1 - $l / max(strlen($a),strlen($b)))*100)>=70)?true:false;
	}
	
	public static function abbr($a,$b) {
		$a1 = strlen($a);
		$b1 = strlen($b);
		if (($a1<=3 && $b1 >3)||($b1<=3 && $a1>3)) {
			//check if abbr.
			$ab = strtoupper(($a1<$b1)?$a:$b);
			$st = strtoupper(($a1<$b1)?$b:$a);
			
			$ab_arr = preg_split('//', $ab, -1, PREG_SPLIT_NO_EMPTY);
			
			foreach($ab_arr as &$tmp) $tmp = "[".$tmp."]";
			$reg_exp = "/".implode(".*[^0-9a-zа-яё]+",$ab_arr)."/ui";
			
			if (count($ab_arr)==3) $reg_exp2 = "/".$ab_arr[0].".*[^0-9a-zа-яё]+".$ab_arr[1]."/ui";
			
			if (preg_match($reg_exp, $st)) 
				return true;
            else if (isset($reg_exp2) &&  preg_match($reg_exp2, $st))
                return true;				
			else
				return false;			
		} else return false;			
	}
	
	
	
}


?>