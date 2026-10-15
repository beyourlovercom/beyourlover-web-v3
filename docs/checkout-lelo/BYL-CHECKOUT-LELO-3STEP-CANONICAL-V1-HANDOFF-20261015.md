# BeYourLover — Checkout LELO 3-Step Canonical V1

**文档性质**：Owner 已确认的视觉基线 + 待实现的业务需求 + 下一开发支线 Handoff  
**版本**：V1 · 2026-10-15  
**项目**：BYL Website V3 / GitHub `beyourlovercom/beyourlover-web-v3`  
**GitHub**：`main` 为唯一代码合并目标；Issue [#89](https://github.com/beyourlovercom/beyourlover-web-v3/issues/89) 为 Checkout LELO 支线，[#87](https://github.com/beyourlovercom/beyourlover-web-v3/issues/87) 为后续 Commerce E2E 门禁。  
**状态**：**视觉方向/Shipping/Payment 获 Owner Design PASS；正式实现、Dev Runtime、支付和生产上线均未因此自动 PASS。**  
**资料**：下方 `references/` 收录本轮用户参考截图与获得认可的概念视觉稿。效果图中的商品、价格、支付品牌只是演示；不可把模拟 UI 当作支付网关功能已启用的证据。

---

## 0. 新聊天 / 执行支线启动口令

> 读取 `BYL-CHECKOUT-LELO-3STEP-CANONICAL-V1-HANDOFF-2026-10-15.md`，直接继续 BYL Website V3 Checkout #89。**Cart → Shipping → Payment 三步设计已获得 Owner 视觉定稿，禁止重新发明或增加 Delivery/Review 步骤。** 优先核对 GitHub main、Cart/Checkout 原 PR、Woo/Andy Core/Andy Commerce/G5 的真实运行契约；随后将本 Handoff 拆成可实现、可回归的独立任务，按 L2 契约 → L3 安全开发 → 隔离 CI → Dev-only 发布申请 → L4/L5 Owner Final 的门禁推进。重复利用 Cart Drawer、G5、BYL49、通知系统的现有成果。可以只读审计、创建独立分支、Draft PR、跑允许的本地/隔离测试、汇报 Issue；**没有单独 Owner 授权，不得合并、发布 Dev/WWW、创建真实订单/支付、写生产数据库、发送真实邮件、改支付网关/货币/SMTP/订阅设置或重启 READY 设备。** 每阶段简洁报告真实进度。

---

## 1. Owner 决策与固定设计边界

| 项目 | 决策 | 状态 |
|---|---|---|
| UI 架构 | **方案 B — Checkout Drawer**，PC/手机统一视觉语言；`/checkout/` 保留安全备用入口 | Owner Design PASS，具体运行时方案待 L2 验证 |
| 步骤 | **1 Cart → 2 Shipping → 3 Payment**；不增加 Delivery 或 Review 第四步 | 设计锁定 |
| Cart | 保留既有 Cart Drawer，`/cart/` 备用，商品/优惠/免邮/数量功能由既有实现继承 | 技术 smoke 曾通过；Cart Owner Final 另行门禁 |
| Shipping | 用户最新微调后的 `Shipping-Owner-PASS-Mobile.png` | **Owner Design PASS** |
| Payment | 用户确认的 `Payment-Owner-PASS-Mobile-Desktop.png` | **Owner Design PASS** |
| 品牌 | BeYourLover 原酒红色、黑色正文、苹方/系统字体；LELO 仅作交互与排版参照；**不对外宣传 SVAKOM 背景** | 锁定 |
| 体验 | 手机优先，白底、克制分隔线，减少不必要装饰、重复确认和额外勾选；底部仅一个主要 CTA | 锁定 |
| Checkout 主权 | WooCommerce 负责真实 Session、Cart、Checkout、税费/运费/折扣/订单/支付权威值；主题仅展示、安全交互 | 技术门禁 |
| Payment 网关 | **以实际启用且对当前国家/币种可用的 Woo 网关为准**；图中 Stripe/PayPal/Alipay/Google Pay 不代表已全开通 | 技术门禁 |
| 下单许可 | 视觉 PASS **不构成**任何真实支付或生产变更授权 | 永久保留 |

### 1.1 视觉资料：设计冻结参照

- **Shipping 已通过最终版**：`references/Shipping-Owner-PASS-Mobile.png`。
- **Payment 已通过最终版（手机+电脑）**：`references/Payment-Owner-PASS-Mobile-Desktop.png`。
- **游客邮箱必须忠实参照**：`references/Email-Guest-Exact-Reference.png`（没有说明文字、不显示登录用户的 Edit profile）。
- 原 LELO Shipping 结构与地址示例：`references/Shipping-LELO-Structure-Reference.png`、`references/Address-LELO-Fields-Reference.png`。
- 原 LELO Payment 手机版与电脑版：`references/Payment-LELO-Mobile-Reference.png`、`references/Payment-LELO-Desktop-Reference.png`。

**注意**：最后两张“Owner PASS”是视觉概念图，部分字句、测试金额和品牌 Logo 为占位；当本文件业务规则与图片的占位演示冲突时，**由本文件明确写明的用户业务约束 + Woo 真实计算/支付安全规则约束运行态**，不得照搬错误占位值。

---

## 2. Step 1 — Cart（复用现有 Cart V2）

1. 使用现有 Cart Drawer；可正常从普通网站页面的 Header 购物车入口打开/关闭；`/cart/` fallback。
2. 展示真实商品、规格、数量、删改、优惠码、免邮进度、折扣后商品小计、运费/税费的正确口径。
3. **BYL49**：以**折扣后、未含税和运费**的小计判断免邮；阈值及只读迁移保护由 Andy Commerce / Woo 规则权威控制，不在 Checkout UI 实现第二套算法。
4. 手机保持一个清晰固定底部 CTA，进入 Shipping；不要新增 Checkout 前的确认步骤。
5. Cart Owner Final PASS 必须以真实 Dev 运行态、M390/M14 和 PC1440 证据单独登记；不能由本次视觉 PASS 代替。

---

## 3. Step 2 — Shipping（Owner Design PASS）

### 3.1 信息结构与顺序（固定）

1. 顶部：标题 `Shipping`、Cart → Shipping → Payment 图标进度、返回 `Your Cart`、关闭 Drawer。
2. **账号/邮箱模块**（见 §3.2）。
3. **Choose your shipping option**（见 §3.3）。
4. **Add shipping details**：地址与特殊清关信息（见 §3.4–3.5）。
5. 按收货国家/条件显示 **Country Notices**（见 §4）。
6. 底部吸附：`Order total (N)`、`See details` 可展开、唯一 `CONTINUE TO PAYMENT` 按钮。最终金额必须由 Woo 计算；金额变化时刷新展示。

### 3.2 邮箱/账号（游客与已登录两种状态）

**未登录默认状态：必须接近用户给出的原 LELO 参考图，不能加顶部多余文案。**

- 右上为 `Log in or create an account`，链接到可返回当前 Checkout 的登录/注册流程。
- 一个方形大输入框：`Email Address*`，支持清楚的标签和真实邮箱验证。
- 邮箱**下面不放解释性提示文字**（原示例含 invoice/order confirmation 说明，但 Owner 最新明确要求**去掉邮箱下方文案**）。
- 下方可独立展示 `Notify me of exclusive offers` **非默认勾选** 的营销许可；这是 Shipping 的可选订阅许可，不能在付款步骤再追加同一勾选；不能因为用户付款而自动订阅。
- 登录用户显示已有邮箱与 `Edit your profile`，允许在合理权限和安全流程下编辑；避免在 Checkout 暴露其他人的账户信息。

**Owner 所需账号行为**：用户填写必填邮箱后，为首次购买用户自动创建可访问的 WordPress 客户账户，发送账号相关通知邮件。**实现安全裁定留给 L2，不能对每次键入/失焦即时创建账号或发送邮件**：推荐在服务器通过验证且订单确实提交的受控阶段创建/关联 customer，以免垃圾账号、邮箱轰炸、重复写入和既有邮箱冒领。使用 Woo 已支持的账户能力及一次性设置密码/密码重置流程；不得发送明文密码。若邮箱已有账号，不能直接把游客资料/订单授权给该账号，应核验身份与 Woo 客户关系契约。真实建号/发送邮件需单独授权与 Dev 沙盒测试。

### 3.3 快递选项置于 Shipping，不延迟到 Payment

- 同步展示 `Express`（例如 2–3 days、加价）与 `Regular`（例如 8–12 days、若符合规则可免费）的互斥单选项。
- **时效、价格、可用性只来自 Woo Shipping Zones/Methods/Rate**，按国家、地址、包裹、货币、金额等条件实时变；图片中的 `$15.00 / Free / 2–3 days` 均为占位而非保证。
- 每次修改国家、地址、购物车或优惠，都重新评估配送选项和总额，处理不可配送地区、无匹配运费和价格跳变。
- Payment 只显示已选运输方案与金额供核对，不再次强制选运输。

### 3.4 地址表单（顺序与视觉冻结）

| 顺序 | 字段 | 规则 |
|---|---|---|
| 1 | `Country / Region*` | 必填；变化触发省州、邮编、运费/税费、国家 Notice、税号规则更新；地址 autocomplete 可手填回退 |
| 2 | `First name*` + `Last name*` | 并排（小屏根据空间合理折行），必填 |
| 3 | `Street address*` | 主街道地址必填 |
| 4 | `+ Add apt, suite, floor or other (optional)` | **默认折叠**，用户点击后展开地址第二行；可再次收起，不清空已填内容 |
| 5 | `City*` + `State / Region` | 城市必填；省州按国家 Woo 地址规则判断必填或选填；不能全球强制星号 |
| 6 | **`Phone*`** | **在 Postal / ZIP Code 之前**，独占一行；底部以一整行显示 LELO 风格保护说明，推荐原文 `We will only use this number in case we need to contact you regarding delivery purposes.`（法律审阅后上线） |
| 7 | `Postal / ZIP Code` | 在 Phone 之后；是否必填以国家的实际 Woo/承运商规则为准，适用必填时加红色 `*` |
| 8 | **国家特定税号 / 清关 ID** | **仅符合触发国家时显示，且置于 Postal / ZIP Code 之后，单独一整行**；解释性文案可多行，详见 §3.5 |
| 9 | `+ Add organization (optional)` | **默认折叠**，点击展开公司/组织名；不将其设成必填 |
| 10 | `Save this address for future orders` | 仅在对当前用户身份有意义且获许可时提供；默认不选 |

- **Billing**：默认复用 Shipping 地址，不要求用户再填一份 Billing；是否保留 `Use this address for billing` 勾选应在 L2 审核法律/支付网关要求。Owner 原则是无需额外 Billing 步骤、无需不必要勾选；若不需选择，可采用隐藏的默认同址并提供必要的编辑入口，而不是多余必勾框。
- 必填字段统一红色 `*`；敏感字段（Phone、Tax ID）配合理解性和隐私说明，不能用红色大警告制造焦虑。
- 国家提示与税号逻辑分离；不能用一条纯文字 Notice 冒充必填验证。

### 3.5 国家特定税号 / 清关字段（须法律物流校验）

| 目的地 | 当前业务拟展示字段 | UI / 技术规则 |
|---|---|---|
| Mexico | RFC | 独占整行，展示清关用途与格式引导；本业务计划必填，需与承运商/海关要求校验 |
| Brazil | CPF | 独占整行；CPF/CNPJ 等适用范围需按收件人性质及承运商校验，避免只接受一种格式 |
| Chile | Customs Tax / ID | 字段标签、格式和必填场景需取证确认 |
| Argentina | Customs Tax / ID | 字段标签、格式和必填场景需取证确认 |
| South Korea | Customs Clearance / Recipient ID | 具体可接受个人通关识别号（如 PCCC）等要求需物流合规确认，不应默认把任何身份证号作为唯一正确类型 |
| Peru | Customs Tax / ID | 字段标签、格式和必填场景需取证确认 |
| Norway | VOEC 提示 | **VOEC 是符合条件卖方的增值税登记识别号码，不是每个挪威买家自己的 7 位数税号**。适用时展示公司 VOEC 与低于 NOK 3,000 的商品/适用规则说明；不得自动向消费者索取“本人 VOEC”作为必填。 |

> 这一栏保留用户提出的特殊国家清单，但“全部消费者必须填税号”的实际合法性、字段名称、格式、清关主体和特殊阈值属于 L2 待核验事项。由物流、税务及 Woo Shipping Contract 确认后才能设为强制验证。必须有前端提示、服务端校验、数据存储的最小化与隐私保护。

---

## 4. Country Notices — 可编辑、带规则的配置系统

### 4.1 建议后台位置

**WordPress Admin → Andy Core → Checkout OS → Country Notices**。

由 Andy Core 提供运营编辑界面和读取 API；规则/结构/校验代码受 GitHub 管理；**运营文案数据在 WP 配置数据库中维护**，支持修订历史、修改人、时间、审核/发布、回滚、预览；不把每个国家文案硬编码在 `checkout.php` 或散落的 CSS/JS 里。

### 4.2 四级规则与叠加

- Global：所有国家默认说明，例如费用是否估算、进口税可由当地机关收取。
- Region：欧盟、欧洲非欧盟等，不可把欧洲视为一个统一税区。
- Country：美国 PO Box、巴西清关、墨西哥 RFC 等。
- Condition：仅在税费未包含、特定运费、VOEC 适用、特定商品/币种等条件出现。
- **覆盖优先级**：Country > Region > Global；但独立条件提示可叠加，而不是互相吞掉。

建议每条记录包含 `rule_id`、`scope`、`country_codes`、`conditions`、`priority`、`enabled`、`effective_at`、`expires_at`、`title`、`body`、`locale`、`revision`、`updated_by`、`published_by`；后台 Preview 与实际 Checkout 使用同一条规则渲染流程。必须 HTML 净化和权限校验，不允许任意脚本注入。

### 4.3 US / 欧洲 / 清关示例

**美国（用户确认样例，只有真实税费口径符合时才使用）**：

> Important Notices  
> This order includes all local taxes.  
> Please note: if you're using a PO box, we can only deliver to U.S. PO box addresses.

- `This order includes all local taxes` 必须由税费确实包含在订单中的 Woo 税务/配送规则支撑。不能只因为目的地是美国就无条件承诺。
- 欧洲需区分进口税 VAT、IOSS、DAP/DDP 与卖方/收件方责任。任何“已缴清全部税费”均需后台订单和税务设置支撑。
- 清关国家要解释税号用途，但税号“是否出现/是否必填”由 `Tax ID Rules` 决定，不由文案决定。
- 底部普适费用提示应避免与“美国已含全部当地税”的国家声明矛盾：根据税费已含/未含的**计算事实**选择措辞，而不是总写“may pay taxes again”。

**注意位置**：在 Shipping 地址/特殊税号区域后，底部订单汇总之前，以压缩版温和 Notice 呈现；重要的清关提示也可紧邻税号字段。国别切换后即时更新，避免旧国家文案残留。

---

## 5. Step 3 — Payment（Owner Design PASS）

### 5.1 桌面端（视觉以 `references/Payment-Owner-PASS-Mobile-Desktop.png` 为准）

- **左右分栏**：左侧 Cart 订单信息、Shipping/Billing 收件信息及 Edit、折扣运费税费和最终总价；右侧是 Payment 标题、三步导航和简洁的支付选项。
- 左栏 Order total 必须展示 Woo 权威数额（币种/换汇与 FX #113/PR #116 合同一致）。商品和地址 `Edit` 不应跳离/丢失已填数据。
- 右栏选项为清晰**互斥单选行**（信用卡、PayPal、Alipay、Google Pay 只在当前 Woo/Provider 对应功能真实可用且适合当前购物车/设备时才展示）；图中的 `Powered by Stripe` 属于示意，不是全局强制品牌。
- 不增加“Review”子步骤；订单核对内嵌页面左栏/底部摘要，Woo 原生 final review 业务验证不能删除。

### 5.2 手机端

- 顶部保留 `Payment`、Cart/Shipping/Payment 三步图标、返回 Shipping、关闭。
- 页面中部只展示可用支付方式列表；提供必要的卡片信息或 Provider 托管 UI，不自制支付敏感表单。
- `Order total` + `See details` **可折叠**；页面底部只保留一个固定 `PAY NOW` 主要操作按钮，不让 Help 弹窗/浮层遮挡。
- 禁止独立 Delivery、Review 和“再确认一次”页面；不再追加多余的 `agree`/地址保存/营销勾选。
- `PAY NOW` CTA 文案与真实支付类型和 Woo 授权一致：对于纯跳转 PayPal、0 元订单、需额外认证的网关可按规则适配，但不得误导称已付款。

### 5.3 “减少分心”不等于绕过支付/法律/安全

- 不新增非必要 checkbox；营销 opt-in 仅在 Shipping 且初始不选。
- 法律同意文本可精简呈现在适当位置，**仍需法务核对当地法律和 Woo 核心的必要流程**。历史 Dev `Terms` 预勾选风险要在 L2 复核，不能自动沿用。
- Woo 的 server-side `nonce`、地址/运费/税费/网关/最终总额验证、3DS/SCA、G5 重试/幂等/重复点击控制、异步结果处理与失败恢复**全部保留**。
- Stripe/PayPal/Alipay/Google Pay 的具体图标和实现能力以真实启用配置、区域、币种和设备资格为准。不可为了让示意图像 LELO 而伪造可点击的网关。

---

## 6. 数据与系统归属（防止双重实现）

| 领域 | 权威系统/归属 | 不得做的事 |
|---|---|---|
| 购物车、订单、客户会话、Checkout 提交 | WooCommerce | Theme 自行再造第二套购物车、订单或客户身份系统 |
| 商品价格/货币/Taxes/Shipping/Total | Woo + 经 Owner 通过的 FX 契约 | Theme 手算金额；混用 JPY/USD 示例金额；静态显示某支付费用 |
| 促销、BYL49、营销规则 | Andy Commerce + Woo | Checkout 按钮自己计算免邮或折扣 |
| 账号创建/用户通知 | Woo/WordPress 正式账号能力 + Mail OS | 失焦就建账号、自动发明文密码、绕过 Mail OS 出站审批 |
| 支付网关、风控、3DS/G5 | Woo 网关插件、G5 独立合同 | 假支付表单、跳过后端校验、擅自重放 Webhook |
| Country Notices | Andy Core / Checkout OS 管理界面（待实现） | 运营改文案必须反复改 PHP/重新发布主题 |
| Tax ID/清关字段及条件 | 独立结构化规则契约（可由 Andy Core 管理，Woo 后端执行） | 靠 Notice 文字判断必填；消费者强制填写卖方 VOEC |
| Checkout Drawer/UI | BYL V3 Theme + 既有 Cart Drawer 交互体系 | 移植旧主题的私有运行时代码造成锁定 |
| 敏感资料 | Woo/WordPress 权限与最小保存、脱敏展示、明确保留期 | 前台日志、Analytics、浏览器持久化记录身份证/税号完整值 |

---

## 7. L2–L5 路线图：先合同再代码

| 阶段 | 产出 | 通过标准 / 门禁 | 当前 |
|---|---|---|---|
| **L1** 视觉方案 | 方案 B 手机/PC、Shipping/Payment 已确认效果图与此 Canonical 规范 | Owner Design PASS；Cart 已有成果复用 | **PASS（设计）** |
| **L2** Contract Freeze | Woo Gateway/Shipping/Address/Tax/FX/Account/Mail/Consent 配置只读审计；Country Notice/Tax ID 数据契约；桌面/移动断点；运行态 Checkout Drawer 兼容 | Owner 确认有争议的字段、税号国家、账号邮件触发方案、网关，形成可回归合约 | **待开始** |
| **L3** UI/Contract 开发 | 在隔离分支复用 Theme/Cart/Andy Core/Commerce，安全实现 Shipping+Payment、规则配置、字段校验和错误态；Draft PR | Code Review、离线/隔离回归、PHP/JS lint、CI 实际 PASS；Billing/Runner 失败不算 PASS | **未开始** |
| **L4** Dev-only Canary | 精确 HEAD、白名单、构建/ZIP hash、备份与回滚；只允许单独审批的 Dev 变更 | M390、iPhone 14、PC1440；真实 Dev 视觉/交互验证；无未授权邮件/订单/支付写入 | **未授权** |
| **L5** Owner Final | 完整 Cart/Shipping/Payment UX、含国家切换、Shipping Rates、异常态、安全回退 | Owner 对**真实 Dev Runtime** 明确 Checkout Final PASS；与 Cart Final/G5 证据单独协调 | **待开始** |
| **#87 E2E** | Cart Owner Final + Checkout Owner Final + G5 既有证据重新核实 | 3 个门禁 AND + 独立 Dev E2E 授权；支付/订单特殊写入另需明确授权 | **HOLD** |
| **T6 WWW** | Theme V3 完整发布候选/回滚/SEO/支付/货币/邮件方案 | 独立 Production 批准，不因本次视觉 PASS 自动放行 | **HOLD** |

### 7.1 优先开发拆分建议

1. **L2-A：当前 checkout 与 Cart/Shipping/G5/FX 只读差异**；确认 Drawer 动态挂载与 Woo Block/Classic Checkout 的运行时契约。
2. **L2-B：Shipping 展示结构**（游客/已登录邮箱、国家地址、手机一行保护文案、快递选项、国家 Notice、税号置末尾单行）。
3. **L2-C：后台 Country Notices 与 Tax ID Rules**（分离文字与字段验证，管理员 CRUD/预览/版本/回滚）。
4. **L2-D：账户/邮件政策**（受控创建/关联、既有账户安全、去重与事务边界、可观察但不能频繁发送）；Mail OS 独立审批。
5. **L3-A：Payment 简化视觉**（支付网关真值、单 CTA、PC 左摘要 / mobile 折叠）。
6. **L3-B：合成回归**（错误、无运费、无网关、无货币映射、国家切换、折扣变化、手机号/Tax ID 检查、键盘/无障碍/焦点管理）。
7. **L4/L5**：严格在已授权的 Dev 环境完成 Owner 最终视觉与交互 UAT，暂不触碰 WWW。

---

## 8. Owner 验收清单（必须逐项有真实证据）

### 8.1 视觉和交互

- [ ] 手机 M390/M14：三步进度正确，Drawer 可返回/关闭，底部仅一个 CTA，无遮挡、横向溢出或焦点陷阱。
- [ ] PC1440：Payment 左摘要+地址/金额，右支付方式；不增加 Review/Delivery 额外步骤。
- [ ] 未登录：邮箱模块与所附 LELO 参考一致，**没有邮箱底部提示文字**、只有 `Log in or create an account`。
- [ ] 已登录：邮箱/profile 可编辑，不能泄露他人信息。
- [ ] Shipping 快递选择在地址填写后可重算、Payment 不重复选择快递。
- [ ] Phone 位于 Postal / ZIP 前，敏感说明独占完整行；税号在 ZIP 后且独占整行；公司和 Apt 可折叠。
- [ ] Mexico、Brazil、South Korea 等条件字段随国家切换正确显示/隐藏；Norway VOEC 不误作客户的必填身份号码。
- [ ] Country Notices 对 Global/Region/Country/Condition 规则按需展示，无错误的已含税声明。
- [ ] 手机 Payment 只保留相关网关和简洁 CTA，不额外勾选；`See details` 折叠可用。

### 8.2 交易、安全和质量

- [ ] Woo 最终金额/币种/折扣/税/运费与 Checkout 显示一致；BYL49 折后商品小计逻辑不被重复实现。
- [ ] 无可用配送/支付方式时给出正确可恢复的 Notice，不显示伪造选项。
- [ ] 未经允许不创建/关联真实账号、不发送通知邮件、不触发订阅、下单、付款或生产 DB 写入。
- [ ] 对邮箱已存在、失效邮件链接、账号重复请求、国家税号无效等异常有验证与隐私边界。
- [ ] Terms 同意的法务要求经核对；不能通过静默默认勾选获得不合法的授权。
- [ ] G5 3DS/Decline/Retry/Idempotency 合同与最终代码对应；测试必须在独立授权沙盒。
- [ ] 完整独立 CI / 实际 PASS 的证据，GitHub PR 精确 SHA，Dev/WWW 部署审批分离。

---

## 9. 已确认与仍待决定（不要混写）

### 已确认（不用重新问 Owner）

- 选 **方案 B**；只保留 Cart → Shipping → Payment 三步。
- Shipping 最新视觉微调方案 PASS；Payment 手机+PC 方案 PASS。
- 邮箱游客初始没有邮箱下面的说明文字；有 `Log in or create an account`，已登录可以 `Edit profile`。
- 配送选项前置 Shipping，Express 可以额外付费；Phone 在 Postal Code 前，敏感说明独占一行。
- 需要的税号置 ZIP 后单独一行；公司和 Apt 默认折叠；国家 Notice 必须后续可由运营修改。
- Payment 不添加让用户犹豫的多余勾选；订单核对在页面内完成，不额外开第四步。

### 仍需 L2 取证/审批（不能自行猜）

- 哪些国家需要何种真实清关号、谁提供、哪些场景必填、格式、货运渠道差异；挪威 VOEC 必须纠正身份主体。
- 美国/欧洲税费“已包含”和“到货可能另外支付”的真实条件，是否存在不同国家/运输服务 DDP/DAP。
- 当前 Woo/Stripe/WooPayments/PayPal 及其他网关在各国家、货币、设备上实际可用性。
- 自动建号触发条件与不登录下单策略、Woo 老账号归属、安全邮件、Mail OS 许可、通知模板。
- Billing/Terms/Marketing 的最终法律合约，不应单凭视觉稿推断技术上可删除 Woo 必要字段。
- Drawer 替换当前 `/checkout/` 的 Dev 实装路线、缓存/会话/可访问性/回退方案。

---

## 10. Roadmap 一句话

**Checkout 三步 Owner Design PASS（Cart 复用 + Shipping PASS + Payment PASS）→ L2 Contract/国家 Notices/账号/税号/网关取证待推进 → L3/L4/L5 实作及真实 Dev Owner Final 尚未开始 → Cart Final & G5 Fresh Gate 待核 → #87 E2E HOLD → WWW T6 HOLD。**