# 模块升级说明

1. 先备份 WHMCS 数据库。
2. 用本目录覆盖现有 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**。
4. 检查邮件模板和限流配置。

1.1.3 会保留设置、验证记录、日志和 WHMCS 购物车会话。如果当前邮件模板仍是旧版默认样式，会自动升级为新版默认模板；如果看起来是管理员自定义模板，则不会覆盖。
