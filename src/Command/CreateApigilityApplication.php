<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Command;

use PolderKnowledge\PkTool\Question\Container;
use PolderKnowledge\PkTool\Question\Value;

final class CreateApigilityApplication extends AbstractCreateCommand
{
    const REPOSITORY_URL = 'https://github.com/polderknowledge/skeleton-apigility-application';

    protected function configure()
    {
        parent::configure();

        $this->setName('create-apigility-application');
        $this->setDescription('Creates a new Apigility application.');
    }

    protected function getDefaultRepository()
    {
        return self::REPOSITORY_URL;
    }

    protected function populateQuestionContainer(Container $container)
    {
        $container->addValueQuestion('org_name', 'Organization name: ');
        $container->addValueQuestion('app_name', 'Application name: ');
        $container->addValueQuestion('app_description', 'Application description: ');
        $container->addWebsiteQuestion('app_website', 'Organization website: ');
        $container->addRepositoryQuestion('app_repository', 'Repository URL: ');
        $container->add('package_vendor', $this->askPackageVendor());
        $container->add('package_name', $this->askPackageName());
        $container->add('package_namespace', $this->askPackageNamespace());
        $container->addChoiceQuestion('license', 'Package license: ', array_keys($this->licenses));
    }

    protected function askPackageVendor()
    {
        return function (Container $container) {
            $organization = $container->getVariable('org_name');
            $packageVendor = preg_replace('/[^a-z0-9]+/', '', strtolower($organization));

            return new Value('Package vendor (' . $packageVendor . '): ', $packageVendor);
        };
    }

    protected function askPackageName()
    {
        return function (Container $container) {
            $applicationName = $container->getVariable('app_name');
            $packageName = preg_replace('/[^a-z0-9]+/', '-', strtolower($applicationName));

            return new Value('Package name (' . $packageName . '): ', $packageName);
        };
    }

    protected function askPackageNamespace()
    {
        return function (Container $container) {
            $applicationName = $container->getVariable('app_name');
            $organization = $container->getVariable('org_name');

            $namespace = str_replace(' ', '', ucwords($organization));
            $namespace .= '\\';
            $namespace .= str_replace(' ', '', ucwords($applicationName));

            return new Value('Application namespace (' . $namespace . '): ', $namespace);
        };
    }
}
