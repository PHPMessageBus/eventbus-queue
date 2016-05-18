# EventBus Queue

[![Build Status](https://travis-ci.org/PHPMessageBus/eventbus-queue.svg?branch=master)](https://travis-ci.org/PHPMessageBus/eventbus-queue) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPMessageBus/eventbus-queue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHPMessageBus/eventbus-queue/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/3e4f3e13-a8c1-4f1e-a5ad-42e799915dfe/mini.png?gold)](https://insight.sensiolabs.com/projects/3e4f3e13-a8c1-4f1e-a5ad-42e799915dfe) [![Latest Stable Version](https://poser.pugx.org/nilportugues/eventbus-queue/v/stable?)](https://packagist.org/packages/nilportugues/eventbus-queue) [![Total Downloads](https://poser.pugx.org/nilportugues/eventbus-queue/downloads?)](https://packagist.org/packages/nilportugues/eventbus-queue) [![License](https://poser.pugx.org/nilportugues/eventbus-queue/license?)](https://packagist.org/packages/nilportugues/eventbus-queue)
[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://paypal.me/nilportugues)
 
 - [Installation](#installation)
 - [Usage](#usage)
    -  [ProducerEventBus](#producereventbus)
    -  [Consumer for the ProducerEventBus](#consumer-for-the-producereventbus)
    -  [EventBusWorker](#eventbusworker)
 - [Adapter Configurations](#adapter-configurations)
    - [PDOQueue](#pdoqueue)
    - [MongoDBQueue](#mongodbqueue)
    - [RedisQueue](#redisqueue)
    - [PredisQueue](#predisqueue)
    - [FileSystemQueue](#filesystemqueue)
    - [AmqpQueue](#amqpqueue)
    - [BeanstalkdQueue](#beanstalkdqueue)
 - [Contribute](#contribute)
 - [Support](#support)
 - [Authors](#authors)
 - [License](#license)

This package is an extension library for **[nilportugues/messagebus](http://github.com/PHPMessageBus/messagebus)**, adding queues to the EventBus implementation.

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
- **FileSystemQueue**: queue built with using the local file system.
- **AmqpQueue**: use RabbitMQ or any queue implementing the Amqp protocol.
- **BeanstalkdQueue**: use Beanstalk as queue.

To set it up, register the `ProducerEventBusMiddleware` to the Event Bus. Because we'll need to define a second EventBus (consumer), we'll call this the `ProducerEventBus`.

#### ProducerEventBus

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
    return new \NilPortugues\MessageBus\EventBus\EventBus([
        $container['LoggerEventBusMiddleware'],
        $container['ProducerEventBusMiddleware'],
        $container['EventBusMiddleware'],
    ]);
};
```

#### Consumer for the ProducerEventBus

The Consumer will need to be a script that reads the EventBus definitions and subscribed events in order to run until all events are handled. To do so, we'll need to register a new `EventBus` we'll refer as `ConsumerEventBus`. 

We will also like to store events that could not be handled or raised an exception. So a new Queue will be required. For instance, let's store errors in a MongoDB database.

This could be as simple as follows:

```php
<?php
//This is our ConsumerEventBus. 
$container['ConsumerEventBus'] = function() use ($container) {
    return new \NilPortugues\MessageBus\EventBus\EventBus([
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

#### EventBusWorker

Finally, we'll have to call a consumer. This package already provides a fully working consumer implementation: `EventBusWorker`.

Use it as follows:

```php
<?php
//... your $container should be available here.

$consumer = \NilPortugues\MessageBus\EventBusQueue\EventBusWorker();
$consumer->consume(
    $container->get('EventBusQueueAdapter'), 
    $container->get('ErrorQueue'), 
    $container->get('ConsumerEventBus')
);
```

Consumer class will run the `consume` method until all events are consumed. Then it will exit. This is optimal to make sure it will not leak memory.

If you need to keep the consumer running forever use server scripts like [Supervisor](http://supervisord.org/). 

#### Supervisor Configuration

Supervisor is a process monitor for the Linux operating system, and will automatically restart your workers if they fail. To install Supervisor on Ubuntu, you may use the following command:

```sh
sudo apt-get install supervisor
```

Supervisor configuration files are typically stored in the `/etc/supervisor/conf.d` directory. Within this directory, you may create any number of configuration files that instruct how your processes should be monitored. 

For instance, let's create `/etc/supervisor/conf.d/my_worker.conf` so that it starts and monitors a worker script named `my_worker.php`:

```ini
[program:my_worker]
process_name=%(program_name)s_%(process_num)02d
command=php my_worker.php
autostart=true
autorestart=true
user=www-data
numprocs=20
redirect_stderr=true
stdout_logfile=/var/log/my_worker.log
```

In this file, we tell Supervisor that we want 20 instances always running. If the `my_worker.php` ends or fails it will spin up a new one.

In order to make this task run forever, you'll have to type in the following commands: 

```sh
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start my_worker
```

## Adapter Configurations

#### PDOQueue

For this to work, you'll be required to create a table in your database. 

For instance, sqlite dialect table creation would be: 

```sql
CREATE TABLE testAdapterQueue (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_data TEXT NOT NULL,
  event_status CHAR(50),
  created_at INTEGER NOT NULL
);
```

#### MongoDBQueue

In order to use it, you require to install PHP 7's mongodb extension. 

```
sudo pecl install mongodb
```

#### RedisQueue

In order to use it, you require to install PHP 7's phpredis extension. 

```
# Build Redis PHP module
git clone -b php7 https://github.com/phpredis/phpredis.git
sudo mv phpredis/ /etc/ && \
cd /etc/phpredis \
phpize \
./configure \
make && make install \
touch /etc/php/7.0/mods-available/redis.ini \
echo 'extension=redis.so' > /etc/php/7.0/mods-available/redis.ini
```

#### PredisQueue

Nothing, but performs better if phpredis extension is found.

#### FileSystemQueue

Nothing to do. 

#### AmqpQueue

Nothing to do other than having access to a amqp server.

#### Beanstalkd

Nothing to do other than having access to a beanstalkd server.

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
