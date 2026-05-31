<?php
// SPDX-License-Identifier: Apache-2.0

/**
 * PeakRack Email Verification Gate
 *
 * Official repository:
 * https://github.com/Techshrr/whmcs_peakrack_email_gate
 *
 * Copyright 2026 PeakRack.
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files for details.
 */

if (!defined('WHMCS')) {
    die('No direct access');
}

require_once __DIR__ . '/lib/Bootstrap.php';

function peakrack_email_gate_config(): array
{
    return [
        'name' => 'PeakRack Email Verification Gate',
        'description' => 'Client-area gate and custom resend verification flow for WHMCS native email verification.',
        'version' => PREG_VERSION,
        'author' => 'PeakRack',
        'language' => 'english',
        'fields' => [],
    ];
}

function peakrack_email_gate_activate(): array
{
    try {
        peakrackEmailGateCreateTables();
        $settings = peakrackEmailGateLoadSettings();
        if ((string) ($settings['hmacSecret'] ?? '') === '') {
            $settings['hmacSecret'] = peakrackEmailGateRandomHex(32);
        }
        peakrackEmailGateSaveSettings($settings);

        return [
            'status' => 'success',
            'description' => 'PeakRack Email Verification Gate has been activated.',
        ];
    } catch (Throwable $e) {
        return [
            'status' => 'error',
            'description' => 'Activation failed: ' . $e->getMessage(),
        ];
    }
}

function peakrack_email_gate_install(): array
{
    return peakrack_email_gate_activate();
}

function peakrack_email_gate_deactivate(): array
{
    return [
        'status' => 'success',
        'description' => 'PeakRack Email Verification Gate has been deactivated. Settings, verification records, and logs were kept.',
    ];
}

function peakrack_email_gate_uninstall(array $vars = []): array
{
    try {
        $confirmed = (string) ($_POST['preg_confirm_delete_logs'] ?? '') === 'DELETE'
            && (string) ($_POST['preg_delete_logs'] ?? '') === '1';

        if ($confirmed) {
            $schema = WHMCS\Database\Capsule::schema();
            foreach ([PREG_RECORDS_TABLE, PREG_SETTINGS_TABLE, PREG_LOGS_TABLE] as $table) {
                if ($schema->hasTable($table)) {
                    $schema->drop($table);
                }
            }

            return [
                'status' => 'success',
                'description' => 'PeakRack Email Verification Gate was uninstalled and log data was deleted after confirmation.',
            ];
        }

        return [
            'status' => 'success',
            'description' => 'PeakRack Email Verification Gate uninstall completed. Module data and logs were kept because log deletion was not explicitly confirmed.',
        ];
    } catch (Throwable $e) {
        return [
            'status' => 'error',
            'description' => 'Uninstall failed: ' . $e->getMessage(),
        ];
    }
}

function peakrack_email_gate_upgrade($vars): void
{
    peakrackEmailGateCreateTables();
    $settings = peakrackEmailGateLoadSettings();
    peakrackEmailGateSaveSettings($settings);
}

function peakrack_email_gate_output(array $vars): void
{
    $message = '';
    $messageType = 'success';

    try {
        peakrackEmailGateCreateTables();
        $settings = peakrackEmailGateLoadSettings();
    } catch (Throwable $e) {
        $settings = peakrackEmailGateDefaults();
        $message = 'Schema check failed: ' . $e->getMessage();
        $messageType = 'danger';
    }

    $language = peakrack_email_gate_admin_language($settings);
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $language = in_array((string) ($_POST['adminLanguage'] ?? $language), ['en', 'zh'], true)
            ? (string) ($_POST['adminLanguage'] ?? $language)
            : $language;

        if (!peakrack_email_gate_verify_admin_token()) {
            $message = peakrack_email_gate_admin_text($language, 'token_failed');
            $messageType = 'danger';
        } else {
            $action = (string) ($_POST['preg_action'] ?? '');
            if ($action === 'save_settings') {
                $settings = peakrack_email_gate_settings_from_post($settings);
                peakrackEmailGateSaveSettings($settings);
                $settings = peakrackEmailGateLoadSettings();
                $language = peakrack_email_gate_admin_language($settings);
                $message = peakrack_email_gate_admin_text($language, 'saved');
            } elseif ($action === 'rotate_secret') {
                $settings['hmacSecret'] = peakrackEmailGateRandomHex(32);
                peakrackEmailGateSaveSettings($settings);
                WHMCS\Database\Capsule::table(PREG_RECORDS_TABLE)->update([
                    'token_hash' => null,
                    'token_expires_at' => null,
                    'code_hash' => null,
                    'code_expires_at' => null,
                    'updated_at' => peakrackEmailGateNow(),
                ]);
                peakrackEmailGateLog('warning', 'admin_rotate_secret', 'Administrator rotated the HMAC secret; outstanding custom links and codes were invalidated.');
                $message = peakrack_email_gate_admin_text($language, 'secret_rotated');
            } elseif ($action === 'unlock_user') {
                $ok = peakrackEmailGateUnlockRecord((string) ($_POST['unlock_identifier'] ?? ''));
                $message = $ok ? peakrack_email_gate_admin_text($language, 'unlocked') : peakrack_email_gate_admin_text($language, 'unlock_not_found');
                $messageType = $ok ? 'success' : 'warning';
            } elseif ($action === 'cleanup_expired') {
                $result = peakrackEmailGateCleanupExpired();
                $message = sprintf(
                    peakrack_email_gate_admin_text($language, 'cleanup_done'),
                    $result['tokens'],
                    $result['codes'],
                    $result['locks']
                );
            } elseif ($action === 'cleanup_logs') {
                peakrackEmailGateCleanupRetention($settings);
                $message = peakrack_email_gate_admin_text($language, 'logs_cleaned');
            }
        }
    }

    echo peakrack_email_gate_render_admin($settings, $message, $messageType, $language);
}

