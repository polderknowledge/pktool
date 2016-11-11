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
use PolderKnowledge\PkTool\Question\Website;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class CreateZFApplication extends AbstractCommand
{
    const REPOSITORY_URL = 'https://github.com/polderknowledge/skeleton-zf-application';

    /**
     * @var array
     */
    private $licenses;

    protected function configure()
    {
        $this->setName('create-zf-application');
        $this->setDescription('Creates a new Zend Framework application.');

        $this->licenses = [
            'proprietary' => __DIR__ . '/../../resources/licenses/proprietary.txt',
            'MIT' => __DIR__ . '/../../resources/licenses/mit.txt',
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetDirectory = '.';

        $output->writeln(sprintf('<info>Cloning %s</info>', self::REPOSITORY_URL));

        $process = new Process(sprintf('git clone %s %s', self::REPOSITORY_URL, $targetDirectory));
        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this->replaceVariables($input, $output);
    }


    private function replaceVariables(InputInterface $input, OutputInterface $output)
    {
        $container = new Container($this->getHelper('question'));
        $container->add('org_name', $this->askApplicationOrganization());
        $container->add('app_name', $this->askApplicationName());
        $container->add('app_description', $this->askApplicationDescription());
        $container->add('app_website', $this->askApplicationWebsite());
        $container->add('app_repository', $this->askApplicationRepository());
        $container->add('package_vendor', $this->askPackageVendor());
        $container->add('package_name', $this->askPackageName());
        $container->add('package_namespace', $this->askPackageNamespace());
        $container->add('license', $this->askPackageLicense());

        $values = $container->ask($input, $output);

        // Static values:
        $values['year'] = date('Y');

        $output->writeln('');
        $output->writeln('<info>Replacing variables in cloned repository...</info>');
        $output->writeln('');

        $this->updateLicenseFile('LICENSE.md', $values['license']);

        $finder = new Finder();
        $finder->files()->ignoreDotFiles(false)->in(getcwd());

        foreach ($finder as $file) {
            $this->replaceVariablesInFile($values, $file->getRealPath());
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    private function replaceVariablesInFile(array $values, $path)
    {
        $contents = file_get_contents($path);

        foreach ($values as $variable => $value) {
            $contents = str_replace('{' . strtoupper($variable) . '}', $value, $contents);
        }

        file_put_contents($path, $contents);
    }

    private function updateLicenseFile($path, $license)
    {
        $contents = file_get_contents($this->licenses[$license]);

        file_put_contents($path, $contents);
    }

    private function askApplicationOrganization()
    {
        return function (Container $container) {
            return new Value('Organization name: ', false);
        };
    }

    private function askApplicationWebsite()
    {
        return function (Container $container) {
            return new Website('Organization website: ', false);
        };
    }

    private function askApplicationRepository()
    {
        return function (Container $container) {
            $question = new Website('Repository URL: ', false);
            $question->setStripOffSlash(true);

            return $question;
        };
    }

    private function askApplicationName()
    {
        return function (Container $container) {
            return new Value('Application name: ', false);
        };
    }

    private function askApplicationDescription()
    {
        return function (Container $container) {
            return new Value('Application description: ', false);
        };
    }

    private function askPackageNamespace()
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

    private function askPackageVendor()
    {
        return function (Container $container) {
            $organization = $container->getVariable('org_name');
            $packageVendor = preg_replace('/[^a-z0-9]+/', '', strtolower($organization));

            return new Value('Package vendor (' . $packageVendor . '): ', $packageVendor);
        };
    }

    private function askPackageName()
    {
        return function (Container $container) {
            $applicationName = $container->getVariable('app_name');
            $packageName = preg_replace('/[^a-z0-9]+/', '-', strtolower($applicationName));

            return new Value('Package name (' . $packageName . '): ', $packageName);
        };
    }

    private function askPackageLicense()
    {
        return function (Container $container) {
            return new ChoiceQuestion('Package license: ', array_keys($this->licenses));
        };
    }
}
