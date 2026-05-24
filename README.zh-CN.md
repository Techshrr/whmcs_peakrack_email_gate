# PeakRack Email Verification Gate

PeakRack Email Verification Gate 是一个适用于 WHMCS 9 的邮箱验证拦截 Addon Module。模块保持 WHMCS 原生 Email Verification 开启，登录后拦截未验证邮箱用户，并提供可控的自定义重发流程：自定义验证链接 + 6 位安全验证码。

目标环境：

- WHMCS 9.0.3
- PHP 8.2 / 8.3
- MySQL 8.0
- WHMCS 原生 Six、Twenty-One、Nexus 主题
- 兼容 Lagom Client Theme

## 功能说明

- 不修改 WHMCS 核心文件。
- 不修改 Lagom 核心文件。
- 使用 Addon Module + `hooks.php`。
- 使用 `ClientAreaPage` 将未验证用户跳转到 `index.php?m=peakrack_email_gate`。
- 使用 `ShoppingCartValidateCheckout` 兜底阻止未验证用户下单。
- `UserAdd` 只初始化记录，第一次注册不发送自定义验证码。
- `UserEmailVerificationComplete` 在原生邮箱验证完成后清理模块 token。
- 客户验证页支持中英双语。
- 6 个独立验证码输入框，支持自动跳格、粘贴、AJAX 自动核对，不再需要提交按钮。
- 验证成功后自动返回客户原先尝试访问的页面。
- 自定义邮件包含按钮式验证链接和蓝色安全验证码块。
- 自定义 token 和 6 位验证码只保存 HMAC 哈希，不明文入库。
- 默认验证码 10 分钟有效，自定义链接 30 分钟有效。
- 默认重发冷却 60 秒，每小时最多 5 次。
- 默认验证码错误最多 5 次，超过锁定 15 分钟。
- 邮件优先使用 WHMCS Local API `SendEmail`，支持 `customsubject`、`custommessage`、`customvars`。
- 同步 WHMCS 9 用户邮箱验证状态，优先尝试 User Model，再检测 `tblusers` 字段 fallback，并同步旧版 `tblclients.email_verified`。
- 关键事件写入 WHMCS Activity Log 和模块日志表。
- 后台提供设置、记录、日志、解锁用户、清理过期 token、日志保留清理、HMAC 密钥轮换功能。
- 使用标准 WHMCS Smarty 客户区模板。

## 业务逻辑

新用户第一次注册后，仍只发送 WHMCS 原生邮箱验证邮件。模块在 `UserAdd` 中只初始化记录，不发送自定义邮件。

用户登录后，如果邮箱未验证，会被跳转到验证页。从用户点击“获取验证码”或后续“重新获取验证码”开始，模块发送自定义邮件，邮件包含：

- 一条自定义验证链接；
- 一个 6 位安全验证码。

点击自定义验证链接或输入 6 位验证码都可以完成验证，并同步 WHMCS 原生邮箱验证状态。

## 未验证用户允许访问

模块允许未验证用户访问：

- 验证页；
- 重发接口；
- 验证码提交接口；
- 退出登录；
- 忘记密码；
- WHMCS 原生邮箱验证回调；
- 静态资源。

客户中心首页、服务、账单、下单、发票支付、工单和资料修改会被跳转或阻止。

## 安装

1. 上传 `peakrack_email_gate/` 到 `modules/addons/peakrack_email_gate/`。
2. 在 WHMCS 后台进入 **系统设置 > Addon Modules**。
3. 启用 **PeakRack Email Verification Gate**。
4. 进入 **Addons > PeakRack Email Verification Gate**。
5. 检查限流配置、邮件模板和后台语言。
6. 保持 WHMCS 原生 **Email Verification** 开启。

本地开发 checkout 中也同步了一份运行目录：`modules/addons/peakrack_email_gate`。公开发布时，安装包目录为 `peakrack_email_gate/`。

## 升级

详见 [UPGRADE.zh-CN.md](UPGRADE.zh-CN.md)。

简要步骤：

1. 备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**，让模块执行结构和配置检查。
4. 检查升级后的邮件模板。

升级不会删除设置、验证记录或日志。

## 卸载

停用模块会保留设置、记录和日志。

卸载函数默认也保留数据，只有管理员显式提交 `DELETE` 确认时才会删除模块数据。

## 安全说明

- 自定义验证链接 token 和 6 位验证码只保存 HMAC 哈希。
- HMAC 密钥在启用模块时自动生成，可在后台工具中轮换。
- 轮换 HMAC 密钥会让所有未使用的自定义链接和验证码失效。
- 验证码错误次数和重发频率在服务端限制。
- 成功验证后的返回地址会经过过滤，避免外部跳转。

## GitHub 仓库信息建议

推荐仓库描述：

`WHMCS 9 邮箱验证拦截模块，支持自定义重发链接、6 位验证码、限流、锁定和中英双语模板。`

推荐 GitHub topics：

`whmcs`, `whmcs-addon`, `email-verification`, `peakrack`, `php83`, `lagom`, `security`

本次建议发布 tag：

`v1.1.0`

## 开源协议

MIT。详见 [LICENSE](LICENSE)。
