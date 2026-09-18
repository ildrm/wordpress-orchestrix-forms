<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Admin;

final class Admin
{
    public function registerMenu(): void
    {
        add_menu_page(__('Orchestrix Forms', 'orchestrix-forms'), __('Orchestrix', 'orchestrix-forms'), 'orchestrix_manage_forms', 'orchestrix-forms', [$this, 'render'], 'dashicons-feedback', 56);
        add_submenu_page('orchestrix-forms', __('Entries', 'orchestrix-forms'), __('Entries', 'orchestrix-forms'), 'orchestrix_view_entries', 'orchestrix-entries', [$this, 'renderEntries']);
        add_submenu_page('orchestrix-forms', __('Diagnostics', 'orchestrix-forms'), __('Diagnostics', 'orchestrix-forms'), 'orchestrix_manage_settings', 'orchestrix-diagnostics', [$this, 'renderDiagnostics']);
    }

    public function enqueue(string $hook): void
    {
        if (! str_contains($hook, 'orchestrix')) {
            return;
        }
        wp_enqueue_style('orchestrix-admin', ORCHESTRIX_FORMS_URL . 'assets/dist/admin.css', [], ORCHESTRIX_FORMS_VERSION);
        wp_enqueue_script('orchestrix-admin', ORCHESTRIX_FORMS_URL . 'assets/dist/admin.js', ['wp-api-fetch'], ORCHESTRIX_FORMS_VERSION, true);
        wp_add_inline_script('orchestrix-admin', 'window.OrchestrixAdmin=' . wp_json_encode([
            'root' => esc_url_raw(rest_url('orchestrix-forms/v1/')), 'nonce' => wp_create_nonce('wp_rest'),
            'page' => sanitize_key((string) ($_GET['page'] ?? '')),
        ]) . ';', 'before');
    }

    public function render(): void
    {
        echo '<div class="wrap orchestrix-admin"><div id="orchestrix-admin-root"><p>' . esc_html__('Loading form builder…', 'orchestrix-forms') . '</p></div></div>';
    }

    public function renderEntries(): void
    {
        echo '<div class="wrap orchestrix-admin"><h1>' . esc_html__('Entries', 'orchestrix-forms') . '</h1><div id="orchestrix-admin-root"></div></div>';
    }

    public function renderDiagnostics(): void
    {
        global $wpdb;
        $jobs = $wpdb->get_results("SELECT status, COUNT(*) count FROM {$wpdb->prefix}orchestrix_jobs GROUP BY status", ARRAY_A) ?: [];
        echo '<div class="wrap orchestrix-admin"><h1>' . esc_html__('Orchestrix diagnostics', 'orchestrix-forms') . '</h1><dl>';
        echo '<dt>' . esc_html__('Plugin version', 'orchestrix-forms') . '</dt><dd>' . esc_html(ORCHESTRIX_FORMS_VERSION) . '</dd>';
        echo '<dt>' . esc_html__('Database version', 'orchestrix-forms') . '</dt><dd>' . esc_html((string) get_option('orchestrix_forms_db_version', 'not installed')) . '</dd>';
        echo '<dt>' . esc_html__('REST API', 'orchestrix-forms') . '</dt><dd>' . esc_html(rest_url('orchestrix-forms/v1/')) . '</dd>';
        echo '<dt>' . esc_html__('Queue', 'orchestrix-forms') . '</dt><dd><pre>' . esc_html(wp_json_encode($jobs, JSON_PRETTY_PRINT) ?: '[]') . '</pre></dd></dl></div>';
    }
}
