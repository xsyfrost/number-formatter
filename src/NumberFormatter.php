<?php
/**
 * This file is part of arius/number-formatter package
 *
 * @author Arkadiusz Ostrycharz <arkadiusz.ostrycharz@gmail.com>
 * @copyright Arius IT Arkadiusz Ostrycharz
 * @homepage http://arius.pl
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Arius;

class NumberFormatter extends \NumberFormatter
{
    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var bool
     */
    private $isSpellout = false;

    /**
     * @var SpelloutInterface
     */
    private $spelloutExtender;

    /**
     * @inheritdoc
     */
    public function __construct($locale, $style, $pattern = null)
    {
        parent::__construct($locale, $style, $pattern);

        if ($style === NumberFormatter::SPELLOUT) {
            $this->isSpellout = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function setTextAttribute($attr, $value): bool
    {
        if ($this->isExtendedSpelloutAvailable($attr, $value)) {
            $this->attributes[$attr] = $value;
            return true;
        }

        $this->spelloutExtender = null;
        return parent::setTextAttribute($attr, $value);
    }

    /**
     * Check if extra spellout is available.
     *
     * @param int $attr
     * @param string $value
     * @return bool
     */
    public function isExtendedSpelloutAvailable($attr, $value):bool
    {
        if ($this->isSpelloutRuleset($attr)) {
            $namespace = $this->getSpelloutNamespace($value);
            if (class_exists($namespace)) {
                return $this->initializeSpelloutExtender($namespace);
            }
        }

        return false;
    }

    private function isSpelloutRuleset($attr):bool
    {
        $ruleset = $attr === NumberFormatter::DEFAULT_RULESET || $attr === NumberFormatter::PUBLIC_RULESETS;
        return $this->isSpellout && $ruleset;
    }

    private function getSpelloutNamespace($value):string
    {
        $string = substr($value, 1);
        $namespace = '\\Arius\\Lang\\'.ucfirst($this->getLocale()).'\\';

        foreach (explode('-', $string) As $stringPart) {
            $namespace .= ucfirst($stringPart);
        }

        return $namespace;
    }

    private function initializeSpelloutExtender($namespace):bool
    {
        $reflectionClass = new \ReflectionClass($namespace);

        if ($reflectionClass->isInstantiable()) {
            $this->spelloutExtender = new $namespace($this);
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getTextAttribute($attr): string|false
    {
        return isset($this->attributes[$attr]) ? $this->attributes[$attr] : parent::getTextAttribute($attr);
    }

    /**
     * @inheritdoc
     */
    public function format($value, $type = \NumberFormatter::TYPE_DEFAULT): string|false
    {
        if ($this->isSpellout && $this->spelloutExtender instanceof SpelloutInterface) {
            return $this->extendedFormat($value);
        }

        return parent::format($value, $type);
    }

    /**
     * @param int $value
     * @return string
     */
    private function extendedFormat($value):string
    {
        return $this->spelloutExtender->format($value);
    }
}
