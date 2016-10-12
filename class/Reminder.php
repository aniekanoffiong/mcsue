<?php

class Reminder extends Items implements itemDetailsInterface, UserInterface {
	
	public function setReminder ( $itemId, $daysAway, $pdo ) {
		$userId = ( $_SESSION['userType'] == 'Admin' ) ? 'Admin' : $_SESSION['userId'];
		$reminderId = parent::createNewId ( __CLASS__, 5, $pdo );
		$sql = "INSERT INTO reminders_tbl VALUES (:reminderId, :itemId, :userId, :daysAway, 'N')";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':reminderId' => $reminderId, ':itemId' => $itemId, ':userId' => $userId, ':daysAway' => $daysAway]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}

	public function setPersonalReminder ( $reminderDesc,  $eventDateTime, $remindDateTime, $othersInvolved, $pdo ) {
		$reminderId = parent::createNewId ( __CLASS__, 5, $pdo );
		$sql = "INSERT INTO personal_remind_tbl VALUES(:reminderId, :reminderDesc, :eventDateTime, :remindDateTime, :othersInvolved, :userId, 'N' )";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':reminderId' => $reminderId, ':reminderDesc' => $reminderDesc, ':eventDateTime' => $eventDateTime, ':remindDateTime' => $remindDateTime, ':othersInvolved' => $othersInvolved, ':userId' => $_SESSION['userId'] ]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}

	public function updatePersonalReminder ( $reminderId, $reminderDesc, $eventDateTime, $remindDateTime, $othersInvolved, $pdo ) {
		$sql = "UPDATE personal_remind_tbl SET remind_desc = :reminderDesc, event_datetime = :eventDateTime, remind_datetime = :remindDateTime, others_involved = :othersInvolved, status = 'N' WHERE user_id = :userId AND reminder_id = :reminderId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':reminderDesc' => $reminderDesc, ':eventDateTime' => $eventDateTime, ':remindDateTime' => $remindDateTime, ':othersInvolved' => $othersInvolved, ':userId' => $_SESSION['userId'], ':reminderId' => $reminderId ]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}

	public function postponePersonalReminder ( $reminderId, $reminderDateTime, $pdo ) {
		$sql = "UPDATE personal_remind_tbl SET remind_datetime = :remindDateTime, status = 'N' WHERE user_id = :userId AND reminder_id = :reminderId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':remindDateTime' => $reminderDateTime, ':userId' => $_SESSION['userId'], ':reminderId' => $reminderId ]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}	
	}

	public function updateReminderStatus ( $reminderId, $pdo, $personal = NULL ) {
		$accessTable = (isset($personal)) ? 'personal_remind_tbl' : 'reminders_tbl';
		$sql = "UPDATE $accessTable SET status = 'Y' WHERE reminder_id = :reminderId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':reminderId' => $reminderId]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}

	private function getPersonalReminders ( $reminderId = NULL, $reminderAlert = NULL ) {
		$sql = "SELECT reminder_id, remind_desc ";
		if (!isset($reminderId)) {
			$sql .= ", DATE_FORMAT(event_datetime, '%a., %D %M, %Y by %I:%i %p') as event_datetime, DATE_FORMAT(remind_datetime, '%a., %D %M, %Y by %I:%i %p') as remind_datetime, DATEDIFF(remind_datetime, curdate()) as days_away ";
		} else {
			$sql .= ", DATE_FORMAT(event_datetime, '%Y-%m-%d') as event_date, DATE_FORMAT(event_datetime, '%k') as event_time, DATE_FORMAT(remind_datetime, '%Y-%m-%d') as remind_date, DATE_FORMAT(remind_datetime, '%k') as remind_time, DATEDIFF(remind_datetime, curdate()) as days_away ";
		}
		$sql .= ", others_involved FROM personal_remind_tbl WHERE user_id = :userId ";
		if (!isset($reminderId)) {
			$sql .=" AND status = 'N' ";
		} else {
			$sql .=" AND reminder_id = :reminderId ";
		}
		if (isset($reminderAlert)) {
			$sql .= " AND DATEDIFF(remind_datetime, curdate()) <= 0 AND TIMEDIFF(remind_datetime, NOW()) <= 0";
		}
		$stmt = $this->pdo->prepare($sql);
		if (!isset($reminderId)) {
			$stmt->execute([':userId' => $this->userId ]);
		} else {
			$stmt->execute([':userId' => $this->userId, ':reminderId' => $reminderId ]);
		}
		return $stmt->fetchAll();
	}

	private function personalRemindersCurrent ( $pdo ) {
		$sql = "SELECT sum((DATEDIFF(remind_datetime, curdate()) <= 0) AND (TIMEDIFF(remind_datetime, NOW()) <= 0)) as count FROM personal_remind_tbl WHERE status = 'N'";
		return $stmt = $pdo->query($sql)->fetchColumn();
	}
	
	public function updateReminder ( $reminderId, $daysAway = NULL, $pdo, $auto = NULL ) {
		if ( isset($auto) ) {
			$sql = "SELECT due FROM orders_tbl WHERE order_id = :itemId";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':itemId' => $itemId]);
			$deadline = $stmt->fetchColumn();
			if ($deadline) {
				//Check for deadline distance from the present
				$dateDiff = staticFunc::compareDates ( $deadline );
				/**
				* 	Days Away helps to set reminders that will be 
				*	triggered long in the future when at that time, the 
				*	value for the days away now tallies with the present 
				*	date when compared with the due date
				*/
				if ( $dateDiff >= 14 ) {
					$setDaysAway = 14;
				} elseif ( $dateDiff < 14 && $dateDiff >= 7 ) {
					$setDaysAway = 7;
				} elseif ( $dateDiff < 7 && $dateDiff >= 3 ) {
					$setDaysAway = 3;
				} elseif ( $dateDiff < 3 && $dateDiff >= 1 ) {
					$setDaysAway = 1;
				} elseif ( $dateDiff < 1 ) {
					$setDaysAway = 0;
				}
			} else {
				$sql = "SELECT payment_deadline FROM debts_tbl WHERE debt_id = :itemId";
				$stmt = $pdo->prepare($sql);
				$stmt->execute([':itemId' => $itemId]);
				$deadline = $stmt->fetchColumn();
				$dateDiff = staticFunc::compareDates ( $deadline );
				if ( $dateDiff >= 14 ) {
					$setDaysAway = 14;
				} elseif ( $dateDiff < 14 && $dateDiff >= 7 ) {
					$setDaysAway = 7;
				} elseif ( $dateDiff < 7 && $dateDiff >= 3 ) {
					$setDaysAway = 3;
				} elseif ( $dateDiff < 3 && $dateDiff >= 1 ) {
					$setDaysAway = 1;
				} elseif ( $dateDiff < 1 ) {
					$setDaysAway = 0;
				}
			}
		} else {
			if ( isset($daysAway) ) {
				$setDaysAway = $daysAway;
			} else {
				//Its not auto update; yet daysAway is not set
				return 'error';
			}
		}
		$sql = "UPDATE reminders_tbl SET days_away = :setDaysAway WHERE reminder_id = :reminderId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':setDaysAway' => $setDaysAway, ':reminderId' => $reminderId]);
		if ( $stmt->rowCount() ) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function allOrdersRemind ( $pdo ) {
		$sql = "SELECT CONCAT_WS(' ', 'Order Delivery received:', DATE_FORMAT(orders_tbl.order_date, '%a., %D %M, %Y')) as item_details, CONCAT_WS(' ', cust_tbl.surname, cust_tbl.firstname) as names, DATE_FORMAT(orders_tbl.due, '%a., %D %M, %Y') as due, DATEDIFF(orders_tbl.due, curdate()) as days_away, cust_tbl.cust_id as userId, reminders_tbl.reminder_id, reminders_tbl.item_id, FORMAT(designs_tbl.pricing - finance_tbl.amount_paid, 2) as amount FROM reminders_tbl INNER JOIN orders_tbl INNER JOIN designs_tbl INNER JOIN cust_tbl INNER JOIN finance_tbl ON finance_tbl.item_id = reminders_tbl.item_id AND reminders_tbl.item_id = orders_tbl.order_id AND orders_tbl.cust_id = cust_tbl.cust_id AND orders_tbl.design_id = designs_tbl.design_id AND DATEDIFF(orders_tbl.due, curdate()) >= 0 AND DATEDIFF(orders_tbl.due, curdate()) <= reminders_tbl.days_away AND reminders_tbl.status = 'N' ORDER BY orders_tbl.due ASC";
		return $stmt = $pdo->query($sql)->fetchAll();
	}

	public function allDebtsRemind ( $pdo ) {
		$sql = "SELECT 'Balance Payment for Fees' as item_details, CONCAT_WS(' ', student_tbl.surname, student_tbl.firstname) as names, DATE_FORMAT(debts_tbl.payment_deadline, '%a., %D %M, %Y') as due, DATEDIFF(debts_tbl.payment_deadline, curdate()) as days_away, student_tbl.student_id as userId, reminders_tbl.reminder_id, reminders_tbl.item_id, FORMAT(prog_tbl.fees - (finance_tbl.amount_paid + finance_tbl.amount_2 + finance_tbl.amount_3), 2) as amount FROM reminders_tbl INNER JOIN debts_tbl INNER JOIN finance_tbl INNER JOIN student_tbl INNER JOIN prog_tbl INNER JOIN prog_reg_tbl ON reminders_tbl.item_id = debts_tbl.debt_id AND prog_reg_tbl.student_id = student_tbl.student_id AND prog_reg_tbl.reg_id = finance_tbl.item_id AND prog_reg_tbl.prog_id = prog_tbl.prog_id AND reminders_tbl.status = 'N' ORDER BY debts_tbl.payment_deadline ASC";
		return $stmt = $pdo->query($sql)->fetchAll();
	}

	private function getReminderDetails ( $reminderId, $itemType ) {
		if ( $itemType == "Order" ) {
			$valTable = "orders_tbl";
			$valId = "order_id";
			$itemDetails = "design_id";
			$userTbl = 'cust_tbl';
			$userId = 'cust_id';
		} elseif ( $itemType == "DebtRecord" ) {
			$valTable = "debts_tbl";
			$valId = "debt_id";
			$userTbl = 'student_tbl';
			$userId = 'student_id';	
		}
		$sql = "SELECT reminders_tbl.days_away, $valTable.$valId, CONCAT_WS(' ', $userTbl.surname, $usertbl.firstname) AS names FROM reminders_tbl INNER JOIN $valTable INNER JOIN cust_tbl ON reminder_id = :reminderId AND reminders_tbl.item_id = $valTable.$valId AND $valTable.cust_id = cust_tbl.cust_id AND reminders_tbl.status = 'N'";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':reminderId' => $reminderId]);
		$stmt->fetchAll();
	}
	
	public function allReminders ( $pdo, $id ) {
		$count = self::reminderAlert($pdo, "Order", $id) + self::reminderAlert($pdo, "DebtRecord", $id) + self::personalRemindersCurrent ( $pdo );
		if ( $count == 0 ) {
			return '';
		} else {
			return $count;
		}
	}
	
	private function reminderAlert ( $pdo, $item, $userType ) {
		if ($item == "Order") {
			$dbTbl = 'orders_tbl';
			$dbDate = 'due';
			$dbId = 'order_id';
		} elseif ($item == "DebtRecord") {
			//Requesting Debt reminders
			$dbTbl = 'debts_tbl';
			$dbDate = 'payment_deadline';
			$dbId = 'debt_id'; 
		}
		if ( $userType == 'Admin' ) {
			$sql = "SELECT sum((DATEDIFF($dbTbl.$dbDate, curdate()) <= reminders_tbl.days_away) AND (DATEDIFF($dbTbl.$dbDate, curdate()) >= 0)) as count FROM reminders_tbl INNER JOIN $dbTbl WHERE $dbTbl.$dbId = reminders_tbl.item_id AND reminders_tbl.status = 'N'";
			return $stmt = $pdo->query($sql)->fetchColumn();
		} else {
			$sql = "SELECT sum((DATEDIFF($dbTbl.$dbDate, curdate()) <= reminders_tbl.days_away) AND (DATEDIFF($dbTbl.$dbDate, curdate()) >= 0)) as count FROM reminders_tbl INNER JOIN $dbTbl WHERE $dbTbl.$dbId = reminders_tbl.item_id AND reminders_tbl.user_id = :id AND reminders_tbl.status = 'N'";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':id' => $_SESSION['userId']]);
			return $stmt->fetchColumn();
		}
	}


	/**
	 * VIEWS PAGE
	 */

	/**
	 * 	method remindersUI creates an interface for viewing current reminders and manually setting new ones
	 */
	protected function remindersUI () {
?>
		<div class='row add-item-row'><button class='btn btn-info margin' onclick="window.location.href='user.php'"><strong>Back To User DashBoard</strong></button><button class='btn btn-info' onclick="window.location.href='personalreminders.php'"><strong>View All Personal Reminders</strong></button></div>
		<form method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>"><fieldset>
<?php
		if ( isset($_GET['reminder']) && isset($_GET['item'])) {
			$getReminder[] = staticFunc::unmaskURLParam($_GET['item']);
			if ( $_GET['reminder'] == 'errorval' ) {
				$type = 'error';
				$msg = '<b>The Value of Postpone Reminder Should Be Numerical</b><br /><i>(Reminder below with Red Borders)</i>';
				staticFunc::alertDisplay( $type, $msg, 1 );
			}
		} elseif (isset($_SESSION['reminderStatus'])) {
			$totalMessages = count($_SESSION['reminderStatus']);
			$type = 'success';
			$errorType = NULL;
			foreach ($_SESSION['reminderStatus'] as $key => $value) {
				if ($value['type'] == 'success') {
					$successType = 1;
				} else {
					$errorType += 1;
					$getReminder[] = $value['id'];
				}
			}
			if (isset($errorType)) {
				$type = (isset($successType)) ? 'warning' : 'error';
			}
			$infoMsg = ($totalMessages == 1) ? 'A Reminder Was Not Updated<br /><i>(Reminder below with Red Borders)</i>' : 'Some Reminders Were Not Updated<br /><i>(Reminder below with Red Borders)</i>';
			$successMsg = ($totalMessages == 1) ? 'Your Reminder Was Successfully Updated' : 'Your Reminders Were Successfully Updated';
			$msg = (isset($successType) && isset($errorType)) ? "$infoMsg" : "$successMsg";
			if ($type == 'success') {
				staticFunc::alertDisplay( $type, $msg );
			} else {
				staticFunc::alertDisplay( $type, $msg, 1 );
			}
			unset($_SESSION['reminderStatus']);
		}
		$particularReminder = NULL;
		$reminderAlert = true;
		$getPersonalReminders = self::getPersonalReminders( $particularReminder, $reminderAlert );
		$remindCount = 0;
		$checkOrders = self::allOrdersRemind ( $this->pdo );
		if ( $checkOrders ) {
			$totalRemindersCount = count($checkOrders)
?>
			<h3 class="text-info text-center"><strong>Application Reminders</strong></h3>
			<table class="table table-hover table-responsive table-center">
				<tr class='bg-table'>
					<th>S/N</th>
					<th>DETAILS</th>
					<th>USER CONCERNED</th>
					<th>DUE</th>
					<th>AMOUNT</th>
					<th></th>
					<th>Mark As Viewed</th>
				</tr>
<?php
		foreach ( $checkOrders as $key => $value ) {
			$remindCount++;
			$daysAway = staticFunc::determinePeriod( $value['days_away'] );
			$highlightReminder = (isset($getReminder) && in_array($value['reminder_id'], $getReminder)) ? 'highlightReminder' : '';
			if ( $value['days_away'] <= 3 ) {
				echo "<tr class='bg-danger text-danger $highlightReminder'>";
			} else {
				echo "<tr class='$highlightReminder'>";
			}
?>
				<td><?php echo $remindCount; ?></td>
				<td><?php echo $value['item_details']; ?></td>
				<td><a href="customerdetails.php?customer=<?php echo staticFunc::maskURLParam($value['userId']); ?>" title="View User Details" class="bold"><?php echo $value['names']; ?></a></td>
				<td><?php echo $value['due'].$daysAway; ?></td>
				<td><?php echo $balance = ( $value['amount'] > 0 ) ? 'Balance Payable: <br /><span class="text-danger bold">N '.$value['amount'].'</span>' : 'Balance Payable: <br /><span class="text-success bold">N '.$value['amount'].'</span>'; ?></td>
				<td><a href="orderdetails.php?item=<?php echo staticFunc::maskURLParam($value['item_id']); ?>" class="btn">View Details</a></td>
				<td><input id="switch-<?php echo $remindCount; ?>" class="cmn-toggle switch" type="checkbox" name='switch-<?php echo $remindCount; ?>' value="<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>" /><label for="switch-<?php echo $remindCount; ?>"></label><div id="remindswitch-<?php echo $remindCount; ?>" class="text-normal initHidden" title="Postpone Reminder"><div class="bold text-info">Postpone Reminder</div><input type="number" class="futureRemind" name="futureRemind<?php echo $remindCount; ?>" /><div class="bold inline">&nbsp;Days</div><p class="help-block">This Reminder Is Set In Terms of Number of Days From The <b>Due</b> Date</p></div>
				<input type="hidden" name="determineItem<?php echo $remindCount; ?>" value="<?php echo staticFunc::maskURLParam($value['item_id']); ?>" /></td>
			</tr>
<?php
		}
		$checkDebts = self::allDebtsRemind ( $this->pdo );
		if ( $checkDebts ) {
			$totalRemindersCount += count($checkDebts);
			foreach ( $checkDebts as $key => $value ) {
				$remindCount++;
				$highlightReminder = (isset($getReminder) && in_array($value['reminder_id'], $getReminder)) ? 'highlightReminder' : '';
				$daysAway = staticFunc::determinePeriod( $value['days_away'] );
				if ( $value['days_away'] <= 3 ) {
					echo "<tr class='bg-danger text-danger $highlightReminder'>";
				} else {
					echo "<tr class='$highlightReminder'>";
				}
?>
				<td><?php echo $remindCount; ?></td>
				<td><?php echo $value['item_details']; ?></td>
				<td><a href="studentdetails.php?student=<?php echo staticFunc::maskURLParam($value['userId']); ?>" title="View User Details" class="bold"><?php echo $value['names']; ?></a></td>
				<td><?php echo $value['due'].$daysAway; ?></td>
				<td><?php echo $balance = ( $value['amount'] > 0 ) ? 'Balance Payable: <br /><span class="text-danger bold">N '.$value['amount'].'</span>' : 'Balance Payable: <br /><span class="text-success bold">N '.$value['amount'].'</span>'; ?></td>
				<td><a href="debts.php?item=<?php echo staticFunc::maskURLParam($value['item_id']); ?>" class="btn">View Details</a></td>
				<td><input id="switch-<?php echo $remindCount; ?>" name="switch-<?php echo $remindCount; ?>" class="cmn-toggle switch" type="checkbox" value="<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>" /><label for="switch-<?php echo $remindCount; ?>"></label><div id="remindswitch-<?php echo $remindCount; ?>" class="text-normal initHidden" title="Postpone Reminder"><div class="bold text-info">Postpone Reminder</div><input type="number" class="futureRemind" name="futureRemind<?php echo $remindCount; ?>" /><div class="bold inline">&nbsp;Days</div><p class="help-block">This Reminder Is Set In Terms of Number of Days From The <b>Due</b> Date</p></div>
				<input type="hidden" name="determineItem<?php echo $remindCount; ?>" value="<?php echo $value['item_id']; ?>" /></td>
			</tr>
<?php
			}
		}
		if (!$getPersonalReminders) echo '<tr><td colspan="7"><input type="submit" name="updateReminderSubmit" class="btn btn-info add-item-btn" value="Done" id="updateReminderSubmit" /><input type="hidden" name="updateReminderForm" /></td></tr>';
		echo '</table>';
		//if no orders reminder, check debt reminder only
		} else {
			$checkDebts = self::allDebtsRemind ( $this->pdo );
			$remindCount = 0;
			if ( $checkDebts ) {
?>
				<h3 class="text-info text-center"><strong>Application Reminders</strong></h3>
				<table class="table table-hover table-responsive table-center">
					<tr class='bg-table'>
						<th>S/N</th>
						<th>DETAILS</th>
						<th>USER CONCERNED</th>
						<th>DUE</th>
						<th>AMOUNT</th>
						<th></th>
						<th>Mark As Viewed</th>
					</tr>
<?php		
				$totalRemindersCount = count($checkDebts);
				foreach ( $checkDebts as $key => $value ) {
					$remindCount++;
					$highlightReminder = (isset($getReminder) && in_array($value['reminder_id'], $getReminder)) ? 'highlightReminder' : '';
					$daysAway = staticFunc::determinePeriod( $value['days_away'] );
					if ( $value['days_away'] <= 3 ) {
						echo "<tr class='bg-danger text-danger $highlightReminder'>";
					} else {
						echo "<tr class='$highlightReminder'>";
					}
?>
					<td><?php echo $remindCount; ?></td>
					<td><?php echo $value['item_details']; ?></td>
					<td><a href="studentdetails.php?student=<?php echo staticFunc::maskURLParam($value['userId']); ?>" title="View User Details" class="bold"><?php echo $value['names']; ?></a></td>
					<td><?php echo $value['due'].$daysAway; ?></td>
					<td><?php echo $balance = ( $value['amount'] > 0 ) ? 'Balance Payable: <br /><span class="text-danger bold">N '.$value['amount'].'</span>' : 'Balance Payable: <br /><span class="text-success bold">N '.$value['amount'].'</span>'; ?></td>
					<td><a href="debts.php?item=<?php echo staticFunc::maskURLParam($value['item_id']); ?>" class="btn">View Details</a></td>
					<td><input id="switch-<?php echo $remindCount; ?>" class="cmn-toggle switch" type="checkbox" name="switch-<?php echo $remindCount; ?>" value="<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>" /><label for="switch-<?php echo $remindCount; ?>"></label><div id="remindswitch-<?php echo $remindCount; ?>" class="text-normal initHidden" title="Postpone Reminder"><div class="bold text-info">Postpone Reminder</div><input type="number" class="futureRemind" name="futureRemind<?php echo $remindCount; ?>" /><div class="bold inline">&nbsp;Days</div><p class="help-block">This Reminder Is Set In Terms of Number of Days From The <b>Due</b> Date</p></div>
					<input type="hidden" name="determineItem<?php echo $remindCount; ?>" value="<?php echo $value['item_id']; ?>" /></td>
				</tr>
<?php
				}
			if (!$getPersonalReminders) echo '<tr><td colspan="7"><input type="submit" name="updateReminderSubmit" class="btn btn-info add-item-btn" value="Done" id="updateReminderSubmit" /><input type="hidden" name="updateReminderForm" /></td></tr>';
			echo '</table>';
			} else {
				if (!$getPersonalReminders) {
					if (!isset($_SESSION['reminderStatus'])) {
						$type = 'info';
						$msg = 'There Are No Reminders Currently';
						staticFunc::alertDisplay( $type, $msg, 1 );
					} else {
						unset($_SESSION['reminderStatus']);
					}
				}
			}
		}
		if (isset($totalRemindersCount)) {
			echo "<input type='hidden' name='countReminders' value='$totalRemindersCount' />";
		}
		
		//Displaying Personal Reminders
		if ($getPersonalReminders) {
			$user = new Users;
			$allAdmin = $user->getAdminIds ( $this->pdo );
			$totalPersonalCount = count($getPersonalReminders);
?>
			<h3 class="text-info pad text-center bold">Personal Reminders</h3>
			<table class="table table-responsive table-center table-hover">
				<tr class="bold text-sm">
					<td>Reminder Description</td>
					<td>Event Schedule</td>
					<td>Due Date</td>
					<td>Persons Involved</td>
					<td>Mark As Viewed</td>
				</tr>
<?php
			foreach ( $getPersonalReminders as $key => $value ) {
				$remindCount++;
				$daysAway = staticFunc::determinePeriod( $value['days_away'] );
				$highlightReminder = (isset($getReminder) && in_array($value['reminder_id'], $getReminder)) ? 'highlightReminder' : '';
				if ($value['others_involved'] !== 'Admins' && $value['others_involved'] !== 'None' ) {
					$involved = '';
					$getDetails = staticFunc::compareShortenedID ( $value['others_involved'], $allAdmin, $user, $this->pdo );
					foreach ($getDetails as $getKey => $getValue) {
						$involved .= "<button id='".urldecode($getValue['photo'])."' getValue='".$getValue['names']."' class='btn btn-link myImg' title='View Photo' data-toggle='modal' data-target='#myModalOrder'>".$getValue['name']."</button><br />";
					}
				} elseif ($value['others_involved'] == 'Admins') {
					$involved = 'All Staff Members';
				} else {
					$involved = 'None';
				}
?>
			<tr class="<?php echo $highlightReminder; ?>">
				<td><?php echo $value['remind_desc']; ?></td>
				<td><?php echo $value['event_datetime']; ?></td>
				<td><?php echo $value['remind_datetime'].$daysAway; ?></td>
				<td><?php echo rtrim($involved, ' <br />'); ?></td>
				<td><input id="switch-<?php echo $remindCount; ?>" class="cmn-toggle switch" type="checkbox" name="switch-<?php echo $remindCount; ?>" value="<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>" /><label for="switch-<?php echo $remindCount; ?>"></label><div id="remindswitch-<?php echo $remindCount; ?>" class="text-normal initHidden" title="Postpone Personal Reminder"><div class="bold text-info">Postpone Reminder</div>
					<input type="date" class="datepicker setRemindDate" name="personalRemind<?php echo $remindCount; ?>" />
					<select name="eventTime<?php echo $remindCount; ?>" id="eventTime<?php echo $remindCount; ?>" class="select-min-width form-inline">
						<option value="0" hidden> Time </option>
						<?php
							$durationNo = range(01,24);
							foreach ($durationNo as $number) {
								$timeAll = staticFunc::timeAMPM ( $number );
								echo "<option value={$number}>{$timeAll}</option>";
							}
						?>
					</select>
					<div class="bold inline"></div><p class="help-block">Select A New Due Date & Time</p></div>
				<input type="hidden" name="determineItem<?php echo $remindCount; ?>" value="<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>" /></td>
			</tr>
<?php
			}
?>
			<tr><td colspan="5"><input type="submit" name="updateReminderSubmit" class="btn btn-info add-item-btn" value="Done" id="updateReminderSubmit" /><input type="hidden" name="updateReminderForm" /></td></tr>
			</table>
<?php
		}
		if (isset($totalPersonalCount)) {
			echo "<input type='hidden' name='countPersonalReminders' value='$totalPersonalCount' />";
		}

		echo '</fieldset></form>';
	}

	protected function personalremindersUI () {
		//For Personal Reminders Set By User
		echo "<div class='row add-item-row'><button class='btn btn-info btn-add-item' onclick=\"window.location.href='setreminder.php'\"><strong>Set New Reminder</strong></button></div>";
		$getPersonalReminders = self::getPersonalReminders();
		if ($getPersonalReminders) {
			$user = new Users;
			$allAdmin = $user->getAdminIds ( $this->pdo );
?>
			<hr class="hr-divide"/>
			<h3 class="text-info pad text-center bold">PERSONAL REMINDERS</h3>
			<table class="table table-responsive table-center">
				<tr class="bold text-sm">
					<td>Reminder Description</td>
					<td>Event Schedule</td>
					<td>Reminder Set For</td>
					<td>Persons Involved</td>
					<td></td>
					<td></td>
				</tr>
<?php
			foreach ( $getPersonalReminders as $key => $value ) {
				$daysAway = staticFunc::determinePeriod( $value['days_away'] );
				if ($value['others_involved'] !== 'Admins' && $value['others_involved'] !== 'None' ) {
					$involved = '';
					$getDetails = staticFunc::compareShortenedID ( $value['others_involved'], $allAdmin, $user, $this->pdo );
					foreach ($getDetails as $getKey => $getValue) {
						$involved .= "<button id='".urldecode($getValue['photo'])."' getValue='".$getValue['names']."' class='btn btn-link myImg' title='View Photo' data-toggle='modal' data-target='#myModalOrder'>".$getValue['name']."</button><br />";
					}
				} elseif ($value['others_involved'] == 'Admins') {
					$involved = 'All Staff Members';
				} else {
					$involved = 'None';
				}
?>
			<tr>
				<td><?php echo $value['remind_desc']; ?></td>
				<td><?php echo $value['event_datetime']; ?></td>
				<td><?php echo $value['remind_datetime'].$daysAway; ?></td>
				<td><?php echo rtrim($involved, ' <br />'); ?></td>
				<td><button class="btn btn-info" onclick="window.location.href='editreminder.php?reminder=<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>'">Edit</button></td>
				<td><button class="btn btn-danger progId" id="<?php echo staticFunc::maskURLParam($value['reminder_id']); ?>" value='Delete Reminder' data-toggle="modal" data-target="#myModalDelete">Delete</button></td>
			</tr>
<?php
			}
?>
			</table>
			<!-- Modal VIEW PHOTO -->
			<div class = "modal fade" id = "myModalOrder" tabindex = "-1" role = "dialog" aria-labelledby = "myModalLabel" aria-hidden = "true">
				<div class = "modal-dialog modal-dialog-order">
					<div class = "modal-content modal-transparent">
						<div class = "modal-header modal-header-order">
							<button type = "button" class = "close" data-dismiss = "modal" aria-hidden = "true"><span>&times;</span></button>
							<h4 class="modal-title modal-title-order" id="myModalLabel"></h4>
						</div>
						<div class = "modal-body modal-body-order">
							<img class="img-responsive" id="img01" width="500"/>
							<span class="to-close">Press <kbd>ESC</kbd> or click outside image to close</span>
						</div>
						<div class = "modal-footer modal-footer-order">
							<button type = "button" id="modal-close" class = "btn btn-default btn-order" data-dismiss = "modal">Close</button>
							<button type = "button" id="modal-save" class = "btn btn-primary btn-order">Submit changes</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
			<!-- Modal -->
			<div class="modal fade" id="myModalDelete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
							<h3 class="modal-title text-center to-close" id="myModalLabel">
							</h3>
						</div>
						<form method="post" id="modalFormTimetable">
							<div class="modal-body">
								<h4 class="text-center to-close">Are You Sure You Want To Delete This Reminder?</h4>
								<span class="text-center to-close">This action cannot be undone!</span>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-info btn-order" data-dismiss="modal">No</button>
								<button type="button" class="btn btn-danger btn-order" name="deleteReminderSubmit" id="deleteReminderSubmit" data-dismiss="modal">Yes! Delete</button>
							</div>
							<input type="hidden" name="deleteReminderForm" id="deleteTrainingForm" />
							<input type="hidden" name="deleteReminderConfirm" id="deleteTrainingConfirm" />
						</form>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
<?php
		}
	}

	protected function setreminderUI () {
?>
		<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href='user.php'"><strong>Back To User Dashboard</strong></button></div>
		<?php 
			if (isset($_SESSION['addedAdminError'])) {
				$type = 'error';
				$msg = '<b>You Need To Select At Least One Staff Member From The List</b>';
				staticFunc::alertDisplay($type, $msg, 1);
				unset($_SESSION['addedAdminError']);
			} elseif (isset($_SESSION['eventDate'])) {
				$type = 'error';
				$msg = '<b>Please Enter Event Date in The Proper Format</b>';
				staticFunc::alertDisplay($type, $msg, 1);
				unset($_SESSION['eventDate']);
			} elseif (isset($_SESSION['targetDate'])) {
				$type = 'error';
				$msg = '<b>Please Enter Target Date in The Proper Format</b>';
				staticFunc::alertDisplay($type, $msg, 1);
				unset($_SESSION['targetDate']);
			} elseif (isset($_SESSION['reminderStatus'])) {
				if ($_SESSION['reminderStatus'] == 'success') {
					$type = 'success';
					$msg = '<b>The Reminder Has Been Successfully Set</b>';
					$_POST = array();
				} else {
					$type = 'error';
					$msg = '<b>There Was An Error Setting The Reminder</b>';
				}
				staticFunc::alertDisplay($type, $msg);
				unset($_SESSION['reminderStatus']);
			}
		?>
		<div class="col-md-8 col-md-offset-2">
			<form class="form-horizontal form-add-info" id="edit-item-form" method="post" action="<?php echo basename($_SERVER['PHP_SELF']); ?>">
				<fieldset>
				<legend class="text-info text-center">Set Personal Reminder</legend>
					<div class="row">
						<div class="col-md-12 <?php if (isset(staticFunc::$formInput['reminderDesc'])) { echo 'has-error'; } ?>">
							<label for="reminderDesc">Reminder Description</label>
							<textarea type="text" class="form-control textarea" id="reminderDesc" maxlength="150" name="reminderDesc" col="2" placeholder="Enter Reminder Description" required><?php if (isset($_POST['reminderDesc'])) { echo $_POST['reminderDesc']; }?></textarea>
							<p class="help-block">Enter a Description of the Reminder</p>
						</div>
						<div class="row pad-row">
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['eventDate'])) { echo 'has-error'; } ?>">
								<label for="datepicker">Event Date</label>
								<input type="date" id="datepicker" name="eventDate" maxlength="10" class="form-control" value="<?php if (isset($_POST['eventDate'])) { echo $_POST['eventDate']; }?>" placeholder="YYYY-MM-DD" required />
								<p class="help-block">Select the Event Date</p>
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['eventTime'])) { echo 'has-error'; } ?>">
								<label for="eventTime">Add Event Time</label><br />
								<button class="btn btn-info" id="addTimeDeadline">Click To Add</button>
								<div class="width-sm inline hidden" id="selectTimeDeadline" >
									<select name="eventTime" id="assgnDeadlineTime" class="select-full-width form-inline" required>
										<option value="0" hidden>- Time -</option>
										<?php
											$durationNo = range(01,24);
											foreach ($durationNo as $number) {
												$timeAll = staticFunc::timeAMPM ( $number );
												if ( isset($_POST['eventTime']) && $_POST['eventTime'] == $number ) {
													echo "<option value={$number} selected>{$timeAll}</option>";
												} else {
													echo "<option value={$number}>{$timeAll}</option>";
												}
											}
										?>
									</select>
								</div>
								<p class="help-block">Add Event Time</p>
							</div>
						</div>
						<div class="row pad-row">
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['targetDate'])) { echo 'has-error'; } ?>">
								<label for="datepicker1">Reminder Target Date</label>
								<input type="date" id="datepicker1" name="targetDate" maxlength="10" class="form-control" value="<?php if (isset($_POST['targetDate'])) { echo $_POST['targetDate']; }?>" placeholder="YYYY-MM-DD" required />
								<p class="help-block">Select Date For Reminder To Be Shown</p>
							</div>
							<div class="col-md-6 <?php if (isset(staticFunc::$formInput['targetTime'])) { echo 'has-error'; } ?>">
								<label for="targetTime">Add Reminder Target Time</label><br />
								<button class="btn btn-info inline" id="addTimeDeadline1">Click To Add</button>
								<div class="width-sm inline hidden" id="selectTimeDeadline1" >
									<select name="targetTime" id="assgnDeadlineTime1" class="select-full-width form-inline" required>
										<option value="0" hidden>- Time -</option>
										<?php
											$durationNo = range(01,24);
											foreach ($durationNo as $number) {
												$timeAll = staticFunc::timeAMPM ( $number );
												if ( isset($_POST['targetTime']) && $_POST['targetTime'] == $number ) {
													echo "<option value={$number} selected>{$timeAll}</option>";
												} else {
													echo "<option value={$number}>{$timeAll}</option>";
												}
											}
										?>
									</select>
								</div>
								<p class="help-block">Add Reminder Target Time</p>
							</div>
						</div>
						<?php if ($this->userType == 'Admin') {?>
						<div class="col-md-8 <?php if (isset(staticFunc::$formInput['targetDate'])) { echo 'has-error'; } ?>">
							<label for="remindOthers">Assign This Reminder For Other Users Too</label>
							<select name="remindOthers" id="remindOthers" class="select-full-width form-inline">
								<option value=0 hidden>- Select Others To Remind Too -</option>
								<option value='1' <?php if (isset($_POST['remindOthers']) && $_POST['remindOthers'] == 1) echo 'selected'; ?> >Every Staff Members</option>
								<option value='2' <?php if (isset($_POST['remindOthers']) && $_POST['remindOthers'] == 2) echo 'selected'; ?> >Targetted Staff Members</option>
								<option value='3' <?php if (isset($_POST['remindOthers']) && $_POST['remindOthers'] == 3) echo 'selected'; ?> >None</option>
							</select>
							<p class="help-block">Select Other Users Who Will Receive This Reminder As Well</p>
						</div>
						<?php
							$validating = staticFunc::dateValidator('2014-12-24');
						?>
						<div class="hidden" id="staffMembersList">
							<?php 
								$allAdmin = new Users;
								$getAllAdmin = $allAdmin->getAllUsers('Admin', $this->pdo, $this->userId);
								if ($getAllAdmin) {
									$counter = 0;
									echo '<div class="col-md-8"><table class="table table-responsive"><tr><td colspan="3" class="text-info text-sm bold text-center">Staff Members</td></tr>';
									foreach ($getAllAdmin as $key => $value) {
										$counter++;
?>
										<tr>
											<?php 
												if (isset($_POST['addedAdmin']) && in_array(staticFunc::maskURLParam($value['userId']), $_POST['addedAdmin'])) {
													$checkedValue = 'checked';
													$currentMarginStyle = 'margin-left-sm text-success';
													$currentStatus = 'Added';
												} else {
													$checkedValue = '';
													$currentMarginStyle = 'margin-left-xs text-danger';
													$currentStatus = 'Removed';
												}
											?>
											<td><span id="addAdminStatus-<?php echo $counter; ?>" class="text-center text-xxs <?php echo $currentMarginStyle; ?>"><?php echo $currentStatus; ?></span><br /><input type="checkbox" name="addedAdmin[]" id="switch-<?php echo $counter; ?>" class="cmn-toggle switch adminCounting" value="<?php echo staticFunc::maskURLParam($value['userId']); ?>" <?php echo $checkedValue; ?> ><label for="switch-<?php echo $counter; ?>"></label></td>
											<td><img src="<?php echo urldecode($value['photo']); ?>" class="img-thumbnail"/></td>
											<td><b><?php echo $value['name']; ?></b></td>
										</tr>
<?php
									}
									echo '</table></div>';
								}
							?>
						</div>
						<?php } ?>
					</div>
					<div class="row">
						<input type="submit" id="setPersonalReminderSubmit" name="setPersonalReminderSubmit" class="btn btn-info add-item-btn" value="Set Reminder"/>
					</div>
					<input type="hidden" name="setPersonalReminderForm" />
				</fieldset>
			</form>
		</div>
