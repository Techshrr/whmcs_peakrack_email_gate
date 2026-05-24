# PeakRack Email Verification Gate 模块

请将本目录安装到：

`modules/addons/peakrack_email_gate/`

同时保持 WHMCS 原生 Email Verification 开启。

## 功能

- 登录后拦截未验证邮箱用户。
- 新用户第一次注册后仍只发送 WHMCS 原生验证邮件。
- 只有用户点击 `获取验证码` / `重新获取验证码` 后，才发送自定义验证邮件。
- 自定义邮件同时包含验证链接和 6 位安全验证码。
- 自定义 token 和验证码只保存 HMAC 哈希。
- 客户区使用 6 格验证码输入，自动核对，并在成功后返回客户原先访问的页面。

## 后台

进入 **Addons > PeakRack Email Verification Gate** 可配置模块设置、邮件模板、日志、记录、用户解锁、清理工具和 HMAC 密钥轮换。

完整安装和升级说明请查看仓库根目录 README 与 UPGRADE 文件。
