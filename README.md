# laravel-app-foundation

ZEN（zen-app）と月詠 TAROT JOURNAL（tarot-app）の 2 つの Laravel アプリケーションで同じ内容だったコードを、1 か所で保守するための共通パッケージです。

両方のアプリで同一、またはほぼ同一だったものだけを入れています。認証の画面、管理画面、課金の処理のように、アプリごとに内容が違うものは入れていません。

## 含まれるもの

| クラス | 内容 | アプリ側で用意するもの |
| --- | --- | --- |
| `Http\Middleware\EnsureActiveUser` | 利用停止中（`is_active` が偽）のユーザーをログアウトさせ、ログイン画面へ戻す | ユーザーの `is_active` 属性、`login` という名前のルート |
| `Http\Middleware\EnsureAdmin` | 管理者でなければ 403 | ユーザーモデルに `Contracts\Administrator` を実装する |
| `Console\PromoteUserToAdmin` | `php artisan users:promote-admin {email}`。有効なユーザーを管理者にする | 同上 |
| `Services\GuestTrialCreditService` | 端末ごとのゲスト試用回数の確認・消費・返却。端末のトークンは HMAC だけを保存する | `guest_trial_credits` テーブル（各アプリの migration が作成済み）、`config/app-foundation.php` の上限 |
| `Http\Controllers\StripeWebhookController` | Stripe の署名を検証し、イベントをアプリのハンドラーへ渡す | `Contracts\HandlesStripeWebhooks` の実装をコンテナに登録する、`services.stripe.webhook_secret`、CSRF の除外 |

migration は含めていません。両方のアプリで適用済みの migration を、そのまま各アプリに残しています。

## 導入

Packagist には登録していないので、`composer.json` にリポジトリを追加します。

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/TakahiroNago/laravel-app-foundation" }
    ],
    "require": {
        "takahironago/laravel-app-foundation": "^0.1"
    }
}
```

サービスプロバイダーは自動で登録されます。

### 1. ユーザーモデル

```php
use TakahiroNago\AppFoundation\Contracts\Administrator;

class User extends Authenticatable implements Administrator
{
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function grantAdminAccess(): void
    {
        $this->forceFill(['is_admin' => true])->save();
    }
}
```

管理者かどうかの持ち方（真偽値の列、`role` の列など）はアプリが決めます。

### 2. ミドルウェア（`bootstrap/app.php`）

```php
use TakahiroNago\AppFoundation\Http\Middleware\EnsureActiveUser;
use TakahiroNago\AppFoundation\Http\Middleware\EnsureAdmin;

$middleware->alias([
    'active' => EnsureActiveUser::class,
    'admin' => EnsureAdmin::class,
]);
```

### 3. ゲストの試用回数（`config/app-foundation.php`）

```php
return [
    'guest_trial' => [
        'credit_limit' => 3,
    ],
];
```

設定ファイルは `php artisan vendor:publish --tag=app-foundation-config` で作成できます。未設定の場合の上限は 1 です。

### 4. Stripe の Webhook

```php
// app/Providers/AppServiceProvider.php
use TakahiroNago\AppFoundation\Contracts\HandlesStripeWebhooks;

public function register(): void
{
    $this->app->bind(HandlesStripeWebhooks::class, StripeBillingService::class);
}

// routes/web.php
use TakahiroNago\AppFoundation\Http\Controllers\StripeWebhookController;

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
```

署名のシークレットが未設定なら 503、署名か本文が不正なら 400 を返し、どちらの場合もハンドラーは呼びません。

## 開発

```sh
composer install
vendor/bin/pest          # Orchestra Testbench 上のテスト
vendor/bin/pint --test   # コードスタイル
```

## バージョン

変更はタグ（`v0.1.0` など）で公開します。アプリ側は `composer update takahironago/laravel-app-foundation` で取り込みます。0.x の間は、マイナーバージョンで互換性のない変更をする場合があります。

## ライセンス

[MIT](LICENSE)
