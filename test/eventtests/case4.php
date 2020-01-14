<?php

/**
 *
 * Extended Validation Sample
 *
 * Nested events used for processing input data
 *
 */
namespace EventTests;

class Case4 extends \App\Controller {

	function get($f3) {

		$events = \Sugar\Event::instance();

		$events->on('content',function($data,$context) {
			echo $data;
			header('Content-Type: text/html');
			exit();
		});
		$events->on('content.render',function($data,$context) {
			$out = '';
			$event = \Sugar\Event::instance();
			foreach($data as $content)
				$out.=$event->emit('build.content',$content);
			return $out;
		});

		$content = array(
				array(
						'headline' => 'News 1',
						'body' => 'Great awesome News'
				),
				array(
						'headline' => 'News 2',
						'body' => 'Sporty Sport News'
				),
		);

		$events->on('build',function($data){
			return implode($data);
		});

		$events->on('build.content.headline',function($data,$c,$e){
			$data[$e['key']] = '<h2>'.$data[$e['key']].'</h2>';
			return $data;
		});

		$events->on('build.content.body',function($data,$c,$e){
			$data[$e['key']] = '<p>'.$data[$e['key']].'</p>';
			return $data;
		});


		$events->emit('content.render',$content);

	}

}
