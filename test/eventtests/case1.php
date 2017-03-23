<?php

/**
 * The "One Fits All" User Registration Event Sample
 *
 * sub event usage for program flow control
 *
 */
namespace EventTests;

class Case1 extends \App\Controller {

	function __construct() {

		\Event::instance()->on('user.register.mail',function($val){
			//mail($val['email'],'Welcome','Your Registration was successful');
			\Base::instance()->push('logs','welcome mail send');
		});

		\Event::instance()->on('user.register.save.jig',function($val){
			// storage
			$mapper = new \DB\Jig\Mapper(new \DB\Jig('data/'),'users.json');
			$mapper->copyfrom($val);
			$mapper->save();
			\Base::instance()->push('logs','user was saved in JIG database');
		});

		\Event::instance()->on('user.register',function($val){
			// validate first
			if (!\Audit::instance()->email($val['email'])) {
				\Base::instance()->push('logs','abort: user email is not valid');
				return false;
			}
		}, 50);

	}

	function get($f3) {

		$tests = new \Test();
		$events = \Event::instance();

		$user = new User();

		$user->register(array(
			'name'=>'Peter',
			'surname'=>'Frost',
			'email'=>'notexisting',
		));

		$logs = $f3->get('logs');
		$tests->expect(
			count($logs) == 1 && $logs[0] == 'abort: user email is not valid'
			,'User registration failed');
		$f3->clear('logs');


		$data =	array(
			'name'=>'Peter',
			'surname'=>'Frost',
			'email'=>'p.frost@domain.com',
		);
		$user->register($data);

		$logs = $f3->get('logs');
		$tests->expect(
			count($logs) == 2
			&& $logs[0] == 'user was saved in JIG database'
			&& $logs[1] == 'welcome mail send'
			,'User registration completed');
		$f3->clear('logs');

		// alter storage behaviour
		$events->off('user.register.save.jig');
		$events->on('user.register.save.sql',function($val){
			// save in SQL DB
			\Base::instance()->push('logs','user was saved in SQL database');
		});
		$user->register($data);
		$logs = $f3->get('logs');
		$tests->expect(
			count($logs) == 2
			&& $logs[0] == 'user was saved in SQL database'
			&& $logs[1] == 'welcome mail send'
			,'Alter storage behaviour');
		$f3->clear('logs');


		// add another validation
		\Event::instance()->on('user.register',function($val){
			// add captcha check
			\Base::instance()->push('logs','abort: captcha check not completed');
			return false;
		}, 50);
		$user->register($data);
		$logs = $f3->get('logs');
		$tests->expect(
				count($logs) == 1 && $logs[0] == 'abort: captcha check not completed'
				,'Captcha check failed');


		$f3->set('results',$tests->results());
	}

}

class User {

	function register($data) {
		\Event::instance()->emit('user.register',$data);
	}

}