function peakrack_email_gate_clientarea(array $vars): array
{
    peakrackEmailGateCreateTables();
    $settings = peakrackEmailGateLoadSettings();
    $context = peakrackEmailGateCurrentContext($vars);
    $language = peakrackEmailGateNormalizeClientLanguage((string) ($context['language'] ?? ''), $vars);
    $returnUrl = peakrackEmailGateRememberReturnUrl((string) ($_GET['return'] ?? $_POST['return_url'] ?? ''));
    $message = '';
    $messageType = 'info';

    if ((string) ($_GET['action'] ?? '') === 'verify') {
        $result = peakrackEmailGateVerifyToken((string) ($_GET['token'] ?? ''), $settings);
        if (!empty($result['success'])) {
            peakrackEmailGateClearReturnUrl();
            if (!headers_sent()) {
                header('Location: ' . $returnUrl);
                exit;
            }
        }
        $message = peakrack_email_gate_client_text($language, $result['message']);
        $messageType = $result['success'] ? 'success' : 'danger';
        $context = peakrackEmailGateCurrentContext($vars);
    }

    $loggedIn = (int) ($context['client_id'] ?? 0) > 0 || (int) ($context['user_id'] ?? 0) > 0;

    if ($loggedIn && !peakrackEmailGateIsVerified($context)) {
        peakrackEmailGateInitializeRecord($context, 'client_page');
    }

    if ($loggedIn && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string) ($_POST['preg_client_action'] ?? '') === 'verify_code_ajax') {
        if (!peakrack_email_gate_verify_client_token()) {
            peakrack_email_gate_json_response([
                'success' => false,
                'message' => peakrack_email_gate_client_text($language, 'token_failed'),
            ]);
        }

        $result = peakrackEmailGateVerifyCode($context, (string) ($_POST['verification_code'] ?? ''), $settings);
        $success = !empty($result['success']);
        $redirectUrl = $success ? peakrackEmailGateRememberReturnUrl((string) ($_POST['return_url'] ?? '')) : '';
        if ($success) {
            peakrackEmailGateClearReturnUrl();
        }

        peakrack_email_gate_json_response([
            'success' => $success,
            'message' => peakrack_email_gate_client_result_message($language, $result),
            'redirect' => $success ? $redirectUrl : '',
            'locked' => in_array((string) ($result['message'] ?? ''), ['locked', 'locked_now'], true),
            'lock_wait' => in_array((string) ($result['message'] ?? ''), ['locked', 'locked_now'], true)
                ? max(0, (int) ($result['wait'] ?? 0))
                : 0,
        ]);
    }

    if ($loggedIn && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!peakrack_email_gate_verify_client_token()) {
            $message = peakrack_email_gate_client_text($language, 'token_failed');
            $messageType = 'danger';
        } else {
            $action = (string) ($_POST['preg_client_action'] ?? '');
            if ($action === 'resend') {
                $result = peakrackEmailGateResendCustomVerification($context, $settings);
                $message = peakrack_email_gate_client_result_message($language, $result);
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'verify_code') {
                $result = peakrackEmailGateVerifyCode($context, (string) ($_POST['verification_code'] ?? ''), $settings);
                if (!empty($result['success'])) {
                    peakrackEmailGateClearReturnUrl();
                    if (!headers_sent()) {
                        header('Location: ' . $returnUrl);
                        exit;
                    }
                }
                $message = peakrack_email_gate_client_result_message($language, $result);
                $messageType = $result['success'] ? 'success' : 'danger';
            }
        }
    } elseif (!$loggedIn && $message === '') {
        $message = peakrack_email_gate_client_text($language, 'login_required');
        $messageType = 'warning';
    }

    $verified = $loggedIn ? peakrackEmailGateIsVerified($context) : false;
    $recordStatus = $loggedIn ? peakrackEmailGateRecordStatus($context) : [
        'exists' => false,
        'last_sent_at' => '',
        'resend_total' => 0,
        'failed_attempts' => 0,
        'locked_until' => '',
        'is_locked' => false,
        'lock_wait' => 0,
        'cooldown_wait' => 0,
        'has_active_code' => false,
    ];
    $texts = peakrack_email_gate_client_texts($language);
    $recordStatus['locked_message'] = '';
    if (!empty($recordStatus['is_locked'])) {
        $recordStatus['locked_message'] = sprintf(
            $texts['locked'],
            max(1, (int) ceil(((int) ($recordStatus['lock_wait'] ?? 0)) / 60))
        );
    }

    return [
        'pagetitle' => $texts['title'],
        'breadcrumb' => [
            peakrackEmailGateModuleUrl() => $texts['title'],
        ],
        'templatefile' => 'templates/clientarea',
        'requirelogin' => false,
        'forcessl' => true,
        'vars' => [
            'prgate' => [
                'modulelink' => $vars['modulelink'] ?? peakrackEmailGateModuleUrl(),
                'token' => peakrack_email_gate_client_token_value(),
                'message' => $message,
                'messageType' => $messageType,
                'verified' => $verified,
                'loggedIn' => $loggedIn,
                'email' => (string) ($context['email'] ?? ''),
                'clientareaUrl' => 'clientarea.php',
                'loginUrl' => 'clientarea.php',
                'logoutUrl' => 'logout.php',
                'returnUrl' => $returnUrl,
                'settings' => [
                    'notice' => $language === 'zh-hk'
                        ? peakrackEmailGateTraditionalize((string) $settings['clientNoticeZh'])
                        : ($language === 'zh' ? $settings['clientNoticeZh'] : $settings['clientNoticeEn']),
                    'resendButtonColor' => (string) $settings['resendButtonColor'],
                    'cooldownSeconds' => (int) $settings['cooldownSeconds'],
                    'codeLifetimeMinutes' => (int) $settings['codeLifetimeMinutes'],
                    'tokenLifetimeMinutes' => (int) $settings['tokenLifetimeMinutes'],
                    'maxFailedAttempts' => (int) $settings['maxFailedAttempts'],
                    'lockMinutes' => (int) $settings['lockMinutes'],
                ],
                'record' => $recordStatus,
                'text' => $texts,
            ],
        ],
    ];
}

