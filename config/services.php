<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool;

use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'factories' => [
        Application::class => Service\ApplicationFactory::class,
        Command\CreateZFApplication::class => InvokableFactory::class,
        Command\SelfUpdate::class => InvokableFactory::class,
    ],
];
