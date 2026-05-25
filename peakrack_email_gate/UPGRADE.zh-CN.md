# 模块升级说明

1. 先备份 WHMCS 数据库。
2. 用本目录覆盖现有 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**。
4. 检查邮件模板和限流配置。

1.1.8 会保留设置、验证记录、日志和 WHMCS 购物车会话。本版本会在验证码锁定期内禁用并置灰 6 个验证码输入框；如果 AJAX 校验刚好触发锁定，也会立即禁用输入框，避免客户继续输入。
