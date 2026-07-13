<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Support\Str;

class UserObserver
{
    private static ?string $plainPassword = null;

    public function creating(User $model): void
    {
        // Auto-generate login name from first initial + full last name.
        if ($model->first_name && $model->last_name) {
            $model->name = strtolower(substr($model->first_name, 0, 1).$model->last_name);
        }

        // Members never get a system-generated password.
        if ($model->user_type === 'member') {
            $model->is_temp_password = false;

            return;
        }

        // System users and escorts get an auto-generated temp password.
        if (empty($model->password)) {
            self::$plainPassword = Str::random(12);
            $model->password = bcrypt(self::$plainPassword);
            $model->is_temp_password = true;
        }
    }

    public function created(User $model): void
    {
        if (empty($model->email)) {
            return;
        }

        if (in_array($model->user_type, ['system_user', 'escort']) && self::$plainPassword) {
            app(UserService::class)->onCreate($model, self::$plainPassword);
            self::$plainPassword = null;
        }

        if ($model->user_type === 'member') {
            // Verification-only email (no password).
            app(UserService::class)->onCreate($model, null);
        }
    }
}
