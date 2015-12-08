<?php

// Eloquent Database init
use \Illuminate\Database\Capsule\Manager as Capsule;
use \Illuminate\Events\Dispatcher;
use \Illuminate\Container\Container;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASSWORD,
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Set the event dispatcher used by Eloquent models... (optional)
$capsule->setEventDispatcher(new Dispatcher(new Container));

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

// Listen for Query Events for Debug
$events = new Dispatcher;
$events->listen('illuminate.query', function($query, $bindings, $time, $name)
{
    // Format binding data for sql insertion
    foreach ($bindings as $i => $binding) {
        if ($binding instanceof \DateTime) {
            $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
        } else if (is_string($binding)) {
            $bindings[$i] = "'$binding'";
        }
    }

    // Insert bindings into query
    $query = str_replace(array('%', '?'), array('%%', '%s'), $query);
    $query = vsprintf($query, $bindings);

    // Debug SQL queries
    App\Log::msg('SQL: [' . $query . ']');
});

$capsule->setEventDispatcher($events);

// Eloquent Databse init End
