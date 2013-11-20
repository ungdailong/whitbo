<?php

class Utils {

/**
	 * remove leading, trailing
	 * and "more than one" space in between words
	 *
	 * @param String $string
	 * @return trim space string
	 */
	private static function trimSpace($string) {
		$pat[0] = "/^\s+/u";
		$pat[1] = "/\s{2,}/u";
		$pat[2] = "/\s+\$/u";
		$rep[0] = "";
		$rep[1] = " ";
		$rep[2] = "";
		$str = preg_replace($pat,$rep,$string);
		return $str;
	}
	/**
	 * Sanitize text before import into database
	 *
	 * @param String $str
	 * @return sanitized string
	 */
	public static function sanitizeText($str,$allow = array( '&', ';', '#')) {
		App::import('Core', 'Sanitize');
		$str = strip_tags($str);
		$str = Sanitize::stripAll($str);
		$str = Sanitize::escape($str);
		$allow = array_merge($allow,array(' ', '-', '_', '.', '@', '$', "'", '/', ',') );
		$str = Sanitize::paranoid($str, $allow);
		return Utils::trimSpace($str);
	}

	public static function sanitizeString($str) {
		App::import('Core', 'Sanitize');
		$allow = array(' ', '-', '_', '.', '@', '$', "'", '/', ',', ';');
		$str = Sanitize::paranoid($str, $allow);
		return Utils::trimSpace($str);
	}

	public static function encodeString($str) {
		App::import('Core', 'Sanitize');
		$str = utf8_encode($str);
		return Sanitize::html($str);
	}

	/**
	 * Sanitize Int before import into database
	 *
	 * @param String $str
	 * @return sanitized int
	 */
	public static function sanitizeInt($str) {
		$ret = 0;
		$input = $str;
		if(is_numeric($input)) {
			$ret = $input;
		}
		return $ret;
	}

	public static function removeHtmlTag($str) {
		App::import('Core', 'Sanitize');
		$str = Sanitize::html($str, array('remove' => true, 'quotes' => ENT_COMPAT));
		return Utils::trimSpace($str);
	}

	public static function htmlspecialString($str) {
		//remove valid html tag
		$str = preg_replace('/(<([^>]+)>)/u', '', $str);
		return Utils::trimSpace($str);
	}

	public static function cleanupFreeText($str){
		// remove html tags
		// trim spaces
		return Utils::trimSpace(strip_tags($str));
	}

	public static function getExpenseCloudUrl() {
		$protocol = (Configure::read("AppHost.RedirectHttps") ? 'https://' : 'http://');
		$url = $protocol . Configure::read('AppHost.Host') . Configure::read("AppHost.ContextPath");

		return $url;

	}

	public static function useModels($obj, $modelNames = array(), $cached = true) {
		foreach ($modelNames as $modelName) {
			if ( $cached && isset($obj->$modelName) ) {
				continue;
			}

			$obj->$modelName = ClassRegistry::init($modelName);
		}
	}

	public static function useComponents($obj, $componentNames = array(), $cached = true) {
		$controller = $obj;
		if (!is_subclass_of($obj, 'Controller')) {
			App::import('Core', 'Controller');
			$controller =& new Controller();
		}

		foreach ($componentNames as $componentName) {
			if ( $cached && isset($obj->$componentName) ) {
				continue;
			}

			App::import('Component', $componentName);
			$componentClass = $componentName . 'Component';
			$obj->$componentName = new $componentClass();

			if (method_exists($obj->$componentName, 'initialize')) {
				$obj->$componentName->initialize($controller);
			}
			if (method_exists($obj->$componentName, 'startup')) {
				$obj->$componentName->startup($controller);
			}
		}
	}

