<?php

/* graph.php Graph API class
 *
 * Katy Nicholson, last updated 17/10/2021
 *
 * https://github.com/CoasterKaty
 * https://katytech.blog/
 * https://twitter.com/coaster_katy
 *
 * Sample class to retrieve data through Graph API once logged in
 */


require_once dirname(__FILE__) . '/auth.php';

class modGraph {
        var $modAuth;
        function __construct() {
                $this->modAuth = new modAuth();
        }
        function getProfile() {
                $profile = json_decode($this->sendGetRequest('https://graph.microsoft.com/v1.0/me/'));
                return $profile;
        }
        function getPhoto() {
                //Photo is a bit different, we need to request the image data which will include content type, size etc, then request the image
                $photoType = json_decode($this->sendGetRequest('https://graph.microsoft.com/v1.0/me/photo/'));
                $photo = $this->sendGetRequest('https://graph.microsoft.com/v1.0/me/photo/%24value');
		if (json_decode($photo)->error) {
			// Show initials if no photo exists
			$profile = $this->getProfile();
			if ($profile->givenName && $profile->surname) {
				return '<span class="userPhoto"><span>' . substr($profile->givenName, 0, 1) . substr($profile->surname, 0, 1) . '</span></span>';
			} else {
				return '<span class="userPhoto"><span>' . substr($profile->displayName, 0, 1) . '</span></span>';
			}
		}
                return '<span class="userPhoto"><img src="data:' . $photoType->{'@odata.mediaContentType'} . ';base64,' . base64_encode($photo) . '" alt="User Photo" /></span>';
        }

        function sendGetRequest($URL, $ContentType = 'application/json') {
                $ch = curl_init($URL);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->modAuth->Token, 'Content-Type: ' . $ContentType));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                curl_close($ch);
                return $response;
        }
}
?>
