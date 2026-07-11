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
        if (empty($model->password)) {
            self::$plainPassword = Str::random(12);
            $model->password = bcrypt(self::$plainPassword);
            $model->is_temp_password = true;
        }
    }

    public function created(User $model): void
    {
        if (! empty($model->email) && $model->is_temp_password) {
            app(UserService::class)->onCreate($model, self::$plainPassword);
            self::$plainPassword = null;
        }
    }
}