	public static function generatePassword($length = 9, $strength = 0, $bNumber = false, $yodleePass = false) {
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		$numbers = '23456789';

		if ($strength & 1) {
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if ($strength & 2) {
			$vowels .= 'AEUY';
		}
		if ($strength & 4) {
			$consonants .= '23456789';
		}
		if ($strength & 8) {
			$consonants .= '@#$%';
		}

		$password = '';
		$alt = time() % 2;

		if ($yodleePass) {
			// Yodlee Restriction: Does not contain the same letter/number three or more times in a row
			// So prepend $alt before the new-generated char will help

			for ($i = 0; $i < $length; $i++) {
				if ($alt == 1) {
					$password .= $alt . $consonants[(rand() % strlen($consonants))];
					$alt = 0;
				} else {
					$password .= $alt . $vowels[(rand() % strlen($vowels))];
					$alt = 1;
				}
			}
		} else {
			for ($i = 0; $i < $length; $i++) {
				if ($alt == 1) {
					$password .= $consonants[(rand() % strlen($consonants))];
					$alt = 0;
				} else {
					$password .= $vowels[(rand() % strlen($vowels))];
					$alt = 1;
				}
			}
		}

		if ($bNumber) {
			$password .= $numbers[(rand() % strlen($numbers))];
		}

		return $password;
	}

	public static function sqlDateToDate($sqlDate, $format = 'Y-m-d', $default = '') {
		if (empty($sqlDate)) {
			return $default;
		}

		list($year, $month, $day) = explode('-', $sqlDate);
		return date($format, mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));
	}

