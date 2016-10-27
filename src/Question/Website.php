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

final class Website extends Question
{
    private $stripOffSlash;

    public function __construct($question, $default)
    {
        parent::__construct($question, $default);

        $this->stripOffSlash = false;

        $this->setValidator(function($value) {
            if (!$value) {
                throw new RuntimeException('No value provided.');
            }

            if (substr($value, 0, 4) !== 'http') {
                throw new RuntimeException('Invalid website address provided.');
            }

            return $this->stripOffSlash ? rtrim($value, '/') : $value;
        });
    }

    /**
     * Sets the value of the "stripOffSlash" field.
     *
     * @param boolean $stripOffSlash
     */
    public function setStripOffSlash($stripOffSlash)
    {
        $this->stripOffSlash = $stripOffSlash;
    }
}
