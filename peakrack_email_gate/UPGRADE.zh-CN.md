# 模块升级说明

1. 先备份 WHMCS 数据库。
2. 用本目录覆盖现有 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**。
4. 检查邮件模板和限流配置。

1.1.9 会保留设置、验证记录、日志和 WHMCS 购物车会话。本版本优化了锁定提示文案，并加入前端静默倒计时：锁定到期后会自动隐藏提示并释放验证码输入框。