	public static function convertDateToSqlDate($userDate) {
		list($month, $day, $year) = split('[/.-]', $userDate);
		return date('Y-m-d', mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));
	}

	public static function convertSqlDateTimetoDateTime($userDate, $format, $default = ''){
		if (empty($userDate)) {
			return $default;
		}
		$datetime = explode(' ', $userDate);
		list($year, $month, $day) = explode('-', $datetime[0]);
		list($h, $m, $s) = explode(':', $datetime[1]);
		return date($format, mktime((int)$h, (int)$m, (int)$s, (int)$month, (int)$day, (int)$year));
	}

	public static function convertToDate($s, $format = DEFAULT_DATE_FORMAT) {
		return date($format, strtotime($s));
	}

	public static function convertDate($stringDate, $fromFormat, $toFormat = 'yyyy-mm-dd', $returnDelimiter = '-') {

		if(empty($stringDate)){
			return false;
		}

		$returnDate = '';
		$fromFormat = strtolower($fromFormat);
		$toFormat = strtolower($toFormat);
		$stringInputDate = $stringDate;
		$stringDate = str_replace(array('\'', '-', '.', ',', ' '), '/', $stringDate);
		$date = explode('/', $stringDate);
		if(count($date) < 3) {
			return false;
		}
		switch($fromFormat) {
			case 'dd/mm/yy' :
			case 'dd/mm/yyyy' :
				$format = explode('/', $stringInputDate);
				if(empty($format)) {
					return false;
				}

				$day = $date[0];
				$month = $date[1];
				$year = $date[2];

			break;

			case 'mm/dd/yy':
			case 'mm/dd/yyyy':
				$format = explode('/', $stringInputDate);
				if(sizeof($format) == 1) {
					return false;
				}

				$month = $date[0];
				$day = $date[1];
				$year = $date[2];
			break;

			case 'yyyy-mm-dd':
			case 'yy-mm-dd':
				$format = explode('-', $stringInputDate);
				if(sizeof($format) == 1) {
					return false;
				}

				$year = $date[0];
				$month = $date[1];
				$day = $date[2];
			break;

			default:
				return false;
		}

		# fix $day with utf8 header (because csv parse has not make this yet
		# TODO: this is just a workaround solution
		# TODO: this must be fixed by the csv parser
		$dayLast1 = (int)substr($day, -1);
		$dayLast2 = (int)substr($day, -2);
		$day = empty($dayLast2) ? $dayLast1 : $dayLast2;

		if (strlen($day) == 1) {
			$day = '0'. $day;
		}
		if (strlen($month) == 1) {
			$month = '0'. $month;
		}

		if (strlen($year) >= 4) {
			$year = substr($year , 0, 4) ;
		}
		if (strlen($year) == 3 ) {
			$year = substr(date('Y'), 0, strlen(date('Y')) - 3). $year;
		}
		if (strlen($year) == 2 ) {
			$year = substr(date('Y'), 0, strlen(date('Y')) - 2). $year;
		}
		if (strlen($year) == 1 ) {
			$year = substr(date('Y'), 0, strlen(date('Y')) - 1). $year;
		}

		switch($toFormat) {
			case 'yyyy-mm-dd': # yyyy/mm/dd
				$returnDate = $year. $returnDelimiter. $month. $returnDelimiter. $day;
			break;
		}

		if (!is_numeric($month) || !is_numeric($day) || !is_numeric($year)) {
			return false;
		} elseif (!checkdate($month, $day, $year)) {
			return false;
		}

		return $returnDate;
	}

	/**
	 * Finds the difference in days between two calendar dates.
	 *
	 * @param Date $startDate
	 * @param Date $endDate
	 * @return Int
	 */
	function dateDiff($startDate, $endDate)
	{
		// Parse dates for conversion
		$startArry = date_parse($startDate);
		$endArry = date_parse($endDate);

		// Convert dates to Julian Days
		$start_date = gregoriantojd($startArry['month'], $startArry['day'], $startArry['year']);
		$end_date = gregoriantojd($endArry['month'], $endArry['day'], $endArry['year']);

		// Return difference
		return round(($end_date - $start_date), 0);
	}

	public static function dateAdd($date, $m=0, $d=0, $y=0) {
		$cd = strtotime($date);
		$newdate = date('Y-m-d', mktime(
			0, 0, 0,
			date('m', $cd) + $m,
			date('d', $cd) + $d,
			date('Y', $cd) + $y)
		);
		return $newdate;
	}

	// Sunday to Saturday
	public static function getDayOfWeek($s) {
		return date('l', strtotime($s));
	}

	public static function makeList($modelArray, $modelName, $keyName, $valueName) {
		$list = array();
		foreach ($modelArray as $e) {
			$list[$e[$modelName][$keyName]] = $e[$modelName][$valueName];
		}
		return $list;
	}

	/**
	 * @param String $file : file path
	 *
	 */
	public static function putUploadFileToS3($file, $uri) {
		App::import('Vendor', 'S3', array('file' => 'Amazon/S3.php'));
		$s3 = new S3(Configure::read('AmazonS3.AWSAccessKeyId'), Configure::read('AmazonS3.SecretAccessKey'));
		return $s3->putObject($s3->inputFile($file, false), Configure::read('AmazonS3.Bucket'), Configure::read('AmazonS3.UploadFolder') . '/' . $uri, S3::ACL_PUBLIC_READ);
	}

	public static function deleteFileOnS3($fileName) {
		App::import('Vendor', 'S3', array('file' => 'Amazon/S3.php'));
		$s3 = new S3(Configure::read('AmazonS3.AWSAccessKeyId'), Configure::read('AmazonS3.SecretAccessKey'));
		return $s3->deleteObject(Configure::read('AmazonS3.Bucket'), Configure::read('AmazonS3.UploadFolder') . '/' . $fileName);
	}

	public static function copyFileOnS3($srcFileName, $desFileName){
		App::import('Vendor', 'S3', array('file' => 'Amazon/S3.php'));
		$s3 = new S3(Configure::read('AmazonS3.AWSAccessKeyId'), Configure::read('AmazonS3.SecretAccessKey'));
		return $s3->copyObject(Configure::read('AmazonS3.Bucket'), Configure::read('AmazonS3.UploadFolder') . '/' . $srcFileName, Configure::read('AmazonS3.Bucket'), Configure::read('AmazonS3.UploadFolder') . '/' . $desFileName, 'public-read');
	}

	public static function deleteFile($file) {
		if (is_file($file)){
			unlink($file);
		}
	}

	/**
	 * Validate short date mm-dd-yyyy
	 *
	 * @param String $date
	 * @return FALSE if date is invalid - Else return the date
	 */
	public static function validateShortDate($date) {
		if (!preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $date, $matches)) {
			return false;
		}
		$month = $matches[1];
		$day = $matches[2];
		$year = $matches[3];
		$unix_timestamp = mktime(0,0,0, $month, $day, $year);
		if($unix_timestamp === false) {
			$unix_timestamp = mktime(0,0,0, $day, $month, $year);
		}
		return date(DEFAULT_DATE_FORMAT, $unix_timestamp);
	}

	public static function createPageParams($page, $limit, $sidx, $sord, $count){

		if(!$sidx) {
			$sidx = 'id';
		}
		if( $count > 0 ) {
			$total_pages = ceil($count/$limit);
			} else {
			$total_pages = 0;
		}

		if ($page > $total_pages) {
			$page = $total_pages;
		}

		$start = $limit * $page - $limit;
		if ($start < 0) {
			$start = 0;
		}

		return array (
				'page'=> $page,
				'limit'=> $limit,
				'sidx'=> $sidx,
				'sord'=> $sord,
				'totalPage'=> $total_pages,
				'start' => $start,
				'count'=> $count
		);
	}

	/**
	 * Render Currency as format $XXX.XX
	 *
	 * @param $amount
	 * @param $default: default render if $amount = 0
	 */
	public static function renderCurrency($amount, $default = '') {
		if ($amount < 0) {
			return '-$' . number_format(-$amount, 2);
		} else if ($amount > 0) {
			return '$' . number_format($amount, 2);
		} else {
			return $default;
		}
	}

	public static function stringToNumber($s, $decimals = 2) {
		return number_format($s, $decimals, '.', '');
	}

	public static function formatNumber($s, $decimals = 2, $thousandsSep = true) {
		return number_format($s, $decimals, '.', $thousandsSep ? ',' : '');
	}

	public static function hashPassword( $password ) {
		return AuthComponent::password($password);
	}

	public static function urlExists($url) {
		if(@file_get_contents($url, 0, NULL, 0, 1)){
			return 1 ;
		}
		return 0;
	}

	public static function catchJavaException ($e) {
		$err = java_cast($e->getCause(), 'S');
		return array(
			'message' 	=> preg_replace("/(.*)Exception:/", '', $err) ,
			'status'		=> 1
		);
	}

	public static function matchString($description, $key){
		if (empty($key) || empty($description)) {
			return false;
		}

		$description = strtolower($description);
		$key = strtolower($key);
		if ( strpos($description, $key) !== FALSE ||
			 strpos($key, $description) !== FALSE ) {
			return true;
		}
		return false;
	}

	public static function compare2Str($str1, $str2) {
		if (empty($str1) || empty($str2)) {
			return FALSE;
		}

		$pattern = '[., !?;-]';
		$exclude = array('a', 'an', 'the', 'and', 'or');
		$rate = 0.7;

		$tok1 = split($pattern, strtolower($str1));
		$tok2 = split($pattern, strtolower($str2));
		$length1 = 0;
		$similarCount = 0;
		$sameWord = 0;
		foreach ($tok1 as $word1) {
			$wordLength1 = strlen($word1);

			if (!empty($word1) && $wordLength1 > 2 && !in_array($word1, $exclude)) {
				$length1++;
				$length2 = 0;
				foreach ($tok2 as $word2) {
					$wordLength2 = strlen($word2);
					if (!empty($word2) && $wordLength2 > 2 && !in_array($word2, $exclude)) {
						$length2++;

						$wordLength = $wordLength1 > $wordLength2 ? $wordLength2 : $wordLength1;
						if (similar_text($word1, $word2) / $wordLength > $rate) {
							$similarCount++;
						}
						if (strpos($word1, $word2) !== FALSE || strpos($word2, $word1) !== FALSE ) {
							$sameWord++;
					}
				}
			}
			}
		}
		if (!isset($length2) || empty($length1) || empty($length2)) {
			return FALSE;
		}
		$length = ($length1 < $length2 ? $length1 : $length2);
		return ($similarCount / $length > $rate) && ($sameWord >= $length / 2);
	}

	public static function formatPeriodDate($sqlDate) {
		$tmps = explode(' ', $sqlDate);
		if(count($tmps) != 2){
			$tmps = array('', '');
		}
		list($date, $time) = $tmps;
		$tmps = explode('-', $date);
		if(count($tmps) != 3){
			$tmps = array(0, 0, 0);
		}
		list($year, $month, $day) = $tmps;
		$tmps = explode(':', $time);
		if(count($tmps) != 3){
			$tmps = array(0, 0, 0);
		}
		list($hour, $minute, $second) = $tmps;
		return self::compareDates(mktime((int)$hour, (int)$minute, (int)$second, (int)$month, (int)$day, (int)$year), time(), true);
	}

	private static function compareDates($date1, $date2, $shortFormat) {
		$blocks = array(
		array('name' => 'year', 'amount' => 60 * 60 * 24 * 365),
		array('name' => 'month', 'amount' => 60 * 60 * 24 * 31),
		array('name' => 'week', 'amount' => 60 * 60 * 24 * 7 ),
		array('name' => 'day', 'amount' => 60 * 60 * 24),
		array('name' => 'hour', 'amount' => 60 * 60),
		array('name' => 'minute', 'amount' => 60),
		array('name' => 'second', 'amount' => 1)
		);

		$diff = abs($date1 - $date2);

		if(empty($diff)) {
			return '0 second ago';
		}

		$levels = 2;
		$currentLevel = 1;
		$result = array();
		foreach($blocks as $block) {
			if ($currentLevel > $levels) {
				break;
			}

			if ($diff / $block['amount'] >= 1) {
				$amount = floor($diff / $block['amount']);
				if ($amount > 1) {
					$plural = 's';
				} else {
					$plural = '';
				}

				$result[] = $amount . ' ' . $block['name'] . $plural;
				$diff -= $amount * $block['amount'];

				if ($shortFormat) {
					return implode(' ', $result) . ' ago';
				}
			}
		}

		return implode(' ', $result) . ' ago';
	}

	public static function getYodleeId($userId) {
		$suffix = Configure::read("Yodlee.suffix");
		return "expbay_" . $userId . "_" . $suffix;
	}

	/**
	 * generate ids id from userId
	 *
	 * @param $userId
	 * @return expbay_userId_suffix
	 */
	public static function getIdsId($userId) {
		$suffix = Configure::read("Ids.suffix");
		return "expbay_" . $userId . "_" . $suffix;
	}

