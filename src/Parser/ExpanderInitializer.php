<?php

declare(strict_types=1);

namespace Coduo\PHPMatcher\Parser;

use Coduo\PHPMatcher\AST\Expander as ExpanderNode;
use Coduo\PHPMatcher\Backtrace;
use Coduo\PHPMatcher\Exception\InvalidArgumentException;
use Coduo\PHPMatcher\Exception\InvalidExpanderTypeException;
use Coduo\PHPMatcher\Exception\UnknownExpanderClassException;
use Coduo\PHPMatcher\Exception\UnknownExpanderException;
use Coduo\PHPMatcher\Matcher\Pattern\Expander;
use Coduo\PHPMatcher\Matcher\Pattern\PatternExpander;

final class ExpanderInitializer
{
    /**
     * @var class-string[]
     */
    private $expanderDefinitions = [
        Expander\After::NAME => Expander\After::class,
        Expander\Before::NAME => Expander\Before::class,
        Expander\Contains::NAME => Expander\Contains::class,
        Expander\NotContains::NAME => Expander\NotContains::class,
        Expander\Count::NAME => Expander\Count::class,
        Expander\EndsWith::NAME => Expander\EndsWith::class,
        Expander\GreaterThan::NAME => Expander\GreaterThan::class,
        Expander\InArray::NAME => Expander\InArray::class,
        Expander\IsDateTime::NAME => Expander\IsDateTime::class,
        Expander\IsEmail::NAME => Expander\IsEmail::class,
        Expander\IsEmpty::NAME => Expander\IsEmpty::class,
        Expander\IsNotEmpty::NAME => Expander\IsNotEmpty::class,
        Expander\IsUrl::NAME => Expander\IsUrl::class,
        Expander\IsIp::NAME => Expander\IsIp::class,
        Expander\IsTzOffset::NAME => Expander\IsTzOffset::class,
        Expander\IsTzAbbreviation::NAME => Expander\IsTzAbbreviation::class,
        Expander\IsTzIdentifier::NAME => Expander\IsTzIdentifier::class,
        Expander\LowerThan::NAME => Expander\LowerThan::class,
        Expander\MatchRegex::NAME => Expander\MatchRegex::class,
        Expander\OneOf::NAME => Expander\OneOf::class,
        Expander\Optional::NAME => Expander\Optional::class,
        Expander\StartsWith::NAME => Expander\StartsWith::class,
        Expander\Repeat::NAME => Expander\Repeat::class,
        Expander\ExpanderMatch::NAME => Expander\ExpanderMatch::class,
        Expander\HasProperty::NAME => Expander\HasProperty::class,
    ];

    /**
     * @var \Coduo\PHPMatcher\Backtrace
     */
    private $backtrace;

    public function __construct(Backtrace $backtrace)
    {
        $this->backtrace = $backtrace;
    }

    public function setExpanderDefinition(string $expanderName, string $expanderFQCN) : void
    {
        if (!\class_exists($expanderFQCN)) {
            throw new UnknownExpanderClassException(\sprintf('Class "%s" does not exists.', $expanderFQCN));
        }

        $this->expanderDefinitions[$expanderName] = $expanderFQCN;
    }

    public function hasExpanderDefinition(string $expanderName) : bool
    {
        return \array_key_exists($expanderName, $this->expanderDefinitions);
    }

    public function getExpanderDefinition(string $expanderName) : string
    {
        if (!$this->hasExpanderDefinition($expanderName)) {
            throw new InvalidArgumentException(\sprintf('Definition for "%s" expander does not exists.', $expanderName));
        }

        return $this->expanderDefinitions[$expanderName];
    }

    public function initialize(ExpanderNode $expanderNode) : PatternExpander
    {
        if (!\array_key_exists($expanderNode->getName(), $this->expanderDefinitions)) {
            throw new UnknownExpanderException(\sprintf('Unknown expander "%s"', $expanderNode->getName()));
        }

        $reflection = new \ReflectionClass($this->expanderDefinitions[$expanderNode->getName()]);

        if ($expanderNode->hasArguments()) {
            $arguments = [];

            foreach ($expanderNode->getArguments() as $argument) {
                $arguments[] = ($argument instanceof ExpanderNode)
                    ? $this->initialize($argument)
                    : $argument;
            }

            $expander = $reflection->newInstanceArgs($arguments);
        } else {
            $expander = $reflection->newInstance();
        }

        if (!$expander instanceof PatternExpander) {
            throw new InvalidExpanderTypeException();
        }

        $expander->setBacktrace($this->backtrace);

        return $expander;
    }
}
