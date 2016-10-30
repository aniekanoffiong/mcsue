function addElement(parentId, elementTag, elementId, html, setClass) {
	//Adds an element to the document
	var p = e(parentId);
	var newElement = document.createElement(elementTag);
	if (setClass) {
		newElement.setAttribute('class', elementId);
	} else {
		newElement.setAttribute('id', elementId);
	}
	newElement.innerHTML = html;
	p.appendChild(newElement);
}

function removeFormerClasses ( elementId ) {
	$(elementId).removeClass('text-success');
	$(elementId).removeClass('text-info');
	$(elementId).removeClass('text-normal');
	$(elementId).removeClass('text-warn');
	$(elementId).removeClass('text-danger');
}

function e(el) {
	return document.getElementById(el);
}

$(document).ready(function(){

	if (e('assgnDeadlineTime') && e('assgnDeadlineTime').value != 0) {
		$("#selectTimeDeadline").removeClass('hidden');
		$("#addTimeDeadline").removeClass('btn-info');
		$("#addTimeDeadline").addClass('btn-danger');
		$("#addTimeDeadline").html('Click To Remove');
	}

	if (e('assgnDeadlineTime1') && e('assgnDeadlineTime1').value != 0) {
		$("#selectTimeDeadline1").removeClass('hidden');
		$("#addTimeDeadline1").removeClass('btn-info');
		$("#addTimeDeadline1").addClass('btn-danger');
		$("#addTimeDeadline1").html('Click To Remove');
	}
	
	if (e('remindOthers') && e('remindOthers').value == 2) {
		$("#staffMembersList").removeClass('hidden');
	}
});

