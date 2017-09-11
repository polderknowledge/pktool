<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Service;

use Interop\Container\ContainerInterface;
use PolderKnowledge\PkTool\Application;
use PolderKnowledge\PkTool\Command\CreateApigilityApplication;
use PolderKnowledge\PkTool\Command\CreatePhpLibrary;
use PolderKnowledge\PkTool\Command\CreateZFApplication;
use PolderKnowledge\PkTool\Command\SelfUpdate;
use PolderKnowledge\PkTool\Command\UpdatePhpLibrary;
use Zend\ServiceManager\Factory\FactoryInterface;

final class ApplicationFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $application = new Application();
        $application->add($container->get(CreateApigilityApplication::class));
        $application->add($container->get(CreatePhpLibrary::class));
        $application->add($container->get(CreateZFApplication::class));
        $application->add($container->get(SelfUpdate::class));
        $application->add($container->get(UpdatePhpLibrary::class));

        return $application;
    }
}
