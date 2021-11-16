<?php
class baseClass {

	function prettyDate($date) {
                //$date should be in timestamp form, Y-m-d H:i:s
		// This shows a very rough date which is not accurate but good enough for "roughly when did this happen"
                $pastDate = strtotime($date);
                $curDate = time();
                $timeElapsed = $curDate - $pastDate;
                $hours = round($timeElapsed / 3600);
                $days = round($timeElapsed / 86400);
                $weeks = round($timeElapsed / 604800);
                $months = round($timeElapsed / 2600640);
                $years = round($timeElapsed / 31207680);
                if ($years > 0) return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
                if ($months > 0) return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                if ($weeks > 0) return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
                if ($days > 0) return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                if ($hours > 0) return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                return 'Just now';
        }

        function base64UrlEncode($toEncode) {
                return str_replace('=', '', strtr(base64_encode($toEncode), '+/', '-_'));
        }


	function uuid() {
        	//uuid function is not my code, but unsure who the original author is. KN
	        //uuid version 4
	        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	            // 32 bits for "time_low"
	            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	            // 16 bits for "time_mid"
	            mt_rand( 0, 0xffff ),
	            // 16 bits for "time_hi_and_version",
	            // four most significant bits holds version number 4
	            mt_rand( 0, 0x0fff ) | 0x4000,
	            // 16 bits, 8 bits for "clk_seq_hi_res",
	            // 8 bits for "clk_seq_low",
	            // two most significant bits holds zero and one for variant DCE1.1
	            mt_rand( 0, 0x3fff ) | 0x8000,
	            // 48 bits for "node"
	            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	        );
	}

}
?>
