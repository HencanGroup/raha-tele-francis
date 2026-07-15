<?php

return [
    'must_reset_password' => [
        'title' => 'Reset Your Password',
        'heading' => 'You must reset your password',
        'field' => [
            'new_password' => [
                'label' => 'New Password',
            ],
            'confirm_password' => [
                'label' => 'Confirm New Password',
            ],
        ],
        'action' => [
            'update_password' => 'Update Password',
        ],
        'notification' => [
            'success_title' => 'Password Updated',
            'success_body' => 'Your password has been updated successfully.',
            'rate_limit_title' => 'Too Many Attempts',
            'rate_limit_body' => 'Please wait :seconds seconds before trying again.',
        ],
    ],

    'user' => [
        'navigation_group' => 'User Management',
        'label' => 'User',
        'plural_label' => 'Users',

        'section' => [
            'personal_info' => 'Personal Information',
            'account' => 'Account',
            'access' => 'Access',
        ],

        'field' => [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'status' => 'Status',
            'roles' => 'Roles',
            'email_verified_at' => 'Email Verified',
            'created_at' => 'Created At',
        ],

        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ],

        'action' => [
            'suspend' => 'Suspend',
            'activate' => 'Activate',
            'force_password_reset' => 'Force Password Reset',
            'assign_role' => 'Assign Role',
            'export' => 'Export',
        ],

        'notification' => [
            'suspended' => 'User has been suspended.',
            'activated' => 'User has been activated.',
            'password_reset' => 'Password reset email has been sent.',
        ],
    ],
    'review' => [
        'section' => [
            'details' => 'Review Details',
            'details_hint' => 'Rating, comment, and the associated user and escort.',
            'moderation' => 'Moderation',
            'moderation_hint' => 'Verify the review and control its visibility on the frontend.',
        ],
        'field' => [
            'author' => 'Author',
            'escort' => 'Escort',
            'rating' => 'Rating',
            'comment' => 'Comment',
            'is_verified' => 'Verified',
            'is_visible' => 'Visible',
        ],
    ],

    'escort_media' => [
        'section' => [
            'file' => 'Media File',
            'file_hint' => 'Select the escort, media type, file paths, and an optional caption.',
            'display' => 'Display Settings',
            'display_hint' => 'Control visibility, verification status, and sort ordering.',
        ],
        'field' => [
            'escort' => 'Escort',
            'type' => 'Type',
            'path' => 'File Path',
            'thumbnail' => 'Thumbnail Path',
            'caption' => 'Caption',
            'is_primary' => 'Primary',
            'is_verified' => 'Verified',
            'is_public' => 'Public',
            'sort_order' => 'Sort Order',
        ],
    ],
];
