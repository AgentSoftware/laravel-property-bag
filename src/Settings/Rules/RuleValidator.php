<?php

namespace LaravelPropertyBag\Settings\Rules;

use LaravelPropertyBag\Exceptions\InvalidSettingsRule;
use LaravelPropertyBag\Helpers\NameResolver;

class RuleValidator
{
    /**
     * Validate the value given for the rule.
     *
     * @throws InvalidSettingsRule
     */
    public function validate(string $rule, mixed $value): bool
    {
        $arguments = $this->buildArgumentArray($rule, $value);

        $method = $this->makeRuleMethod($rule);

        if ($this->userDefinedExists($method)) {
            $class = NameResolver::makeRulesFileName();

            return call_user_func_array([$class, $method], $arguments);
        } elseif (method_exists(Rules::class, $method)) {
            return call_user_func_array([Rules::class, $method], $arguments);
        }

        throw InvalidSettingsRule::ruleNotFound($rule, $method);
    }

    /**
     * String is a rule.
     */
    public function isRule(string $string): bool|string
    {
        if ($isRule = preg_match('/:(.*?):/', $string, $match)) {
            return $match[1];
        }

        return (bool) $isRule;
    }

    /**
     * Make method name used to validate rule.
     */
    protected function makeRuleMethod(string $rule): string
    {
        if (strpos($rule, '=') !== false) {
            $rule = explode('=', $rule)[0];
        }

        return 'rule'.ucfirst($rule);
    }

    /**
     * User defined rule method exists.
     */
    protected function userDefinedExists(string $method): bool
    {
        $userDefined = NameResolver::makeRulesFileName();

        return class_exists($userDefined) &&
            method_exists($userDefined, $method);
    }

    /**
     * Build argument array from rule and value.
     *
     * @return array<int, mixed>
     */
    protected function buildArgumentArray(string $rule, mixed $value): array
    {
        $argumentString = explode('=', $rule);

        $arguments = [$value];

        if (isset($argumentString[1])) {
            return array_merge($arguments, explode(',', $argumentString[1]));
        }

        return $arguments;
    }
}
