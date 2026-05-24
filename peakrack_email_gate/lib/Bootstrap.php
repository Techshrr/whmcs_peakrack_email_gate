<?php

/**
 * Shared runtime helpers for PeakRack Email Verification Gate.
 *
 * Target runtime: WHMCS 9.0.3 / PHP 8.2-8.3.
 */

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('No direct access');
}

const PREG_MODULE = 'peakrack_email_gate';
const PREG_VERSION = '1.1.3';
const PREG_SETTING_KEY = 'config';
const PREG_SETTINGS_TABLE = 'mod_peakrack_email_gate_settings';
const PREG_RECORDS_TABLE = 'mod_peakrack_email_gate_records';
const PREG_LOGS_TABLE = 'mod_peakrack_email_gate_logs';

if (!function_exists('peakrackEmailGateDefaults')) {
    function peakrackEmailGateDefaults(): array
    {
        return [
            'enabled' => true,
            'gateEnabled' => true,
            'checkoutBlock' => true,
            'activityLog' => true,
            'adminLanguage' => 'en',
            'hmacSecret' => '',
            'cooldownSeconds' => 60,
            'hourlyLimit' => 5,
            'codeLifetimeMinutes' => 10,
            'tokenLifetimeMinutes' => 30,
            'maxFailedAttempts' => 5,
            'lockMinutes' => 15,
            'logRetentionDays' => 180,
            'maxLogs' => 10000,
            'emailTemplateVersion' => '1.1.0',
            'emailSubjectEn' => 'Verify your email address',
            'emailSubjectZh' => '请验证你的邮箱地址',
            'emailMessageEn' => peakrackEmailGateDefaultEmailMessage('en'),
            'emailMessageZh' => peakrackEmailGateDefaultEmailMessage('zh'),
            'clientNoticeEn' => 'Your email address is not verified yet. To use all account features, please complete email verification. Use the system verification email above first, or request a new code below.',
            'clientNoticeZh' => '由于你的邮箱尚未验证，使用完整系统功能前请先完成邮箱验证。请优先使用上方系统发送的验证邮件，也可以在下方重新获取验证码。',
        ];
    }
}

if (!function_exists('peakrackEmailGateDefaultEmailMessage')) {
    function peakrackEmailGateDefaultEmailMessage(string $language): string
    {
        if ($language === 'zh') {
            return '<div style="font-family:Arial,Helvetica,sans-serif;color:#172033;line-height:1.65;max-width:640px;">'
                . "\n" . '<p style="margin:0 0 16px;">你好 {$client_name}，</p>'
                . "\n" . '<p style="margin:0 0 18px;">为了保护你的账户安全，请验证你在 {$company_name} 的邮箱地址。</p>'
                . "\n" . '<p style="margin:0 0 24px;"><a href="{$verification_link}" style="display:inline-block;background:#0b63ce;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:700;">验证邮箱地址</a></p>'
                . "\n" . '<p style="margin:0 0 8px;color:#475467;font-size:14px;font-weight:700;">安全验证码</p>'
                . "\n" . '<div style="display:inline-block;background:#0b63ce;color:#ffffff;border-radius:8px;padding:14px 20px;font-family:Consolas,Monaco,monospace;font-size:30px;font-weight:800;letter-spacing:7px;line-height:1;">{$verification_code}</div>'
                . "\n" . '<p style="margin:18px 0 0;color:#667085;font-size:13px;">验证码 {$code_minutes} 分钟内有效，验证链接 {$token_minutes} 分钟内有效。如果不是你本人操作，可以忽略本邮件。</p>'
                . "\n" . '<p style="margin:22px 0 0;">{$signature}</p>'
                . "\n" . '</div>';
        }

        return '<div style="font-family:Arial,Helvetica,sans-serif;color:#172033;line-height:1.65;max-width:640px;">'
            . "\n" . '<p style="margin:0 0 16px;">Hello {$client_name},</p>'
            . "\n" . '<p style="margin:0 0 18px;">To keep your account secure, please verify the email address you use with {$company_name}.</p>'
            . "\n" . '<p style="margin:0 0 24px;"><a href="{$verification_link}" style="display:inline-block;background:#0b63ce;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:700;">Verify email address</a></p>'
            . "\n" . '<p style="margin:0 0 8px;color:#475467;font-size:14px;font-weight:700;">Security code</p>'
            . "\n" . '<div style="display:inline-block;background:#0b63ce;color:#ffffff;border-radius:8px;padding:14px 20px;font-family:Consolas,Monaco,monospace;font-size:30px;font-weight:800;letter-spacing:7px;line-height:1;">{$verification_code}</div>'
            . "\n" . '<p style="margin:18px 0 0;color:#667085;font-size:13px;">The code expires in {$code_minutes} minutes. The verification link expires in {$token_minutes} minutes. If you did not request this, you can ignore this email.</p>'
            . "\n" . '<p style="margin:22px 0 0;">{$signature}</p>'
            . "\n" . '</div>';
    }
}