/**
	 * get currency sign
	 * @param String $currency
	 * @return String $currency_sign
	 */
	public static function getCurrencySign($currency) {
		$ret = '$';
		if($currency == 'ALL') {
			$ret = 'Lek';
		} else if($currency == 'USD') {
			$ret = '$';
		} else if($currency == 'AFN') {
			$ret = '$';
		} else if($currency == 'ARS') {
			$ret = '$';
		} else if($currency == 'AWG') {
			$ret = '&#x192;';
		} else if($currency == 'AUD') {
			$ret = '$';
		} else if($currency == 'AZN') {
			$ret = '$';
		} else if($currency == 'BSD') {
			$ret = '$';
		} else if($currency == 'BBD') {
			$ret = '$';
		} else if($currency == 'BYR') {
			$ret = 'p.';
		} else if($currency == 'EUR') {
			$ret = '&#x80;';
		} else if($currency == 'BZD') {
			$ret = 'BZ$';
		} else if($currency == 'BMD') {
			$ret = '$';
		} else if($currency == 'BOB') {
			$ret = '$b';
		} else if($currency == 'BAM') {
			$ret = 'KM';
		} else if($currency == 'BWP') {
			$ret = 'P';
		} else if($currency == 'BGN') {
			$ret = '$';
		} else if($currency == 'BRL') {
			$ret = 'R$';
		} else if($currency == 'GBP') {
			$ret = '&#x20A4;';
		} else if($currency == 'BND') {
			$ret = '$';
		} else if($currency == 'KHR') {
			$ret = '$';
		} else if($currency == 'CAD') {
			$ret = '$';
		} else if($currency == 'KYD') {
			$ret = '$';
		} else if($currency == 'CLP') {
			$ret = '$';
		} else if($currency == 'CNY') {
			$ret = '&yen;';
		} else if($currency == 'COP') {
			$ret = '$';
		} else if($currency == 'CRC') {
			$ret = '$';
		} else if($currency == 'HRK') {
			$ret = 'kn';
		} else if($currency == 'CUP') {
			$ret = '&#x20b1;';
		} else if($currency == 'CZK') {
			$ret = 'Kc';
		} else if($currency == 'DKK') {
			$ret = 'kr';
		} else if($currency == 'DOP') {
			$ret = 'RD$';
		} else if($currency == 'XCD') {
			$ret = '$';
		} else if($currency == 'EGP') {
			$ret = '&#x20A4;';
		} else if($currency == 'SVC') {
			$ret = '$';
		} else if($currency == 'GBP') {
			$ret = '&#x20A4;';
		} else if($currency == 'EEK') {
			$ret = 'kr';
		} else if($currency == 'FKP') {
			$ret = '&#x20A4;';
		} else if($currency == 'FJD') {
			$ret = '$';
		} else if($currency == 'GHC') {
			$ret = 'Â¢';
		} else if($currency == 'GIP') {
			$ret = '&#x20A4;';
		} else if($currency == 'GTQ') {
			$ret = 'Q';
		} else if($currency == 'GGP') {
			$ret = '&#x20A4;';
		} else if($currency == 'GYD') {
			$ret = '$';
		} else if($currency == 'HNL') {
			$ret = 'L';
		} else if($currency == 'HKD') {
			$ret = '$';
		} else if($currency == 'HUF') {
			$ret = 'Ft';
		} else if($currency == 'ISK') {
			$ret = 'kr';
		} else if($currency == 'INR') {
			$ret = '$';
		} else if($currency == 'IDR') {
			$ret = '&#x20a8;';
		} else if($currency == 'IRR') {
			$ret = '$';
		} else if($currency == 'IMP') {
			$ret = '&#x20A4;';
		} else if($currency == 'ILS') {
			$ret = '&#x20AA;';
		} else if($currency == 'JMD') {
			$ret = 'J$';
		} else if($currency == 'JPY') {
			$ret = '&yen;';
		} else if($currency == 'JEP') {
			$ret = '&#x20A4;';
		} else if($currency == 'KZT') {
			$ret = '$';
		} else if($currency == 'KPW') {
			$ret = '&#x20a9;';
		} else if($currency == 'KRW') {
			$ret = '&#x20a9;';
		} else if($currency == 'KGS') {
			$ret = '$';
		} else if($currency == 'LAK') {
			$ret = '&#x20AD;';
		} else if($currency == 'LVL') {
			$ret = 'Ls';
		} else if($currency == 'LBP') {
			$ret = '&#x20A4;';
		} else if($currency == 'LRD') {
			$ret = '$';
		} else if($currency == 'CHF') {
			$ret = 'CHF';
		} else if($currency == 'LTL') {
			$ret = 'Lt';
		} else if($currency == 'MKD') {
			$ret = '$';
		} else if($currency == 'MYR') {
			$ret = 'RM';
		} else if($currency == 'MUR') {
			$ret = '$';
		} else if($currency == 'MXN') {
			$ret = '$';
		} else if($currency == 'MNT') {
			$ret = '&#x20ae;';
		} else if($currency == 'MZN') {
			$ret = 'MT';
		} else if($currency == 'NAD') {
			$ret = '$';
		} else if($currency == 'NPR') {
			$ret = '$';
		} else if($currency == 'ANG') {
			$ret = '&fnof;';
		} else if($currency == 'NZD') {
			$ret = '$';
		} else if($currency == 'NIO') {
			$ret = 'C$';
		} else if($currency == 'NGN') {
			$ret = '&#x20a6;';
		} else if($currency == 'KPW') {
			$ret = '$';
		} else if($currency == 'NOK') {
			$ret = 'kr';
		} else if($currency == 'OMR') {
			$ret = '$';
		} else if($currency == 'PKR') {
			$ret = '$';
		} else if($currency == 'PAB') {
			$ret = 'B/.';
		} else if($currency == 'PYG') {
			$ret = 'Gs';
		} else if($currency == 'PEN') {
			$ret = 'S/.';
		} else if($currency == 'PHP') {
			$ret = 'Php';
		} else if($currency == 'PLN') {
			$ret = 'zl';
		} else if($currency == 'QAR') {
			$ret = '$';
		} else if($currency == 'RON') {
			$ret = 'lei';
		} else if($currency == 'RUB') {
			$ret = '$';
		} else if($currency == 'SHP') {
			$ret = '&#x20A4;';
		} else if($currency == 'SAR') {
			$ret = '$';
		} else if($currency == 'RSD') {
			$ret = '$';
		} else if($currency == 'SCR') {
			$ret = '$';
		} else if($currency == 'SGD') {
			$ret = '$';
		} else if($currency == 'SBD') {
			$ret = '$';
		} else if($currency == 'SOS') {
			$ret = 'S';
		} else if($currency == 'ZAR') {
			$ret = 'R';
		} else if($currency == 'KRW') {
			$ret = '$';
		} else if($currency == 'LKR') {
			$ret = '$';
		} else if($currency == 'SEK') {
			$ret = 'kr';
		} else if($currency == 'CHF') {
			$ret = 'CHF';
		} else if($currency == 'SRD') {
			$ret = '$';
		} else if($currency == 'SYP') {
			$ret = '&#x20A4;';
		} else if($currency == 'TWD') {
			$ret = 'NT$';
		} else if($currency == 'THB') {
			$ret = '&#xe3f;';
		} else if($currency == 'TTD') {
			$ret = 'TT$';
		} else if($currency == 'TRY') {
			$ret = 'TL';
		} else if($currency == 'TRL') {
			$ret = '&#x20A4;';
		} else if($currency == 'TVD') {
			$ret = '$';
		} else if($currency == 'UAH') {
			$ret = '&#x20b4;';
		} else if($currency == 'GBP') {
			$ret = '&#x20A4;';
		} else if($currency == 'UYU') {
			$ret = '$U';
		} else if($currency == 'UZS') {
			$ret = '$';
		} else if($currency == 'VEF') {
			$ret = 'Bs';
		} else if($currency == 'VND') {
			$ret = '&#x20AB;';
		} else if($currency == 'YER') {
			$ret = '$';
		} else if($currency == 'ZWD') {
			$ret = 'Z$';
		}

		return $ret;
	}

	public static function getDestinationPath($userId){
		return date('Y') .'/'. date('m') .'/'. date('d') .'/'. $userId;
	}

	public static function objectToArray(stdClass $Class){
		# Typecast to (array) automatically converts stdClass -> array.
		$Class = (array)$Class;
		# Iterate through the former properties looking for any stdClass properties.
		# Recursively apply (array).
		foreach($Class as $key => $value){
			if(is_object($value)&&get_class($value)==='stdClass'){
				$Class[$key] = self::objectToArray($value);
			}
		}
		return $Class;
	}

	public static function createDestinationPath($rootPath, $userId){
		$relativePath = Utils::getDestinationPath($userId) ;
		$path = $rootPath . '/' . $relativePath .'/' ;
		if (!file_exists($path)) {
			mkdir($path, 0755, true);
		}
		return 	$relativePath;
	}

	public static function getUserIP() {
		$ip = null;
		$serverKeys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);
		foreach ($serverKeys as $key){
			if (array_key_exists($key, $_SERVER) === true){
				$validIps = true;
				foreach (explode(',', $_SERVER[$key]) as $possibleIp) {
					$possibleIp = trim($possibleIp);
					if (filter_var($possibleIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false){
						$validIps = false;
					}
				}
				if ($validIps) {
					$ip = $_SERVER[$key];
					break;
				}
			}
		}
		return $ip;
	}

	/**
	 * format value of JS var of html input tag value to advoid ' "
	 */
	public function addslashContentValue($content) {
		if(empty($content)) {
			return '';
		}
		return preg_replace("/\r?\n/", "\\n", addslashes($content));
	}

	/**
	 * Encoding (Raw) AmazonS3FileUrl
	 *
	 * ********************************************************************************
	 * Notice that this function is good for AmazoneS3 formated path case only ********
	 * ********************************************************************************
	 *
	 * @param string $file
	 * @return string
	 *
	 * @example	Utils::getEncodedAmazonS3FileUrl('2011/09/26/3/mail_-abc%#.pdf') -> http://expreceipts.s3.amazonaws.com/uploads/2011/09/26/3/mail_-abc%25%23.pdf
	 * @example Utils::getEncodedAmazonS3FileUrl('mail_-abc%#.pdf') -> http://expreceipts.s3.amazonaws.com/uploads/mail_-abc%25%23.pdf
	 * @example Utils::getEncodedAmazonS3FileUrl('/mail_-abc%#.pdf') -> http://expreceipts.s3.amazonaws.com/uploads/mail_-abc%25%23.pdf
	 */
	public function getEncodedAmazonS3FileUrl($file){
		$file = ltrim($file, '/');
		$name = basename($file);
		$path = dirname($file);
		// . is current directory
		if($path == '.') {
			$path = '';
		} else {
			$path .= '/';
		}
		return Configure::read('AmazonS3.ViewURL') . $path. rawurlencode($name);
	}

	/**
	 * Base on Security.salt, generate pair of unique code
	 * 	code1 = md5(uniqid());
	 *  code2 = md5(code1 + Security.salt)
	 *
	 * @return array
	 */
	public function generatePairOfCodes(){
		$code1 = md5(uniqid());
		$code2 = md5(Configure::read('Security.salt') . $code1);
		return array(
			'code1' => $code1,
			'code2' => $code2
		);
	}

	/**
	 * @see Utils::generatePairOfCodes()
	 * 			to know how this 2 codes match
	 *
	 * @param string $code1
	 * @param string $code2
	 *
	 * @return bool
	 */
	public function validatePairOfCodes($code1, $code2){
		$code2i = md5(Configure::read('Security.salt') . $code1);
		return $code2i == $code2;
	}

	/** Generate a token for User Session
	 */
	public function generateEcTokenId() {
		return md5(uniqid(time(), true));
	}

	/**
	 * Data URI scheme
	 */
	public function getImageDataURI($file) {
		$pathinfo = pathinfo($file);
		$ext = strtolower($pathinfo['extension']);
		if($ext == 'jpg' ) {
			$ext = 'jpeg';
		}
		$mime = 'image/'. $ext;
		$contents = file_get_contents($file);
    	$base64 = base64_encode($contents);
    	return "data:$mime;base64,$base64";
	}

	/**
	 *
	 * Check a variable as NaN
	 * @param string $var
	 * @return boolean
	 */
	public function isNaN($var) {
		return !ereg ("^[-]?[0-9]+([\.][0-9]+)?$", $var);
	}

	/**
	 *
	 * Translate unicode characters to ASCII characters
	 * @param string $str
	 */
	public static function unicode2Ascii($str) {
		$unwanted_array = array(
			'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z',
			'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c', 'À' => 'A', 'Á' => 'A',
			'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C',
			'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
			'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
			'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
			'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a',
			'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
			'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
			'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u',
			'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
			'ả' => 'a', 'ạ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ấ' => 'a',
			'ậ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ắ' => 'a',
			'ặ' => 'a', 'Ả' => 'A', 'Ạ' => 'A', 'Ầ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A',
			'Ấ' => 'A', 'Ậ' => 'A', 'Ă' => 'A', 'Ằ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
			'Ắ' => 'A', 'Ặ' => 'A', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e', 'ề' => 'e',
			'ể' => 'e', 'ễ' => 'e', 'ế' => 'e', 'ệ' => 'e', 'Ẻ' => 'E', 'Ẽ' => 'E',
			'Ẹ' => 'E', 'Ề' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ế' => 'E', 'Ệ' => 'E',
			'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
			'ỏ' => 'o', 'ọ' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ố' => 'o',
			'ộ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ớ' => 'o',
			'ợ' => 'o', 'Ỏ' => 'O', 'Ọ' => 'O', 'Ồ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O',
			'Ố' => 'O', 'Ộ' => 'O', 'Ơ' => 'O', 'Ờ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
			'Ớ' => 'O', 'Ợ' => 'O', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u', 'ư' => 'u',
			'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'Ủ' => 'U',
			'Ũ' => 'U', 'Ụ' => 'U', 'Ư' => 'U', 'Ừ' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
			'Ứ' => 'U', 'Ự' => 'U', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
			'Ỳ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y'
		);
		$str = strtr( $str, $unwanted_array );

		return $str;
	}
}
?>