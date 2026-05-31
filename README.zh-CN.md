# PeakRack Email Verification Gate

> 官方仓库：https://github.com/Techshrr/whmcs_peakrack_email_gate
> 许可证：Apache License 2.0

PeakRack Email Verification Gate 是一个 WHMCS 插件，用于在指定客户区动作前把未验证邮箱的客户引导到邮箱验证页面。

## 项目说明

本插件保留 WHMCS 原生邮箱验证机制，并为未验证客户增加独立验证页。客户可以在该页面请求自定义验证邮件，邮件中包含签名链接和六位验证码。

插件会把设置、验证记录和日志保存到专用模块表中。停用插件不会删除这些数据；卸载时只有管理员明确确认删除后才会移除数据表。

## 功能特性

- 将已登录但邮箱未验证的客户跳转到独立验证页。
- 拦截未验证客户的结账动作，验证后返回结账页。
- 发送包含签名链接和六位验证码的自定义验证邮件。
- 验证链接和验证码仅以 HMAC 哈希保存。
- 支持重发冷却、每小时重发限制、验证码错误锁定和 token 过期。
- 提供英文、简体中文和香港繁体中文客户文本、邮件模板和后台界面。
- 提供后台记录、日志、解锁、清理和 HMAC 密钥轮换工具。
- 可将关键事件同步到 WHMCS Activity Log。

## 环境要求

- WHMCS 9.0.x
- PHP 8.2 或更高版本
- MySQL 5.7 / 8.0
- WHMCS 原生邮箱验证已启用

## 安装方法

1. 从官方仓库下载最新版本。
2. 将插件目录上传到：

   `modules/addons/peakrack_email_gate/`

3. 登录 WHMCS 后台。
4. 进入 **System Settings > Addon Modules** 并启用 **PeakRack Email Verification Gate**。
5. 打开 **Addons > PeakRack Email Verification Gate**，生产环境使用前检查所有设置。

## 配置说明

| 配置项 | 说明 | 默认值 |
|---|---|---|
| Enable module | 是否启用自定义验证页逻辑 | 开启 |
| Force unverified users to gate page | 未验证客户访问客户区时跳转到验证页 | 开启 |
| Redirect unverified checkout to verification | 未验证客户结账时跳转到验证页 | 开启 |
| Mirror key events to WHMCS Activity Log | 将关键事件写入 WHMCS 活动日志 | 开启 |
| Resend cooldown seconds | 两次请求自定义邮件之间的最短间隔 | 60 |
| Max resends per hour | 每个用户每小时最多重发次数 | 5 |
| Code lifetime minutes | 六位验证码有效时间 | 10 |
| Link lifetime minutes | 签名链接有效时间 | 30 |
| Max failed code attempts | 临时锁定前允许的错误次数 | 5 |
| Lock minutes | 临时锁定时长 | 15 |
| Log retention days | 按时间清理模块日志 | 180 |
| Maximum log rows | 按数量清理模块日志 | 10000 |
| Get Code button color | 客户区获取验证码按钮颜色 | #2563eb |
| Email subject/body templates | 英文、简体中文和繁体中文自定义验证邮件内容 | 内置模板 |
| Gate notice templates | 英文、简体中文和繁体中文客户区提示内容 | 内置提示 |

## 使用说明

管理员启用插件后，应保持 WHMCS 原生邮箱验证开启，并检查验证页、结账拦截、速率限制、邮件模板和日志保留设置。

未验证邮箱的已登录客户会进入验证页。客户可以使用 WHMCS 原生验证邮件，也可以请求自定义邮件，然后点击签名链接或输入六位验证码。验证成功后，插件会同步 WHMCS 邮箱验证状态，并把客户跳回之前访问的本地页面。

## 数据库表

- `mod_peakrack_email_gate_settings`
- `mod_peakrack_email_gate_records`
- `mod_peakrack_email_gate_logs`

## 升级说明

请查看 [UPGRADE.zh-CN.md](UPGRADE.zh-CN.md)。

## 英文文档

请查看 [README.md](README.md)。

## 安全说明

请勿提交生产环境凭据、API Key、数据库密码、支付密钥、WHMCS 授权信息、客户数据、身份证件或私有签名密钥。

安全问题报告方式请查看 [SECURITY.md](SECURITY.md)。

## 许可证

本项目基于 Apache License 2.0 发布。完整许可证请查看 [LICENSE](LICENSE)。

其他项目声明请查看 [NOTICE](NOTICE)。
