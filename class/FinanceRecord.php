<?php

class FinanceRecord extends Items implements UserInterface {
	
	public function createRecord ( $itemId, $userId, $itmeType, $amountPaid, $staffId, $transDate, $pdo ) {
		//Set financeId Value from inherited method createNewId()
		$financeId = parent::createNewId ( __CLASS__ );
		//Add Finance Record to database
		$sql = "INSERT INTO finance_tbl VALUES ( :financeId, :itemId, :userId, :itemType, :amountPaid, :staffId, curDate() )";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':financeId' => $financeId, ':itemId' => $itemId, ':userId' => $userId, ':itemType' => $itemType, ':amountPaid' => $amountPaid, ':staffId' => $staffId]);
		if ( $stmt->rowCount() > 0 ) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	public function getUserRecord ( $userType, $userId ) {
		if ( $userType == 'Customer' ) {
			$sql = "SELECT * FROM finance_tbl WHERE cust_id = :userId";
		} elseif ( $userType == 'Student' ) {
			$sql = "SELECT * FROM finance_tbl WHERE cust_id = :userId";
		}
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':userId' => $userId]);
		return $stmt->fetchAll();
	}

	
	/**
	*	VIEW PAGES
	*/	
	protected function financeUI () {
		$finances = self::getItems ( __CLASS__, $this->tableLimit );
		if ( !is_array($finances) || empty($finances) ) {
			$type = 'error';
			$msg = "<b>There Are No Current Financial Records Available To View.</b>";
			//Display Alert;
			staticFunc::alertDisplay ( $type, $msg );
		} else {
?>
			<div class="row">
			<div class="panel panel-info panel-order">
				<div class="panel-heading"><span CLASS="text-center"><strong>FINANCIAL RECORDS</strong></span></div>
				<table class="table table-striped table-hover table-responsive table-center">
				<tr>
					<th>Transaction Date</th>
					<th>Customer</th>
					<th>Order Details</th>
					<th>Amount (N)</th>
					<th>Authorising Staff</th>
				</tr>
<?php
			foreach ( $finances as $key => $value ) {
?>
				<tr>
					<td><?php echo $value['trans_date']; ?></td>
					<td><button id="<?php echo urldecode($value['photo']); ?>" value="<?php echo $value['cust_name']; ?>" class="btn btn-link myImg" title="View Customer Photo" data-toggle="modal" data-target="#myModalOrder"><?php echo $value['cust_name']; ?></button></td>
					<td><?php echo $value['order_details']; ?></td>
					<td><?php echo $value['amount_paid']; ?></td>
					<td><?php echo $value['auth_staff']; ?></td>
				</tr>				
<?php		}
			echo '</table></div></div>';
			Paginate::displayPageLink();
?>
		<!-- Modal -->
		<div class = "modal fade" id = "myModalOrder" tabindex = "-1" role = "dialog" aria-labelledby = "myModalLabel" aria-hidden = "true">
			<div class = "modal-dialog modal-dialog-order">
				<div class = "modal-content modal-transparent">
					<div class = "modal-header modal-header-order">
						<button type="button" class="close" data-dismiss="modal" aria-hidden = "true"><span>&times;</span></button>
						<h4 class = "modal-title modal-title-order" id = "myModalLabel">
						</h4>
					</div>
					<div class="modal-body modal-body-order">
						<img class="img-responsive" id="img01" width="500"/>
						<span class="to-close">Press <kbd>ESC</kbd> or click outside image to close</span>
					</div>
					<div class="modal-footer modal-footer-order">
						<button type="button" class="btn btn-default btn-order" data-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary btn-order">Submit changes</button>
					</div>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
<?php
		}
	}
}