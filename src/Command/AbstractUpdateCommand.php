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
use PolderKnowledge\PkTool\Utils\FileSystem;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractUpdateCommand extends AbstractCommand
{
    /**
     * @var array
     */
    protected $licenses;

    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'source',
            null,
            InputOption::VALUE_OPTIONAL,
            'Define the source repository',
            $this->getDefaultRepository()
        );

        $this->addArgument(
            'target-directory',
            InputArgument::OPTIONAL,
            'The directory to store the created project',
            getcwd()
        );

        $this->licenses = [
            'proprietary' => __DIR__ . '/../../resources/licenses/proprietary.txt',
            'MIT' => __DIR__ . '/../../resources/licenses/mit.txt',
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetDirectory = $input->getArgument('target-directory');

        if (is_dir($targetDirectory) && FileSystem::isDirectoryEmpty($targetDirectory)) {
            $targetDirectory = realpath($targetDirectory);
            throw new RuntimeException(sprintf('The target directory "%s" is not empty.', $targetDirectory));
        } elseif (!is_dir($targetDirectory)) {
            throw new RuntimeException(
                sprintf(
                    'The target directory "%s" is empty, try to run create-command.',
                    $targetDirectory
                )
            );
        }

        $container = new Container($this->getHelper('question'));
        $currentName = $this->fetchGitConfigVariable('name');
        $currentEmail = $this->fetchGitConfigVariable('email');
        if ($currentName === '') {
            $container->addValueQuestion('name', 'Please provide a committer name:');
        }

        if ($currentEmail === '') {
            $container->addValueQuestion('email', 'Please provide a committer e-mail:');
        }

        $gitConfig = $container->ask($input, $output);

        $this->addSkeletonRemote($targetDirectory, $input->getOption('source'), $gitConfig, $output);

        $this->replaceVariables($targetDirectory, $input, $output);

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    protected function addSkeletonRemote($targetDirectory, $source, $gitConfig, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Fetching changes %s</info>', $source));

        foreach ($gitConfig as $key => $value) {
            $process = new Process(sprintf('git config --global user.%s "%s"', $key, $value));
            $process->run(function ($type, $buffer) use ($output) {
                $output->writeln($buffer);
            });
        }

        $process = new Process(sprintf('git remote add skeleton %s', $source));
        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $process = new Process(sprintf('git pull skeleton master'));
        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });

        if (!$process->isSuccessful()) {
            $output->writeln(
                '<error>Faild to merge clean,
                after the process is completed you will have to resolve the conflicts</error>'
            );
        }

        $process = new Process(sprintf('git remote remove skeleton'));
        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });
    }

    private function replaceVariables($targetDirectory, InputInterface $input, OutputInterface $output)
    {
        $container = new Container($this->getHelper('question'));
        $this->populateQuestionContainer($container);

        $variables = $container->ask($input, $output);
        $variables = $this->populateVariableArray($variables);

        $output->writeln('');
        $output->writeln('<info>Replacing variables in repository...</info>');
        $output->writeln('');

        $finder = $this->getFileFinder($targetDirectory);
        $this->replaceVariablesInFinder($finder, $variables);
    }

    private function replaceVariablesInFinder(Finder $finder, array $variables)
    {
        foreach ($finder as $file) {
            $this->replaceFile($variables, $file);
        }
    }

    private function replaceFile(array $variables, SplFileInfo $file)
    {
        $contents = file_get_contents($file->getRealPath());

        $contents = $this->replaceFileContent($file, $contents, $variables);

        file_put_contents($file, $contents);
    }

    protected function replaceFileContent(SplFileInfo $file, $content, array $variables)
    {
        if ($file->getBasename() === 'LICENSE.md') {
            $content = $this->updateLicenseFile($variables['license']);
        }

        foreach ($variables as $variable => $value) {
            $content = str_replace('{' . strtoupper($variable) . '}', $value, $content);
        }

        return $content;
    }

    private function updateLicenseFile($license)
    {
        if (!array_key_exists($license, $this->licenses)) {
            throw new RuntimeException(sprintf('License "%s" not found', $license));
        }

        return file_get_contents($this->licenses[$license]);
    }

    /**
     * Returns the default repository to clone for cases where the source option has not been provided.
     *
     * @return string
     */
    abstract protected function getDefaultRepository();

    /**
     * Populates the question container with question to ask the user.
     *
     * @param Container $container
     */
    abstract protected function populateQuestionContainer(Container $container);

    /**
     * Populates the variable array with static variable values.
     *
     * @param array $values The array with variables to expand.
     * @return array
     */
    protected function populateVariableArray(array $values)
    {
        $values['year'] = date('Y');

        return $values;
    }

    /**
     * Creates a new instance of a Finder which is used to find the files to replace variables in.
     *
     * @param string $targetDirectory The path to search files in.
     * @return Finder
     */
    protected function getFileFinder($targetDirectory)
    {
        $finder = new Finder();

        $finder->files()->ignoreDotFiles(false)->in(realpath($targetDirectory));

        return $finder;
    }

    private function fetchGitConfigVariable($variable)
    {
        $process = new Process(sprintf('git config user.%s', $variable));
        $process->run();
        return $process->getOutput();
    }
}