$(function () { 
	$("#birthday").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd",
		yearRange: "1950:2016",
		onSelect : function() {
			$('#question').focus();
		},
	});
	
	$("#datepicker1").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd",
		onSelect : function() {
			$('#addTimeDeadline1').focus();
		},
	});
	
	$("#datepicker").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd",
		onSelect : function() {
			$('#addTimeDeadline').focus();
		},
	});

	$(".datepicker").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "yy-mm-dd",
		onSelect : function() {
			$(this).focus();
		},
	});

	$('.markToDelete').change(function(evt){
		 if (evt.target.checked) {
		// 	alert('true');
			console.log($(this).next('span').id);
			$(this).next('img').addClass('hidden');
			$(evt.target).next('span').removeClass('hidden');
			$('#headSection').removeClass('hidden');
		} else {
			$(this).next('img').removeClass('hidden');
			$(evt.target).next('span').addClass('hidden');
			if(!$('.markToDelete').is(':checked')) {
				$('#headSection').addClass('hidden');
			}
			/*$('.markToDelete').each(function(e) {
				if (!$(this).is(':checked')) {
					unchecked = 'true';
				}
			});
			if (unchecked == 'true') {
				$('#headSection').addClass('hidden');
			}*/
		}
	});

	$("#addAnotherQuestion").click(function(evt) {
		evt.preventDefault();
		var target = evt.target;
		var currentQuestionNo = parseInt($("#addQuesForm").val()) + 1;
		var element = appendChild
		var html = '<label for="question'+ currentQuestionNo +'">Question '+ currentQuestionNo +'</label><textarea rows="3" class=\'form-control full-width\' id="question'+ currentQuestionNo +'" name=\'questions[]\' placeholder=\'Enter Question '+ currentQuestionNo +'\'></textarea>';
	//	var html = '<span class="text-info bold">Image Title: </span><input type="text" name="updatePhotoTitle2" class="new_element" id="photoName2" placeholder="Enter Selected Image Title" /><span class="help-info">Current Image Will Be Replaced</span>';
		addElement('questionsRow', 'div', 'col-md-6', html, 1);
	//	addElement('nameInput2', 'p', '', html, 1);
	});

	$('#senderList').click(function(evt) {
		alert(evt.target.value);
		console.log(evt.target.value);
		alert($(evt.target).val());
	});

	function confirmUserId( userId ) {
		if ($('.thumbnail-mail-span')) {
			alert(userId);
			$('.thumbnail-mail-span').each(function() {
				if ($(this).attr('id') == userId) {
					return false;
				} else {
					return false;
				}
			});
		/*	if ($('.thumbnail-mail-span').attr('id') == userId) {
				alert($('.thumbnail-mail-span').attr('id'));
				return;
			}	*/
		}
	}

	$('#inputSenderList').bind('select', function () {
		var userId = $(this).val();
		//var obtainUserId = 'userId';
		var countOccurrence = 0;
		$(this).val('');
		var ifExist = $('.thumbnail-mail-span').each(function(index, item) {
			if ($(item).attr('id') == userId) {
				countOccurrence++;
				return true;
			}
		});
		if (countOccurrence > 0) {
			var intended = (userId == 'allStaff' || userId == 'allCustomers' || userId == 'allStudents') ? 'Option' : 'User';
			$('#addedPreviously').html('You Have Added This '+ intended +' Already');
			$('#addedPreviously').removeClass('hidden').delay(2000).queue(function(n){
				$('#addedPreviously').addClass('hidden'); n();
			});
			return;
		}
		if (userId == 'allStaff' || userId == 'allCustomers' || userId == 'allStudents') {
			var userTitle = '';
			if (userId == 'allStaff') {
				userTitle = 'All Staff Members';
			} else if(userId == 'allCustomers') {
				userTitle = 'All Customers';
			} else if(userId == 'allStudents') {
				userTitle = 'All Students';
			}
			var generatingElement = '<span class="thumbnail-mail-span" id="'+ userId +'"><img class="thumbnail-width-img" src="users/photodefault.gif" class="thumbnail-mail-img" /><span>'+ userTitle +'</span><span class="btn-closeUserDetails" title="Remove This User" >&times;</span></span>';
			$('#senderDetails').append(generatingElement);
			return;
		}
		var userDetails = $.ajax({
			type	:	"POST",
			url		: 	"messages.php",
			data	:	"obtainUserId=" + userId +"&mailUserType=user",
			async	: 	false,
			dataType: 	"text",
			success	:	function(msg){
				var obj = $.parseJSON(msg);
				var generatingElement = '<span class="thumbnail-mail-span" id="'+ obj.userId +'"><img class="thumbnail-width-img" src="'+ obj.photo +'" class="thumbnail-mail-img" /><span>'+ obj.name +'</span><span class="btn-closeUserDetails" title="Remove This User" >&times;</span></span>';
				$('#senderDetails').append(generatingElement);
			},
		});
	});

	$('#alertDisplay').removeClass('hidden').delay(5000).queue(function(n){
		$('#alertDisplay').addClass('hidden'); n();
	});
	
	$('#delayedReminder').delay(5000).queue(function(n){
		$('#delayedReminder').removeClass('hidden'); n();
	});
	
	$('#mailMessageSubmit').click(function(evt) {
		evt.stopPropagation();
		alert('stop');
		var listOfSenders = '';
		$('.thumbnail-mail-span').each(function(index, item) {
			listOfSenders += $(item).attr('id') + ';';
		});
		var finalList = listOfSenders.slice(0,-1);
		console.log(finalList);
		//$('#mailMessageForm').append();
	});

	$('#divContentEditable').on("click", ".btn-closeUserDetails", function() {
		$(this).parent().remove();
	});

	$("#addTimeDeadline").click(function (evt) {
		evt.preventDefault();
		var target = evt.target;
		if (target.classList.contains('btn-info')) {
			$("#selectTimeDeadline").removeClass('hidden');
			$("#addTimeDeadline").removeClass('btn-info');
			$("#addTimeDeadline").addClass('btn-danger');
			$("#addTimeDeadline").html('Click To Remove');
		} else {
			$("#selectTimeDeadline").addClass('hidden');
			$("#addTimeDeadline").removeClass('btn-danger');
			$("#addTimeDeadline").addClass('btn-info');
			$("#addTimeDeadline").html('Click To Add');
		}
	});
	
	$("#addTimeDeadline1").click(function (evt) {
		evt.preventDefault();
		var target = evt.target;
		if (target.classList.contains('btn-info')) {
			$("#selectTimeDeadline1").removeClass('hidden');
			$("#addTimeDeadline1").removeClass('btn-info');
			$("#addTimeDeadline1").addClass('btn-danger');
			$("#addTimeDeadline1").html('Click To Remove');
		} else {
			$("#selectTimeDeadline1").addClass('hidden');
			$("#addTimeDeadline1").removeClass('btn-danger');
			$("#addTimeDeadline1").addClass('btn-info');
			$("#addTimeDeadline1").html('Click To Add');
		}
	});
	
	$('#myModalOrder').modal({
	  keyboard: true,
	  show: false
	})

	$('#myModalDelete').modal({
		keyboard: true,
		show: 	false
	})

	$('#myModalCustomerInfo').modal({
		keyboard: true,
		show: 	false
	})
	
	if ($('.switch:checkbox').is(':checked')){
		var divId = 'remind' + this.id;
		$("#" + divId).show(300);
	} else {
		$("#" + divId).hide();
	}
	
	$('.switch:checkbox').click(function () {
		var divId = 'remind' + this.id;
		if($(this).is(":checked")){
			$("#" + divId).show(300);
		} else {
			$("#" + divId).hide(200);
		}
	});

	$('#viewMsgDetails').click(function (evt) {
		evt.preventDefault();
		$('#mailDetails').toggleClass('hidden');
		if (e('mailDetails').classList.contains('hidden')) {
			$(this).html('View Details');
		} else {
			$(this).html('Hide Details');
		}
	});
	
	if ($("#cmn-toggle-4").is(':checked')) {
		$("#confirmPaymentSchedule").removeClass('initHidden');		
		$("#confirmPaymentSchedule").show(300);
	} else {
		$('.initHidden').hide();
	}
	
	$("#cmn-toggle-4").click(function () {
		if($(this).is(":checked")){
			$("#confirmPaymentSchedule").show(300);	
		} else {
			$("#confirmPaymentSchedule").hide(200);
		}
	});
	
	$(".viewFileNow").click(function (evt) {
		evt.preventDefault();
		var imgLink = $(this).val();
	//	alert(imgLink);
		e('imageViewerFile').src = imgLink;
		$('#imageViewer').removeClass('hidden');
	});
	
	$('#changeSlide1').change(function(evt) {
		$('#photoDetails1').toggleClass('change_details');
		$('.replace1').addClass('added_element');
		$('.replace1').toggleClass('replace1');
		var html = '<span class="text-info bold">Image Title: </span><input type="text" name="updatePhotoTitle1" class="new_element" id="photoName1" placeholder="Enter Selected Image Title" /><span class="help-info">Current Image Will Be Replaced</span>';
		addElement('nameInput1', 'p', 'photoName1', html);
		$(this).off(evt);
	});
	
	$('#remindOthers').change(function() {
		if (this.value == 2) {
			$('#staffMembersList').removeClass('hidden');
		} else {
			$('#staffMembersList').addClass('hidden');
		}
	});

	$('.adminCounting').change(function(evt) {
		var target = evt.target;
		var currentItem = $('.adminCounting').length;
		for ( var i = 1; i <= currentItem; i++ ) {
			var statusId = '#addAdminStatus-' + i;
			var switchId = 'switch-' + i;
			if (target.id == switchId) {
				if (target.checked) {
					$(statusId).html('Added');
					$(statusId).removeClass('text-danger');
					$(statusId).removeClass('margin-left-xs');
					$(statusId).removeClass('text-success');
					$(statusId).removeClass('margin-left-sm');
					$(statusId).toggleClass('text-success');
					$(statusId).toggleClass('margin-left-sm');
				} else {
					$(statusId).html('Removed');
					$(statusId).removeClass('text-success');
					$(statusId).removeClass('margin-left-sm');
					$(statusId).removeClass('text-danger');
					$(statusId).removeClass('margin-left-xs');
					$(statusId).toggleClass('text-danger');
					$(statusId).toggleClass('margin-left-xs');
				}
			}
		}
	});

	$('#changeSlide2').change(function(evt) {
		$('#photoDetails2').toggleClass('change_details');
		$('.replace2').addClass('added_element');
		$('.replace2').toggleClass('replace2');
		var html = '<span class="text-info bold">Image Title: </span><input type="text" name="updatePhotoTitle2" class="new_element" id="photoName2" placeholder="Enter Selected Image Title" /><span class="help-info">Current Image Will Be Replaced</span>';
		addElement('nameInput2', 'p', 'photoName2', html);	
		$(this).off(evt);
	});
	
	$('.input-width').change(function( evt ) {
		var target = evt.target;
		var getValue = $(target).val();
		var currentItem = $('.input-width').length;
		for ( var i = 1; i <= currentItem; i++ ) {
			var currentClass = 'studentGrade' + i;
			if ( target.id == currentClass ) {
				var gradeId = '#setGrade' + i;
				if ( getValue >= 70 ) {
					removeFormerClasses ( gradeId );
					$(gradeId).addClass('text-success');
					$(gradeId).html('A');
				} else if ( getValue < 70 && getValue >= 60 ) {
					removeFormerClasses ( gradeId );
					$(gradeId).addClass('text-info');
					$(gradeId).html('B');
				} else if ( getValue < 60 && getValue >= 50 ) {
					removeFormerClasses ( gradeId );
					$(gradeId).addClass('text-normal');
					$(gradeId).html('C');
				} else if ( getValue < 50 && getValue >= 45 ) {
					removeFormerClasses ( gradeId );
					$(gradeId).addClass('text-warn');
					$(gradeId).html('D');
				} else if ( getValue < 45 && getValue >= 40 ) {
					removeFormerClasses ( gradeId );
					$(gradeId).addClass('text-warn');
					$(gradeId).html('E');	
				} else {
					removeFormerClasses ( gradeId );
					$(gradeId).addClass('text-danger');
					$(gradeId).html('F');	
				}
			}
		}	
	});
});

