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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class CreatePhpLibrary extends AbstractCommand
{
    const REPOSITORY_URL = 'https://github.com/polderknowledge/skeleton-php-library';

    /**
     * @var array
     */
    private $licenses;

    protected function configure()
    {
        $this->setName('create-php-library');
        $this->setDescription('Creates a new PHP library.');
        $this->addOption(
            'source',
            null,
            InputOption::VALUE_OPTIONAL,
            'Define the source repository',
            self::REPOSITORY_URL
        );

        $this->licenses = [
            'proprietary' => __DIR__ . '/../../resources/licenses/proprietary.txt',
            'MIT' => __DIR__ . '/../../resources/licenses/mit.txt',
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetDirectory = '.';

        $source = $input->getOption('source');

        $output->writeln(sprintf('<info>Cloning %s</info>', $source));

        $process = new Process(sprintf('git clone %s %s', $source, $targetDirectory));
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
        $container->add('name', $this->askName());
        $container->add('description', $this->askDescription());
        $container->add('repository', $this->askRepository());
        $container->add('license', $this->askPackageLicense());

        $values = $container->ask($input, $output);

        // Static values:
        $values['organization'] = 'Polder Knowledge';
        $values['org_email'] = 'wij@polderknowledge.nl';
        $values['package_name'] = preg_replace('/[^a-z0-9]+/', '-', strtolower($values['name']));
        $values['package_vendor'] = 'polderknowledge';
        $values['website'] = 'https://polderknowledge.com';
        $values['year'] = date('Y');
        $values['namespace_package'] = str_replace(' ', '', ucwords($values['name']));
        $values['namespace'] = 'PolderKnowledge\\\\' . $values['namespace_package'];
        $values['app_name'] = $values['organization']; // used in the license file.

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

        $contents = $this->replaceNamespaceInFile($contents, $values, $path);

        file_put_contents($path, $contents);
    }

    private function replaceNamespaceInFile($contents, array $values, $path)
    {
        $namespaceToReplace = 'namespace PolderKnowledge\\Skeleton;';
        $namespaceToSet = 'namespace PolderKnowledge\\' . $values['namespace_package'] . ';';

        return str_replace($namespaceToReplace, $namespaceToSet, $contents);
    }

    private function updateLicenseFile($path, $license)
    {
        $contents = file_get_contents($this->licenses[$license]);

        file_put_contents($path, $contents);
    }

    private function askName()
    {
        return function (Container $container) {
            return new Value('Package name: ', false);
        };
    }

    private function askDescription()
    {
        return function (Container $container) {
            return new Value('Package description: ', false);
        };
    }

    private function askPackageLicense()
    {
        return function (Container $container) {
            return new ChoiceQuestion('Package license: ', array_keys($this->licenses));
        };
    }

    private function askRepository()
    {
        return function (Container $container) {
            $url = 'https://github.com/polderknowledge/' .
                preg_replace('/[^a-z0-9]+/', '-', strtolower($container->getVariable('name')));

            $question = new Website('Repository URL (' . $url . '): ', $url);
            $question->setStripOffSlash(true);

            return $question;
        };
    }
}
