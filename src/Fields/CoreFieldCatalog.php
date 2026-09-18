<?php

declare(strict_types=1);

namespace Orchestrix\Forms\Fields;

final class CoreFieldCatalog
{
    public static function register(FieldRegistry $registry): void
    {
        $groups = [
            'basic' => [
                'text' => 'text', 'textarea' => 'textarea', 'email' => 'email', 'url' => 'url', 'telephone' => 'tel',
                'number' => 'number', 'decimal' => 'number', 'integer' => 'integer', 'hidden' => 'hidden', 'password' => 'password',
            ],
            'choice' => [
                'select' => 'select', 'searchable_select' => 'select', 'multi_select' => 'multiselect', 'checkbox' => 'boolean',
                'checkbox_group' => 'checkboxes', 'radio' => 'radio', 'toggle' => 'boolean', 'button_choice' => 'radio',
                'image_choice' => 'radio', 'icon_choice' => 'radio', 'color_choice' => 'color',
            ],
            'date_time' => [
                'date' => 'date', 'time' => 'time', 'datetime' => 'datetime-local', 'date_range' => 'text', 'duration' => 'text',
                'month_year' => 'text', 'date_of_birth' => 'date', 'timezone_datetime' => 'datetime-local',
            ],
            'advanced' => [
                'address' => 'textarea', 'international_address' => 'textarea', 'country' => 'select', 'state' => 'text', 'city' => 'text',
                'postal_code' => 'text', 'map_location' => 'text', 'geolocation' => 'text', 'autocomplete_address' => 'text',
                'range_slider' => 'range', 'rating' => 'number', 'star_rating' => 'number', 'signature' => 'text', 'rich_text' => 'richtext',
                'code_editor' => 'textarea', 'file' => 'file', 'image_upload' => 'file', 'multiple_uploads' => 'file',
                'camera_capture' => 'file', 'drawing' => 'text',
            ],
            'structural' => [
                'section' => 'structural', 'heading' => 'structural', 'paragraph' => 'structural', 'html' => 'html', 'divider' => 'structural',
                'step' => 'structural', 'tab' => 'structural', 'accordion' => 'structural', 'columns' => 'structural',
                'repeater' => 'structural', 'nested_repeater' => 'structural', 'flexible_layout' => 'structural', 'conditional_container' => 'structural',
            ],
            'wordpress' => [
                'post' => 'select', 'page' => 'select', 'custom_post_type' => 'select', 'taxonomy' => 'select', 'term' => 'select',
                'user' => 'select', 'role' => 'select', 'post_relationship' => 'multiselect', 'post_object' => 'select',
                'featured_image' => 'file', 'comment' => 'textarea', 'attachment' => 'file', 'acf_field' => 'text',
            ],
            'survey' => [
                'likert' => 'radio', 'matrix' => 'radio', 'nps' => 'number', 'ranking' => 'multiselect', 'scale' => 'range',
                'satisfaction' => 'radio', 'poll' => 'radio', 'semantic_differential' => 'radio',
            ],
            'quiz' => [
                'multiple_choice' => 'radio', 'multiple_answer' => 'checkboxes', 'true_false' => 'radio', 'short_answer' => 'text',
                'numeric_answer' => 'number', 'weighted_choice' => 'radio', 'personality_choice' => 'radio',
            ],
            'commerce' => [
                'product' => 'select', 'quantity' => 'integer', 'price' => 'number', 'subtotal' => 'number', 'discount' => 'number',
                'coupon' => 'text', 'tax' => 'number', 'total' => 'number', 'shipping' => 'select', 'payment_method' => 'select',
                'donation_amount' => 'number', 'custom_amount' => 'number', 'subscription_plan' => 'select',
            ],
            'special' => [
                'consent' => 'boolean', 'terms' => 'boolean', 'captcha' => 'text', 'antispam' => 'hidden', 'honeypot' => 'hidden',
                'otp' => 'text', 'confirmation' => 'text', 'line_item' => 'structural', 'calculation' => 'number',
                'lookup' => 'text', 'hierarchical_select' => 'select', 'remote_options' => 'select',
            ],
        ];
        foreach ($groups as $category => $types) {
            foreach ($types as $type => $input) {
                $registry->register(new CoreFieldType($type, ['category' => $category, 'input' => $input]));
            }
        }
    }
}
