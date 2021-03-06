<?php
/***********************************************************************/
/* ATutor															   */
/***********************************************************************/
/* Copyright (c) 2002-2013                                             */
/* Inclusive Design Institute	                                       */
/* http://atutor.ca													   */
/*																	   */
/* This program is free software. You can redistribute it and/or	   */
/* modify it under the terms of the GNU General Public License		   */
/* as published by the Free Software Foundation.					   */
/***********************************************************************/
// $Id$

$_user_location = 'users';
define('AT_INCLUDE_PATH', 'include/');
require (AT_INCLUDE_PATH.'vitals.inc.php');


if($_POST['cancel']){

	header("Location:users/browse.php");
	exit;
}

$course = intval($_GET['course']);
if ($course == 0) {
	$course = intval($_POST['form_course_id']);
}

if ($course == 0) {
	exit;
}

$sql	= "SELECT access, member_id FROM ".TABLE_PREFIX."courses WHERE course_id=$course";
$course_info = queryDB($sql, array(TABLE_PREFIX, $course), TRUE);
   
if ($_POST['submit']) {
	$_SESSION['enroll'] = AT_ENROLL_YES;
	$_POST['form_course_id'] = intval($_POST['form_course_id']);

	if ($course_info['access'] == 'private') {

		$sql	= "INSERT INTO %scourse_enrollment VALUES (%d, %d, 'n', 0, '"._AT('student')."', 0)";
		$result = queryDB($sql, array(TABLE_PREFIX,$_SESSION['member_id'], $_POST['form_course_id']));
		
		// send the email thing. if needed
		$sql	= "SELECT notify, member_id FROM %scourses WHERE course_id=%d AND notify=1";
		$row = queryDB($sql, array(TABLE_PREFIX, $_POST['form_course_id']), TRUE);
		
		if (isset($row['member_id']) && $row['member_id'] !='') {
			// notify is enabled. get the email
			$sql	= "SELECT email, first_name, last_name FROM %smembers WHERE member_id=%d";
			$row = queryDB($sql, array(TABLE_PREFIX, $row['member_id']), TRUE);

			$to_email = $row['email'];

			$tmp_message  = $row['first_name'].' '.$row['last_name'].",\n\n";
			$tmp_message .= _AT('enrol_msg', $system_courses[$_POST['form_course_id']]['title']);
			$tmp_message .= _AT('enrol_login');
			if ($to_email != '') {

				require(AT_INCLUDE_PATH . 'classes/phpmailer/atutormailer.class.php');

				$mail = new ATutorMailer;

				$mail->From     = $_config['contact_email'];
				$mail->AddAddress($to_email);
				$mail->Subject = _AT('course_enrolment');
				$mail->Body    = $tmp_message;

				if(!$mail->Send()) {
					//echo 'There was an error sending the message';
				   $msg->printErrors('SENDING_ERROR');
				   exit;
				}
				unset($mail);
			}
		}
	} else {

		// public or protected
		$sql	= "INSERT INTO %scourse_enrollment VALUES ('%d', '%d', 'y', 0, '"._AT('student')."', 0)";
		$result = queryDB($sql, array(TABLE_PREFIX, $_SESSION['member_id'], $_POST['form_course_id']));


	}

}

if ($_SESSION['valid_user'] === true) {

	$sql	= "SELECT * FROM %scourse_enrollment WHERE member_id=%d AND course_id=%d";
	$row = queryDB($sql, array(TABLE_PREFIX,$_SESSION['member_id'], $course ), TRUE);

	if (($course_info['access'] == 'public') || ($course_info['access'] == 'protected')) {
		if ($row['member_id'] != '') {
		
			$feedback = array('NOW_ENROLLED', $system_courses[$course]['title']);
			$msg->addFeedback($feedback);
			header("Location:index.php");
		} else if ($course_info['member_id'] != $_SESSION['member_id']) {

			require(AT_INCLUDE_PATH.'header.inc.php'); ?>

			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" name="form_course_id" value="<?php echo $course; ?>">
			<div class="input-form">
				<div class="row">
					<p><?php  echo _AT('use_enrol_button'); ?></p>
				</div>

				<div class="row buttons">
					<input type="submit" name="submit" value="<?php echo _AT('enroll_me'); ?>" />
					<input type="submit" name="cancel" value="<?php echo _AT('cancel'); ?>" />
				</div>
			</div>
			</form>
<?php
		} else {
			// we own this course!
			$msg->printErrors('ALREADY_OWNED');
		}
	} else { // private

		require(AT_INCLUDE_PATH.'header.inc.php'); 

		if ((!$_POST['submit']) && ($row == '')) {

			$msg->printInfos('PRIVATE_ENROL'); ?>

			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<input type="hidden" name="form_course_id" value="<?php echo $course; ?>">
			<input type="submit" name="submit" class="button" value="<?php echo _AT('request_enrollment'); ?>">
			</form>
<?php

		} else if ($_POST['submit']) {
			$msg->printFeedbacks('APPROVAL_PENDING');
		} else if ($course_info['member_id'] != $_SESSION['member_id'] ){
			 // request has already been made
			 $msg->printErrors('ALREADY_ENROLED');
		} else {
			$msg->printErrors('ALREADY_OWNED');
		}
	}

} else {
	require(AT_INCLUDE_PATH.'header.inc.php'); 
	$msg->printErrors('LOGIN_ENROL');	
	?>
	<div class="input-form">
		<div class="row">
			<h3><?php echo _AT('login'); ?></h3>
			<p><a href="login.php?course=<?php echo $_SESSION[course_id]; ?>"><?php echo _AT('login_into_atutor'); ?></a></p>
		</div>
	</div>

	<form action="registration.php" method="get">
	<div class="input-form">
		<div class="row">
			<h3><?php echo _AT('new_user');?></h3>
			<p><?php echo _AT('registration_text'); ?></p>
		</div>
		<div class="row buttons">
			<input type="submit" name="register" value="<?php echo _AT('register'); ?>" class="button" />
		</div>
	</div>
	</form>

<?php
}

require(AT_INCLUDE_PATH.'footer.inc.php');
?> 