$(function () {
	$('#mainContainer').on('click', function(evt) {
		var target = evt.target;
		if (target.classList.contains('myImg')) {
			var modalImg = e("img01");
			var title = e("myModalLabel");		
			modalImg.src = target.id;
			modalImg.alt = target.value;
			title.innerHTML = target.value;
		} else if (target.classList.contains('progId')) {
			var itemName = e('progName');
			var inputForm = e('deleteTimetableForm');
			var inputConfirm = e('deleteTimetableConfirm');
			itemName.innerHTML = target.value;
			inputForm.value = target.id;
			inputConfirm.value = target.value;
		} else if (target.classList.contains('modalUserSettings')) {
			evt.preventDefault();
			e('h3-modal-info').innerHTML = 'Are You Sure You Want To Delete This SlideShow Photo';
			e('availablePhoto').src = target.value;
			e('h3-modal-addedinfo').innerHTML = "This Action Cannot Be Undone";
			$('#confirmDeleteSlidePhoto').on ('click', function() {
				if (target.id == 'deleteSlidePhoto1') {
					$('#inputdeleteSlidePhoto1').val(1);
					$('#userSettingsSubmit').trigger('click');
				} else if (target.id == 'deleteSlidePhoto2') {
					$('#inputdeleteSlidePhoto2').val(1);
					$('#userSettingsSubmit').trigger('click');
				}
			});
		}
	});
});

