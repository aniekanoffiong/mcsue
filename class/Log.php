<?php

class Log {

	public function createLog ( $pdo, $userId ) {
		$sql = "INSERT INTO log_tbl VALUES ( :logId, :userId, NOW(), :userAgent)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':logId' => NULL, ':userId' => $userId, ':userAgent' => $_SERVER['HTTP_USER_AGENT']]);
		if ($stmt->rowCount()) {
			return 'success';
		} else {
			return 'error!';
		}
	}
	
	public function readLog ( $pdo, $userId ) {
		$sql = "SELECT TIMEDIFF(now(), date_time) as since_login FROM log_tbl WHERE user_id = :userId ORDER BY since_login ASC LIMIT 1";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		$log = $stmt->fetch();
		self::formatLog ( $log['since_login'] );
	}
	
	private function formatLog ( $logValue ) {
		$returned = explode( ':', $logValue, 3 );
		if ( $returned[0] !== '00' ) {
			if ( $returned[0] == 01 ) {
				$loggedIn = ltrim($returned[0], 0).' Hour, ';
			} else {
				$loggedIn = ltrim($returned[0], 0).' Hours, ';
			}
		} else {
			$loggedIn = '';
		}
		if ( $returned[1] !== '00' ) {
			if ( $returned[1] == 01 ) {
				$loggedIn .= ltrim($returned[1], 0).' Minutes, ';
			} else {
				$loggedIn .= ltrim($returned[1], 0).' Minutes, ';
			}
		} else {
			$loggedIn = '';
		}
		if ( $returned[2] !== '00' ) {
			if ( $returned[2] == 01 ) {
				$loggedIn .= ltrim($returned[2], 0).' Second.';
			} else {
				$loggedIn .= ltrim($returned[2], 0).' Seconds.';
			}
		} else {
			$loggedIn = 'Just Logged In!';
		}
		echo $loggedIn;
	}
}