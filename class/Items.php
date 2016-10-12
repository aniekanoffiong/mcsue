<?php

/** class Items parent class for all items
*/
class Items {
	protected $pdo;
	protected $tableLimit; 
	protected $gridLimit = 6;
	protected $userType;
	protected $userId;
	protected $itemId;
	
	public function __construct () {
		$this->tableLimit = staticFunc::$pref[0]['table_limit'];
	}

	protected function createNewId ( $itemType, $length = NULL, $pdo = NULL ) {
		if ($itemType == "Design") {
			$valTable = 'designs_tbl';
			$valId = 'design_id';
		} elseif ($itemType == "Order") {
			$valTable = 'orders_tbl';
			$valId = 'order_id';
		} elseif ($itemType == "Trainings") {
			$valTable = 'prog_tbl';
			$valId = 'prog_id';
		} elseif ($itemType == "StudentResult") {
			$valTable = 'result_tbl';
			$valId = 'result_id';
		} elseif ($itemType == "Reminder") {
			$valTable = 'reminders_tbl';
			$valId = 'reminder_id';
		} elseif ($itemType == "FinanceRecord") {
			$valTable = 'finance_tbl';
			$valId = 'finance_id';
		} elseif ($itemType == "DebtRecord") {
			$valTable = 'debts_tbl';
			$valId = 'debt_id';
		} elseif ($itemType == "Timetables") {
			$valTable = 'timetable_tbl';
			$valId = 'timetable_id';
		} elseif ($itemType == "Assignment") {
			$valTable = 'assgn_tbl';
			$valId = 'assgn_id';
		} elseif ($itemType == "MailMessages") {
			$valTable = 'msgs_in_out';
			$valId = 'msg_id';
		}
		//generate Id for the programme		
		do {$newId = (isset($length)) ? staticFunc::generateId( $length ) : staticFunc::generateId( 8 );
		//Check just in case ID exist in Database using Do-While loop
		$sql = "SELECT $valId FROM $valTable WHERE $valId = ?";
		$stmt = (isset($pdo)) ? $pdo->prepare($sql) : $this->pdo->prepare($sql);
		$stmt->execute([$newId]);
		$foundId = $stmt->fetchColumn();
		} while ($foundId);
		return $newId;
	}

	/**
	 * Method obtainPage to get the page to be viewed
	 */

	public function obtainPage ( $class, $pdo, $page, $userType, $userId, $itemId = NULL ) {
		$this->pdo = $pdo;
		$this->userType = $userType;
		$this->userId = $userId;
		$setClass = get_class($class);
		echo $setClass;
		if (isset($itemId)) {
			return self::getDetails ( $itemId );
		}
		//Automatically sets the page to its title link without the .php extension and add UI to the end
		$pagelink = explode('.', $page, 2);
		if (method_exists($setClass, $pagelink[0].'UI')) {
			//call_user_func( __CLASS__ .'::'.$pagelink[0].'UI');
			//call_user_func( $setClass.'::'.$pagelink[0].'UI');
			/** 
			 *	Using @ Operator to suppress Strict Standards Error
			 * 	caused by calling the child function in an possible statical way.
			 * 	Error Text: Strict Standards: call_user_func() expects parameter 
			 *	1 to be a valid callback, non-static method Assignment::assignmentsUI()
			 *	should not be called statically, assuming $this from compatible context 
			 *	Assignment in C:\wamp\www\mcSueApp\class\Items.php on line 78
			 */
			@call_user_func( array ( $setClass, $pagelink[0].'UI' ));
		} else {
			//Error Page; Page not available
			staticFunc::errorPage('error');
		}
	}
	
