<?php
require_once dirname(__FILE__) . '/certtools.php';
require_once dirname(__FILE__) . '/mysql.php';
require_once dirname(__FILE__) . '/graph-noninteractive.php';

class alertMailTask  extends modCertTools {

	private $modGraph;
	private $settings;
	function __construct() {
		$this->modGraph = new modGraphNI();
		parent::__construct();
		$this->settings = $this->getSettings();
		error_reporting(E_ALL & ~E_NOTICE);
	}

	function sendAlert() {
		if ($this->settings['emailAlerts']) {
			$apps = $this->getApps(0, $pageCount);

			// Output table showing Warning first, then Expired. Only show items which haven't been muted.
			$alertMuted = 0;
			$alertTable = '<table><thead><tr><td>Status</td><td>Expiry Date</td><td>Item</td><td>Type</td></tr></thead><tbody>';
			foreach ($apps as $app) {
				if ($app['status'] == 'WARN' && $app['muteAlert'] != '1') {
					$alertTable .= '<tr><td>Warning</td><td>' . date('Y-m-d', strtotime($app['endDateTime'])) . '</td><td>' . ($app['link'] ? ' <a href="' . $app['link'] . '">' . $app['displayName'] . '</a>' : $app['displayName']) . '</td><td>' . $app['typeDisplayName'] . '</td></tr>';
				}
				if ($app['status'] == 'WARN' && $app['muteAlert']) $alertMuted++;
			}
			foreach ($apps as $app) {
				if ($app['status'] == 'EXPIRED' && $app['muteAlert'] != '1') {
					$alertTable .= '<tr><td>Expired</td><td>' . date('Y-m-d', strtotime($app['endDateTime'])) . '</td><td>' . ($app['link'] ? ' <a href="' . $app['link'] . '">' . $app['displayName'] . '</a>' : $app['displayName']) . '</td><td>' . $app['typeDisplayName'] . '</td></tr>';
				}
				if ($app['status'] == 'EXPIRED' && $app['muteAlert']) $alertMuted++;

			}

			$alertTable .= '</tbody></table>';

			$alertMessage = $this->settings['alertTemplate'];
			$alertMessage = str_replace('{$alertMuted}', $alertMuted, $alertMessage);
			$alertMessage = str_replace('{$alertDays}', $this->settings['alertDays'], $alertMessage);
			$alertMessage = str_replace('{$data}', $alertTable, $alertMessage);
			$alertMessage = str_replace('{$url}', '<a href="' . _URL . '">' . _URL . '</a>', $alertMessage);




			/* TO DO

				*** Update database saying when it was last alerted, which item, which recipient
				*** obey the 'Alert every x days' setting
				*** actually send to the recipients instead of me

			*/


			$mailArgs = array(	'subject' => 'Expiring Certificates Notification',
						'importance' => 'High',
						'toRecipients' => array(array('name' => 'Katy', 'address' => 'katy@katynicholson.uk')),
						'body' => $alertMessage);


			// sender email address below
			$this->modGraph->sendMail($this->settings['senderEmail'], $mailArgs);
		}
	}

}
$alertMailer = new alertMailTask();
$alertMailer->sendAlert();
?>
