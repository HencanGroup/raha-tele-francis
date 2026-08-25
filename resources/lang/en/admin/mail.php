<?php

return [
    'user' => [
        'subject' => 'Your Account Credentials',
        'greeting' => 'Hello :name!',
        'intro' => 'Your account has been created. Please find your login credentials below.',
        'email_label' => 'Email',
        'password_label' => 'Temporary Password',
        'verify_button' => 'Verify Email Address',
        'outro' => 'You will be required to set a new password upon your first login.',
        'thanks' => 'Thank you',
    ],

    'escort_registration_confirmed' => [
        'subject' => 'Your RAHA-TELE application has been received',
        'greeting' => 'Hello :name!',
        'body' => 'Thank you for registering as an escort on RAHA-TELE. We have received your application and it is now under review by our team.',
        'next_steps' => 'We will notify you by email once your profile has been approved. This usually takes 24-48 hours. In the meantime, you can log in to your account, but your profile will not be visible to clients until it is verified.',
        'outro' => 'If you have any questions, please contact our support team.',
        'thanks' => 'Thank you',
    ],

    'escort_approved' => [
        'subject' => 'Your RAHA-TELE profile has been approved!',
        'greeting' => 'Hello :name!',
        'body' => 'Great news — your escort profile has been approved! You are now visible to clients and can start receiving bookings.',
        'verify_instruction' => 'Please verify your email address by clicking the button below to fully activate your account:',
        'verify_button' => 'Verify Email Address',
        'outro' => 'Once verified, you can log in and start managing your profile. If you have questions, please contact our support team.',
        'thanks' => 'Thank you',
    ],

    'escort_verification' => [
        'approved_subject' => 'Your RAHA-TELE profile has been approved',
        'rejected_subject' => 'Your RAHA-TELE profile application was not approved',
        'greeting' => 'Hello :name!',
        'approved_body' => 'Great news — your profile has been approved. You are now visible to clients and can start receiving bookings.',
        'rejected_body' => 'Unfortunately, your profile application was not approved. You can update your profile and re-submit for review.',
        'reason_label' => 'Reason',
        'login_button' => 'Log In to Your Account',
        'outro' => 'If you have questions, please contact our support team.',
        'thanks' => 'Thank you',
    ],
];