function peakrack_email_gate_json_response(array $payload): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function peakrack_email_gate_settings_from_post(array $current): array
{
    $settings = $current;
    foreach (['enabled', 'gateEnabled', 'checkoutBlock', 'activityLog'] as $key) {
        $settings[$key] = isset($_POST[$key]) && (string) $_POST[$key] === '1';
    }

    $settings['adminLanguage'] = in_array((string) ($_POST['adminLanguage'] ?? 'en'), ['en', 'zh'], true)
        ? (string) $_POST['adminLanguage']
        : 'en';

    foreach (['cooldownSeconds', 'hourlyLimit', 'codeLifetimeMinutes', 'tokenLifetimeMinutes', 'maxFailedAttempts', 'lockMinutes', 'logRetentionDays', 'maxLogs'] as $key) {
        $settings[$key] = (int) ($_POST[$key] ?? $settings[$key] ?? 0);
    }

    $settings['resendButtonColor'] = peakrackEmailGateNormalizeHexColor((string) ($_POST['resendButtonColor'] ?? $settings['resendButtonColor'] ?? '#2563eb'));

    foreach (['emailSubjectEn', 'emailSubjectZh', 'emailMessageEn', 'emailMessageZh', 'clientNoticeEn', 'clientNoticeZh'] as $key) {
        $settings[$key] = trim((string) ($_POST[$key] ?? $settings[$key] ?? ''));
    }

    return peakrackEmailGateMergeSettings(peakrackEmailGateDefaults(), $settings);
}

function peakrack_email_gate_github_icon(): string
{
    return '<svg aria-hidden="true" viewBox="0 0 16 16" width="14" height="14" focusable="false" style="display:inline-block;vertical-align:-2px;fill:currentColor;flex:0 0 auto"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82A7.64 7.64 0 0 1 8 3.86c.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/></svg>';
}

function peakrack_email_gate_github_admin_html(): string
{
    return '<a class="preg-github-link" href="https://github.com/Techshrr/whmcs_peakrack_email_gate" target="_blank" rel="noopener noreferrer" title="GitHub repository">' . peakrack_email_gate_github_icon() . '<span>GitHub</span></a>'
        . '<a class="preg-update-badge" href="https://github.com/Techshrr/whmcs_peakrack_email_gate/releases" target="_blank" rel="noopener noreferrer" data-prk-github-update data-prk-github-repo="Techshrr/whmcs_peakrack_email_gate" data-prk-github-current="' . peakrackEmailGateE(PREG_VERSION) . '" data-prk-github-label="New version {version}" style="display:none"></a>'
        . '<script>(function(){if(window.PeakRackGithubUpdateCheck){window.PeakRackGithubUpdateCheck();return;}window.PeakRackGithubUpdateCheck=function(){var nodes=document.querySelectorAll("[data-prk-github-update]");if(!nodes.length||!window.fetch){return;}function normalize(v){return String(v||"").replace(/^v/i,"").replace(/[^0-9A-Za-z.\\-+]/g,"");}function compare(a,b){var aa=normalize(a).split(/[.\\-+]/),bb=normalize(b).split(/[.\\-+]/),len=Math.max(aa.length,bb.length);for(var i=0;i<len;i++){var av=aa[i]||"",bv=bb[i]||"";if(av===""&&bv!==""){return 1;}if(av!==""&&bv===""){return -1;}var an=/^\\d+$/.test(av),bn=/^\\d+$/.test(bv);if(an&&bn){var ai=parseInt(av,10),bi=parseInt(bv,10);if(ai!==bi){return ai>bi?1:-1;}}else if(av!==bv){return av>bv?1:-1;}}return 0;}function readCache(repo){try{var raw=localStorage.getItem("peakrack.github.update."+repo);if(!raw){return null;}var data=JSON.parse(raw);if(!data||!data.checkedAt||Date.now()-data.checkedAt>43200000){return null;}return data;}catch(e){return null;}}function writeCache(repo,data){try{data.checkedAt=Date.now();localStorage.setItem("peakrack.github.update."+repo,JSON.stringify(data));}catch(e){}}function fetchJson(url){var controller=window.AbortController?new AbortController():null;var timer=controller?window.setTimeout(function(){controller.abort();},2000):null;return fetch(url,{headers:{Accept:"application/vnd.github+json"},signal:controller?controller.signal:undefined}).then(function(resp){if(timer){window.clearTimeout(timer);}if(!resp.ok){throw new Error("http");}return resp.json();}).catch(function(err){if(timer){window.clearTimeout(timer);}throw err;});}function latest(repo){var base="https://api.github.com/repos/"+repo;return fetchJson(base+"/releases/latest").then(function(data){return{version:data.tag_name||"",url:data.html_url||("https://github.com/"+repo+"/releases")};}).catch(function(){return fetchJson(base+"/tags?per_page=1").then(function(tags){var tag=tags&&tags[0]?tags[0].name:"";return{version:tag,url:tag?("https://github.com/"+repo+"/releases/tag/"+encodeURIComponent(tag)):("https://github.com/"+repo+"/releases")};});});}function apply(node,info){var current=node.getAttribute("data-prk-github-current")||"";if(info&&info.version&&compare(info.version,current)>0){node.href=info.url||node.href;node.textContent=(node.getAttribute("data-prk-github-label")||"New version {version}").replace("{version}",info.version);node.style.display="inline-flex";}}Array.prototype.forEach.call(nodes,function(node){var repo=node.getAttribute("data-prk-github-repo")||"";if(!repo){return;}var cached=readCache(repo);if(cached){apply(node,cached);return;}latest(repo).then(function(info){writeCache(repo,info);apply(node,info);}).catch(function(){});});};window.PeakRackGithubUpdateCheck();})();</script>';
}

