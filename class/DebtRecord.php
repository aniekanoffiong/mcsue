<?php

class DebtRecord extends Items implements itemDetailsInterface, UserInterface {
	
	private function createDebtRecord ( $pdo, $custId, $itemId, $amount, $paymentDeadline ) {
		//Set debtId Value from inherited method createNewId()
		$debtId = parent::createNewId ( __CLASS__ );
		//Add Finance Record to database
		$sql = "INSERT INTO debts_tbl VALUES (:debtId, :custId, :itemId, :amount, :paymentDeadline )";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':debtId' => $debtId, ':custId' => $custId, ':itemId' => $itemId, ':amount' => $amount, ':paymentDeadline' => $paymentDeadline]);
		if ($stmt->rowCount() > 0) {
			return 'success';
		} else {
			return 'error';
		}
	}
	
	private function getDetails ( $custId ) {
		$sql = "SELECT * FROM debt_tbl WHERE cust_id = :custId";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([':custId' => $custId]);
		return $stmt->fetchAll();
	}
}