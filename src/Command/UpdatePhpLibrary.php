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
use PolderKnowledge\PkTool\Question\Website;
use Symfony\Component\Finder\SplFileInfo;

final class UpdatePhpLibrary extends AbstractUpdateCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('update-php-library');
        $this->setDescription('Updates the current project with the skeleton.');
    }

    protected function getDefaultRepository()
    {
        return CreatePhpLibrary::REPOSITORY_URL;
    }

    protected function populateQuestionContainer(Container $container)
    {
        $container->addValueQuestion('name', 'Package name: ');
        $container->addValueQuestion('description', 'Package description: ');
        $container->add('repository', $this->askRepository());
        $container->addChoiceQuestion('license', 'Package license: ', array_keys($this->licenses));
    }

    protected function populateVariableArray(array $values)
    {
        $values = parent::populateVariableArray($values);

        $values['organization'] = 'Polder Knowledge';
        $values['org_email'] = 'wij@polderknowledge.nl';
        $values['package_name'] = preg_replace('/[^a-z0-9]+/', '-', strtolower($values['name']));
        $values['package_vendor'] = 'polderknowledge';
        $values['website'] = 'https://polderknowledge.com';
        $values['year'] = date('Y');
        $values['namespace_package'] = str_replace(' ', '', ucwords($values['name']));
        $values['namespace'] = 'PolderKnowledge\\\\' . $values['namespace_package'];
        $values['app_name'] = $values['organization']; // used in the license file.

        return $values;
    }

    protected function replaceFileContent(SplFileInfo $file, $content, array $variables)
    {
        $content = parent::replaceFileContent($file, $content, $variables);

        $content = $this->replaceNamespaceInFile($content, $variables);

        return $content;
    }

    private function replaceNamespaceInFile($contents, array $values)
    {
        $namespaceToReplace = 'namespace PolderKnowledge\\Skeleton;';
        $namespaceToSet = 'namespace PolderKnowledge\\' . $values['namespace_package'] . ';';

        return str_replace($namespaceToReplace, $namespaceToSet, $contents);
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
