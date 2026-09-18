<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/Core/Autoloader.php';
\Orchestrix\Forms\Core\Autoloader::register(dirname(__DIR__) . '/src');

use Orchestrix\Forms\Calculations\ExpressionEngine;
use Orchestrix\Forms\Calculations\ExpressionException;
use Orchestrix\Forms\Payments\PaymentStateMachine;
use Orchestrix\Forms\Quizzes\QuizScorer;
use Orchestrix\Forms\Rules\RuleEngine;
use Orchestrix\Forms\Security\CsvEscaper;
use Orchestrix\Forms\Surveys\SurveyStatistics;
use Orchestrix\Forms\Webhooks\SignatureVerifier;
use Orchestrix\Forms\Workflows\WorkflowStateMachine;

$tests = [];
$test = static function (string $name, Closure $case) use (&$tests): void {
    $tests[$name] = $case;
};
$assert = static function (bool $condition, string $message = 'Assertion failed'): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};

$test('calculation precedence and functions', function () use ($assert): void {
    $engine = new ExpressionEngine();
    $assert($engine->evaluate('ROUND(quantity * price * (1 + tax), 2)', ['quantity' => 3, 'price' => 4.5, 'tax' => .2]) === 16.2);
    $assert($engine->evaluate('SUM(items.price * items.quantity)', ['items' => [['price' => 2, 'quantity' => 3], ['price' => 4, 'quantity' => 2]]]) === 14.0);
});
$test('calculation dependency cycle rejection', function () use ($assert): void {
    try {
        (new ExpressionEngine())->order(['a' => 'b + 1', 'b' => 'a + 1']);
    } catch (ExpressionException) {
        $assert(true);
        return;
    }
    $assert(false, 'Cycle was accepted');
});
$test('nested conditional rules', function () use ($assert): void {
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
    $assert((new RuleEngine())->evaluate($rule, ['country' => 'DE', 'age' => 20]));
    $assert(!(new RuleEngine())->evaluate($rule, ['country' => 'FR', 'age' => 20]));
});
$test('payment and workflow state machines reject invalid transitions', function () use ($assert): void {
    $assert((new PaymentStateMachine())->transition('pending', 'succeeded') === 'succeeded');
    try {
        (new PaymentStateMachine())->transition('refunded', 'succeeded');
        $assert(false);
    } catch (LogicException) {
    }
    $assert((new WorkflowStateMachine())->transition('failed', 'retrying') === 'retrying');
});
$test('webhook signature rejects replay window', function () use ($assert): void {
    $verifier = new SignatureVerifier();
    $now = time();
    $signature = $verifier->sign('{}', 'secret', $now);
    $assert($verifier->verify('{}', 'secret', $now, $signature));
    $assert(! $verifier->verify('{}', 'secret', $now - 1000, $verifier->sign('{}', 'secret', $now - 1000)));
});
$test('spreadsheet formula injection is neutralized', function () use ($assert): void {
    $assert((new CsvEscaper())->cell('=HYPERLINK("https://evil")') === "'=HYPERLINK(\"https://evil\")");
});
$test('survey NPS and quiz scoring', function () use ($assert): void {
    $nps = (new SurveyStatistics())->nps([10, 9, 8, 6]);
    $assert($nps['nps'] === 25.0);
    $score = (new QuizScorer())->score(['q1' => 'a', 'q2' => ['x', 'y']], ['q1' => ['a' => 2, 'b' => 0], 'q2' => ['x' => 1, 'y' => -0.5]]);
    $assert($score['score'] === 2.5);
});

$passed = 0;
$failed = 0;
foreach ($tests as $name => $case) {
    try {
        $case();
        printf("PASS %s\n", $name);
        $passed++;
    } catch (Throwable $error) {
        printf("FAIL %s: %s\n", $name, $error->getMessage());
        $failed++;
    }
}
printf("PHP unit tests: %d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
