<?php 

/**	
*	Class Admin for McSue Application
*/
class Admin extends Users implements UserInterface {

	/**	
	 *	VIEWS PAGE
	 */

	protected function indexUI () {
?>
		<div class="row index-row">
			<div class="well index-well">
				<h2 class="text-center"><strong>
					Welcome to McSue Designz . . . Your Home of exquisite Clothing Designs.<br />
					<small>We specialize in making you look outstanding</small></strong></h2>
					<a href="about.php" class="btn btn-info"><b>Learn More About Us</b></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="designs.php" title="Designs"><span class="fa fa-image home-icon"></span>
				<div class="text-center inline-block home-text">Designs</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="customers.php" title="Customers"><span class="fa fa-users home-icon"></span>
				<div class="text-center inline-block home-text">Customers</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="orders.php" title="Orders"><span class="fa fa-shopping-cart home-icon"></span>
				<div class="text-center inline-block home-text">Orders</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="students.php" title="Students"><span class="fa fa-book home-icon"></span>
				<div class="text-center inline-block home-text">Students</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="training.php" title="Training"><span class="glyphicon glyphicon-education home-icon"></span>
				<div class="text-center inline-block home-text">Training</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="finance.php" title="Finance"><span class="glyphicon glyphicon-piggy-bank home-icon"></span>
				<div class="text-center inline-block home-text">Finance</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="debts.php" title="Debts"><span class="fa fa-money home-icon"></span>
				<div class="text-center inline-block home-text">Debts</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="settings.php" title="Application Settings"><span class="fa fa-gear home-icon"></span>
				<div class="text-center inline-block home-text">Application Settings</div></a>
			</div>
			<div class="col-sm-6 col-md-4 home-icon">
				<a href="user.php" title="Personal Area"><span class="fa fa-user home-icon"></span>
				<div class="text-center inline-block home-text">Personal Area</div></a>
			</div>
		</div>	
<?php	
	}
}