<?php

namespace TakahiroNago\AppFoundation\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use TakahiroNago\AppFoundation\Contracts\Administrator;

#[Signature('users:promote-admin {email}')]
#[Description('Promote an active user to administrator')]
class PromoteUserToAdmin extends Command
{
    public function handle(): int
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model');

        if (! is_a($model, Administrator::class, true)) {
            throw new LogicException(sprintf('%s must implement %s.', $model, Administrator::class));
        }

        /** @var (Model&Administrator)|null $user */
        $user = $model::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('そのメールアドレスのユーザーは見つかりません。');

            return self::FAILURE;
        }

        if (! $user->is_active) {
            $this->error('利用停止中のユーザーはAdminに昇格できません。');

            return self::FAILURE;
        }

        $user->grantAdminAccess();
        $this->info("{$user->email} をAdminに設定しました。");

        return self::SUCCESS;
    }
}
