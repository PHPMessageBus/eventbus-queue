# EventBus Queue

[![Build Status](https://travis-ci.org/PHPRepository/eventbus-queue.svg)](https://travis-ci.org/PHPRepository/eventbus-queue) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPMessageBus/eventbus-queue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHPMessageBus/eventbus-queue/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/3e4f3e13-a8c1-4f1e-a5ad-42e799915dfe/mini.png?gold)](https://insight.sensiolabs.com/projects/3e4f3e13-a8c1-4f1e-a5ad-42e799915dfe) [![Latest Stable Version](https://poser.pugx.org/nilportugues/eventbus-queue/v/stable?)](https://packagist.org/packages/nilportugues/eventbus-queue) [![Total Downloads](https://poser.pugx.org/nilportugues/eventbus-queue/downloads?)](https://packagist.org/packages/nilportugues/eventbus-queue) [![License](https://poser.pugx.org/nilportugues/eventbus-queue/license?)](https://packagist.org/packages/nilportugues/eventbus-queue)
[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://paypal.me/nilportugues)

This package is an extension library for `nilportugues/messagebus` that adds Producer-Consumer queues to the EventBus implementation. 

This package will provide you for the classes necessary to build:
 
- **The Producer**: code that sends the Event to a Queue by serializing the Event. This happens synchronously.
- **The Consumer**: code that reads in the background, therefore asynchronously, reads and unserializes the Event from the Queue and passes it to the EventBus to do the heavy lifting.

## Installation

In order to start using this project you require to install it using [Composer](https://getcomposer.org):

```
composer require nilportugues/eventbus-queue
```

## Usage

This package will provide you with a new middleware: `ProducerEventBusMiddleware`.   

This middleware requires a serializer and a storage that will depend on the Queue Adapter used. Supported adapters are: 

- **PDOQueue**: queue built with a SQL database using Doctrine's DBAL.
- **MongoDBQueue**: queue built with MongoDB library.
- **RedisQueue**: queue using the Redis PHP extension.
- **PredisQueue**: queue using the Predis library.
- **FileSystem**: queue built with using the local file system.
- **Amqp**: use RabbitMQ or any queue implementing the Amqp protocol.
- **Beanstalkd**: use Beanstalk as queue.

To set it up, register the `ProducerEventBusMiddleware` to the Event Bus. Because we'll need to define a second EventBus (consumer), we'll call this the `ProducerEventBus`.

**ProducerEventBus**

```php
<?php
$container['LoggerEventBusMiddleware'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBus\LoggerEventBusMiddleware(
        $container['Monolog']
    );
};

//Definition of the Serializer
$container['NativeSerializer'] = function() use ($container) {
    return new \NilPortugues\MessageBus\Serializer\NativeSerializer();
};

//Definition of the Queue driver
$container['RabbitMQ'] = function() use ($container) {
    return new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
};

//Definition of the Event Bus Queue. For instance RabbitMQ.
$container['EventBusQueueAdapter'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBusQueue\Adapters\AmqpQueue(
        $container['NativeSerializer'],
        $container['RabbitMQ'],
        'myEventBusQueue' //queue Name
    );
};

//Definition of the Producer.
$container['ProducerEventBusMiddleware'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBusQueue\ProducerEventBusMiddleware(
        $container['EventBusQueueAdapter']
    );
};

//This is our ProducerEventBus. 
$container['ProducerEventBus'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBus\QueryBus([
        $container['LoggerEventBusMiddleware'],
        $container['ProducerEventBusMiddleware'],
        $container['EventBusMiddleware'],
    ]);
};
```

**Consumer for the ProducerEventBus**

The Consumer will need to be a script that reads the EventBus definitions and subscribed events in order to run until all events are handled. To do so, we'll need to register a new `EventBus` we'll refer as `ConsumerEventBus`. 

We will also like to store events that could not be handled or raised an exception. So a new Queue will be required. For instance, let's store errors in a MongoDB database.

This could be as simple as follows:

```php
<?php
//This is our ConsumerEventBus. 
$container['ConsumerEventBus'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBus\QueryBus([
        $container['LoggerEventBusMiddleware'],
        $container['EventBusMiddleware'],
    ]);
};

$container['MongoDB'] = function() use ($container) {
    return new \MongoDB\Client();
};

//This is an error Queue.
$container['ErrorQueue'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBusQueue\Adapters\MongoQueue(
        $container['NativeSerializer'],
        $container['MongoDB'], 
        'error_queues', 
        'myEventBusErrorQueue'
     );
};

```

**The Consumer code**

Finally, we'll have to call a consumer. This package already provides a fully working consumer implementation: `EventBusWorker`.

Use it as follows:

```php
<?php
//...

$consumer = NilPortugues\MessageBus\EventBusQueue\EventBusWorker();
$consumer->consume(
    $container->get('EventBusQueueAdapter'), 
    $container->get('ErrorQueue'), 
    $container->get('ConsumerEventBus')
);
```

Consumer class will run the `consume` method until all events are consumed. Then it will exit. This is optimal to make sure it will not leak memory.

If you need to keep the consumer running forever use server scripts like [supervisord](http://supervisord.org/). If you need to speed up consuming data you may spin up multiple consumer scripts. [supervisord](http://supervisord.org/) can handle this too.



## Contribute

Contributions to the package are always welcome!

* Report any bugs or issues you find on the [issue tracker](https://github.com/PHPMessageBus/event-bus-queue/issues/new).
* You can grab the source code at the package's [Git repository](https://github.com/PHPMessageBus/event-bus-queue).


## Support

Get in touch with me using one of the following means:

 - Emailing me at <contact@nilportugues.com>
 - Opening an [Issue](https://github.com/PHPMessageBus/event-bus-queue/issues/new)


## Authors

* [Nil Portugués Calderó](http://nilportugues.com)
* [The Community Contributors](https://github.com/PHPMessageBus/event-bus-queue/graphs/contributors)


## License
The code base is licensed under the [MIT license](LICENSE).
