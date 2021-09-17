<?php

namespace Tochka\JsonRpc\Contracts;

use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\JsonRpc\Route\Parameters\ParameterObject;

interface ParamsResolverInterface
{
    /**
     * @param \ReflectionMethod $reflectionMethod
     * @return array<string, Parameter>
     */
    public function resolveParameters(\ReflectionMethod $reflectionMethod): array;
    
    public function resolveResult(\ReflectionMethod $reflectionMethod): Parameter;
    
    public function getClasses(): array;
    
    public function getParameterObject(string $className): ?ParameterObject;
}