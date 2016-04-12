<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 29/03/16
 * Time: 22:40.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\MessageBus\EventBusQueue;

use NilPortugues\MessageBus\EventBus\Contracts\Event;

/**
 * Class NullEvent.
 */
class NullEvent implements Event
{
    /**
     * @var NullEvent The reference to *Singleton* instance of this class
     */
    private static $instance;

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return NullEvent The *Singleton* instance.
     */
    public static function create() : NullEvent
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }
}
