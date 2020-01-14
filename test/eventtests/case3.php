<?php

/**
 *
 * Send Mail Event
 *
 * Using parent events to proceed the event task
 *
 */
namespace EventTests;

class Case3 extends \App\Controller {

	function get($f3) {

		$tests = new \Test();
		$events = \Sugar\Event::instance();
		$mail = new Mailer();

		$user = array(
			'name' => 'Corben Dallas',
			'mail' => 'c.d@fhloston-paradise.com'
		);

		$out = $mail->send_welcome($user);
		$tests->expect(
			$out=='send mail to: c.d@fhloston-paradise.com, subject: Welcome, message: Welcome Corben Dallas'
			, 'Send mail test with went propagation');


		$out = $mail->send_login_failed($user);
		$tests->expect(
			$out=='send mail to: c.d@fhloston-paradise.com, subject: Login Failed, message: Hallo Corben Dallas. Login failed from IP: 127.0.0.1'
			, 'Send another test mail');


		$events->on('mail.user.welcome.plugin1',function($msg,&$context){
			$context['subject'] .= ' on board';
			return str_replace('Welcome','Nice to see you,',$msg);
		});
		$out = $mail->send_welcome($user);
		$tests->expect(
			$out=='send mail to: c.d@fhloston-paradise.com, subject: Welcome on board, message: Nice to see you, Corben Dallas'
			, 'Modify message from a plugin');

		$f3->set('results',$tests->results());
	}

}

class Mailer {

	/** @var \Event */
	protected
		$events;

	function __construct() {
		$this->events = \Sugar\Event::instance();

		$this->events->on('mail',function($data,$context){
			//mail($context['mail'],$context['subject'],$data)
			return 'send mail to: '.$context['mail'].', subject: '.$context['subject'].', message: '.$data;
		});
		$this->events->on('mail.user.welcome',function($data,&$context){
			$msg = 'Welcome '.$context['name'];
			$context['subject'] = 'Welcome';
			return $msg;
		});
		$this->events->on('mail.user.login_failed',function($data,&$context){
			$msg = 'Hallo '.$context['name'].'. Login failed from IP: 127.0.0.1';
			$context['subject'] = 'Login Failed';
			return $msg;
		});
	}

	function send_welcome($user) {
		return $this->events->emit('mail.user.welcome',null,$user);
	}
	function send_login_failed($user) {
		return $this->events->emit('mail.user.login_failed',null,$user);
	}
}