# Sugar Events 

This is a event system for the PHP Fat-free Framework. Here is what's included so far:

* emit events from any point of your app
* attach one or multiple listeners to an event
* a listener (hook) can have a priority order
* additional options for listeners
* local events on specific objects
* send payload and context data with an event
* sub-events and event propagation
* stop the event chain
* works with F3 v3.5 and PHP v5.4+

---

This event system is **experimental**, so please handle with care.


### Installation


- Method 1: use composer: `composer require ikkez/f3-events`

- Method 2: copy the `lib/event.php` file into your F3 `lib/` directory or another directory that is known to the [AUTOLOADER](https://fatfreeframework.com/quick-reference#AUTOLOAD)


### How does it work:

The Event class is a child of Prefab, so you can get it everywhere like this:

```php
// fetch the global Event instance
$events = \Sugar\Event::instance();
```

Define a listener / hook:

```php
// typical F3 callstring
$events->on('user_login', 'Notification->user_login');
// or with callbacks
$events->on('user_login', function(){
  // ...
});
// or with callables
$events->on('user_login', [$this,'method']); 
```

Send an event:

```php
$events->emit('user_login');
```

Send payload with event:

```php
$events->on('user_login', function($username){
  \Logger::log($username.' logged in');
});
$events->emit('user_login', 'freakazoid');
```

Multiple listeners with prioritization:

```php
$events->on('user_login', function($username){
  \Logger::log($username.' logged in');
}, 10); // 10 is default priority
$events->on('user_login', function(){
  \Flash::addMessage('You have logged in successfully');
}, 20); // 20 is a higher priority and is called first
$events->emit('user_login', 'freakazoid');
```

Stop the event chain:

```php
$events->on('user_login', function($username){
  \Logger::log($username.' logged in');
});
$events->on('user_login', function(){
  \Flash::addMessage('You have logged in successfully');
  return false; // <-- skip any other listener on the same event
}, 20);
$events->emit('user_login', 'freakazoid');
// The logger event isn't called anymore
```

Additional event context data:

```php
$events->on('user_login', function($username,$context){
  if ($context['lang'] == 'en')
    \Flash::addMessage('You have logged in successfully');
  elseif($context['lang'] == 'de')
    \Flash::addMessage('Du hast dich erfolgreich angemeldet');
});
$events->emit('user_login', 'freakazoid', array('lang'=>'en'));
```

Additional listener options:

```php
$events->on('user_login', function($username,$context,$event){
  \Flash::addMessage('You have logged in successfully', $event['options']['type']);
}, 20, array('type'=>'success'));
```

I think that are the basic usage samples that could fit the most cases. Nevertheless here are some more advanced things you can do:


Filter payload:
```php
$events->on('get_total', function($basket){
  $sum = 0;
  foreach($basket as $prod) {
    $sum+=$prod;
  }
  return $sum;
});

$products = array(
  'a' => 2,
  'b' => 8,
  'c' => 15,
);

$sum = $events->emit('get_total',$products);
echo $sum; // 25
```

Add a sub-event. These are called after the parent event. Listeners and sub-events follow the FIFO processing, which means the first that is registered is the first that will be called.

```php
$events->on('get_total.tax', function($sum){
  return $sum+($sum*0.2);
});
$events->on('get_total.shipping', function($sum){
  return $sum+5;
});
$sum = $events->emit('get_total',$products);
echo $sum; // 35
```

Remove hooks:

```php
$events->off('get_total.tax');
$sum = $events->emit('get_total',$products);
echo $sum; // 30
```

There is also a mechanic build in which supports local events for mappers and such, which have implemented it:

```php
$user = new \Model\User();
$events->watch($user)->on('update.email','\Mailer->sendEmailActivationLink');
```


### Unit tests

to add the tests to your local F3 test-bench, add this:

```php
// Event Tests
$f3->concat('AUTOLOAD', ',path/to/f3-events/test/');
\Sugar\EventTests::init();
```


## License

You are allowed to use this plugin under the terms of the GNU General Public License version 3 or later.

Copyright (C) 2017 Christian Knuth [ikkez]
