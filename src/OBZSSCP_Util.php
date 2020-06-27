<?php

class OBZSSCP_Util {

	public static function log($fileName, $lineNumber, $message) {
		if (OBZSSCP_LOGGING) {
			$logFileName = WP_CONTENT_DIR. '/obzsscp.log';
			if (file_exists($logFileName)) {
				$file = fopen($logFileName, 'r+');
				if ($file) {
					fseek($file, 0, SEEK_END);
				}
			}
			else
			{
				$file = fopen($logFileName, 'w');
			}
			if ($file) {
				fwrite($file, "\r\n" . date("m-d-Y, G:i:s T") . "$fileName - $lineNumber: " . $message);
				fclose($file);
			}
		}
	}

	public static function cleancqrcodes() {
		$obzsscp_qrcode_files = glob(WP_CONTENT_DIR.'/qrcodes/*.png');
		foreach($obzsscp_qrcode_files as $obzsscp_qrcode_file) { 
			if (time()-filemtime($obzsscp_qrcode_file) > ( 60 * 60 * 24 * 7 ) ) {
				unlink($obzsscp_qrcode_file);
			}
		}
	}

	public static function serialize_buffer($buffer) {
		return self::safe_string_escape(serialize($buffer));
	}

	// credit: http://www.php.net/manual/en/function.mysql-real-escape-string.php#100854
	private static function safe_string_escape ($str)
	{
		$len=strlen($str);
		$escapeCount=0;
		$targetString='';
		for ($offset=0; $offset<$len; $offset++)
		{
			switch($c=$str[$offset])
			{
				case "'":
				// Escapes this quote only if its not preceded by an unescaped backslash
					if($escapeCount % 2 == 0) $targetString.="\\";
					$escapeCount=0;
					$targetString.=$c;
					break;
				case '"':
				// Escapes this quote only if its not preceded by an unescaped backslash
					if($escapeCount % 2 == 0) $targetString.="\\";
						$escapeCount=0;
						$targetString.=$c;
						break;
				case '\\':
						$escapeCount++;
						$targetString.=$c;
						break;
				default:
						$escapeCount=0;
						$targetString.=$c;
			}
		}
		return $targetString;
	}

	public static function version_upgrade()
	{
		global $wpdb;

		$OBZSSCP_VERSION = get_option('obzsscp_version');
		if ($OBZSSCP_VERSION==null) 
		{
			add_option('obzsscp_version', OBZSSCP_VERSION);
			$OBZSSCP_VERSION=OBZSSCP_VERSION;
		}
		
		if (version_compare($OBZSSCP_VERSION, OBZSSCP_VERSION, '<')) {
			update_option('obzsscp_version', OBZSSCP_VERSION);
		}

		//apply fixes
		$tableName = $wpdb->prefix . 'obzsscp_electrum_addresses';
		$query = "CREATE TABLE IF NOT EXISTS `$tableName` 
			(
				`id` bigint(12) unsigned NOT NULL AUTO_INCREMENT,
				`mpk` char(150) NOT NULL,
				`mpk_index` bigint(20) NOT NULL DEFAULT '0',
				`address` char(150) NOT NULL,
				`cryptocurrency` char(12) NOT NULL,
				`status` char(24)  NOT NULL DEFAULT 'error',
				`total_received` decimal( 16, 8 ) NOT NULL DEFAULT '0.00000000',
				`last_checked` bigint(20) NOT NULL DEFAULT '0',
				`assigned_at` bigint(20) NOT NULL DEFAULT '0',
				`order_id` bigint(10) NULL,
				`order_amount` decimal(16, 8) NOT NULL DEFAULT '0.00000000',
				PRIMARY KEY (`id`),
				UNIQUE KEY `address` (`address`),
				KEY `status` (`status`),
				KEY `mpk_index` (`mpk_index`),
				KEY `mpk` (`mpk`)
			);";
		$wpdb->query($query);

		$query = "ALTER TABLE `$tableName` CHANGE `mpk` `mpk` CHAR(150), CHANGE `address` `address` CHAR(150)";
		$wpdb->query($query);
	}
}

?>
