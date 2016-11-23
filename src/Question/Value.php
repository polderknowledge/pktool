<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Question;

use RuntimeException;
use Symfony\Component\Console\Question\Question;

final class Value extends Question
{
    public function __construct($question, $default = null)
    {
        parent::__construct($question, $default);

        $this->setValidator(function ($value) {
            if (!$value) {
                throw new RuntimeException('No value provided.');
            }

            return $value;
        });
    }
}
