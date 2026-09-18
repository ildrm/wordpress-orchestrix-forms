<?php

use Orchestrix\Forms\AntiSpam\SpamScorer;
use Orchestrix\Forms\Calculations\ExpressionEngine;
use Orchestrix\Forms\Fields\CoreFieldCatalog;
use Orchestrix\Forms\Fields\FieldRegistry;
use Orchestrix\Forms\Forms\FormRepository;
use Orchestrix\Forms\Jobs\JobQueue;
use Orchestrix\Forms\Rules\RuleEngine;
use Orchestrix\Forms\Schema\SchemaInspector;
use Orchestrix\Forms\Security\RateLimiter;
use Orchestrix\Forms\Security\SubmissionToken;
use Orchestrix\Forms\Submissions\SubmissionPipeline;
use Orchestrix\Forms\Submissions\SubmissionRepository;
use Orchestrix\Forms\Validation\FormValidator;

$assert = static function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};

$schema = [
    'fields' => [
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
        ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'unique' => 'global'],
        ['key' => 'quantity', 'type' => 'integer', 'label' => 'Quantity', 'required' => true],
        ['key' => 'price', 'type' => 'price', 'label' => 'Price', 'required' => true],
        ['key' => 'total', 'type' => 'calculation', 'label' => 'Total'],
        ['key' => 'message', 'type' => 'textarea', 'label' => 'Message'],
        ['key' => 'credential', 'type' => 'password', 'label' => 'Credential', 'privacy' => 'secret'],
    ],
    'calculations' => [['key' => 'total', 'expression' => 'quantity * price']],
];
$forms = new FormRepository();
$formId = $forms->create('Acceptance Contact ' . wp_generate_password(6, false), $schema, ['confirmation_message' => 'Received'], 1);
$versionId = $forms->publish($formId, 1);
$published = $forms->find($formId, true);
$assert(is_array($published) && (int) $published['version_id'] === $versionId, 'Published version resolution failed.');
$html = \Orchestrix\Forms\Plugin::instance()->renderer()->render($formId);
$assert(str_contains($html, 'Name') && str_contains($html, 'orchestrix_token'), 'Server renderer failed.');

$fields = new FieldRegistry();
CoreFieldCatalog::register($fields);
$inspector = new SchemaInspector();
$submissions = new SubmissionRepository();
$tokens = new SubmissionToken();
$jobs = new JobQueue();
$pipeline = new SubmissionPipeline(
    $forms,
    new FormValidator($fields, $inspector, new RuleEngine()),
    $submissions,
    $inspector,
    new ExpressionEngine(),
    new SpamScorer(),
    new RateLimiter(),
    $tokens,
    $jobs
);
$result = $pipeline->submit($formId, [
    'token' => $tokens->issue($formId, $versionId),
    'rendered_at' => time() - 5,
    'source_url' => 'https://example.test/contact',
    'values' => [
        'name' => 'Ada',
        'email' => 'ada-' . wp_generate_password(6, false) . '@example.test',
        'quantity' => 3,
        'price' => 4.5,
        'message' => 'Hello',
        'credential' => 'not-plain-at-rest',
    ],
], '127.0.0.1-smoke-' . wp_generate_password(6, false));
$entry = $submissions->find((int) $result['submission_id']);
$assert(is_array($entry), 'Submission persistence failed.');
$assert((int) $entry['form_version_id'] === $versionId, 'Historical version reference failed.');
$assert((float) $entry['values']['total'] === 13.5, 'Authoritative calculation failed.');
$assert($entry['values']['credential'] === null, 'Secret value was exposed without authorization.');
$privilegedEntry = $submissions->find((int) $result['submission_id'], true);
$assert(
    is_array($privilegedEntry) && $privilegedEntry['values']['credential'] === 'not-plain-at-rest',
    'Authorized secret decryption failed.'
);
global $wpdb;
$storedSecret = $wpdb->get_var($wpdb->prepare(
    "SELECT value_text FROM {$wpdb->prefix}orchestrix_submission_values
     WHERE submission_id = %d AND field_key = 'credential'",
    (int) $result['submission_id']
));
$assert(
    is_string($storedSecret) && str_starts_with($storedSecret, 'encrypted:v1:'),
    'Secret value was not encrypted at rest.'
);
$assert($jobs->process(10) >= 1, 'Queued workflow was not processed.');
$leaseUuid = $jobs->dispatch('lease_recovery_test', ['test' => true]);
$wpdb->update(
    $wpdb->prefix . 'orchestrix_jobs',
    ['status' => 'running', 'locked_at' => gmdate('Y-m-d H:i:s', time() - 1000)],
    ['uuid' => $leaseUuid]
);
$assert($jobs->process(10) >= 1, 'Expired worker lease was not recovered.');
$recoveredJob = $wpdb->get_row($wpdb->prepare(
    "SELECT status,attempts FROM {$wpdb->prefix}orchestrix_jobs WHERE uuid = %s",
    $leaseUuid
), ARRAY_A);
$assert(
    is_array($recoveredJob) && $recoveredJob['status'] === 'succeeded'
        && (int) $recoveredJob['attempts'] === 1,
    'Recovered worker lease did not complete with an attempt recorded.'
);

$draftSchema = $schema;
$draftSchema['fields'][0]['label'] = 'Full name';
$draftVersionId = $forms->saveDraft($formId, $draftSchema, 1);
$editable = $forms->find($formId);
$stillPublished = $forms->find($formId, true);
$assert(
    is_array($editable) && (int) $editable['version_id'] === $draftVersionId
        && $editable['version_state'] === 'draft',
    'Editable form did not resolve the latest draft.'
);
$assert(
    is_array($stillPublished) && (int) $stillPublished['version_id'] === $versionId,
    'Saving a draft changed the public version.'
);

wp_set_current_user(0);
do_action('rest_api_init');
$request = new WP_REST_Request('GET', '/orchestrix-forms/v1/submissions/' . (int) $result['submission_id']);
$response = rest_do_request($request);
$assert($response->get_status() === 401, 'Anonymous private-entry request was not denied.');

echo wp_json_encode([
    'form_id' => $formId,
    'version_id' => $versionId,
    'submission_id' => (int) $result['submission_id'],
    'calculated_total' => (float) $entry['values']['total'],
    'draft_version_id' => $draftVersionId,
    'secret_storage' => 'encrypted',
    'expired_queue_lease' => 'recovered',
    'anonymous_entry_status' => $response->get_status(),
    'queue' => 'processed',
], JSON_PRETTY_PRINT) . PHP_EOL;
