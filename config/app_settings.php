<?php

return [

    // All the sections for the settings page
    'sections' => [
        'app' => [
            'title' => 'General Settings',
            'descriptions' => 'Application general settings.', // (optional)
            'icon' => 'fa fa-cog', // (optional)

            'inputs' => [
                [
                    'name' => 'app_name', // unique key for setting
                    'type' => 'text', // type of input can be text, number, textarea, select, boolean, checkbox etc.
                    'label' => 'App Name', // label for input
                    // optional properties
                    'placeholder' => 'Application Name', // placeholder for input
                    'class' => 'form-control', // override global input_class
                    'style' => '', // any inline styles
                    'rules' => 'required|min:2|max:20', // validation rules for this input
                    'value' => config('app.name'), // any default value
                    'hint' => 'You can set the app name here' // help block text for input
                ],
                [
                    'name' => 'app_currency',
                    'type' => 'text',
                    'label' => 'App Currency',
                    'placeholder' => 'Application Currency',
                    'class' => 'form-control',
                    'style' => '', // any inline styles
                    'rules' => 'required|max:10', // validation rules for this input
                    'value' => '$', // any default value
                    'hint' => 'Use your currency symbol like $',
                ],
                [
                    'name' => 'logo',
                    'type' => 'image',
                    'label' => 'Upload logo',
                    'hint' => 'Must be an image and cropped in desired size',
                    'rules' => 'image|max:500',
                    'disk' => 'public', // which disk you want to upload
                    'path' => 'app', // path on the disk,
                    'preview_class' => 'thumbnail',
                    'preview_style' => 'height:40px'
                ],
                [
                    'name' => 'favicon',
                    'type' => 'image',
                    'label' => 'Upload favicon',
                    'hint' => 'Must be an image and cropped in desired size',
                    'rules' => 'image|max:500',
                    'disk' => 'public', // which disk you want to upload
                    'path' => 'app', // path on the disk,
                    'preview_class' => 'thumbnail',
                    'preview_style' => 'height:40px'
                ]
            ]
        ],

        'notifications' => [
            'title' => 'Notifications',
            'descriptions' => 'WhatsApp / SMS alerts sent to customers when their order is completed.',
            'icon' => 'fa fa-bell',

            'inputs' => [
                [
                    'name' => 'notify_enabled',
                    'type' => 'boolean',
                    'label' => 'Turn notifications on',
                    'rules' => 'required|in:0,1',
                    'value' => '1',
                    'hint' => 'Off = no message is ever sent.',
                ],
                [
                    'name' => 'notify_driver',
                    'type' => 'select',
                    'label' => 'Send via',
                    'rules' => 'required|in:log,whatsapp,sms',
                    'value' => 'log',
                    'options' => [
                        'log' => 'Log file only (default, safe)',
                        'whatsapp' => 'WhatsApp Cloud API (free tier)',
                        'sms' => 'SMS HTTP gateway',
                    ],
                    'hint' => 'Log driver just writes to storage/logs/notify.log — useful for testing.',
                ],
                [
                    'name' => 'notify_message',
                    'type' => 'textarea',
                    'label' => 'Message template',
                    'rules' => 'nullable|max:300',
                    'value' => 'Hello {customer}, your order {order_no} is complete. Total {amount}, balance {balance}. Please collect it. Thank you - {shop}',
                    'hint' => 'Placeholders: {customer} {order_no} {amount} {balance} {shop}',
                ],
                [
                    'name' => 'notify_whatsapp_token',
                    'type' => 'text',
                    'label' => 'WhatsApp access token',
                    'rules' => 'nullable|max:300',
                    'value' => '',
                    'hint' => 'From https://developers.facebook.com (WhatsApp > API Setup).',
                ],
                [
                    'name' => 'notify_whatsapp_phone_id',
                    'type' => 'text',
                    'label' => 'WhatsApp phone number id',
                    'rules' => 'nullable|max:60',
                    'value' => '',
                    'hint' => 'The phone number id shown next to the token.',
                ],
                [
                    'name' => 'notify_gateway_url',
                    'type' => 'text',
                    'label' => 'SMS gateway URL',
                    'rules' => 'nullable|max:300',
                    'value' => '',
                    'hint' => 'GET url?to=&message=&sender=&apikey= — set this only if you pick the SMS driver.',
                ],
                [
                    'name' => 'notify_gateway_key',
                    'type' => 'text',
                    'label' => 'SMS gateway key',
                    'rules' => 'nullable|max:300',
                    'value' => '',
                    'hint' => 'Sent as apikey=.',
                ],
                [
                    'name' => 'notify_from',
                    'type' => 'text',
                    'label' => 'Sender id / from',
                    'rules' => 'nullable|max:30',
                    'value' => '',
                    'hint' => 'DLT sender id (SMS) or your brand name.',
                ],
            ]
        ],

    ],

    // Setting page url, will be used for get and post request
    'url' => 'settings',

    // Any middleware you want to run on above route
    'middleware' => ['auth', 'admin'],

    // View settings
    'setting_page_view' => 'settings',
    'flash_partial' => 'app_settings::_flash',

    // Setting section class setting
    'section_class' => 'card mb-3',
    'section_heading_class' => 'card-header',
    'section_body_class' => 'card-body',

    // Input wrapper and group class setting
    'input_wrapper_class' => 'form-group',
    'input_class' => 'form-control',
    'input_error_class' => 'has-error',
    'input_invalid_class' => 'is-invalid',
    'input_hint_class' => 'form-text text-muted',
    'input_error_feedback_class' => 'text-danger',

    // Submit button
    'submit_btn_text' => 'Save Settings',
    'submit_success_message' => 'Settings has been saved.',

    // Remove any setting which declaration removed later from sections
    'remove_abandoned_settings' => false,

    // Controller to show and handle save setting
    'controller' => '\QCod\AppSettings\Controllers\AppSettingController',

    // settings group
    'setting_group' => function() {
        // return 'user_'.auth()->id();
        return 'default';
    }
];
