<?php

class Paginate {
	private static $itemType;
	private static $page;
	private static $countResult;
	private static $noOfPages;
	private static $start;
	private static $end;
	private $limit;
	private $queryLimitValue;

	public function __construct ( $page, $count, $limit, $itemType ) {
		if ( $itemType == 'FinanceRecord' ) {
			self::$itemType = 'Financial Records';
		} elseif ( $itemType == 'DebtRecord' ) {
			self::$itemType = 'Debt Records';
		} elseif ( $itemType == 'Trainings' ) {
			self::$itemType = 'Training Programmes';
		} else {
			self::$itemType = $itemType.'s';
		}
		self::$page = (int) $page;
		self::$countResult = (int) $count;
		$this->limit = (int) $limit;
	}
	
	public function segmentToPages () {
		self::$noOfPages = ceil(self::$countResult / $this->limit);
		if ( self::$noOfPages > 1 && self::$page <= self::$noOfPages ) {
			self::$start = ( self::$page == 1 ) ? 0 : ($this->limit * (self::$page - 1));
			self::$end = (( self::$start + $this->limit ) >= self::$countResult) ? self::$countResult : ( self::$start + $this->limit );
			$total = self::$end - self::$start;
			$queryLimitValues[] = $total;
			$queryLimitValues[] = self::$start;
			return $queryLimitValues;
		} elseif ( self::$noOfPages == 1 && self::$page == self::$noOfPages ) {
			self::$start = 0;
			self::$end = self::$countResult;
			$queryLimitValues[] = self::$end;
			$queryLimitValues[] = self::$start;
			return $queryLimitValues;
		} else {
			return;
		}
	}
	
	public static function displayPageLink () {
		if ( self::$noOfPages > 1 && self::$page == 1 ) {
		//First Page of more than one total pages
?>
			<div class="row">
				<nav><ul class="pager pager-links">
					<span class="pager-info"><strong><?php echo self::$start+1 .' - '. self::$end.' of '. self::$countResult. ' ' . self::$itemType; ?></strong></span>
					<li class="disabled"><a href="#"><span aria-hidden="true" class="glyphicon glyphicon-backward"></span>&nbsp;&nbsp;Previous</a></li><li class="active"><a href="<?php echo basename($_SERVER['PHP_SELF']); ?>?page=<?php echo self::$page+1; ?>">Next&nbsp;&nbsp;<span aria-hidden="true" class="glyphicon glyphicon-forward"></a></li>
				</ul></nav>
			</div>
<?php
		} elseif ( self::$noOfPages == self::$page && self::$page == 1 ) {
		//Single Page result
		//It won't display Links for Next and Back
?>
			<div class="row">
				<span class="pager-info pager-alone"><strong>Showing Results: <?php echo self::$start+1 .' - '. self::$end.' of '. self::$countResult. ' ' . self::$itemType; ?></strong></span>
			</div>
<?php
		} elseif ( self::$noOfPages == self::$page && self::$page > 1 ) {
		//Last Page; Next Navigation is disabled
?>		
			<div class="row">
				<div><ul class="pager pager-links">
					<span class="pager-info"><strong><?php echo self::$start+1 .' - '. self::$end.' of '. self::$countResult. ' ' . self::$itemType; ?></strong></span>
					<li class="active"><a href="<?php echo $thisPage = ( (self::$page - 1) !== 1 ) ? basename($_SERVER['PHP_SELF']).'?page='.(self::$page-1) : basename($_SERVER['PHP_SELF']); ?>"><span aria-hidden="true" class="glyphicon glyphicon-backward"></span>&nbsp;&nbsp;Previous</a></li><li class="disabled"><a href="#">Next&nbsp;&nbsp;<span aria-hidden="true" class="glyphicon glyphicon-forward"></a></li>
				</ul></div>
			</div>
<?php	
		} elseif ( self::$noOfPages > self::$page && self::$page > 1 ) {
		//Pages in between the first and last page
?>
			<div class="row">
				<nav><ul class="pager pager-links">
					<span class="pager-info"><strong><?php echo self::$start+1 .' - '. self::$end.' of '. self::$countResult. ' ' . self::$itemType; ?></strong></span>
					<li class="active"><a href="<?php echo $thisPage = ( (self::$page - 1) !== 1 ) ? basename($_SERVER['PHP_SELF']).'?page='.(self::$page-1) : basename($_SERVER['PHP_SELF']); ?>"><span aria-hidden="true" class="glyphicon glyphicon-backward"></span>&nbsp;&nbsp;Previous</a></li><li class="active"><a href="<?php echo basename($_SERVER['PHP_SELF']).'?page='.(self::$page+1); ?>">Next&nbsp;&nbsp;<span aria-hidden="true" class="glyphicon glyphicon-forward"></a></li>
				</ul></nav>
			</div>
<?php	
		} else {
			echo 'Page 1 of 1';
		}
	}
}