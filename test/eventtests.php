<?php

/**
 * Created by PhpStorm.
 * User: ikkez
 * Date: 01.11.2015
 * Time: 23:23
 */
class EventTests extends \App\Controller {

	static function init() {
		/** @var \Base $f3 */
		$f3 = \Base::instance();
		$f3->menu['/event'] = 'Events';
		$f3->menu['/event/case1'] = 'Events.C1';
		$f3->menu['/event/case2'] = 'Events.C2';
		$f3->menu['/event/case3'] = 'Events.C3';
		$f3->menu['/event/case4'] = 'Events.C4';
		$f3->route('GET /event','EventTests->get');
		$f3->route('GET /event/case1','EventTests\Case1->get');
		$f3->route('GET /event/case2','EventTests\Case2->get');
		$f3->route('GET /event/case3','EventTests\Case3->get');
		$f3->route('GET /event/case4','EventTests\Case4->get');
	}
	function get(\Base $f3) {

		$tests = new \Test();

		$events = \Event::instance();

		$tests->expect(true,'Events initiated');

		$events->on('foo','Foo->foo');
		$events->on('bar','Foo->bar');

		$tests->expect(
			$f3->exists('EVENTS.foo',$foo) && $foo == array(10=>array('Foo->foo')) &&
			$f3->exists('EVENTS.bar',$bar) && $bar == array(10=>array('Foo->bar'))
			,'Add events to hive');

		$events->on('foo','Foo->foo2');

		$tests->expect(
			$f3->exists('EVENTS.foo',$foo) && $foo == array(10=>array('Foo->foo','Foo->foo2'))
			,'Multiple listeners on a single event');

		$events->on('foo.baz','Foo->baz');

		$tests->expect(
			$f3->exists('EVENTS.foo.baz',$foobar) && $foobar == array(10=>array('Foo->baz'))
			,'Add sub-event');

		$f3->clear('EVENTS');

		$events->on('foo',function($args){
			return $args+100;
		});
		$events->on('foo',function($args){
			return $args+10;
		});

		$tests->expect(
			$events->emit('foo') == 110
			,'Emit simple event');

		$tests->expect(
			$events->emit('foo',1) == 111
			,'Emit event with arguments');

		$events->on('foo',function($args){
			return $args*2;
		});

		$tests->expect(
			$events->emit('foo',5) == 230
			,'FIFO processing on multiple event listeners');

		$events->on('foo',function($args){
			return $args-7;
		},50);

		$tests->expect(
			$events->emit('foo',5) == 216
			,'Custom prioritisation for event listeners');

		$tests->expect(
			!$events->has('eventX') && $events->has('foo')
			,'Check existence of event keys');

		$events->off('foo');

		$tests->expect(
			!$events->has('foo')
			,'Remove existing event listeners for an event key');

		$tests->expect(
			$events->emit('foo',5) == 5
			,'Emitting events with no listeners attached');

		$events->on('foo.bar',function($args){
			return $args.'b';
		});

		$tests->expect(
			$events->emit('foo','a') == 'ab'
			,'Emit downwards to sub-events');

		$events->on('foo.bar',function($args){
			return $args.'c';
		});

		$tests->expect(
			$events->emit('foo','a') == 'abc'
			,'FIFO test on multiple listeners on same sub-event');

		$events->on('foo.bar',function($args){
			return 'x'.$args.'x';
		},50);

		$tests->expect(
			$events->emit('foo','a') == 'xaxbc'
			,'Sub-event prioritisation');

		$events->on('foo',function($args){
			return strrev($args);
		});

		$tests->expect(
			$events->emit('foo','az') == 'xzaxbc'
			,'Emit calls event key listeners first');

		$tests->expect(
			$events->emit('foo.bar','az') == 'cbxzax'
			,'Emit on a sub-event propagates upwards to the root');

		$events->on('foo.bar.up',function($args){
			return strtoupper($args);
		});

		$tests->expect(
			$events->emit('foo.bar','az') == 'CBXZAX'
			,'Sub-Events are called after its parent');

		$events->on('foo.bar.up',function($args){
			return false;
		},50);

		$tests->expect(
			$events->emit('foo.bar','az') == 'cbxzax'
			,'Cancel listener stack from prioritized listener');

		$events->on('foo',function($args){
			return false;
		},50);

		$tests->expect(
			$events->emit('foo.bar','az') == 'xazxbc'
			,'Break event propagation chain from upper listener');

		$tests->expect(
			$events->emit('missing.event',123) == 123
			,'Emit event with non existing listeners');

		$events->on('bar.baz.narf',function($args, $context, $e) use ($tests) {
			$tests->expect(
				$e['name'] == 'bar.baz.narf'
				,'Event name is passed to listener');
		});
		$events->emit('bar');

		$events->on('context.one',function($args, $context) use ($tests,$events) {
			$tests->expect(
				$context['language'] == 'en'
				,'Send contextual data along with the event');
			$events->off('context.one');
		});

		$data = array('text'=>'Hello World');
		$context = array('language'=>'en');
		$events->emit('context',$data,$context);

		$events->on('context.two',function($args, &$context) {
			$context['language'] = 'de';
			$args['text'] = 'Hallo Welt';
			return 'Hallo Welt';
		});
		$tests->expect(
			$events->emit('context',$data,$context) == 'Hallo Welt' &&
			$context['language'] == 'de'
			,'Alter contextual data within a listener');

		$events->on('context.options',function($args, $context, $e) use ($tests,$events) {
			$tests->expect(
				isset($e['options']['foo'])
				&& $e['options']['foo'] == 'bar'
				,'Pass additional options to the listener');
		}, 10, array('foo'=>'bar'));
		$events->emit('context');

		$dumpy1 = new Dumpy('goofy');
		$dumpy2 = new Dumpy('donald');
		$tests->expect(
			$dumpy1->getName() == 'goofy' && $dumpy2->getName() == 'donald'
			,'Created dummy objects');

		$events->watch($dumpy1)->on('get.name',function($val){
			return ucfirst($val);
		});

		$tests->expect(
			$dumpy1->getName() == 'Goofy' && $dumpy2->getName() == 'donald'
			,'Add local event to a single object');


		$f3->set('results',$tests->results());
	}

}

class Dumpy {
	protected $name;
	protected $events;

	function __construct($name) {
		$this->name = $name;
		$this->events = \Event::instance()->watch($this);
	}

	function getName() {
		return $this->events->emit('get.name',$this->name);
	}

	function __destruct() {
		$this->events->unwatch($this);
	}
}