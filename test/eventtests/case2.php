<?php

/**
 *
 * Extended Validation Sample
 *
 * Nested events used for processing input data
 *
 */
namespace EventTests;

class Case2 extends \App\Controller {

	function get($f3) {

		$tests = new \Test();
		$events = \Event::instance();

		$user = new User();

		$f3->set('POST',array(
			'name'=>'',
			'surname'=>'',
			'mail'=>'',
		));
		$user->post($f3);
		$tests->expect(
			$user->post($f3) && empty($f3->errors),
			'No validation at all');
		$f3->clear('errors');



		$events->on('validate.user.name',   'EventTests\Validator->required');
		$events->on('validate.user.surname','EventTests\Validator->required');
		$events->on('validate.user.mail',   'EventTests\Validator->required');
		$events->on('validate.user.mail',   'EventTests\Validator->mail');
		$tests->expect(
			!$user->post($f3)
				&& $f3->errors['mail'][0]=='required'
				&& $f3->errors['surname'][0]=='required'
				&& $f3->errors['mail'][0]=='required'
			, 'Validation is active');
		$f3->clear('errors');



		$f3->set('POST',array(
			'name'=>'Peter',
			'surname'=>'Frost',
			'mail'=>'foobar',
		));
		$tests->expect(
			!$user->post($f3)
			&& $f3->errors['mail'][0]=='invalid'
			, 'Validate input data');
		$f3->clear('errors');



		$f3->set('POST.mail','info@fatfreeframework.com');
		$tests->expect(
			$user->post($f3)
			&& empty($f3->errors)
			, 'Validation passed');
		$f3->clear('errors');



		$f3->set('POST.mail','info@notexistingdomainnamethatmakesnosense.com');
		$tests->expect(
			$user->post($f3)
			&& empty($f3->errors)
			, 'Check mail mx record');
		$f3->clear('errors');



		$events->off('validate.user.mail');
		$events->on('validate.user.mail', 'EventTests\Validator->required');
		$events->on('validate.user.mail', 'EventTests\Validator->mail',10,array('mx'=>true));
		$tests->expect(
			!$user->post($f3)
			&& $f3->errors['mail'][0]=='invalid'
			, 'Refactor mail check event');
		$f3->clear('errors');


		$events->on('validate.user.mail.blacklist', function($data,&$errors,$e){
			$key = $e['options']['key'];
			$mail = explode('@',$data[$key]);
			if (!isset($mail[1]) || in_array($mail[1],array(
					'trash-mail.com',
					'anonymbox.com',
					'fakeinbox.com'
					))) {
				$msg = 'email host is not allowed';
				$errors[$key][] = $msg;
			}
		}, 10, array('key'=>'mail'));

		$f3->set('POST.mail','foo@trash-mail.com');

		$tests->expect(
			!$user->post($f3)
			&& in_array('email host is not allowed',$f3->errors['mail'])
			, 'Extend validation');
		$f3->clear('errors');
		$events->off('validate.user.mail.blacklist');



		$f3->set('POST.password','');
		$events->on('validate.user.password', 'EventTests\Validator->required');
		$events->on('validate.user.password.length', 'EventTests\Validator->length',10, array('key'=>'password'));

		$tests->expect(
			!$user->post($f3)
			&& $f3->errors['password'][0]=='required'
			&& $f3->errors['password'][1]=='too short'
			, 'Add length validation');
		$f3->clear('errors');



		$f3->set('POST.password','123456');
		$events->off('validate.user');
		$events->on('validate.user.password.length', 'EventTests\Validator->length',
			10,
			array('key'=>'password','min'=>6)
		);

		$tests->expect(
			$user->post($f3)
			&& empty($f3->errors)
			, 'Modify length validator');
		$f3->clear('errors');


		$f3->set('POST.password','123');
		$events->on('validate.user.password.length', function($data,&$err){
			if (strlen($data['password']) < 6)
				$err['password'][] = 'no way';
			return false;
		}, 1000);

		$tests->expect(
			!$user->post($f3)
			&& $f3->errors['password'][0]=='no way'
			, 'Intercept length validator');


		$f3->set('results',$tests->results());
	}

}

class User {

	function post($f3) {

		$errors = array();
		\Event::instance()->emit('validate.user',$f3->get('POST'),$errors);
		if (!empty($errors)) {
			// validation fails
			$f3->set('errors', $errors);
			return false;
		} else {
			// user data okay, register
			return true;
		}

	}

}

class Validator extends \Prefab {

	function required($data, &$errors, $e) {
		if (!isset($data[$e['key']]) || empty($data[$e['key']])) {
			$msg = 'required';
			$errors[$e['key']][] = $msg;
			return false;
		}
	}

	function mail($data, &$errors, $e) {
		$mx = isset($e['options']['mx']) ? $e['options']['mx'] : false;
		if (!\Audit::instance()->email($data[$e['key']],$mx)) {
			$msg = 'invalid';
			$errors[$e['key']][] = $msg;
		}
	}

	function length($data, &$errors, $e) {
		$key = $e['options']['key'];
		$min = isset($e['options']['min']) ? $e['options']['min'] : 8;
		$max = isset($e['options']['max']) ? $e['options']['max'] : 32;
		if (strlen($data[$key]) < $min) {
			$errors[$key][] = 'too short';
		}
		if (strlen($data[$key]) > $max) {
			$errors[$key][] = 'too long';
		}
	}
}