# 升级说明

## 升级到 1.1.7

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**，让模块替换 1.1.6 有问题的默认邮件模板。
4. 发送一封测试验证邮件，确认正文不再显示成带边框的一行一行。

从 1.1.6 升级到 1.1.7 不需要数据库迁移。

## 升级到 1.1.6

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**，让模块刷新默认邮件模板。
4. 发送一封测试验证邮件，确认邮件正文宽度跟随 WHMCS 系统邮件容器。

从 1.1.5 升级到 1.1.6 不需要数据库迁移。

## 升级到 1.1.5

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**，让模块补齐新的按钮颜色设置和邮件默认模板。
4. 如果你之前手动改过中英文邮件模板，请升级后再检查一次模板内容。
5. 如果客户验证页仍显示旧的邮箱标签排版，请清理 WHMCS 模板缓存。

从 1.1.4 升级到 1.1.5 不需要数据库迁移。

## 升级到 1.1.4

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 如果客户验证页仍显示旧间距或旧按钮文案，请清理 WHMCS 模板缓存。
4. 使用未验证邮箱客户测试结账流程，确认当 WHMCS 无法立即发送跳转 header 时，会显示 5 秒倒计时并前往验证页。

从 1.1.3 升级到 1.1.4 不需要数据库迁移。

## 升级到 1.1.3

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 使用未验证邮箱客户测试结账流程：
   - 添加商品到购物车；
   - 如有需要，先应用优惠码；
   - 继续进入结账；
   - 确认客户会跳转到邮箱验证页；
   - 完成邮箱验证；
   - 确认客户返回结账页后，购物车商品和已应用优惠仍然保留。

从 1.1.2 升级到 1.1.3 不需要数据库迁移。

## 升级到 1.1.2

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 进入 **Addons > PeakRack Email Verification Gate**，确认后台顶部已显示新的深色说明区。
4. 如果后台仍显示旧布局，请清理 WHMCS 模板缓存后重试。

从 1.1.1 升级到 1.1.2 不需要数据库迁移。

## 升级到 1.1.1

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 如果客户区仍显示旧按钮文案，请清理 WHMCS 模板缓存。
4. 请求一封验证邮件，确认重发按钮会倒计时，倒计时结束后才能再次点击。

从 1.1.0 升级到 1.1.1 不需要数据库迁移。

## 升级到 1.1.0

1. 先备份 WHMCS 数据库。
2. 用新版 `peakrack_email_gate/` 覆盖 `modules/addons/peakrack_email_gate/`。
3. 进入一次 **Addons > PeakRack Email Verification Gate**，让模块执行数据库结构和配置检查。
4. 检查英文和中文邮件模板。
5. 发送一封测试验证邮件，确认：
   - 验证链接可以正常打开；
   - 蓝色 6 位验证码块显示正常；
   - 验证码输入满 6 位后可以自动核对；
   - 验证成功后会返回客户原先尝试访问的客户区页面。

升级行为：

- 保留设置。
- 保留验证记录。
- 保留日志。
- 如果当前邮件模板仍是旧版模块默认样式，会自动切换为 1.1.0 新默认模板。
- 如果管理员已经自定义过邮件模板，且内容不像旧版默认模板，则会保留自定义内容。

## 从 1.0.0 升级

1.1.0 新增 6 格验证码输入、AJAX 自动核对、验证成功返回原页面、重新设计的 HTML 邮件模板，以及更完整的发布文档。

不需要手动执行数据库迁移。

## 回滚

如需回滚：

1. 用旧版模块目录覆盖 `modules/addons/peakrack_email_gate/`。
2. 进入 **Addons > PeakRack Email Verification Gate**。
3. 在继续发送验证邮件前检查邮件模板。

如果 1.1.0 的默认模板已经被保存，回滚不会自动恢复旧模板文本。
