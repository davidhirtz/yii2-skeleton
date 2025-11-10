<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $ecsConfig->skip([
        __DIR__ . '/src/messages',
        __DIR__ . '/tests/_output',
        __DIR__ . '/tests/support',
    ]);

    $ecsConfig->sets([
        SetList::CLEAN_CODE,
        SetList::COMMENTS,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::PSR_12,
        SetList::STRICT,
    ]);

//    $ecsConfig->skip([
//        NotOperatorWithSuccessorSpaceFixer::class
//    ]);
//
//    $ecsConfig->ruleWithConfiguration(ClassAttributesSeparationFixer::class, [
//        'elements' => ['method' => 'one'],
//    ]);
};
