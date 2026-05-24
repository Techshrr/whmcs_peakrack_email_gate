# 模块升级说明

1. 先备份 WHMCS 数据库。
2. 用本目录覆盖现有 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**。
4. 检查邮件模板和限流配置。

1.1.7 会保留设置、验证记录、日志和 WHMCS 购物车会话。本版本将 1.1.6 有问题的 table 默认邮件正文替换为普通 div/p 片段，避免触发 WHMCS 邮件里的表格边框样式。如果当前邮件模板仍是旧版默认样式，会自动升级为新版默认模板；如果看起来是管理员自定义模板，则不会覆盖。
