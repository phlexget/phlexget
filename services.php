<?php

$container = new Pimple;

$container['event_dispatcher.class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
$container['event_dispatcher'] = $container->share(function($container){
    return new $container['event_dispatcher.class'];
});

$container['buzz.class'] = 'Buzz\\Browser';
$container['buzz_client.class'] = 'Buzz\\Client\\Curl';
$container['buzz'] = function($container){
    $client = new $container['buzz_client.class'];
    return new $container['buzz.class']($client);
};

return $container;