	/**	
	 *	Method getItems returns every Item
	 *	@param $pdo connection variable to the database
	 *	@param $itemsInterface interface of all items
	 *	@return returns all the Item
	 */
	protected function getItems ( $itemType, $displayLimit, $userId = NULL ) {
		if ($itemType == "Design") {
			$valTable = 'designs_tbl';
		} elseif ($itemType == "Order") {
			$valTable = 'orders_tbl';
		} elseif ($itemType == "Trainings") {
			$valTable = 'prog_tbl';				
		} elseif ($itemType == "StudentResult") {
			$valTable = 'result_tbl';
		} elseif ($itemType == "Reminder") {
			$valTable = 'reminders_tbl';
		} elseif ($itemType == "Timetables") {
			$valTable = 'timetable_tbl';
		} elseif ($itemType == "FinanceRecord") {
			$valTable = 'finance_tbl';
		} elseif ($itemType == "DebtRecord") {
			$valTable = 'debt_tbl';
		}
		//Count the total number of results available
		if ( $this->userType == 'Admin' ) {
			if ($itemType == 'Design' ) { //Admin Access identifier
				$count = $this->pdo->query("SELECT count(*) FROM $valTable")->fetchColumn();
			} elseif ( $itemType == 'Order' ) {
			//SQL Based on User Preferences
				$sql = "SELECT count(*) FROM $valTable ";
				if ( staticFunc::$pref[0]['view_orders'] == 'current' ) {
					$sql .= " WHERE DATEDIFF(orders_tbl.due, curdate()) >= 0";
				}
				$count = $this->pdo->query($sql)->fetchColumn();
			} elseif ( $itemType == 'FinanceRecord' ) {
				$sql = "SELECT count(*) FROM finance_tbl INNER JOIN orders_tbl ON finance_tbl.item_id = orders_tbl.order_id";
				$count = $this->pdo->query($sql)->fetchColumn();
			} else {
				$count = $this->pdo->query("SELECT count(*) FROM $valTable")->fetchColumn();
			}
		} elseif ( $this->userType == 'Customer' ) {
			if ( $itemType == 'Design' ) { 
				//All other Access types
				$stmt = $this->pdo->query("SELECT count(*) FROM $valTable WHERE access = 'All'");
				$count = $stmt->fetchColumn();
			} elseif ( $itemType == 'Order' ) {
				//SQL Based on User Preferences
				$sql = "SELECT count(*) FROM $valTable WHERE ";
				if ( staticFunc::$pref[0]['view_orders'] == 'current' ) {
					$sql .= " DATEDIFF(orders_tbl.due, curdate()) >= 0 AND cust_id = :custId";
				} else {
					$sql .= " cust_id = :custId";
				}
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute([':custId' => $userId]);
				$count = $stmt->fetchColumn();
			} elseif ( $itemType == 'Trainings' ) {
				//$sql = "SELECT count(*) FROM $valTable WHERE student_id = :studentId";
				//$stmt = $this->pdo->prepare($sql);
				//$stmt->execute([':studentId' => $userId]);
				$sql = "SELECT count(*) FROM $valTable";
				$count = $stmt = $this->pdo->query($sql)->fetchColumn();
			} else {
				$stmt = $this->pdo->prepare("SELECT count(*) FROM $valTable WHERE cust_id = :custId");
				$stmt->execute([':custId' => $userId]);
				$count = $stmt->fetchColumn();
			}
		} else { //Student
			$count = $this->pdo->query("SELECT count(*) FROM $valTable")->fetchColumn();
		}
		$msg= '';
		if ( $count !== 0 ) {
			$currentPage = ( isset($_GET['page']) && is_numeric($_GET['page'])) ? $_GET['page'] : 1;
			$paginate = new Paginate( $currentPage, $count, $displayLimit, $itemType );
			$page = $paginate->segmentToPages();
			if (!is_array($page)) {
				$type = 'error';
				return $msg = 'Please Use the Navigation Links To View Pages<br />';
			} else {
				$end = $page[0];
				$start = $page[1];
			
				if ( $itemType == "Order" ) {
					if ( $this->userType == 'Customer' ) {
						//Displays results for only orders from present onwards
						$sql = "SELECT orders_tbl.order_id, orders_tbl.design_id, DATE_FORMAT(orders_tbl.order_date, '%a., %D %M, %Y') as order_date, CONCAT_WS(' ', DATE_FORMAT(orders_tbl.due, '%a., %D %M, %Y by %I:%i %p')) as due, DATEDIFF(orders_tbl.due, curdate()) AS days_away, DATE_FORMAT(TIMEDIFF(orders_tbl.due, NOW()), '%k:%i:%s') AS hours_away, orders_tbl.delivery_venue, DATE_FORMAT(orders_tbl.delivered_date, '%a., %D %M, %Y') as delivered_date, CONCAT_WS(' ', cust_tbl.surname, cust_tbl.firstname ) as cust_name, designs_tbl.title, designs_tbl.photo, cust_tbl.photo as user_photo FROM orders_tbl INNER JOIN cust_tbl INNER JOIN designs_tbl ON cust_tbl.cust_id = orders_tbl.cust_id ";
						if ( staticFunc::$pref[0]['view_orders'] == 'current' ) {
							$sql .= " AND DATEDIFF(orders_tbl.due, curdate()) >= 0 AND orders_tbl.design_id = designs_tbl.design_id AND cust_tbl.cust_id = :custId ORDER BY orders_tbl.due ASC LIMIT $end OFFSET $start";
						} else {
							$sql .= " AND orders_tbl.design_id = designs_tbl.design_id AND cust_tbl.cust_id = :custId ORDER BY orders_tbl.due ASC LIMIT $end OFFSET $start";
							$stmt = $pdo->prepare($sql);
							$stmt->execute([':custId' => $userId]);
							return $stmt->fetchAll();
						}
					} else { //Admin Access
						//Displays results for only orders from present onwards
						$sql = "SELECT orders_tbl.order_id, orders_tbl.design_id, DATE_FORMAT(orders_tbl.order_date, '%a., %D %M, %Y') as order_date, CONCAT_WS(' ', DATE_FORMAT(orders_tbl.due, '%a., %D %M, %Y by %I:%i %p')) as due, DATEDIFF(orders_tbl.due, curdate()) AS days_away, DATE_FORMAT(TIMEDIFF(orders_tbl.due, NOW()), '%k:%i:%s') AS hours_away, orders_tbl.delivery_venue, DATE_FORMAT(orders_tbl.delivered_date, '%a., %D %M, %Y') as delivered_date, CONCAT_WS(' ', cust_tbl.surname, cust_tbl.firstname) as cust_name, cust_tbl.photo as user_photo, designs_tbl.title, designs_tbl.photo FROM orders_tbl INNER JOIN cust_tbl INNER JOIN designs_tbl ON ";
						if ( staticFunc::$pref[0]['view_orders'] == 'current' ) {
							$sql .= " DATEDIFF(orders_tbl.due, curdate()) >= 0 AND TIMEDIFF(orders_tbl.due, NOW()) > 0 AND orders_tbl.design_id = designs_tbl.design_id ORDER BY orders_tbl.due ASC LIMIT $end OFFSET $start";
						} else {
							$sql .= " orders_tbl.design_id = designs_tbl.design_id ORDER BY orders_tbl.due ASC LIMIT $end OFFSET $start";
						}
						$stmt = $this->pdo->query($sql);
						return $stmt->fetchAll();
					}
				} elseif ( $itemType == "FinanceRecord" ) {
					$sql = "SELECT FORMAT(finance_tbl.amount_paid, 2) as amount_paid, DATE_FORMAT(finance_tbl.trans_date, '%a., %D %M, %Y') as trans_date, CONCAT_WS(' ', cust_tbl.surname, cust_tbl.firstname) as cust_name, cust_tbl.photo, CONCAT_WS('<br /> ', designs_tbl.title, orders_tbl.added_details) as order_details, CONCAT_WS(' ', staff_tbl.surname, staff_tbl.firstname) as auth_staff FROM finance_tbl INNER JOIN cust_tbl INNER JOIN orders_tbl INNER JOIN designs_tbl INNER JOIN staff_tbl ON finance_tbl.item_id = orders_tbl.order_id AND orders_tbl.cust_id = cust_tbl.cust_id AND orders_tbl.design_id = designs_tbl.design_id AND finance_tbl.auth_staff_id = staff_tbl.staff_id ORDER BY finance_tbl.trans_date ASC LIMIT $end OFFSET $start
					";
					$stmt = $this->pdo->query($sql);
					return $stmt->fetchAll();
				} else {
					$sql = "SELECT * FROM $valTable LIMIT $end OFFSET $start";
					$stmt = $this->pdo->query($sql);
					return $stmt->fetchAll();
				}
			}
		} else {
			return $msg;
		}
	}

