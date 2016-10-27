<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Question;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Container
{
    private $helper;
    private $questions;
    private $variables;

    public function __construct(QuestionHelper $helper)
    {
        $this->helper = $helper;
        $this->questions = [];
    }

    public function add($variable, callable $question)
    {
        $this->questions[$variable] = $question;
    }

    public function getVariable($name, $defaultValue = null)
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        return $defaultValue;
    }

    public function ask(InputInterface $input, OutputInterface $output)
    {
        $this->variables = [];

        foreach ($this->questions as $variable => $callback) {
            $question = $callback($this);

            $value = $this->helper->ask($input, $output, $question);

            $this->variables[$variable] = $value;
        }

        return $this->variables;
    }
}