if (!function_exists('peakrackEmailGateCreateTables')) {
    function peakrackEmailGateCreateTables(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable(PREG_SETTINGS_TABLE)) {
            $schema->create(PREG_SETTINGS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->string('setting', 100)->unique();
                $table->longText('value');
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(PREG_RECORDS_TABLE)) {
            $schema->create(PREG_RECORDS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable()->unique();
                $table->unsignedInteger('client_id')->nullable()->index();
                $table->string('email', 255)->nullable()->index();
                $table->string('status', 40)->default('pending')->index();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_sent_at')->nullable()->index();
                $table->timestamp('hourly_window_start')->nullable();
                $table->unsignedSmallInteger('hourly_send_count')->default(0);
                $table->unsignedInteger('resend_total')->default(0);
                $table->string('token_hash', 128)->nullable()->index();
                $table->timestamp('token_expires_at')->nullable()->index();
                $table->string('code_hash', 128)->nullable();
                $table->timestamp('code_expires_at')->nullable()->index();
                $table->unsignedSmallInteger('failed_attempts')->default(0);
                $table->timestamp('locked_until')->nullable()->index();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(PREG_LOGS_TABLE)) {
            $schema->create(PREG_LOGS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->unsignedInteger('client_id')->nullable()->index();
                $table->string('email', 255)->nullable()->index();
                $table->string('level', 20)->index();
                $table->string('event', 80)->index();
                $table->string('message', 255);
                $table->longText('context')->nullable();
                $table->string('ip', 64)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }
}

if (!function_exists('peakrackEmailGateLoadSettings')) {
    function peakrackEmailGateLoadSettings(): array
    {
        try {
            peakrackEmailGateCreateTables();
            $row = Capsule::table(PREG_SETTINGS_TABLE)
                ->where('setting', PREG_SETTING_KEY)
                ->first();
            $stored = $row ? peakrackEmailGateJsonDecode((string) $row->value, []) : [];
        } catch (Throwable) {
            $stored = [];
        }

        return peakrackEmailGateMergeSettings(peakrackEmailGateDefaults(), $stored);
    }
}

if (!function_exists('peakrackEmailGateSaveSettings')) {
    function peakrackEmailGateSaveSettings(array $settings): void
    {
        peakrackEmailGateCreateTables();
        $settings = peakrackEmailGateMergeSettings(peakrackEmailGateDefaults(), $settings);
        if ((string) $settings['hmacSecret'] === '') {
            $settings['hmacSecret'] = peakrackEmailGateRandomHex(32);
        }

        $payload = [
            'setting' => PREG_SETTING_KEY,
            'value' => peakrackEmailGateJsonEncode($settings),
            'updated_at' => peakrackEmailGateNow(),
        ];

        $exists = Capsule::table(PREG_SETTINGS_TABLE)
            ->where('setting', PREG_SETTING_KEY)
            ->exists();

        if ($exists) {
            Capsule::table(PREG_SETTINGS_TABLE)->where('setting', PREG_SETTING_KEY)->update($payload);
            return;
        }

        Capsule::table(PREG_SETTINGS_TABLE)->insert($payload);
    }
}

if (!function_exists('peakrackEmailGateMergeSettings')) {
    function peakrackEmailGateMergeSettings(array $defaults, array $stored): array
    {
        $settings = array_replace($defaults, $stored);
        foreach (['enabled', 'gateEnabled', 'checkoutBlock', 'activityLog'] as $key) {
            $settings[$key] = peakrackEmailGateBool($settings[$key] ?? $defaults[$key]);
        }

        $settings['adminLanguage'] = in_array((string) ($settings['adminLanguage'] ?? 'en'), ['en', 'zh'], true)
            ? (string) $settings['adminLanguage']
            : 'en';

        $settings['cooldownSeconds'] = peakrackEmailGateClampInt($settings['cooldownSeconds'] ?? 60, 10, 3600, 60);
        $settings['hourlyLimit'] = peakrackEmailGateClampInt($settings['hourlyLimit'] ?? 5, 1, 100, 5);
        $settings['codeLifetimeMinutes'] = peakrackEmailGateClampInt($settings['codeLifetimeMinutes'] ?? 10, 1, 1440, 10);
        $settings['tokenLifetimeMinutes'] = peakrackEmailGateClampInt($settings['tokenLifetimeMinutes'] ?? 30, 1, 1440, 30);
        $settings['maxFailedAttempts'] = peakrackEmailGateClampInt($settings['maxFailedAttempts'] ?? 5, 1, 50, 5);
        $settings['lockMinutes'] = peakrackEmailGateClampInt($settings['lockMinutes'] ?? 15, 1, 1440, 15);
        $settings['logRetentionDays'] = peakrackEmailGateClampInt($settings['logRetentionDays'] ?? 180, 0, 3650, 180);
        $settings['maxLogs'] = peakrackEmailGateClampInt($settings['maxLogs'] ?? 10000, 0, 1000000, 10000);

        foreach (['hmacSecret', 'emailTemplateVersion', 'emailSubjectEn', 'emailSubjectZh', 'emailMessageEn', 'emailMessageZh', 'clientNoticeEn', 'clientNoticeZh'] as $key) {
            $settings[$key] = (string) ($settings[$key] ?? $defaults[$key] ?? '');
        }

        foreach (['emailSubjectEn', 'emailSubjectZh', 'emailMessageEn', 'emailMessageZh', 'clientNoticeEn', 'clientNoticeZh'] as $key) {
            $settings[$key] = peakrackEmailGateNormalizeEditableText((string) $settings[$key]);
        }

        if (peakrackEmailGateLooksLikeLegacyDefaultEmail($settings['emailMessageEn'], 'en')) {
            $settings['emailMessageEn'] = $defaults['emailMessageEn'];
        }
        if (peakrackEmailGateLooksLikeLegacyDefaultEmail($settings['emailMessageZh'], 'zh')) {
            $settings['emailMessageZh'] = $defaults['emailMessageZh'];
        }
        if (str_contains($settings['clientNoticeEn'], 'native WHMCS verification email')) {
            $settings['clientNoticeEn'] = $defaults['clientNoticeEn'];
        }
        if (str_contains($settings['clientNoticeZh'], 'WHMCS 原生验证邮件')
            || str_contains($settings['clientNoticeZh'], '你的邮箱地址尚未验证')) {
            $settings['clientNoticeZh'] = $defaults['clientNoticeZh'];
        }
        $settings['emailTemplateVersion'] = $defaults['emailTemplateVersion'];

        return $settings;
    }
}

if (!function_exists('peakrackEmailGateLooksLikeLegacyDefaultEmail')) {
    function peakrackEmailGateLooksLikeLegacyDefaultEmail(string $message, string $language): bool
    {
        $normalized = peakrackEmailGateNormalizeEditableText($message);
        if (str_contains($normalized, 'font-size:24px;letter-spacing:4px;')
            || str_contains($normalized, 'font-size:24px; letter-spacing:4px;')
            || str_contains($normalized, 'Verification code:')
            || str_contains($normalized, '6 位验证码')
            || str_contains($normalized, '6位验证码')) {
            return true;
        }

        if ($language === 'zh') {
            return str_contains($normalized, '<p>安全验证码</p>')
                && str_contains($normalized, '{$verification_code}')
                && str_contains($normalized, '验证邮箱地址</a>');
        }

        return str_contains($normalized, '<p>Security code</p>')
            && str_contains($normalized, '{$verification_code}')
            && str_contains($normalized, 'Verify email address</a>');
    }
}

if (!function_exists('peakrackEmailGateInitializeRecord')) {
    function peakrackEmailGateInitializeRecord(array $context, string $source = 'user_add'): void
    {
        peakrackEmailGateCreateTables();
        $userId = (int) ($context['user_id'] ?? 0);
        $clientId = (int) ($context['client_id'] ?? 0);
        $email = trim((string) ($context['email'] ?? ''));

        if ($userId <= 0 && $email === '') {
            return;
        }

        $now = peakrackEmailGateNow();
        $payload = [
            'user_id' => $userId > 0 ? $userId : null,
            'client_id' => $clientId > 0 ? $clientId : null,
            'email' => $email !== '' ? $email : null,
            'status' => 'pending',
            'first_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $record = peakrackEmailGateRecordForContext($context);
        if ($record) {
            Capsule::table(PREG_RECORDS_TABLE)
                ->where('id', (int) $record->id)
                ->update([
                    'client_id' => $clientId > 0 ? $clientId : ($record->client_id ?? null),
                    'email' => $email !== '' ? $email : ($record->email ?? null),
                    'updated_at' => $now,
                ]);
            return;
        }

        Capsule::table(PREG_RECORDS_TABLE)->insert($payload);
        peakrackEmailGateLog('info', 'user_initialized', 'Email gate record initialized without sending a custom email.', $context, ['source' => $source]);
    }
}

if (!function_exists('peakrackEmailGateResendCustomVerification')) {
    function peakrackEmailGateResendCustomVerification(array $context, array $settings): array
    {
        if (!peakrackEmailGateBool($settings['enabled'] ?? true)) {
            return ['success' => false, 'message' => 'module_disabled'];
        }

        if (peakrackEmailGateIsVerified($context)) {
            return ['success' => true, 'message' => 'already_verified'];
        }

        peakrackEmailGateInitializeRecord($context, 'resend');
        $record = peakrackEmailGateRecordForContext($context);
        if (!$record) {
            return ['success' => false, 'message' => 'record_missing'];
        }

        $nowTs = time();
        $lastSent = peakrackEmailGateTimestamp($record->last_sent_at ?? null);
        $cooldown = (int) ($settings['cooldownSeconds'] ?? 60);
        if ($lastSent > 0 && ($nowTs - $lastSent) < $cooldown) {
            return [
                'success' => false,
                'message' => 'cooldown',
                'wait' => $cooldown - ($nowTs - $lastSent),
            ];
        }

        $windowStart = peakrackEmailGateTimestamp($record->hourly_window_start ?? null);
        $hourlyCount = (int) ($record->hourly_send_count ?? 0);
        if ($windowStart <= 0 || ($nowTs - $windowStart) >= 3600) {
            $windowStart = $nowTs;
            $hourlyCount = 0;
        }

        $hourlyLimit = (int) ($settings['hourlyLimit'] ?? 5);
        if ($hourlyCount >= $hourlyLimit) {
            return ['success' => false, 'message' => 'hourly_limit'];
        }

        $token = peakrackEmailGateRandomToken();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tokenHash = peakrackEmailGateHmac($token, $settings);
        $codeHash = peakrackEmailGateHmac($code, $settings);
        $tokenExpiresAt = date('Y-m-d H:i:s', $nowTs + ((int) $settings['tokenLifetimeMinutes'] * 60));
        $codeExpiresAt = date('Y-m-d H:i:s', $nowTs + ((int) $settings['codeLifetimeMinutes'] * 60));

        Capsule::table(PREG_RECORDS_TABLE)
            ->where('id', (int) $record->id)
            ->update([
                'status' => 'custom_sent',
                'token_hash' => $tokenHash,
                'token_expires_at' => $tokenExpiresAt,
                'code_hash' => $codeHash,
                'code_expires_at' => $codeExpiresAt,
                'failed_attempts' => 0,
                'locked_until' => null,
                'last_sent_at' => peakrackEmailGateNow(),
                'hourly_window_start' => date('Y-m-d H:i:s', $windowStart),
                'hourly_send_count' => $hourlyCount + 1,
                'resend_total' => ((int) ($record->resend_total ?? 0)) + 1,
                'updated_at' => peakrackEmailGateNow(),
            ]);

        $sendResult = peakrackEmailGateSendCustomEmail($context, $settings, $token, $code);
        if (!$sendResult['success']) {
            Capsule::table(PREG_RECORDS_TABLE)
                ->where('id', (int) $record->id)
                ->update([
                    'status' => (string) ($record->status ?? 'pending'),
                    'token_hash' => $record->token_hash ?? null,
                    'token_expires_at' => $record->token_expires_at ?? null,
                    'code_hash' => $record->code_hash ?? null,
                    'code_expires_at' => $record->code_expires_at ?? null,
                    'failed_attempts' => (int) ($record->failed_attempts ?? 0),
                    'locked_until' => $record->locked_until ?? null,
                    'last_sent_at' => $record->last_sent_at ?? null,
                    'hourly_window_start' => $record->hourly_window_start ?? null,
                    'hourly_send_count' => (int) ($record->hourly_send_count ?? 0),
                    'resend_total' => (int) ($record->resend_total ?? 0),
                    'updated_at' => peakrackEmailGateNow(),
                ]);
            peakrackEmailGateLog('error', 'custom_email_failed', 'Custom verification email failed to send.', $context, ['error' => $sendResult['message'] ?? 'unknown']);
            return ['success' => false, 'message' => 'send_failed'];
        }

        peakrackEmailGateLog('info', 'custom_email_sent', 'Custom verification email sent.', $context, [
            'token_expires_at' => $tokenExpiresAt,
            'code_expires_at' => $codeExpiresAt,
        ]);

        return ['success' => true, 'message' => 'sent'];
    }
}

if (!function_exists('peakrackEmailGateVerifyToken')) {
    function peakrackEmailGateVerifyToken(string $token, array $settings): array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 160) {
            return ['success' => false, 'message' => 'invalid_token'];
        }

        $hash = peakrackEmailGateHmac($token, $settings);
        $record = Capsule::table(PREG_RECORDS_TABLE)
            ->where('token_hash', $hash)
            ->first();

        if (!$record) {
            return ['success' => false, 'message' => 'invalid_token'];
        }

        if (peakrackEmailGateTimestamp($record->token_expires_at ?? null) < time()) {
            peakrackEmailGateLog('warning', 'token_expired', 'Expired verification link was used.', peakrackEmailGateContextFromRecord($record));
            return ['success' => false, 'message' => 'token_expired'];
        }

        return peakrackEmailGateCompleteVerification($record, 'token');
    }
}

if (!function_exists('peakrackEmailGateVerifyCode')) {
    function peakrackEmailGateVerifyCode(array $context, string $code, array $settings): array
    {
        $code = trim($code);
        if (!preg_match('/^[0-9]{6}$/', $code)) {
            return ['success' => false, 'message' => 'invalid_code'];
        }

        $record = peakrackEmailGateRecordForContext($context);
        if (!$record || trim((string) ($record->code_hash ?? '')) === '') {
            return ['success' => false, 'message' => 'code_missing'];
        }

        $lockUntil = peakrackEmailGateTimestamp($record->locked_until ?? null);
        if ($lockUntil > time()) {
            return ['success' => false, 'message' => 'locked', 'wait' => $lockUntil - time()];
        }

        if (peakrackEmailGateTimestamp($record->code_expires_at ?? null) < time()) {
            return ['success' => false, 'message' => 'code_expired'];
        }

        $codeHash = peakrackEmailGateHmac($code, $settings);
        if (hash_equals((string) $record->code_hash, $codeHash)) {
            return peakrackEmailGateCompleteVerification($record, 'code');
        }

        $failed = ((int) ($record->failed_attempts ?? 0)) + 1;
        $updates = [
            'failed_attempts' => $failed,
            'updated_at' => peakrackEmailGateNow(),
        ];
        $maxFailed = (int) ($settings['maxFailedAttempts'] ?? 5);
        if ($failed >= $maxFailed) {
            $updates['locked_until'] = date('Y-m-d H:i:s', time() + ((int) $settings['lockMinutes'] * 60));
        }

        Capsule::table(PREG_RECORDS_TABLE)
            ->where('id', (int) $record->id)
            ->update($updates);

        peakrackEmailGateLog($failed >= $maxFailed ? 'warning' : 'info', $failed >= $maxFailed ? 'code_locked' : 'code_failed', 'Verification code check failed.', $context, [
            'failed_attempts' => $failed,
            'locked' => $failed >= $maxFailed,
        ]);

        return ['success' => false, 'message' => $failed >= $maxFailed ? 'locked_now' : 'code_failed'];
    }
}

if (!function_exists('peakrackEmailGateCompleteVerification')) {
    function peakrackEmailGateCompleteVerification(object $record, string $method): array
    {
        $context = peakrackEmailGateContextFromRecord($record);
        $sync = peakrackEmailGateMarkWhmcsEmailVerified((int) ($context['user_id'] ?? 0), (int) ($context['client_id'] ?? 0));
        if (!$sync['success']) {
            peakrackEmailGateLog('error', 'sync_failed', 'Email verification succeeded but WHMCS status sync failed.', $context, ['method' => $method, 'error' => $sync['message']]);
            return ['success' => false, 'message' => 'sync_failed'];
        }

        Capsule::table(PREG_RECORDS_TABLE)
            ->where('id', (int) $record->id)
            ->update([
                'status' => 'verified',
                'token_hash' => null,
                'token_expires_at' => null,
                'code_hash' => null,
                'code_expires_at' => null,
                'failed_attempts' => 0,
                'locked_until' => null,
                'verified_at' => peakrackEmailGateNow(),
                'updated_at' => peakrackEmailGateNow(),
            ]);

        peakrackEmailGateLog('info', 'verified', 'Email verification completed.', $context, ['method' => $method, 'sync' => $sync['method']]);
        return ['success' => true, 'message' => 'verified'];
    }
}

if (!function_exists('peakrackEmailGateMarkWhmcsEmailVerified')) {
    function peakrackEmailGateMarkWhmcsEmailVerified(int $userId, int $clientId): array
    {
        $now = peakrackEmailGateNow();
        $modelMessage = '';

        if ($userId > 0 && class_exists('\\WHMCS\\User\\User')) {
            try {
                $userClass = '\\WHMCS\\User\\User';
                $user = method_exists($userClass, 'find') ? $userClass::find($userId) : null;
                if ($user) {
                    foreach (['markEmailAddressAsVerified', 'markEmailAsVerified', 'verifyEmail', 'setEmailVerified'] as $method) {
                        if (method_exists($user, $method)) {
                            $user->{$method}();
                            if (method_exists($user, 'save')) {
                                $user->save();
                            }
                            if (peakrackEmailGateUserHasVerifiedAt($userId)) {
                                peakrackEmailGateMarkClientEmailVerified($clientId);
                                return ['success' => true, 'method' => 'user_model_' . $method];
                            }
                        }
                    }

                    $user->email_verified_at = $now;
                    if (property_exists($user, 'email_verification_token') || isset($user->email_verification_token)) {
                        $user->email_verification_token = '';
                    }
                    if (property_exists($user, 'email_verification_token_expiry') || isset($user->email_verification_token_expiry)) {
                        $user->email_verification_token_expiry = null;
                    }
                    if (method_exists($user, 'save')) {
                        $user->save();
                    }
                    if (peakrackEmailGateUserHasVerifiedAt($userId)) {
                        peakrackEmailGateMarkClientEmailVerified($clientId);
                        return ['success' => true, 'method' => 'user_model_property'];
                    }
                }
            } catch (Throwable $e) {
                $modelMessage = $e->getMessage();
            }
        }

        try {
            $updatedUser = false;
            $schema = Capsule::schema();
            if ($userId > 0 && $schema->hasTable('tblusers') && $schema->hasColumn('tblusers', 'email_verified_at')) {
                $payload = ['email_verified_at' => $now];
                if ($schema->hasColumn('tblusers', 'email_verification_token')) {
                    $payload['email_verification_token'] = '';
                }
                if ($schema->hasColumn('tblusers', 'email_verification_token_expiry')) {
                    $payload['email_verification_token_expiry'] = null;
                }
                if ($schema->hasColumn('tblusers', 'updated_at')) {
                    $payload['updated_at'] = $now;
                }
                Capsule::table('tblusers')->where('id', $userId)->update($payload);
                $updatedUser = peakrackEmailGateUserHasVerifiedAt($userId);
            }

            $updatedClient = peakrackEmailGateMarkClientEmailVerified($clientId);
            if ($updatedUser || ($userId <= 0 && $updatedClient)) {
                return ['success' => true, 'method' => $updatedUser ? 'tblusers_fallback' : 'tblclients_legacy_fallback'];
            }
        } catch (Throwable $e) {
            return ['success' => false, 'method' => 'fallback_failed', 'message' => $e->getMessage()];
        }

        return ['success' => false, 'method' => 'unavailable', 'message' => $modelMessage !== '' ? $modelMessage : 'No supported WHMCS email verification field was available.'];
    }
}

if (!function_exists('peakrackEmailGateMarkClientEmailVerified')) {
    function peakrackEmailGateMarkClientEmailVerified(int $clientId): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        try {
            $schema = Capsule::schema();
            if ($schema->hasTable('tblclients') && $schema->hasColumn('tblclients', 'email_verified')) {
                $payload = [
                    'email_verified' => 1,
                ];
                if ($schema->hasColumn('tblclients', 'updated_at')) {
                    $payload['updated_at'] = peakrackEmailGateNow();
                }
                Capsule::table('tblclients')->where('id', $clientId)->update($payload);
                return true;
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }
}

if (!function_exists('peakrackEmailGateCleanupNativeVerification')) {
    function peakrackEmailGateCleanupNativeVerification(array $context, string $source = 'native_hook'): void
    {
        $record = peakrackEmailGateRecordForContext($context);
        if (!$record) {
            return;
        }

        Capsule::table(PREG_RECORDS_TABLE)
            ->where('id', (int) $record->id)
            ->update([
                'status' => 'native_verified',
                'token_hash' => null,
                'token_expires_at' => null,
                'code_hash' => null,
                'code_expires_at' => null,
                'failed_attempts' => 0,
                'locked_until' => null,
                'verified_at' => peakrackEmailGateNow(),
                'updated_at' => peakrackEmailGateNow(),
            ]);

        peakrackEmailGateLog('info', 'native_verified_cleanup', 'Native WHMCS email verification completed; module tokens were cleared.', $context, ['source' => $source]);
    }
}

if (!function_exists('peakrackEmailGateUnlockRecord')) {
    function peakrackEmailGateUnlockRecord(string $identifier): bool
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return false;
        }

        $query = Capsule::table(PREG_RECORDS_TABLE);
        if (ctype_digit($identifier)) {
            $query->where('user_id', (int) $identifier);
        } else {
            $query->where('email', $identifier);
        }

        $record = $query->first();
        if (!$record) {
            return false;
        }

        Capsule::table(PREG_RECORDS_TABLE)
            ->where('id', (int) $record->id)
            ->update([
                'failed_attempts' => 0,
                'locked_until' => null,
                'updated_at' => peakrackEmailGateNow(),
            ]);

        peakrackEmailGateLog('info', 'admin_unlock', 'Administrator unlocked an email gate record.', peakrackEmailGateContextFromRecord($record));
        return true;
    }
}

if (!function_exists('peakrackEmailGateCleanupExpired')) {
    function peakrackEmailGateCleanupExpired(): array
    {
        $now = peakrackEmailGateNow();
        $tokenCount = Capsule::table(PREG_RECORDS_TABLE)
            ->whereNotNull('token_hash')
            ->where('token_expires_at', '<', $now)
            ->update([
                'token_hash' => null,
                'token_expires_at' => null,
                'updated_at' => $now,
            ]);

        $codeCount = Capsule::table(PREG_RECORDS_TABLE)
            ->whereNotNull('code_hash')
            ->where('code_expires_at', '<', $now)
            ->update([
                'code_hash' => null,
                'code_expires_at' => null,
                'updated_at' => $now,
            ]);

        $unlockCount = Capsule::table(PREG_RECORDS_TABLE)
            ->whereNotNull('locked_until')
            ->where('locked_until', '<', $now)
            ->update([
                'failed_attempts' => 0,
                'locked_until' => null,
                'updated_at' => $now,
            ]);

        peakrackEmailGateLog('info', 'cleanup_expired', 'Expired email gate artifacts were cleaned.', [], [
            'tokens' => (int) $tokenCount,
            'codes' => (int) $codeCount,
            'locks' => (int) $unlockCount,
        ]);

        return ['tokens' => (int) $tokenCount, 'codes' => (int) $codeCount, 'locks' => (int) $unlockCount];
    }
}

if (!function_exists('peakrackEmailGateCleanupRetention')) {
    function peakrackEmailGateCleanupRetention(array $settings): void
    {
        $days = (int) ($settings['logRetentionDays'] ?? 0);
        if ($days > 0) {
            Capsule::table(PREG_LOGS_TABLE)
                ->where('created_at', '<', date('Y-m-d H:i:s', time() - ($days * 86400)))
                ->delete();
        }

        $maxLogs = (int) ($settings['maxLogs'] ?? 0);
        if ($maxLogs > 0) {
            $ids = Capsule::table(PREG_LOGS_TABLE)
                ->orderBy('id', 'desc')
                ->limit($maxLogs)
                ->pluck('id')
                ->all();
            if ($ids !== []) {
                Capsule::table(PREG_LOGS_TABLE)
                    ->whereNotIn('id', array_map('intval', $ids))
                    ->delete();
            }
        }
    }
}

if (!function_exists('peakrackEmailGateSendCustomEmail')) {
    function peakrackEmailGateSendCustomEmail(array $context, array $settings, string $token, string $code): array
    {
        if (!function_exists('localAPI')) {
            return ['success' => false, 'message' => 'localAPI unavailable'];
        }

        $clientId = (int) ($context['client_id'] ?? 0);
        if ($clientId <= 0) {
            return ['success' => false, 'message' => 'Client ID missing'];
        }

        $language = (string) ($context['language'] ?? 'en');
        $language = $language === 'zh' ? 'zh' : 'en';
        $subject = $language === 'zh' ? (string) $settings['emailSubjectZh'] : (string) $settings['emailSubjectEn'];
        $message = $language === 'zh' ? (string) $settings['emailMessageZh'] : (string) $settings['emailMessageEn'];
        $verificationLink = peakrackEmailGateAbsoluteUrl([
            'action' => 'verify',
            'token' => $token,
        ]);
        $clientName = trim((string) (($context['first_name'] ?? '') . ' ' . ($context['last_name'] ?? '')));
        if ($clientName === '') {
            $clientName = (string) ($context['email'] ?? '');
        }
        $companyName = peakrackEmailGateConfigValue('CompanyName') ?: 'PeakRack';
        $signature = peakrackEmailGateConfigValue('Signature');
        $customVars = [
            'verification_link' => $verificationLink,
            'verification_code' => $code,
            'code_minutes' => (int) $settings['codeLifetimeMinutes'],
            'token_minutes' => (int) $settings['tokenLifetimeMinutes'],
            'client_name' => $clientName,
            'client_email' => (string) ($context['email'] ?? ''),
            'company_name' => $companyName,
            'signature' => $signature,
        ];

        $message = peakrackEmailGateReplaceTemplateVars($message, $customVars);
        $subject = peakrackEmailGateReplaceTemplateVars($subject, $customVars);

        try {
            $result = localAPI('SendEmail', [
                'id' => $clientId,
                'customtype' => 'general',
                'customsubject' => $subject,
                'custommessage' => $message,
                'customvars' => base64_encode(serialize($customVars)),
            ]);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        if (is_array($result) && (string) ($result['result'] ?? '') === 'success') {
            return ['success' => true, 'message' => 'sent'];
        }

        return ['success' => false, 'message' => is_array($result) ? (string) ($result['message'] ?? json_encode($result)) : 'Unexpected SendEmail response'];
    }
}

if (!function_exists('peakrackEmailGateShouldGateRequest')) {
    function peakrackEmailGateShouldGateRequest(array $vars = []): bool
    {
        $settings = peakrackEmailGateLoadSettings();
        if (empty($settings['enabled']) || empty($settings['gateEnabled'])) {
            return false;
        }

        if (peakrackEmailGateAllowedRequest()) {
            return false;
        }

        $context = peakrackEmailGateCurrentContext($vars);
        if ((int) ($context['client_id'] ?? 0) <= 0 && (int) ($context['user_id'] ?? 0) <= 0) {
            return false;
        }

        return !peakrackEmailGateIsVerified($context);
    }
}

if (!function_exists('peakrackEmailGateAllowedRequest')) {
    function peakrackEmailGateAllowedRequest(): bool
    {
        if ((string) ($_GET['m'] ?? '') === PREG_MODULE) {
            return true;
        }

        $script = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '')));
        if (in_array($script, ['logout.php', 'pwreset.php', 'dologin.php'], true)) {
            return true;
        }

        $action = strtolower((string) ($_GET['action'] ?? $_GET['a'] ?? ''));
        if ($script === 'clientarea.php' && in_array($action, ['logout', 'details'], true) && $action === 'logout') {
            return true;
        }

        $rp = strtolower((string) ($_GET['rp'] ?? ''));
        if ($rp !== '') {
            if (str_contains($rp, 'password') || str_contains($rp, 'pwreset')) {
                return true;
            }
            if (str_contains($rp, 'verify') && str_contains($rp, 'email')) {
                return true;
            }
        }

        if (str_contains($action, 'verify') && str_contains($action, 'email')) {
            return true;
        }

        $requestPath = strtolower((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
        foreach (['/assets/', '/templates/', '/modules/addons/' . PREG_MODULE . '/assets/'] as $allowedPath) {
            if (str_contains($requestPath, $allowedPath)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('peakrackEmailGateIsVerified')) {
    function peakrackEmailGateIsVerified(array $context): bool
    {
        $userId = (int) ($context['user_id'] ?? 0);
        $clientId = (int) ($context['client_id'] ?? 0);

        if ($userId > 0) {
            try {
                $schema = Capsule::schema();
                if ($schema->hasTable('tblusers') && $schema->hasColumn('tblusers', 'email_verified_at')) {
                    return peakrackEmailGateUserHasVerifiedAt($userId);
                }
            } catch (Throwable) {
                return false;
            }
        }

        if ($clientId > 0) {
            try {
                $row = Capsule::table('tblclients')->where('id', $clientId)->first(['email_verified']);
                if ($row && peakrackEmailGateBool($row->email_verified ?? false)) {
                    return true;
                }
            } catch (Throwable) {
                return false;
            }
        }

        return false;
    }
}

if (!function_exists('peakrackEmailGateUserHasVerifiedAt')) {
    function peakrackEmailGateUserHasVerifiedAt(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        try {
            $schema = Capsule::schema();
            if (!$schema->hasTable('tblusers') || !$schema->hasColumn('tblusers', 'email_verified_at')) {
                return false;
            }
            $value = Capsule::table('tblusers')->where('id', $userId)->value('email_verified_at');
            $value = trim((string) $value);
            return $value !== '' && $value !== '0000-00-00 00:00:00';
        } catch (Throwable) {
            return false;
        }
    }
}

if (!function_exists('peakrackEmailGateCurrentContext')) {
    function peakrackEmailGateCurrentContext(array $vars = []): array
    {
        $context = [
            'user_id' => 0,
            'client_id' => 0,
            'email' => '',
            'first_name' => '',
            'last_name' => '',
            'language' => 'en',
        ];

        $context = array_replace($context, peakrackEmailGateContextFromCurrentUserObject());

        foreach (['auth_user_id', 'user_id', 'userid'] as $key) {
            if ((int) ($context['user_id'] ?? 0) <= 0 && isset($_SESSION[$key]) && $key !== 'userid') {
                $context['user_id'] = (int) $_SESSION[$key];
            }
        }

        if ((int) ($context['client_id'] ?? 0) <= 0) {
            $context['client_id'] = (int) ($_SESSION['uid'] ?? $vars['userid'] ?? $vars['clientid'] ?? 0);
        }

        if ((int) ($context['client_id'] ?? 0) <= 0 && isset($vars['clientsdetails']) && is_array($vars['clientsdetails'])) {
            $context['client_id'] = (int) ($vars['clientsdetails']['userid'] ?? $vars['clientsdetails']['id'] ?? 0);
        }

        if ((int) ($context['user_id'] ?? 0) <= 0 && isset($vars['user'])) {
            $user = $vars['user'];
            if (is_object($user) && isset($user->id)) {
                $context['user_id'] = (int) $user->id;
            } elseif (is_array($user) && isset($user['id'])) {
                $context['user_id'] = (int) $user['id'];
            }
        }

        if ((int) ($context['user_id'] ?? 0) <= 0 && (int) ($context['client_id'] ?? 0) > 0) {
            $context['user_id'] = peakrackEmailGateOwnerUserId((int) $context['client_id']);
        }

        $context = array_replace($context, peakrackEmailGateHydrateUserAndClient($context));
        $context['language'] = peakrackEmailGateNormalizeClientLanguage((string) ($context['language'] ?? ''), $vars);

        return $context;
    }
}

if (!function_exists('peakrackEmailGateContextFromCurrentUserObject')) {
    function peakrackEmailGateContextFromCurrentUserObject(): array
    {
        $context = [];
        if (!class_exists('\\WHMCS\\Authentication\\CurrentUser')) {
            return $context;
        }

        try {
            $currentUser = new \WHMCS\Authentication\CurrentUser();
            $user = null;
            foreach (['user', 'getUser'] as $method) {
                if (method_exists($currentUser, $method)) {
                    $user = $currentUser->{$method}();
                    break;
                }
            }
            if ($user) {
                $context['user_id'] = (int) ($user->id ?? 0);
                $context['email'] = (string) ($user->email ?? '');
                $context['first_name'] = (string) ($user->first_name ?? $user->firstname ?? '');
                $context['last_name'] = (string) ($user->last_name ?? $user->lastname ?? '');
                $context['language'] = (string) ($user->language ?? '');
            }
        } catch (Throwable) {
            return $context;
        }

        return $context;
    }
}

if (!function_exists('peakrackEmailGateHydrateUserAndClient')) {
    function peakrackEmailGateHydrateUserAndClient(array $context): array
    {
        $hydrated = [];
        $userId = (int) ($context['user_id'] ?? 0);
        $clientId = (int) ($context['client_id'] ?? 0);

        try {
            if ($userId > 0) {
                $user = Capsule::table('tblusers')->where('id', $userId)->first();
                if ($user) {
                    $hydrated['email'] = (string) ($user->email ?? ($context['email'] ?? ''));
                    $hydrated['first_name'] = (string) ($user->first_name ?? ($context['first_name'] ?? ''));
                    $hydrated['last_name'] = (string) ($user->last_name ?? ($context['last_name'] ?? ''));
                    $hydrated['language'] = (string) ($user->language ?? ($context['language'] ?? ''));
                }
            }

            if ($clientId > 0) {
                $client = Capsule::table('tblclients')->where('id', $clientId)->first();
                if ($client) {
                    $hydrated['client_id'] = $clientId;
                    $hydrated['email'] = (string) ($hydrated['email'] ?? $client->email ?? '');
                    $hydrated['first_name'] = (string) ($hydrated['first_name'] ?? $client->firstname ?? '');
                    $hydrated['last_name'] = (string) ($hydrated['last_name'] ?? $client->lastname ?? '');
                    $hydrated['language'] = (string) ($hydrated['language'] ?? $client->language ?? '');
                }
            }

            if ($clientId <= 0 && $userId > 0) {
                $schema = Capsule::schema();
                if ($schema->hasTable('tblusers_clients')) {
                    $query = Capsule::table('tblusers_clients');
                    if ($schema->hasColumn('tblusers_clients', 'auth_user_id')) {
                        $query->where('auth_user_id', $userId);
                    } elseif ($schema->hasColumn('tblusers_clients', 'user_id')) {
                        $query->where('user_id', $userId);
                    }
                    $rel = $query->first();
                    if ($rel) {
                        $hydrated['client_id'] = (int) ($rel->client_id ?? 0);
                    }
                }
            }
        } catch (Throwable) {
            return $hydrated;
        }

        return $hydrated;
    }
}

if (!function_exists('peakrackEmailGateOwnerUserId')) {
    function peakrackEmailGateOwnerUserId(int $clientId): int
    {
        if ($clientId <= 0) {
            return 0;
        }

        try {
            $schema = Capsule::schema();
            if (!$schema->hasTable('tblusers_clients')) {
                return 0;
            }

            $query = Capsule::table('tblusers_clients')->where('client_id', $clientId);
            if ($schema->hasColumn('tblusers_clients', 'owner')) {
                $query->where('owner', 1);
            }
            $row = $query->first();
            return (int) ($row->auth_user_id ?? $row->user_id ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }
}

if (!function_exists('peakrackEmailGateContextFromUserAdd')) {
    function peakrackEmailGateContextFromUserAdd(array $vars): array
    {
        $userId = (int) ($vars['userId'] ?? $vars['user_id'] ?? $vars['userid'] ?? $vars['id'] ?? 0);
        $clientId = (int) ($vars['clientId'] ?? $vars['client_id'] ?? $_SESSION['uid'] ?? 0);
        $context = [
            'user_id' => $userId,
            'client_id' => $clientId,
            'email' => (string) ($vars['email'] ?? ''),
            'first_name' => (string) ($vars['firstname'] ?? $vars['first_name'] ?? ''),
            'last_name' => (string) ($vars['lastname'] ?? $vars['last_name'] ?? ''),
            'language' => (string) ($vars['language'] ?? ''),
        ];

        return array_replace($context, peakrackEmailGateHydrateUserAndClient($context));
    }
}

if (!function_exists('peakrackEmailGateContextFromVerificationHook')) {
    function peakrackEmailGateContextFromVerificationHook(array $vars): array
    {
        $context = [
            'user_id' => (int) ($vars['userId'] ?? $vars['user_id'] ?? $vars['userid'] ?? 0),
            'client_id' => (int) ($vars['clientId'] ?? $vars['client_id'] ?? $vars['clientid'] ?? 0),
            'email' => (string) ($vars['email'] ?? ''),
            'first_name' => '',
            'last_name' => '',
            'language' => '',
        ];

        return array_replace($context, peakrackEmailGateHydrateUserAndClient($context));
    }
}

if (!function_exists('peakrackEmailGateRecordForContext')) {
    function peakrackEmailGateRecordForContext(array $context): ?object
    {
        peakrackEmailGateCreateTables();
        $userId = (int) ($context['user_id'] ?? 0);
        $email = trim((string) ($context['email'] ?? ''));

        if ($userId > 0) {
            $record = Capsule::table(PREG_RECORDS_TABLE)->where('user_id', $userId)->first();
            if ($record) {
                return $record;
            }
        }

        if ($email !== '') {
            $record = Capsule::table(PREG_RECORDS_TABLE)->where('email', $email)->orderBy('id', 'desc')->first();
            if ($record) {
                return $record;
            }
        }

        return null;
    }
}

if (!function_exists('peakrackEmailGateContextFromRecord')) {
    function peakrackEmailGateContextFromRecord(object $record): array
    {
        $context = [
            'user_id' => (int) ($record->user_id ?? 0),
            'client_id' => (int) ($record->client_id ?? 0),
            'email' => (string) ($record->email ?? ''),
            'first_name' => '',
            'last_name' => '',
            'language' => '',
        ];

        return array_replace($context, peakrackEmailGateHydrateUserAndClient($context));
    }
}

if (!function_exists('peakrackEmailGateRecordStatus')) {
    function peakrackEmailGateRecordStatus(array $context): array
    {
        $record = peakrackEmailGateRecordForContext($context);
        if (!$record) {
            return [
                'exists' => false,
                'last_sent_at' => '',
                'resend_total' => 0,
                'failed_attempts' => 0,
                'locked_until' => '',
                'cooldown_wait' => 0,
                'has_active_code' => false,
            ];
        }

        $settings = peakrackEmailGateLoadSettings();
        $lastSent = peakrackEmailGateTimestamp($record->last_sent_at ?? null);
        $cooldownWait = 0;
        if ($lastSent > 0) {
            $cooldownWait = max(0, ((int) $settings['cooldownSeconds']) - (time() - $lastSent));
        }

        return [
            'exists' => true,
            'last_sent_at' => (string) ($record->last_sent_at ?? ''),
            'resend_total' => (int) ($record->resend_total ?? 0),
            'failed_attempts' => (int) ($record->failed_attempts ?? 0),
            'locked_until' => (string) ($record->locked_until ?? ''),
            'cooldown_wait' => $cooldownWait,
            'has_active_code' => trim((string) ($record->code_hash ?? '')) !== ''
                && peakrackEmailGateTimestamp($record->code_expires_at ?? null) >= time(),
        ];
    }
}

if (!function_exists('peakrackEmailGateLog')) {
    function peakrackEmailGateLog(string $level, string $event, string $message, array $context = [], array $extra = []): void
    {
        try {
            peakrackEmailGateCreateTables();
            Capsule::table(PREG_LOGS_TABLE)->insert([
                'user_id' => ((int) ($context['user_id'] ?? 0)) ?: null,
                'client_id' => ((int) ($context['client_id'] ?? 0)) ?: null,
                'email' => trim((string) ($context['email'] ?? '')) ?: null,
                'level' => substr($level, 0, 20),
                'event' => substr($event, 0, 80),
                'message' => substr($message, 0, 255),
                'context' => $extra === [] ? null : peakrackEmailGateJsonEncode($extra),
                'ip' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
                'created_at' => peakrackEmailGateNow(),
            ]);
        } catch (Throwable) {
            // Logging must not break login, checkout, or verification flows.
        }

        try {
            $settings = peakrackEmailGateLoadSettings();
            if (!empty($settings['activityLog']) && function_exists('logActivity')) {
                $clientId = (int) ($context['client_id'] ?? 0);
                logActivity('PeakRack Email Gate: ' . $message . ' [' . $event . ']', $clientId > 0 ? $clientId : 0);
            }
        } catch (Throwable) {
        }
    }
}

if (!function_exists('peakrackEmailGateAbsoluteUrl')) {
    function peakrackEmailGateAbsoluteUrl(array $params = []): string
    {
        $systemUrl = rtrim(peakrackEmailGateSystemUrl(), '/');
        return $systemUrl . '/' . peakrackEmailGateModuleUrl($params);
    }
}

if (!function_exists('peakrackEmailGateModuleUrl')) {
    function peakrackEmailGateModuleUrl(array $params = []): string
    {
        return 'index.php?' . http_build_query(array_merge(['m' => PREG_MODULE], $params));
    }
}

if (!function_exists('peakrackEmailGateRedirectToGate')) {
    function peakrackEmailGateRedirectToGate(string $returnUrl = ''): void
    {
        $returnUrl = peakrackEmailGateRememberReturnUrl($returnUrl);
        $params = $returnUrl !== '' ? ['return' => $returnUrl] : [];
        header('Location: ' . peakrackEmailGateModuleUrl($params));
    }
}

if (!function_exists('peakrackEmailGateCurrentRelativeUrl')) {
    function peakrackEmailGateCurrentRelativeUrl(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($uri !== '') {
            return $uri;
        }

        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? 'clientarea.php');
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        return $query !== '' ? $script . '?' . $query : $script;
    }
}

if (!function_exists('peakrackEmailGateSanitizeReturnUrl')) {
    function peakrackEmailGateSanitizeReturnUrl(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return '';
        }

        if (str_starts_with($url, '//')) {
            return '';
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return '';
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            if ((string) ($query['m'] ?? '') === PREG_MODULE) {
                return '';
            }
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' && isset($parts['query'])) {
            $path = 'index.php';
        }
        if ($path === '') {
            return '';
        }

        $basename = strtolower(basename($path));
        if (in_array($basename, ['logout.php', 'dologin.php'], true)) {
            return '';
        }

        return $url;
    }
}

if (!function_exists('peakrackEmailGateRememberReturnUrl')) {
    function peakrackEmailGateRememberReturnUrl(string $candidate = ''): string
    {
        $candidate = peakrackEmailGateSanitizeReturnUrl($candidate);
        if ($candidate !== '') {
            $_SESSION['peakrack_email_gate_return_url'] = $candidate;
            return $candidate;
        }

        $stored = peakrackEmailGateSanitizeReturnUrl((string) ($_SESSION['peakrack_email_gate_return_url'] ?? ''));
        return $stored !== '' ? $stored : 'clientarea.php';
    }
}

if (!function_exists('peakrackEmailGateClearReturnUrl')) {
    function peakrackEmailGateClearReturnUrl(): void
    {
        unset($_SESSION['peakrack_email_gate_return_url']);
    }
}

if (!function_exists('peakrackEmailGateSystemUrl')) {
    function peakrackEmailGateSystemUrl(): string
    {
        $configured = peakrackEmailGateConfigValue('SystemURL');
        if ($configured !== '') {
            return $configured;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }
}

if (!function_exists('peakrackEmailGateConfigValue')) {
    function peakrackEmailGateConfigValue(string $setting): string
    {
        try {
            return (string) Capsule::table('tblconfiguration')->where('setting', $setting)->value('value');
        } catch (Throwable) {
            return '';
        }
    }
}

if (!function_exists('peakrackEmailGateReplaceTemplateVars')) {
    function peakrackEmailGateReplaceTemplateVars(string $template, array $vars): string
    {
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{$' . $key . '}'] = (string) $value;
            $replace['{' . $key . '}'] = (string) $value;
        }

        return strtr($template, $replace);
    }
}

if (!function_exists('peakrackEmailGateNormalizeEditableText')) {
    function peakrackEmailGateNormalizeEditableText(string $value): string
    {
        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $value) {
                break;
            }
            $value = $decoded;
        }

        return str_replace(["\r\n", "\r"], "\n", $value);
    }
}

if (!function_exists('peakrackEmailGateHmac')) {
    function peakrackEmailGateHmac(string $value, array $settings): string
    {
        $secret = (string) ($settings['hmacSecret'] ?? '');
        if ($secret === '') {
            $secret = peakrackEmailGateConfigValue('CC_ENCRYPTION_HASH') ?: peakrackEmailGateConfigValue('cc_encryption_hash') ?: PREG_MODULE;
        }

        return hash_hmac('sha256', $value, $secret);
    }
}

if (!function_exists('peakrackEmailGateRandomToken')) {
    function peakrackEmailGateRandomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}

if (!function_exists('peakrackEmailGateRandomHex')) {
    function peakrackEmailGateRandomHex(int $bytes): string
    {
        return bin2hex(random_bytes($bytes));
    }
}

if (!function_exists('peakrackEmailGateNormalizeClientLanguage')) {
    function peakrackEmailGateNormalizeClientLanguage(string $language, array $vars = []): string
    {
        $candidate = strtolower($language);
        if ($candidate === '') {
            $candidate = strtolower((string) ($_SESSION['Language'] ?? $vars['language'] ?? ''));
        }

        return str_contains($candidate, 'chinese') || str_contains($candidate, 'zh') ? 'zh' : 'en';
    }
}

if (!function_exists('peakrackEmailGateBool')) {
    function peakrackEmailGateBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on', 'enabled'], true);
    }
}

if (!function_exists('peakrackEmailGateClampInt')) {
    function peakrackEmailGateClampInt(mixed $value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}

if (!function_exists('peakrackEmailGateTimestamp')) {
    function peakrackEmailGateTimestamp(mixed $value): int
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return 0;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }
}

if (!function_exists('peakrackEmailGateNow')) {
    function peakrackEmailGateNow(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('peakrackEmailGateJsonEncode')) {
    function peakrackEmailGateJsonEncode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

if (!function_exists('peakrackEmailGateJsonDecode')) {
    function peakrackEmailGateJsonDecode(string $value, array $default = []): array
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $default;
    }
}

if (!function_exists('peakrackEmailGateE')) {
    function peakrackEmailGateE(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
