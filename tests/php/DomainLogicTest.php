<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Tests;

use LogicException;
use Orchestrix\Forms\Calculations\ExpressionEngine;
use Orchestrix\Forms\Calculations\ExpressionException;
use Orchestrix\Forms\Payments\PaymentStateMachine;
use Orchestrix\Forms\Quizzes\QuizScorer;
use Orchestrix\Forms\Rules\RuleEngine;
use Orchestrix\Forms\Surveys\SurveyStatistics;
use Orchestrix\Forms\Workflows\WorkflowStateMachine;
use PHPUnit\Framework\TestCase;

final class DomainLogicTest extends TestCase
{
    public function testCalculationsRespectPrecedenceAggregatesAndDependencies(): void
    {
        $engine = new ExpressionEngine();

        self::assertSame(
            16.2,
            $engine->evaluate(
                'ROUND(quantity * price * (1 + tax), 2)',
                ['quantity' => 3, 'price' => 4.5, 'tax' => 0.2]
            )
        );
        self::assertSame(
            14.0,
            $engine->evaluate(
                'SUM(items.price * items.quantity)',
                ['items' => [['price' => 2, 'quantity' => 3], ['price' => 4, 'quantity' => 2]]]
            )
        );
        self::assertSame(['subtotal', 'total'], $engine->order([
            'total' => 'subtotal * 1.2',
            'subtotal' => 'quantity * price',
        ]));
    }

    public function testCalculationCyclesAndDivisionByZeroAreRejected(): void
    {
        $engine = new ExpressionEngine();

        try {
            $engine->order(['a' => 'b + 1', 'b' => 'a + 1']);
            self::fail('A circular dependency was accepted.');
        } catch (ExpressionException) {
            self::assertTrue(true);
        }

        $this->expectException(ExpressionException::class);
        $engine->evaluate('10 / 0', []);
    }

    public function testNestedRulesAndUnsafeRegularExpressions(): void
    {
        $engine = new RuleEngine();
        $rule = [
            'group' => 'AND',
            'rules' => [
                ['field' => 'country', 'operator' => 'equals', 'value' => 'DE'],
                [
                    'group' => 'OR',
                    'rules' => [
                        ['field' => 'age', 'operator' => 'greater_equal', 'value' => 18],
                        ['field' => 'guardian', 'operator' => 'not_empty'],
                    ],
                ],
            ],
        ];

        self::assertTrue($engine->evaluate($rule, ['country' => 'DE', 'age' => 20]));
        self::assertFalse($engine->evaluate($rule, ['country' => 'FR', 'age' => 20]));
        self::assertFalse($engine->evaluate([
            'field' => 'value',
            'operator' => 'regex',
            'value' => '(a+)+$',
        ], ['value' => str_repeat('a', 100) . '!']));
    }

    public function testStateMachinesAndRefundBoundaries(): void
    {
        $payments = new PaymentStateMachine();

        self::assertSame('succeeded', $payments->transition('pending', 'succeeded'));
        self::assertSame('partially_refunded', $payments->refundState(1000, 0, 500));
        self::assertSame('refunded', $payments->refundState(1000, 500, 500));
        self::assertSame('retrying', (new WorkflowStateMachine())->transition('failed', 'retrying'));

        $this->expectException(LogicException::class);
        $payments->transition('refunded', 'succeeded');
    }

    public function testSurveyAndQuizScoring(): void
    {
        self::assertSame(25.0, (new SurveyStatistics())->nps([10, 9, 8, 6])['nps']);
        self::assertSame(2.5, (new QuizScorer())->score(
            ['q1' => 'a', 'q2' => ['x', 'y']],
            ['q1' => ['a' => 2, 'b' => 0], 'q2' => ['x' => 1, 'y' => -0.5]]
        )['score']);
    }
}