	/**	
	 * 	Method deleteItem deletes the item from database
	 *	@param $pdo connection to the database
	 *	@param $itemType type of item to be deleted: used for Dependency Injection
	 *	@param $itemId id of the item to be deleted
	 */
	public function deleteItem ( $itemType, $itemId, $pdo ) {
		if ($itemType == "Design") {
			$valTable = 'designs_tbl';
			$valId = 'design_id';
		} elseif ($itemType == "Order") {
			$valTable = 'orders_tbl';
			$valId = 'order_id';
		} elseif ($itemType == "Trainings") {
			$valTable = 'prog_tbl';
			$valId = 'prog_id';
		} elseif ($itemType == "StudentResult") {
			$valTable = 'result_tbl';
			$valId = 'result_id';
		} elseif ($itemType == "Reminder") {
			$valTable = 'reminders_tbl';
			$valId = 'reminder_id';
		} elseif ($itemType == "Timetables") {
			$valTable = 'timetable_tbl';
			//Deleting according to programmes
			$valId = 'prog_id';
		} elseif ($itemType == "DebtRecord") {
			$valTable = 'debt_tbl';
			$valId = 'debt_id';
		} elseif ($itemType == "MailMessages") {
			$valTable = 'msgs_in_out';
			$valId = 'msg_id';
		}
		//Delete the particular item from the database
		if ($itemType == "Design") {
			$pdo->beginTransaction();
			$sql = "DELETE FROM $valTable WHERE $valId = :itemId LIMIT 1";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':itemId' => $itemId]);
			if ($stmt->rowCount()) {
				$sql = "DELETE FROM design_details_tbl WHERE design_id = :itemId LIMIT 1";
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute([':itemId' => $itemId]);
				if ($stmt->rowCount()) {
					$pdo->commit();
					return 'success';
				} else {
					$pdo->rollBack();
					return 'error';
				}
			} else {
				return 'error';
			}
		} elseif ($itemType == "Timetables") {
			$sql = "DELETE FROM $valTable WHERE $valId = :itemId";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':itemId' => $itemId]);
			if ($stmt->rowCount()) {
				return 'success';
			} else {
				return 'error';
			}
		} else {
			$sql = "DELETE FROM $valTable WHERE $valId = :itemId LIMIT 1";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([':itemId' => $itemId]);
			if ($stmt->rowCount()) {
				return 'success';
			} else {
				return 'error';
			}
		}
	}
	

	/** 
	 *	Cant remember what I created it for!!!
	 */
	private function resultPages () {
		
	}
}