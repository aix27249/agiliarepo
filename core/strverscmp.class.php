<?php
/* Version comparsion class
 *
 * Since this is a very old code actually (ported from old repository), it requires review.
 */

class VersionCompare {
	public static $strver_debug = false;
	private static function splitChunks($version) {
		// Replacing _ with .
		$v1=preg_replace("/_/", '.', $version);
		$length = strlen($v1);
		$d[]=0;
		for ($i=1; $i<$length; $i++) {
			if ($v1[$i-1]=='.') $d[]=$i;
			else {
				if ($v1[$i]<'a') {
					if ($v1[$i-1]>'9') $d[]=$i;
				}
				else {
					if ($v1[$i-1]<'a') $d[]=$i;
				}
			}
		}
		return $d;

	}

	// Returns:
	// 1: version1 > version2
	// 0: version1 === version2
	// -1: version1 < version2
	public static function strverscmp($version1, $version2) {
		$d1=self::splitChunks($version1);
		$d2=self::splitChunks($version2);
		$chunks1 = sizeof($d1);
		$chunks2 = sizeof($d2);

		// DEBUG PART START
		if (self::$strver_debug) {
			print "Comparing $version1 vs $version2<br>";
			print "first: $chunks1, second: $chunks2<br>";
			print "CHUNKS1:<br>";
			for($i=1; $i<$chunks1+1; $i++) {
				if ($i<$chunks1) $s1 = preg_replace('/[.]/', '', substr($version1, $d1[$i-1], $d1[$i]-$d1[$i-1]));
				else if ($i==$chunks1) $s1 =  preg_replace('/[.]/', '', substr($version1, $d1[$i-1]));
				else $s1=0;
				if ($s1==="rc") $s1=-2;
				else if ($s1==="pre") $s1=-3;
				else if ($s1==="beta") $s1=-4;
				else if ($s1==="alpha") $s1=-5;
				else if ($s1==="prealpha") $s1=-6;
				else if ($s1==="git" || $s1==="svn" || $s1==="hg" || $s1==="r" || $s1==="rev" || $s1==="cvs") $s1=-7;

				print "$s1<br>";
			}
			print "CHUNKS2:<br>";
			for($i=1; $i<$chunks2+1; $i++) {
				if ($i<$chunks2) $s2 = preg_replace('/[.]/', '', substr($version2, $d2[$i-1], $d2[$i]-$d2[$i-1]));
				else if ($i==$chunks2) $s2 =  preg_replace('/[.]/', '', substr($version2, $d2[$i-1]));
				else $s2=0;
				if ($s2==="rc") $s2=-2;
				else if ($s2==="pre") $s2=-3;
				else if ($s2==="beta") $s2=-4;
				else if ($s2==="alpha") $s2=-5;
				else if ($s2==="prealpha") $s2=-6;
				else if ($s2==="git" || $s2==="svn" || $s2==="hg" || $s2==="r" || $s2==="rev" || $s1==="cvs") $s2=-7;

				print "$s2<br>";
			}
		}
		// DEBUG PART END
		


		$version1=preg_replace("/_/", '.', $version1);
		$version2=preg_replace("/_/", '.', $version2);

		for ($i=1; $i<$chunks1+1 || $i<$chunks2+1; $i++) {
			//print "Iteration $i<br>";

			if ($i<$chunks1) $s1 = preg_replace('/[.]/', '', substr($version1, $d1[$i-1], $d1[$i]-$d1[$i-1]));
			else if ($i==$chunks1) $s1 =  preg_replace('/[.]/', '', substr($version1, $d1[$i-1]));
			else $s1="0";

			if ($i<$chunks2) $s2 = preg_replace('/[.]/', '', substr($version2, $d2[$i-1], $d2[$i]-$d2[$i-1]));
			else if ($i==$chunks2) $s2 =  preg_replace('/[.]/', '', substr($version2, $d2[$i-1]));
			else $s2="0";

			if ($s1==="rc") $s1="-2";
			else if ($s1==="pre") $s1="-3";
			else if ($s1==="beta") $s1="-4";
			else if ($s1==="alpha") $s1="-5";
			else if ($s1==="prealpha") $s1="-6";
			else if ($s1==="git" || $s1==="svn" || $s1==="hg" || $s1==="r" || $s1==="rev" || $s1==="cvs") $s1="-7";

			if ($s2==="rc") $s2="-2";
			else if ($s2==="pre") $s2="-3";
			else if ($s2==="beta") $s2="-4";
			else if ($s2==="alpha") $s2="-5";
			else if ($s2==="prealpha") $s2="-6";
			else if ($s2==="git" || $s2==="svn" || $s2==="hg" || $s2==="r" || $s2==="rev" || $s1==="cvs") $s2="-7";


			if ($s1===$s2) {
				continue;
			}
			// NOTE: intval(string) is ALWAYS zero.
			if (intval($s1)>intval($s2)) {
				return 1;
			}
			else if (intval($s1)<intval($s2)) {
				return -1;
			}
			else {
				if (intval($s1)===0 && $s1!=="0") {
					if (intval($s2)===0 && $s2!=="0") {
						$scomp = strcmp($s1, $s2);
						if ($scomp<0) {
							return -1;
						}
						else if ($scomp>0) {
							return 1;
						}
					}
					else {
						return 1;
					}
				}
				else { // Means first is string zero
					if (intval($s2)===0 && $s2!=="0") {
						return -1;
					}
				}
			}
		}
		return 0;

	}

	// Condition should be in SQL format (int). Returns true if OK, false if not.
	public static function checkDepCondition($required_version, $pkgversion, $condition) {
		$result = self::strverscmp($pkgversion, $required_version);
		switch($condition) {
		case 1:
			if ($result>0) return true;
			else return false;
		case 2:
			if ($result<0) return true;
			else return false;
		case 3: 
			if ($result==0) return true;
			else return false;
		case 4:
			if ($result!=0) return true;
			else return false;
		case 5:
			if ($result>=0) return true;
			else return false;
		case 6:
			if ($result<=0) return true;
			else return false;
		case 7:
			return true;
		default:
			print "Unknown condition $condition<br>";
			return false;
		}

	}
	public static function getDepCondition($cond) {
		switch($cond) {
		case 1: return '>';
		case 2: return '<';
		case 3: return '==';
		case 4: return '!=';
		case 5: return '>=';
		case 6: return '<=';
		case 7: return 'any';
		}
	}

	public static function getDepConditionFromXML($condition) {
		$condition = trim($condition);
		if ($condition=="more") return 1;
		if ($condition=="less") return 2;
		if ($condition=="equal") return 3;
		if ($condition=="notequal") return 4;
		if ($condition=="atleast") return 5;
		if ($condition=="notmore") return 6;
		if ($condition=="any") return 7;
		if ($condition=="(any)") return 7;
		return "OMG";
	}
	public static function getDepConditionBack($condition) {
		if ($condition==1) return "more";
		if ($condition==2) return "less";
		if ($condition==3) return "equal";
		if ($condition==4) return "notequal";
		if ($condition==5) return "atleast";
		if ($condition==6) return "notmore";
		if ($condition==7) return "any";
		return $condition;
	}
}