<?php	
	}

	protected function editreminderUI () {
		echo '<div class="row add-item-row"><button class="btn btn-info btn-add-item" onclick="window.location.href=\'user.php\'"><strong>Back To User Dashboard</strong></button></div>';
		if (isset($_SESSION['reminderStatus'])) {
			if ($_SESSION['reminderStatus'] == 'success') {
				$type = 'success';
				$msg = '<b>The Reminder Has Been Successfully Updated</b>';
				$_POST = array();
			} else {
				$type = 'error';
				$msg = '<b>There Was An Error Updating The Reminder</b>';
			}
			staticFunc::alertDisplay($type, $msg);
			unset($_SESSION['reminderStatus']);
		}
		if (isset($_GET['reminder'])) {
			$reminderId = staticFunc::unmaskURLParam($_GET['reminder']);
			$getReminder = self::getPersonalReminders ( $reminderId );
			if ($getReminder) {
				foreach ($getReminder as $key => $value) {
?>
			<div class="col-md-8 col-md-offset-2">
				<form class="form-horizontal form-add-info" id="edit-item-form" method="post" action="<?php echo basename($_SERVER['PHP_SELF']).'?reminder='.$_GET['reminder']; ?>">
					<fieldset>
					<legend class="text-info text-center">Update Personal Reminder</legend>
						<div class="row">
							<div class="col-md-12 <?php if (isset(staticFunc::$formInput['reminderDesc'])) { echo 'has-error'; } ?>">
								<label for="reminderDesc">Reminder Description</label>
								<textarea type="text" class="form-control textarea" id="reminderDesc" maxlength="150" name="reminderDesc" col="2" placeholder="Enter Reminder Description" required><?php if (isset($_POST['reminderDesc'])) { echo $_POST['reminderDesc']; } else { echo $value['remind_desc']; } ?></textarea>
								<p class="help-block">Update Description of the Reminder</p>
							</div>
							<div class="row pad-row">
								<div class="col-md-6 <?php if (isset(staticFunc::$formInput['eventDate'])) { echo 'has-error'; } ?>">
									<label for="datepicker">Event Date</label>
									<input type="date" id="datepicker" name="eventDate" maxlength="10" class="form-control" value="<?php if (isset($_POST['eventDate'])) { echo $_POST['eventDate']; } else { echo $value['event_date']; } ?>" placeholder="YYYY-MM-DD" required />
									<p class="help-block">Select the Event Date</p>
								</div>
								<div class="col-md-6 <?php if (isset(staticFunc::$formInput['eventTime'])) { echo 'has-error'; } ?>">
									<label for="eventTime">Add Event Time</label><br />
									<button class="btn btn-info" id="addTimeDeadline">Click To Add</button>
									<div class="width-sm inline hidden" id="selectTimeDeadline" >
										<select name="eventTime" id="assgnDeadlineTime" class="select-full-width form-inline" required>
											<option value="0" hidden>- Time -</option>
											<?php
												$durationNo = range(01,24);
												foreach ($durationNo as $number) {
													$timeAll = staticFunc::timeAMPM ( $number );
													if ( (isset($_POST['eventTime']) && $_POST['eventTime'] == $number) || $value['event_time'] == $number ) {
														echo "<option value={$number} selected>{$timeAll}</option>";
													} else {
														echo "<option value={$number}>{$timeAll}</option>";
													}
												}
											?>
										</select>
									</div>
									<p class="help-block">Update Event Time</p>
								</div>
							</div>
							<div class="row pad-row">
								<div class="col-md-6 <?php if (isset(staticFunc::$formInput['targetDate'])) { echo 'has-error'; } ?>">
									<label for="datepicker1">Reminder Target Date</label>
									<input type="date" id="datepicker1" name="targetDate" maxlength="10" class="form-control" value="<?php if (isset($_POST['targetDate'])) { echo $_POST['targetDate']; } else { echo $value['remind_date']; } ?>" placeholder="YYYY-MM-DD" required />
									<p class="help-block">Select Date For Reminder To Be Shown</p>
								</div>
								<div class="col-md-6 <?php if (isset(staticFunc::$formInput['targetTime'])) { echo 'has-error'; } ?>">
									<label for="targetTime">Update Reminder Target Time</label><br />
									<button class="btn btn-info inline" id="addTimeDeadline1">Click To Update</button>
									<div class="width-sm inline hidden" id="selectTimeDeadline1" >
										<select name="targetTime" id="assgnDeadlineTime1" class="select-full-width form-inline" required>
											<option value="0" hidden>- Time -</option>
											<?php
												$durationNo = range(01,24);
												foreach ($durationNo as $number) {
													$timeAll = staticFunc::timeAMPM ( $number );
													if ( (isset($_POST['targetTime']) && $_POST['targetTime'] == $number) || $value['remind_time'] == $number ) {
														echo "<option value={$number} selected>{$timeAll}</option>";
													} else {
														echo "<option value={$number}>{$timeAll}</option>";
													}
												}
											?>
										</select>
									</div>
									<p class="help-block">Update Reminder Target Time</p>
								</div>
							</div>
							<?php if ($this->userType == 'Admin') {?>
							<div class="col-md-8 <?php if (isset(staticFunc::$formInput['targetDate'])) { echo 'has-error'; } ?>">
								<label for="remindOthers">Assign This Reminder For Other Users Too</label>
								<select name="remindOthers" id="remindOthers" class="select-full-width form-inline">
									<option value=0 hidden>- Select Others To Remind Too -</option>
									<option value='1' <?php if ((isset($_POST['remindOthers']) && $_POST['remindOthers'] == 1) || $value['others_involved'] == 'Admins') echo 'selected'; ?> >Every Staff Members</option>
									<option value='2' <?php if ((isset($_POST['remindOthers']) && $_POST['remindOthers'] == 2) || ($value['others_involved'] !== 'Admins' && $value['others_involved'] !== 'None')) echo 'selected'; ?> >Targetted Staff Members</option>
									<option value='3' <?php if ((isset($_POST['remindOthers']) && $_POST['remindOthers'] == 3) || $value['others_involved'] == 'None') echo 'selected'; ?> >None</option>
								</select>
								<p class="help-block">Update List of Other Users Who Will Receive This Reminder As Well</p>
							</div>
							<div class="hidden" id="staffMembersList">
								<?php 
									$allAdmin = new Users;
									$getAllAdmin = $allAdmin->getAllUsers('Admin', $this->pdo, $this->userId);
									$adminIds = $allAdmin->getAdminIds ( $this->pdo );
									if ($getAllAdmin) {
										$counter = 0;
										echo '<div class="col-md-8"><table class="table table-responsive"><tr><td colspan="3" class="text-info text-sm bold text-center">Staff Members</td></tr>';
										$getIds = staticFunc::compareShortenedID ( $value['others_involved'], $adminIds, $allAdmin, $this->pdo, 1 );
										foreach ($getAllAdmin as $key => $value) {
											$counter++;
?>
											<tr>
												<?php 
													if ((isset($_POST['addedAdmin']) && in_array(staticFunc::maskURLParam($value['userId']), $_POST['addedAdmin'])) || in_array($value['userId'], $getIds)) {
															$checkedValue = 'checked';
															$currentMarginStyle = 'margin-left-sm text-success';
															$currentStatus = 'Added';
														} else {
															$checkedValue = '';
															$currentMarginStyle = 'margin-left-xs text-danger';
															$currentStatus = 'Removed';
														}
												?>
												<td><span id="addAdminStatus-<?php echo $counter; ?>" class="text-center text-xxs <?php echo $currentMarginStyle; ?>"><?php echo $currentStatus; ?></span><br /><input type="checkbox" name="addedAdmin[]" id="switch-<?php echo $counter; ?>" class="cmn-toggle switch adminCounting" value="<?php echo staticFunc::maskURLParam($value['userId']); ?>" <?php echo $checkedValue; ?> ><label for="switch-<?php echo $counter; ?>"></label></td>
												<td><img src="<?php echo urldecode($value['photo']); ?>" class="img-thumbnail"/></td>
												<td><b><?php echo $value['name']; ?></b></td>
											</tr>
<?php
										}
										echo '</table></div>';
									}
								?>
							</div>
							<?php } ?>
						</div>
						<div class="row">
							<input type="submit" id="updatePersonalReminderSubmit" name="updatePersonalReminderSubmit" class="btn btn-info add-item-btn" value="Update Reminder"/>
						</div>
						<input type="hidden" name="updatePersonalReminderForm" value="<?php echo $_GET['reminder']; ?>" />
					</fieldset>
				</form>
			</div>
<?php 			
				}
			} else {
				staticFunc::errorPage('error');
			}
		} else {
			staticFunc::errorPage('error');
		}

	}
}