{if $prgate.message}
    <div class="alert alert-{$prgate.messageType|escape}">
        {$prgate.message|escape}
    </div>
{/if}

<style>
    .preg-email-gate .preg-summary {
        display: grid;
        grid-template-columns: minmax(72px, max-content) minmax(0, 1fr);
        column-gap: 16px;
        row-gap: 8px;
        align-items: baseline;
        margin: 0 0 18px;
    }
    .preg-email-gate .preg-summary dt,
    .preg-email-gate .preg-summary dd,
    .preg-email-gate .preg-summary .col-sm-3,
    .preg-email-gate .preg-summary .col-sm-9 {
        float: none;
        width: auto;
        padding-left: 0;
        padding-right: 0;
        margin-bottom: 0;
    }
    .preg-email-gate .preg-summary dt {
        white-space: nowrap;
        word-break: keep-all;
    }
    .preg-email-gate .preg-code-box { max-width: 430px; }
    .preg-email-gate .preg-code-inputs { display: flex; gap: 10px; margin: 8px 0 0; }
    .preg-email-gate .preg-code-digit {
        width: 56px;
        height: 56px;
        text-align: center;
        font-size: 24px;
        font-weight: 600;
        line-height: 1;
        padding: 0;
    }
    .preg-email-gate .preg-code-digit:disabled {
        background: #f1f5f9 !important;
        border-color: #d0d5dd !important;
        color: #98a2b3 !important;
        cursor: not-allowed;
        opacity: 1;
        box-shadow: none !important;
    }
    .preg-email-gate .preg-actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 14px; }
    .preg-email-gate .preg-actions form { margin: 0; }
    .preg-email-gate .preg-resend-button { min-width: 158px; text-align: center; white-space: nowrap; }
    .preg-email-gate .preg-resend-button:not(:disabled),
    .preg-email-gate .preg-resend-button:not(:disabled):hover,
    .preg-email-gate .preg-resend-button:not(:disabled):focus {
        background: var(--preg-resend-color, #2563eb);
        border-color: var(--preg-resend-color, #2563eb);
        color: #fff;
        box-shadow: none;
    }
    .preg-email-gate .preg-resend-button:disabled {
        background: #f3f4f6 !important;
        border-color: #f3f4f6 !important;
        color: #98a2b3 !important;
        opacity: 1;
    }
    .preg-email-gate .preg-logout-link,
    .preg-email-gate .preg-logout-link:hover,
    .preg-email-gate .preg-logout-link:focus,
    .preg-email-gate .preg-logout-link:active {
        color: #2563eb !important;
        background: transparent !important;
        border-color: transparent !important;
        text-decoration: none;
        box-shadow: none !important;
    }
    .preg-email-gate .preg-code-status { margin-top: 12px; margin-bottom: 0; }
    @media (max-width: 640px) {
        .preg-email-gate .preg-summary { grid-template-columns: minmax(68px, max-content) minmax(0, 1fr); }
        .preg-email-gate .preg-code-box { max-width: 100%; }
        .preg-email-gate .preg-code-inputs { gap: 6px; }
        .preg-email-gate .preg-code-digit { width: 44px; height: 50px; font-size: 20px; }
    }
</style>

{if $prgate.verified}
    <div class="panel panel-default card mb-3 preg-email-gate">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prgate.text.verified_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <p>{$prgate.text.verified_notice|escape}</p>
            <a href="{$prgate.clientareaUrl|escape}" class="btn btn-primary">{$prgate.text.continue|escape}</a>
        </div>
    </div>
{else}
    <div class="panel panel-default card mb-3 preg-email-gate" style="--preg-resend-color: {$prgate.settings.resendButtonColor|escape};">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prgate.text.status_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <p>{$prgate.settings.notice|escape}</p>
            <dl class="row preg-summary">
                <dt class="col-sm-3">{$prgate.text.email_label|escape}</dt>
                <dd class="col-sm-9">{$prgate.email|escape}</dd>
            </dl>

            {if $prgate.loggedIn}
                <div class="preg-code-box">
                    <form id="preg-code-form" method="post" action="{$prgate.modulelink|escape}" data-verifying-message="{$prgate.text.verifying|escape}" data-error-message="{$prgate.text.code_check_failed|escape}" data-default-redirect="{$prgate.clientareaUrl|escape}" data-code-locked="{if $prgate.record.is_locked}1{else}0{/if}" novalidate>
                        <input type="hidden" name="token" value="{$prgate.token|escape}">
                        <input type="hidden" name="preg_client_action" value="verify_code_ajax">
                        <input type="hidden" name="return_url" value="{$prgate.returnUrl|escape}">
                        <input type="hidden" id="preg-verification-code" name="verification_code" value="">
                        <div class="form-group">
                            <label for="preg-code-1">{$prgate.text.code_label|escape}</label>
                            <div class="preg-code-inputs" dir="ltr">
                                <input type="text" class="form-control preg-code-digit" id="preg-code-1" inputmode="numeric" autocomplete="one-time-code" maxlength="1" aria-label="1" {if not $prgate.record.has_active_code or $prgate.record.is_locked}disabled{/if}>
                                <input type="text" class="form-control preg-code-digit" inputmode="numeric" maxlength="1" aria-label="2" {if not $prgate.record.has_active_code or $prgate.record.is_locked}disabled{/if}>
                                <input type="text" class="form-control preg-code-digit" inputmode="numeric" maxlength="1" aria-label="3" {if not $prgate.record.has_active_code or $prgate.record.is_locked}disabled{/if}>
                                <input type="text" class="form-control preg-code-digit" inputmode="numeric" maxlength="1" aria-label="4" {if not $prgate.record.has_active_code or $prgate.record.is_locked}disabled{/if}>
                                <input type="text" class="form-control preg-code-digit" inputmode="numeric" maxlength="1" aria-label="5" {if not $prgate.record.has_active_code or $prgate.record.is_locked}disabled{/if}>
                                <input type="text" class="form-control preg-code-digit" inputmode="numeric" maxlength="1" aria-label="6" {if not $prgate.record.has_active_code or $prgate.record.is_locked}disabled{/if}>
                            </div>
                        </div>
                        <div id="preg-code-status" class="alert preg-code-status" style="display:none;"></div>
                    </form>

                    <div class="preg-actions">
                        <form method="post" action="{$prgate.modulelink|escape}">
                            <input type="hidden" name="token" value="{$prgate.token|escape}">
                            <input type="hidden" name="return_url" value="{$prgate.returnUrl|escape}">
                            <input type="hidden" name="preg_client_action" value="resend">
                            <button type="submit" id="preg-resend-button" class="btn preg-resend-button" data-cooldown="{$prgate.record.cooldown_wait|intval}" data-cooldown-label="{$prgate.text.get_code|escape}" data-ready-label="{$prgate.text.get_code|escape}" {if $prgate.record.cooldown_wait > 0}disabled{/if}>{if $prgate.record.cooldown_wait > 0}{$prgate.record.cooldown_wait|intval} {$prgate.text.get_code|escape}{else}{$prgate.text.get_code|escape}{/if}</button>
                        </form>
                        <a href="{$prgate.logoutUrl|escape}" class="btn btn-link preg-logout-link">{$prgate.text.logout|escape}</a>
                    </div>
                </div>
                {literal}
                <script>
                (function () {
                    var form = document.getElementById('preg-code-form');
                    if (!form) {
                        return;
                    }

                    var inputs = Array.prototype.slice.call(form.querySelectorAll('.preg-code-digit'));
                    var hidden = document.getElementById('preg-verification-code');
                    var statusBox = document.getElementById('preg-code-status');
                    var resendButton = document.getElementById('preg-resend-button');
                    var verifying = false;
                    var codeLocked = form.getAttribute('data-code-locked') === '1';

                    initResendCooldown();

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        submitCode();
                    });

                    function codeValue() {
                        return inputs.map(function (input) {
                            return input.value;
                        }).join('');
                    }

                    function setStatus(type, message) {
                        if (!statusBox) {
                            return;
                        }
                        statusBox.className = 'alert preg-code-status alert-' + type;
                        statusBox.textContent = message;
                        statusBox.style.display = message ? 'block' : 'none';
                    }

                    function setDisabled(disabled) {
                        inputs.forEach(function (input) {
                            input.disabled = disabled || codeLocked;
                        });
                    }

                    function initResendCooldown() {
                        if (!resendButton) {
                            return;
                        }

                        var remaining = parseInt(resendButton.getAttribute('data-cooldown') || '0', 10);
                        if (!remaining || remaining <= 0) {
                            return;
                        }

                        var readyLabel = resendButton.getAttribute('data-ready-label') || resendButton.textContent;
                        var cooldownLabel = resendButton.getAttribute('data-cooldown-label') || readyLabel;
                        resendButton.disabled = true;
                        resendButton.textContent = String(remaining) + ' ' + cooldownLabel;

                        var timer = window.setInterval(function () {
                            remaining -= 1;
                            if (remaining <= 0) {
                                window.clearInterval(timer);
                                resendButton.disabled = false;
                                resendButton.textContent = readyLabel;
                                resendButton.setAttribute('data-cooldown', '0');
                                return;
                            }

                            resendButton.textContent = String(remaining) + ' ' + cooldownLabel;
                            resendButton.setAttribute('data-cooldown', String(remaining));
                        }, 1000);
                    }

                    function submitCode() {
                        var code = codeValue();
                        if (code.length !== 6 || verifying) {
                            return;
                        }

                        verifying = true;
                        hidden.value = code;
                        setDisabled(true);
                        setStatus('info', form.getAttribute('data-verifying-message') || '');

                        var body = new URLSearchParams(new FormData(form)).toString();
                        fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'Accept': 'application/json'
                            },
                            body: body
                        }).then(function (response) {
                            return response.json();
                        }).then(function (data) {
                            if (data && data.success) {
                                window.location.href = data.redirect || form.getAttribute('data-default-redirect') || 'clientarea.php';
                                return;
                            }

                            verifying = false;
                            if (data && data.locked) {
                                codeLocked = true;
                                form.setAttribute('data-code-locked', '1');
                            }
                            setDisabled(false);
                            setStatus('danger', (data && data.message) || form.getAttribute('data-error-message') || '');
                            inputs.forEach(function (input) {
                                input.value = '';
                            });
                            if (!codeLocked) {
                                inputs[0].focus();
                            }
                        }).catch(function () {
                            verifying = false;
                            setDisabled(false);
                            setStatus('danger', form.getAttribute('data-error-message') || '');
                            if (!codeLocked) {
                                inputs[0].focus();
                            }
                        });
                    }

                    function fillFromText(text, startIndex) {
                        if (codeLocked) {
                            return;
                        }
                        var digits = String(text || '').replace(/\D/g, '').slice(0, 6 - startIndex).split('');
                        digits.forEach(function (digit, offset) {
                            inputs[startIndex + offset].value = digit;
                        });
                        var nextIndex = Math.min(startIndex + digits.length, inputs.length - 1);
                        inputs[nextIndex].focus();
                        submitCode();
                    }

                    inputs.forEach(function (input, index) {
                        input.addEventListener('input', function () {
                            if (codeLocked) {
                                input.value = '';
                                return;
                            }

                            var value = input.value.replace(/\D/g, '');
                            if (value.length > 1) {
                                input.value = '';
                                fillFromText(value, index);
                                return;
                            }

                            input.value = value;
                            if (value && inputs[index + 1]) {
                                inputs[index + 1].focus();
                            }
                            submitCode();
                        });

                        input.addEventListener('keydown', function (event) {
                            if (event.key === 'Backspace' && !input.value && inputs[index - 1]) {
                                inputs[index - 1].focus();
                            }
                        });

                        input.addEventListener('paste', function (event) {
                            event.preventDefault();
                            var text = (event.clipboardData || window.clipboardData).getData('text');
                            fillFromText(text, index);
                        });
                    });
                })();
                </script>
                {/literal}
            {else}
                <a href="{$prgate.loginUrl|escape}" class="btn btn-primary">{$prgate.text.login|escape}</a>
            {/if}
        </div>
    </div>
{/if}