function peakrack_email_gate_render_admin(array $settings, string $message, string $messageType, string $language): string
{
    $t = peakrack_email_gate_admin_texts($language);
    $logs = [];
    $records = [];
    try {
        $logs = WHMCS\Database\Capsule::table(PREG_LOGS_TABLE)->orderBy('id', 'desc')->limit(100)->get()->all();
        $records = WHMCS\Database\Capsule::table(PREG_RECORDS_TABLE)->orderBy('updated_at', 'desc')->limit(25)->get()->all();
    } catch (Throwable) {
    }

    ob_start();
    ?>
    <style>
        .preg-admin { max-width: 1180px; margin: 0; color: #263238; }
        .preg-admin * { box-sizing: border-box; }
        .preg-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; background: #0f172a; color: #fff; border-radius: 6px; padding: 22px 24px; margin: 0 0 18px; }
        .preg-hero-main { flex: 1 1 auto; min-width: 0; max-width: 100%; }
        .preg-hero h2 { margin: 0 0 8px; color: #fff; font-size: 22px; font-weight: 600; }
        .preg-hero p { margin: 0; color: #cbd5e1; line-height: 1.6; font-size: 14px; }
        .preg-hero-actions { flex: 0 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; max-width: 360px; }
        .preg-version-badge { display: inline-flex; align-items: center; justify-content: center; min-height: 26px; border-radius: 999px; padding: 3px 10px; background: rgba(37,99,235,.18); color: #bfdbfe; border: 1px solid rgba(191,219,254,.35); font-size: 12px; font-weight: 700; white-space: nowrap; }
        .preg-github-link, .preg-update-badge { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 26px; border-radius: 999px; padding: 3px 10px; background: rgba(255,255,255,.08); color: #e5edf8; border: 1px solid rgba(203,213,225,.45); font-size: 12px; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .preg-github-link:hover, .preg-update-badge:hover { color: #fff; background: rgba(255,255,255,.14); text-decoration: none; }
        .preg-update-badge { background: rgba(245,158,11,.16); color: #fde68a; border-color: rgba(253,230,138,.45); }
        .preg-lang { display: grid; grid-template-columns: 1fr 1fr; width: 132px; height: 38px; border: 1px solid rgba(203,213,225,.45); border-radius: 6px; overflow: hidden; background: rgba(255,255,255,.06); }
        .preg-lang a { display: inline-flex; align-items: center; justify-content: center; min-width: 0; height: 38px; padding: 0 8px; color: #cbd5e1; text-decoration: none; font-size: 12px; font-weight: 700; line-height: 1; white-space: nowrap; }
        .preg-lang a.active { background: #2563eb; color: #fff; }
        .preg-template-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .preg-template-panel { border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; background: #fff; }
        .preg-template-panel h3 { margin: 0 0 12px; font-size: 15px; font-weight: 700; }
        @media (max-width: 900px) { .preg-hero { display: block; padding: 22px 20px; } .preg-hero-actions { justify-content: flex-start; max-width: none; margin-top: 14px; } .preg-template-grid { grid-template-columns: 1fr; } }
    </style>
    <div class="preg-admin">
        <div class="preg-hero">
            <div class="preg-hero-main">
                <h2><?php echo peakrackEmailGateE($t['title']); ?></h2>
                <p><?php echo peakrackEmailGateE($t['subtitle']); ?></p>
            </div>
            <div class="preg-hero-actions">
                <span class="preg-version-badge"><?php echo peakrackEmailGateE(sprintf($t['version'], PREG_VERSION)); ?></span>
                <?php echo peakrack_email_gate_github_admin_html(); ?>
                <div class="preg-lang" aria-label="Admin language">
                    <a class="<?php echo $language === 'zh' ? 'active' : ''; ?>" href="<?php echo peakrackEmailGateE(peakrack_email_gate_admin_url('zh')); ?>">中文</a>
                    <a class="<?php echo $language === 'en' ? 'active' : ''; ?>" href="<?php echo peakrackEmailGateE(peakrack_email_gate_admin_url('en')); ?>">English</a>
                </div>
            </div>
        </div>

        <?php if ($message !== '') { ?>
            <div class="alert alert-<?php echo peakrackEmailGateE($messageType); ?>"><?php echo peakrackEmailGateE($message); ?></div>
        <?php } ?>

        <ul class="nav nav-tabs" role="tablist">
            <li class="active"><a href="#preg-settings" data-toggle="tab"><?php echo peakrackEmailGateE($t['settings']); ?></a></li>
            <li><a href="#preg-records" data-toggle="tab"><?php echo peakrackEmailGateE($t['records']); ?></a></li>
            <li><a href="#preg-logs" data-toggle="tab"><?php echo peakrackEmailGateE($t['logs']); ?></a></li>
            <li><a href="#preg-tools" data-toggle="tab"><?php echo peakrackEmailGateE($t['tools']); ?></a></li>
        </ul>

        <div class="tab-content" style="padding-top: 20px;">
            <div class="tab-pane active" id="preg-settings">
                <form method="post" action="addonmodules.php?module=<?php echo PREG_MODULE; ?>">
                    <?php echo peakrack_email_gate_admin_token_field(); ?>
                    <input type="hidden" name="preg_action" value="save_settings">
                    <input type="hidden" name="adminLanguage" value="<?php echo peakrackEmailGateE($language); ?>">
                    <div class="row">
                        <?php echo peakrack_email_gate_admin_checkbox('enabled', $t['enabled'], (bool) $settings['enabled']); ?>
                        <?php echo peakrack_email_gate_admin_checkbox('gateEnabled', $t['gate_enabled'], (bool) $settings['gateEnabled']); ?>
                        <?php echo peakrack_email_gate_admin_checkbox('checkoutBlock', $t['checkout_block'], (bool) $settings['checkoutBlock']); ?>
                        <?php echo peakrack_email_gate_admin_checkbox('activityLog', $t['activity_log'], (bool) $settings['activityLog']); ?>
                    </div>
                    <div class="row">
                        <?php echo peakrack_email_gate_admin_number('cooldownSeconds', $t['cooldown'], (int) $settings['cooldownSeconds']); ?>
                        <?php echo peakrack_email_gate_admin_number('hourlyLimit', $t['hourly_limit'], (int) $settings['hourlyLimit']); ?>
                        <?php echo peakrack_email_gate_admin_number('codeLifetimeMinutes', $t['code_lifetime'], (int) $settings['codeLifetimeMinutes']); ?>
                        <?php echo peakrack_email_gate_admin_number('tokenLifetimeMinutes', $t['token_lifetime'], (int) $settings['tokenLifetimeMinutes']); ?>
                    </div>
                    <div class="row">
                        <?php echo peakrack_email_gate_admin_number('maxFailedAttempts', $t['max_failed'], (int) $settings['maxFailedAttempts']); ?>
                        <?php echo peakrack_email_gate_admin_number('lockMinutes', $t['lock_minutes'], (int) $settings['lockMinutes']); ?>
                        <?php echo peakrack_email_gate_admin_number('logRetentionDays', $t['log_retention'], (int) $settings['logRetentionDays']); ?>
                        <?php echo peakrack_email_gate_admin_number('maxLogs', $t['max_logs'], (int) $settings['maxLogs']); ?>
                    </div>
                    <div class="row">
                        <?php echo peakrack_email_gate_admin_color_input('resendButtonColor', $t['resend_button_color'], (string) $settings['resendButtonColor']); ?>
                    </div>
                    <div class="preg-template-grid">
                        <div class="preg-template-panel">
                            <h3><?php echo peakrackEmailGateE($t['english_template']); ?></h3>
                            <?php echo peakrack_email_gate_admin_text_input('emailSubjectEn', $t['subject_en'], (string) $settings['emailSubjectEn']); ?>
                            <?php echo peakrack_email_gate_admin_textarea('emailMessageEn', $t['message_en'], (string) $settings['emailMessageEn'], 12); ?>
                            <?php echo peakrack_email_gate_admin_textarea('clientNoticeEn', $t['notice_en'], (string) $settings['clientNoticeEn'], 3); ?>
                        </div>
                        <div class="preg-template-panel">
                            <h3><?php echo peakrackEmailGateE($t['chinese_template']); ?></h3>
                            <?php echo peakrack_email_gate_admin_text_input('emailSubjectZh', $t['subject_zh'], (string) $settings['emailSubjectZh']); ?>
                            <?php echo peakrack_email_gate_admin_textarea('emailMessageZh', $t['message_zh'], (string) $settings['emailMessageZh'], 12); ?>
                            <?php echo peakrack_email_gate_admin_textarea('clientNoticeZh', $t['notice_zh'], (string) $settings['clientNoticeZh'], 3); ?>
                        </div>
                    </div>
                    <p class="help-block"><?php echo peakrackEmailGateE($t['placeholders']); ?></p>
                    <button type="submit" class="btn btn-primary"><?php echo peakrackEmailGateE($t['save']); ?></button>
                </form>
            </div>

            <div class="tab-pane" id="preg-records">
                <?php echo peakrack_email_gate_render_records($records, $t); ?>
            </div>

            <div class="tab-pane" id="preg-logs">
                <?php echo peakrack_email_gate_render_logs($logs, $t); ?>
            </div>

            <div class="tab-pane" id="preg-tools">
                <div class="panel panel-default">
                    <div class="panel-heading"><?php echo peakrackEmailGateE($t['unlock_user']); ?></div>
                    <div class="panel-body">
                        <form method="post" class="form-inline">
                            <?php echo peakrack_email_gate_admin_token_field(); ?>
                            <input type="hidden" name="preg_action" value="unlock_user">
                            <input type="text" class="form-control" name="unlock_identifier" placeholder="<?php echo peakrackEmailGateE($t['unlock_placeholder']); ?>">
                            <button type="submit" class="btn btn-default"><?php echo peakrackEmailGateE($t['unlock']); ?></button>
                        </form>
                    </div>
                </div>
                <form method="post" style="display:inline-block; margin-right: 10px;">
                    <?php echo peakrack_email_gate_admin_token_field(); ?>
                    <input type="hidden" name="preg_action" value="cleanup_expired">
                    <button type="submit" class="btn btn-default"><?php echo peakrackEmailGateE($t['cleanup_expired']); ?></button>
                </form>
                <form method="post" style="display:inline-block; margin-right: 10px;">
                    <?php echo peakrack_email_gate_admin_token_field(); ?>
                    <input type="hidden" name="preg_action" value="cleanup_logs">
                    <button type="submit" class="btn btn-default"><?php echo peakrackEmailGateE($t['cleanup_logs']); ?></button>
                </form>
                <form method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo peakrackEmailGateE($t['rotate_confirm']); ?>');">
                    <?php echo peakrack_email_gate_admin_token_field(); ?>
                    <input type="hidden" name="preg_action" value="rotate_secret">
                    <button type="submit" class="btn btn-danger"><?php echo peakrackEmailGateE($t['rotate_secret']); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_email_gate_render_records(array $records, array $t): string
{
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-condensed">
            <thead>
            <tr>
                <th>ID</th><th><?php echo peakrackEmailGateE($t['user']); ?></th><th><?php echo peakrackEmailGateE($t['client']); ?></th><th><?php echo peakrackEmailGateE($t['email']); ?></th><th><?php echo peakrackEmailGateE($t['status']); ?></th><th><?php echo peakrackEmailGateE($t['last_sent']); ?></th><th><?php echo peakrackEmailGateE($t['failed']); ?></th><th><?php echo peakrackEmailGateE($t['locked_until']); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $record) { ?>
                <tr>
                    <td><?php echo (int) $record->id; ?></td>
                    <td><?php echo (int) ($record->user_id ?? 0); ?></td>
                    <td><?php echo (int) ($record->client_id ?? 0); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($record->email ?? '')); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($record->status ?? '')); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($record->last_sent_at ?? '')); ?></td>
                    <td><?php echo (int) ($record->failed_attempts ?? 0); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($record->locked_until ?? '')); ?></td>
                </tr>
            <?php } ?>
            <?php if ($records === []) { ?>
                <tr><td colspan="8" class="text-muted"><?php echo peakrackEmailGateE($t['no_records']); ?></td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_email_gate_render_logs(array $logs, array $t): string
{
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-condensed">
            <thead>
            <tr>
                <th>ID</th><th><?php echo peakrackEmailGateE($t['time']); ?></th><th><?php echo peakrackEmailGateE($t['level']); ?></th><th><?php echo peakrackEmailGateE($t['event']); ?></th><th><?php echo peakrackEmailGateE($t['user']); ?></th><th><?php echo peakrackEmailGateE($t['client']); ?></th><th><?php echo peakrackEmailGateE($t['message']); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log) { ?>
                <tr>
                    <td><?php echo (int) $log->id; ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($log->created_at ?? '')); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($log->level ?? '')); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($log->event ?? '')); ?></td>
                    <td><?php echo (int) ($log->user_id ?? 0); ?></td>
                    <td><?php echo (int) ($log->client_id ?? 0); ?></td>
                    <td><?php echo peakrackEmailGateE((string) ($log->message ?? '')); ?></td>
                </tr>
            <?php } ?>
            <?php if ($logs === []) { ?>
                <tr><td colspan="7" class="text-muted"><?php echo peakrackEmailGateE($t['no_logs']); ?></td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_email_gate_admin_checkbox(string $name, string $label, bool $checked): string
{
    return '<div class="col-sm-3"><div class="checkbox"><label><input type="checkbox" name="' . peakrackEmailGateE($name) . '" value="1" ' . ($checked ? 'checked' : '') . '> ' . peakrackEmailGateE($label) . '</label></div></div>';
}

function peakrack_email_gate_admin_number(string $name, string $label, int $value): string
{
    return '<div class="col-sm-3 form-group"><label>' . peakrackEmailGateE($label) . '</label><input type="number" class="form-control" name="' . peakrackEmailGateE($name) . '" value="' . $value . '"></div>';
}

function peakrack_email_gate_admin_text_input(string $name, string $label, string $value): string
{
    return '<div class="form-group"><label>' . peakrackEmailGateE($label) . '</label><input type="text" class="form-control" name="' . peakrackEmailGateE($name) . '" value="' . peakrackEmailGateE($value) . '"></div>';
}

function peakrack_email_gate_admin_color_input(string $name, string $label, string $value): string
{
    $value = peakrackEmailGateNormalizeHexColor($value);
    return '<div class="col-sm-3 form-group"><label>' . peakrackEmailGateE($label) . '</label><input type="color" class="form-control" style="height:34px;padding:3px;" name="' . peakrackEmailGateE($name) . '" value="' . peakrackEmailGateE($value) . '"></div>';
}

function peakrack_email_gate_admin_textarea(string $name, string $label, string $value, int $rows): string
{
    return '<div class="form-group"><label>' . peakrackEmailGateE($label) . '</label><textarea class="form-control" rows="' . $rows . '" name="' . peakrackEmailGateE($name) . '">' . peakrackEmailGateE($value) . '</textarea></div>';
}

function peakrack_email_gate_admin_language(array $settings = []): string
{
    $requested = peakrack_email_gate_normalize_admin_language($_GET['preg_admin_lang'] ?? '');
    if ($requested !== '') {
        $_SESSION['peakrack_email_gate_admin_lang'] = $requested;
        if (!headers_sent()) {
            setcookie('peakrack_email_gate_admin_lang', $requested, time() + 31536000, '', '', false, true);
        }

        return $requested;
    }

    $sessionLanguage = peakrack_email_gate_normalize_admin_language($_SESSION['peakrack_email_gate_admin_lang'] ?? '');
    if ($sessionLanguage !== '') {
        return $sessionLanguage;
    }

    $cookieLanguage = peakrack_email_gate_normalize_admin_language($_COOKIE['peakrack_email_gate_admin_lang'] ?? '');
    if ($cookieLanguage !== '') {
        return $cookieLanguage;
    }

    return peakrack_email_gate_normalize_admin_language($settings['adminLanguage'] ?? '') ?: 'en';
}

function peakrack_email_gate_normalize_admin_language($language): string
{
    return in_array((string) $language, ['en', 'zh'], true) ? (string) $language : '';
}

function peakrack_email_gate_admin_url(string $language): string
{
    return 'addonmodules.php?' . http_build_query([
        'module' => PREG_MODULE,
        'preg_admin_lang' => peakrack_email_gate_normalize_admin_language($language) ?: 'en',
    ]);
}

function peakrack_email_gate_admin_text(string $language, string $key): string
{
    $texts = peakrack_email_gate_admin_texts($language);
    return $texts[$key] ?? $key;
}

function peakrack_email_gate_admin_texts(string $language): array
{
    $texts = [
        'en' => [
            'title' => 'PeakRack Email Verification Gate',
            'subtitle' => 'Keeps WHMCS native email verification enabled while guiding unverified clients through a focused verification page. Custom resend emails include a secure link, a 6-digit code, cooldown controls, lockout protection, and automatic return to the client page they originally requested.',
            'version' => 'Version %s',
            'settings' => 'Settings',
            'records' => 'Records',
            'logs' => 'Logs',
            'tools' => 'Tools',
            'enabled' => 'Enable module',
            'gate_enabled' => 'Force unverified users to gate page',
            'checkout_block' => 'Redirect unverified checkout to verification',
            'activity_log' => 'Mirror key events to WHMCS Activity Log',
            'cooldown' => 'Resend cooldown seconds',
            'hourly_limit' => 'Max resends per hour',
            'code_lifetime' => 'Code lifetime minutes',
            'token_lifetime' => 'Link lifetime minutes',
            'max_failed' => 'Max failed code attempts',
            'lock_minutes' => 'Lock minutes',
            'log_retention' => 'Log retention days',
            'max_logs' => 'Maximum log rows',
            'resend_button_color' => 'Get Code button color',
            'admin_language' => 'Admin language',
            'english_template' => 'English Email & Notice',
            'chinese_template' => 'Chinese Email & Notice',
            'subject_en' => 'English email subject',
            'message_en' => 'English HTML email message',
            'subject_zh' => 'Chinese email subject',
            'message_zh' => 'Chinese HTML email message',
            'notice_en' => 'English gate notice',
            'notice_zh' => 'Chinese gate notice',
            'placeholders' => 'Supported placeholders: {$verification_link}, {$verification_code}, {$code_minutes}, {$token_minutes}, {$client_name}, {$client_email}, {$company_name}, {$signature}. customvars are also sent to WHMCS Local API SendEmail.',
            'save' => 'Save Settings',
            'saved' => 'Settings saved.',
            'token_failed' => 'Security token validation failed. Refresh the page and try again.',
            'secret_rotated' => 'HMAC secret rotated. Outstanding custom links and codes were invalidated.',
            'unlock_user' => 'Unlock User',
            'unlock_placeholder' => 'User ID or email address',
            'unlock' => 'Unlock',
            'unlocked' => 'User record unlocked.',
            'unlock_not_found' => 'No matching locked record was found.',
            'cleanup_expired' => 'Clean Expired Tokens',
            'cleanup_logs' => 'Apply Log Retention',
            'cleanup_done' => 'Cleanup complete. Tokens: %d, codes: %d, locks: %d.',
            'logs_cleaned' => 'Log retention cleanup complete.',
            'rotate_secret' => 'Rotate HMAC Secret',
            'rotate_confirm' => 'Rotating the HMAC secret invalidates all outstanding custom links and codes. Continue?',
            'time' => 'Time',
            'level' => 'Level',
            'event' => 'Event',
            'user' => 'User',
            'client' => 'Client',
            'email' => 'Email',
            'message' => 'Message',
            'status' => 'Status',
            'last_sent' => 'Last Sent',
            'failed' => 'Failed',
            'locked_until' => 'Locked Until',
            'no_logs' => 'No logs recorded yet.',
            'no_records' => 'No records recorded yet.',
        ],
        'zh' => [
            'title' => 'PeakRack Email Verification Gate',
            'subtitle' => '保持 WHMCS 原生邮箱验证开启，同时将未验证客户引导到独立验证页。自定义重发邮件包含安全链接和 6 位验证码，并提供冷却倒计时、错误锁定、防刷限制以及验证成功后返回原访问页面。',
            'version' => '版本 %s',
            'settings' => '设置',
            'records' => '记录',
            'logs' => '日志',
            'tools' => '工具',
            'enabled' => '启用模块',
            'gate_enabled' => '强制未验证用户进入验证页',
            'checkout_block' => '未验证结账时跳转到验证页',
            'activity_log' => '关键事件同步到 WHMCS Activity Log',
            'cooldown' => '重发冷却秒数',
            'hourly_limit' => '每小时最多重发次数',
            'code_lifetime' => '验证码有效分钟数',
            'token_lifetime' => '链接有效分钟数',
            'max_failed' => '验证码最大错误次数',
            'lock_minutes' => '锁定分钟数',
            'log_retention' => '日志保留天数',
            'max_logs' => '最大日志行数',
            'resend_button_color' => '获取验证码按钮颜色',
            'admin_language' => '后台语言',
            'english_template' => '英文邮件与提示',
            'chinese_template' => '中文邮件与提示',
            'subject_en' => '英文邮件标题',
            'message_en' => '英文 HTML 邮件正文',
            'subject_zh' => '中文邮件标题',
            'message_zh' => '中文 HTML 邮件正文',
            'notice_en' => '英文验证页提示',
            'notice_zh' => '中文验证页提示',
            'placeholders' => '支持变量：{$verification_link}, {$verification_code}, {$code_minutes}, {$token_minutes}, {$client_name}, {$client_email}, {$company_name}, {$signature}。同时会通过 WHMCS Local API SendEmail 传入 customvars。',
            'save' => '保存设置',
            'saved' => '设置已保存。',
            'token_failed' => '安全令牌验证失败，请刷新页面后重试。',
            'secret_rotated' => 'HMAC 密钥已轮换，未使用的自定义链接和验证码已失效。',
            'unlock_user' => '解锁用户',
            'unlock_placeholder' => '用户 ID 或邮箱地址',
            'unlock' => '解锁',
            'unlocked' => '用户记录已解锁。',
            'unlock_not_found' => '未找到匹配的锁定记录。',
            'cleanup_expired' => '清理过期 token',
            'cleanup_logs' => '执行日志保留清理',
            'cleanup_done' => '清理完成。链接：%d，验证码：%d，锁定：%d。',
            'logs_cleaned' => '日志保留清理完成。',
            'rotate_secret' => '轮换 HMAC 密钥',
            'rotate_confirm' => '轮换 HMAC 密钥会让所有未使用的自定义链接和验证码失效，是否继续？',
            'time' => '时间',
            'level' => '级别',
            'event' => '事件',
            'user' => '用户',
            'client' => '客户',
            'email' => '邮箱',
            'message' => '消息',
            'status' => '状态',
            'last_sent' => '上次发送',
            'failed' => '错误次数',
            'locked_until' => '锁定至',
            'no_logs' => '暂无日志。',
            'no_records' => '暂无记录。',
        ],
    ];

    if ($language === 'zh-hk') {
        foreach ($texts['zh'] as $key => $value) {
            $texts['zh-hk'][$key] = peakrackEmailGateTraditionalize($value);
        }
    }

    return $texts[$language] ?? $texts['en'];
}

function peakrack_email_gate_client_result_message(string $language, array $result): string
{
    if (($result['message'] ?? '') === 'cooldown') {
        return sprintf(peakrack_email_gate_client_text($language, 'cooldown'), (int) ($result['wait'] ?? 0));
    }
    if (in_array((string) ($result['message'] ?? ''), ['locked', 'locked_now'], true)) {
        return sprintf(
            peakrack_email_gate_client_text($language, (string) ($result['message'] ?? 'locked')),
            max(1, (int) ceil(((int) ($result['wait'] ?? 0)) / 60))
        );
    }

    return peakrack_email_gate_client_text($language, (string) ($result['message'] ?? 'unknown'));
}

function peakrack_email_gate_client_text(string $language, string $key): string
{
    $texts = peakrack_email_gate_client_texts($language);
    return $texts[$key] ?? $key;
}

function peakrack_email_gate_client_texts(string $language): array
{
    $texts = [
        'en' => [
            'title' => 'Email Verification',
            'status_title' => 'Email Verification Required',
            'verified_title' => 'Email Verified',
            'verified_notice' => 'Your email address is verified. You can continue to the client area.',
            'email_label' => 'Email address',
            'code_label' => 'Security code',
            'get_code' => 'Get Code',
            'resend' => 'Get Code',
            'verify_code' => 'Verify Automatically',
            'verifying' => 'Checking code...',
            'continue' => 'Continue to Client Area',
            'logout' => 'Log Out',
            'login' => 'Log In',
            'last_sent' => 'Last custom email sent',
            'failed_attempts' => 'Failed code attempts',
            'locked_until' => 'Locked until',
            'not_sent' => 'No custom verification email has been sent yet.',
            'login_required' => 'Please log in before requesting or entering a verification code. If you opened a verification link, it can be completed directly when the link is valid.',
            'token_failed' => 'Security token validation failed. Refresh the page and try again.',
            'sent' => 'A verification email was sent. It contains a secure link and a code.',
            'send_failed' => 'The verification email could not be sent. Please contact support.',
            'cooldown' => 'Please wait %d seconds before requesting another verification email.',
            'hourly_limit' => 'You have reached the hourly resend limit. Please try again later.',
            'verified' => 'Your email address has been verified.',
            'already_verified' => 'Your email address is already verified.',
            'invalid_token' => 'The verification link is invalid.',
            'token_expired' => 'The verification link has expired. Please request a new email.',
            'invalid_code' => 'Enter the verification code.',
            'code_check_failed' => 'The code is incorrect or expired. Please check it and try again, or request a new code.',
            'code_missing' => 'No active code was found. Please request a new code.',
            'code_expired' => 'The code is incorrect or expired. Please request a new code.',
            'code_failed' => 'The code is incorrect or expired. Please check it and try again, or request a new code.',
            'locked_now' => 'Too many incorrect attempts. Verification has been temporarily locked. Please try again in about %d minutes.',
            'locked' => 'Too many incorrect attempts. Verification is temporarily locked. Please try again in about %d minutes.',
            'sync_failed' => 'Verification succeeded, but WHMCS email status could not be updated. Please contact support.',
            'record_missing' => 'Verification record could not be prepared. Please contact support.',
            'module_disabled' => 'Email verification gate is disabled.',
            'unknown' => 'The request could not be completed.',
        ],
        'zh' => [
            'title' => '邮箱验证',
            'status_title' => '需要验证邮箱',
            'verified_title' => '邮箱已验证',
            'verified_notice' => '你的邮箱地址已验证，可以继续进入客户中心。',
            'email_label' => '邮箱地址',
            'code_label' => '安全验证码',
            'get_code' => '获取验证码',
            'resend' => '获取验证码',
            'verify_code' => '自动验证',
            'verifying' => '正在核对验证码...',
            'continue' => '进入客户中心',
            'logout' => '退出登录',
            'login' => '登录',
            'last_sent' => '上次发送自定义邮件',
            'failed_attempts' => '验证码错误次数',
            'locked_until' => '锁定至',
            'not_sent' => '尚未发送自定义验证邮件。',
            'login_required' => '请先登录后再请求或输入验证码。如果你打开的是有效验证链接，可以直接完成验证。',
            'token_failed' => '安全令牌验证失败，请刷新页面后重试。',
            'sent' => '验证邮件已发送，邮件中包含安全链接和验证码。',
            'send_failed' => '验证邮件发送失败，请联系支持。',
            'cooldown' => '请等待 %d 秒后再重新发送验证邮件。',
            'hourly_limit' => '你已达到每小时重发上限，请稍后再试。',
            'verified' => '你的邮箱地址已验证。',
            'already_verified' => '你的邮箱地址已经验证。',
            'invalid_token' => '验证链接无效。',
            'token_expired' => '验证链接已过期，请重新发送验证邮件。',
            'invalid_code' => '请输入验证码。',
            'code_check_failed' => '验证码不正确或已过期，请核对后重试，或重新获取验证码。',
            'code_missing' => '没有可用的验证码，请重新获取验证码。',
            'code_expired' => '验证码不正确或已过期，请重新获取验证码。',
            'code_failed' => '验证码不正确或已过期，请核对后重试，或重新获取验证码。',
            'locked_now' => '输入错误次数过多，验证已临时锁定，请约 %d 分钟后再试。',
            'locked' => '输入错误次数过多，验证已临时锁定，请约 %d 分钟后再试。',
            'sync_failed' => '验证已通过，但无法同步 WHMCS 邮箱验证状态，请联系支持。',
            'record_missing' => '无法创建验证记录，请联系支持。',
            'module_disabled' => '邮箱验证拦截模块已禁用。',
            'unknown' => '请求无法完成。',
        ],
    ];

    if ($language === 'zh-hk') {
        $traditionalTexts = [];
        foreach ($texts['zh'] as $key => $value) {
            $traditionalTexts[$key] = peakrackEmailGateTraditionalize($value);
        }

        return $traditionalTexts;
    }

    return $texts[$language] ?? $texts['en'];
}

function peakrack_email_gate_verify_admin_token(): bool
{
    if (function_exists('check_token')) {
        return (bool) check_token('WHMCS.admin.default');
    }

    return true;
}

function peakrack_email_gate_verify_client_token(): bool
{
    if (function_exists('check_token')) {
        return (bool) check_token('WHMCS.default');
    }

    return true;
}

function peakrack_email_gate_admin_token_field(): string
{
    if (function_exists('generate_token')) {
        return '<input type="hidden" name="token" value="' . peakrackEmailGateE((string) generate_token('plain')) . '">';
    }

    return '';
}

function peakrack_email_gate_client_token_value(): string
{
    return function_exists('generate_token') ? (string) generate_token('plain') : '';
}
