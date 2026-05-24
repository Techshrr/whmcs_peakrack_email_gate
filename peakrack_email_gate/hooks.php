<?php

/**
 * Hooks for PeakRack Email Verification Gate.
 */

if (!defined('WHMCS')) {
    die('No direct access');
}

require_once __DIR__ . '/lib/Bootstrap.php';

add_hook('UserAdd', 1, static function (array $vars): void {
    try {
        peakrackEmailGateCreateTables();
        peakrackEmailGateInitializeRecord(peakrackEmailGateContextFromUserAdd($vars), 'user_add');
    } catch (Throwable $e) {
        peakrackEmailGateLog('error', 'user_add_failed', 'UserAdd initialization failed.', [], ['error' => $e->getMessage()]);
    }
});

add_hook('UserEmailVerificationComplete', 1, static function (array $vars): void {
    try {
        peakrackEmailGateCreateTables();
        peakrackEmailGateCleanupNativeVerification(peakrackEmailGateContextFromVerificationHook($vars), 'UserEmailVerificationComplete');
    } catch (Throwable $e) {
        peakrackEmailGateLog('error', 'native_cleanup_failed', 'Native email verification cleanup failed.', [], ['error' => $e->getMessage()]);
    }
});

add_hook('ClientAreaPage', 1, static function (array $vars): array {
    try {
        if (!peakrackEmailGateShouldGateRequest($vars)) {
            return [];
        }

        $context = peakrackEmailGateCurrentContext($vars);
        peakrackEmailGateLog('info', 'gate_redirect', 'Unverified user was redirected to the email verification gate.', $context, [
            'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        ]);

        if (!headers_sent()) {
            $returnUrl = peakrackEmailGateSanitizeReturnUrl(peakrackEmailGateCurrentRelativeUrl());
            $params = $returnUrl !== '' ? ['return' => $returnUrl] : [];
            header('Location: ' . peakrackEmailGateModuleUrl($params));
            exit;
        }
    } catch (Throwable $e) {
        peakrackEmailGateLog('error', 'gate_redirect_failed', 'Email gate redirect check failed.', [], ['error' => $e->getMessage()]);
    }

    return [];
});

add_hook('ShoppingCartValidateCheckout', 1, static function (array $vars): array {
    try {
        $settings = peakrackEmailGateLoadSettings();
        if (empty($settings['enabled']) || empty($settings['checkoutBlock'])) {
            return [];
        }

        $context = peakrackEmailGateCurrentContext($vars);
        if (((int) ($context['client_id'] ?? 0) > 0 || (int) ($context['user_id'] ?? 0) > 0) && !peakrackEmailGateIsVerified($context)) {
            peakrackEmailGateLog('warning', 'checkout_blocked', 'Checkout was blocked because email is not verified.', $context);
            $language = peakrackEmailGateNormalizeClientLanguage((string) ($context['language'] ?? ''), $vars);
            return [$language === 'zh' ? '请先完成邮箱验证后再下单。' : 'Please verify your email address before placing an order.'];
        }
    } catch (Throwable $e) {
        peakrackEmailGateLog('error', 'checkout_check_failed', 'Checkout email verification check failed.', [], ['error' => $e->getMessage()]);
    }

    return [];
});

add_hook('DailyCronJob', 1, static function (): void {
    try {
        $settings = peakrackEmailGateLoadSettings();
        peakrackEmailGateCleanupExpired();
        peakrackEmailGateCleanupRetention($settings);
    } catch (Throwable $e) {
        peakrackEmailGateLog('error', 'cron_cleanup_failed', 'Daily cleanup failed.', [], ['error' => $e->getMessage()]);
    }
});
