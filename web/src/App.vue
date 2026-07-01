<template>
  <section v-if="!authed" class="login-shell">
    <div class="login-card">
      <div class="login-art">
        <div class="sun"></div>
        <div class="mountain mountain-a"></div>
        <div class="mountain mountain-b"></div>
        <div class="tree tree-a"></div>
        <div class="tree tree-b"></div>
        <div class="tree tree-c"></div>
        <div class="avatar"></div>
      </div>
      <form class="login-form" @submit.prevent="login">
        <label class="login-field">
          <span>用户</span>
          <input v-model="loginForm.username" autocomplete="username" />
        </label>
        <label class="login-field">
          <span>密码</span>
          <input v-model="loginForm.password" type="password" autocomplete="current-password" placeholder="ADMIN_TOKEN" />
        </label>
        <div class="captcha-row">
          <label class="login-field">
            <span>验证</span>
            <input v-model="loginForm.captcha" placeholder="验证码" />
          </label>
          <button class="captcha" type="button" @click="makeCaptcha">{{ captcha }}</button>
        </div>
        <label class="remember"><input v-model="loginForm.remember" type="checkbox" />保持会话</label>
        <button class="login-button" type="submit">登 录</button>
        <p v-if="loginError" class="login-error">{{ loginError }}</p>
      </form>
    </div>
  </section>

  <section v-else class="layout">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">微</div>
        <div>
          <strong>公众号后台</strong>
          <span>WeChat Admin</span>
        </div>
      </div>
      <button class="nav-item" :class="{ active: activePage === 'accounts' }" @click="activePage = 'accounts'">公众号列表</button>
      <button class="nav-item" :class="{ active: activePage === 'rules' }" @click="openRulesPage()">回调规则</button>
      <div class="nav-group">
        <span>素材管理</span>
        <button class="nav-item child" :class="{ active: activePage === 'materialGroups' }" @click="openMaterialPage('materialGroups')">素材分组</button>
        <button class="nav-item child" :class="{ active: activePage === 'materialImages' }" @click="openMaterialPage('materialImages')">图片列表</button>
        <button class="nav-item child" :class="{ active: activePage === 'heartQuotes' }" @click="openMaterialPage('heartQuotes')">心语签列表</button>
        <button class="nav-item child" :class="{ active: activePage === 'punchRecords' }" @click="openPunchRecords">打卡记录</button>
      </div>
    </aside>

    <main class="workspace">
      <header class="topbar">
        <div>
          <h1>{{ pageTitle }}</h1>
          <p>{{ pageSubtitle }}</p>
        </div>
        <button class="ghost" @click="logout">退出登录</button>
      </header>

      <template v-if="activePage === 'accounts'">
        <section class="toolbar">
          <div class="search">
            <span>搜索</span>
            <input v-model="keyword" placeholder="名称 / AppID" />
          </div>
          <button class="primary" @click="openAccountDialog">添加公众号</button>
        </section>

        <section class="table-panel">
          <table>
            <thead>
              <tr>
                <th>公众号</th>
                <th>AppID</th>
                <th>回调地址</th>
                <th>菜单发布时间</th>
                <th>状态</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredAccounts" :key="item.id">
                <td>
                  <strong>{{ item.name }}</strong>
                  <small>{{ item.original_id || '未填写原始 ID' }}</small>
                </td>
                <td><code>{{ item.app_id }}</code></td>
                <td><code>{{ origin }}/wechat/official-account/{{ item.app_id }}</code></td>
                <td>{{ item.menu_published_at || '-' }}</td>
                <td><span class="status-pill" :class="{ off: !item.is_active }">{{ item.is_active ? '启用' : '停用' }}</span></td>
                <td>
                  <div class="row-actions">
                    <button @click="openAccountDialog(item)">编辑配置</button>
                    <button @click="openMenuDialog(item)">编辑菜单</button>
                    <button @click="openRulesPage(item.id)">回调规则</button>
                    <button class="danger-text" @click="removeAccount(item)">删除</button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredAccounts.length === 0">
                <td class="empty" colspan="6">暂无公众号</td>
              </tr>
            </tbody>
          </table>
        </section>
      </template>

      <template v-else-if="activePage === 'rules'">
        <section class="toolbar rules-toolbar">
          <div class="rule-filters">
            <label>选择公众号
              <select v-model="selectedRuleAccountId" @change="onRuleAccountChange">
                <option value="" disabled>请选择公众号</option>
                <option v-for="item in ruleAccounts" :key="item.id" :value="String(item.id)">{{ item.name }}（{{ item.app_id }}）</option>
              </select>
            </label>
            <div class="search">
              <span>搜索</span>
              <input v-model="ruleKeyword" placeholder="规则名 / 关键词 / 回复内容" />
            </div>
          </div>
          <button class="primary" :disabled="!selectedRuleAccountId" @click="startCreateRule">新增规则</button>
        </section>

        <section class="rule-page-grid">
          <form class="rule-form-panel" @submit.prevent="saveReplyRule">
            <div class="rule-form-head">
              <div>
                <h2>{{ editingRuleId ? '编辑回调规则' : '新增回调规则' }}</h2>
                <p>{{ currentRuleAccount ? currentRuleAccount.name : '请先选择公众号' }}</p>
              </div>
              <button v-if="editingRuleId" class="ghost" type="button" @click="startCreateRule">取消编辑</button>
            </div>

            <div class="form-grid">
              <label>规则名<input v-model="replyForm.name" required placeholder="例如：关注欢迎语" /></label>
              <label>消息类型
                <select v-model="replyForm.msg_type">
                  <option value="*">全部</option>
                  <option value="text">文本</option>
                  <option value="image">图片</option>
                  <option value="voice">语音</option>
                  <option value="video">视频</option>
                  <option value="shortvideo">小视频</option>
                  <option value="location">位置</option>
                  <option value="link">链接</option>
                  <option value="event">事件</option>
                </select>
              </label>
              <label>事件<input v-model="replyForm.event" placeholder="subscribe / CLICK，可不填" /></label>
              <label>关键词<input v-model="replyForm.keyword" placeholder="用户消息包含这些字时触发" /></label>
              <label>匹配方式
                <select v-model="replyForm.keyword_match">
                  <option value="contains">包含</option>
                  <option value="equals">等于</option>
                  <option value="prefix">前缀</option>
                </select>
              </label>
              <label>回复类型
                <select v-model="replyForm.reply_type">
                  <option value="text">文本</option>
                  <option value="image">图片</option>
                  <option value="voice">语音</option>
                  <option value="video">视频</option>
                  <option value="music">音乐</option>
                  <option value="news">图文</option>
                </select>
              </label>
              <label>优先级<input v-model.number="replyForm.priority" type="number" /></label>
              <label v-if="replyForm.reply_type === 'text'" class="stack wide">文本内容<textarea v-model="replyContent.text" placeholder="例如：欢迎关注，我们会尽快回复你。"></textarea></label>
              <label v-if="['image', 'voice'].includes(replyForm.reply_type)" class="wide">素材 Media ID<input v-model="replyContent.media_id" placeholder="请填写微信永久素材 Media ID" /></label>
              <template v-if="replyForm.reply_type === 'video'">
                <label>视频 Media ID<input v-model="replyContent.media_id" placeholder="请填写视频素材 Media ID" /></label>
                <label>视频标题<input v-model="replyContent.title" placeholder="视频标题" /></label>
                <label class="wide">视频描述<textarea v-model="replyContent.description" placeholder="视频描述"></textarea></label>
              </template>
              <template v-if="replyForm.reply_type === 'music'">
                <label>音乐标题<input v-model="replyContent.title" /></label>
                <label>缩略图 Media ID<input v-model="replyContent.thumb_media_id" /></label>
                <label>音乐链接<input v-model="replyContent.music_url" placeholder="https://example.com/music.mp3" /></label>
                <label>高清音乐链接<input v-model="replyContent.hq_music_url" placeholder="https://example.com/music-hq.mp3" /></label>
                <label class="wide">音乐描述<textarea v-model="replyContent.description"></textarea></label>
              </template>
              <section v-if="replyForm.reply_type === 'news'" class="wide news-editor">
                <div class="editor-note">图文最多 8 条，至少填写标题和链接。</div>
                <article v-for="(article, index) in replyContent.articles" :key="index" class="news-card">
                  <div class="menu-card-head">
                    <strong>图文 {{ index + 1 }}</strong>
                    <button class="danger-text" type="button" @click="removeArticle(index)">删除</button>
                  </div>
                  <div class="form-grid">
                    <label>标题<input v-model="article.title" /></label>
                    <label>跳转链接<input v-model="article.url" placeholder="https://example.com" /></label>
                    <label class="wide">图片地址<input v-model="article.pic_url" placeholder="https://example.com/a.jpg" /></label>
                    <label class="wide">摘要<textarea v-model="article.description"></textarea></label>
                  </div>
                </article>
                <button class="ghost add-line" type="button" @click="addArticle" :disabled="replyContent.articles.length >= 8">添加图文</button>
              </section>
            </div>

            <div class="rule-form-actions">
              <button class="ghost" type="button" @click="startCreateRule">重置</button>
              <button class="primary" type="submit" :disabled="!selectedRuleAccountId">{{ editingRuleId ? '保存修改' : '新增规则' }}</button>
            </div>
          </form>

          <section class="table-panel rules-table-panel">
            <table>
              <thead>
                <tr>
                  <th>规则</th>
                  <th>触发条件</th>
                  <th>回复</th>
                  <th>优先级</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="rule in filteredReplyRules" :key="rule.id">
                  <td><strong>{{ rule.name }}</strong></td>
                  <td>
                    <span>{{ displayTrigger(rule) }}</span>
                    <small>{{ rule.keyword ? `关键词：${rule.keyword}` : '未设置关键词' }}</small>
                  </td>
                  <td>
                    <span>{{ displayReplyType(rule.reply_type) }}</span>
                    <code>{{ summarizeReplyContent(rule) }}</code>
                  </td>
                  <td>{{ rule.priority || 0 }}</td>
                  <td>
                    <div class="row-actions">
                      <button @click="openReplyEditDialog(rule)">编辑</button>
                      <button class="danger-text" @click="removeRule(rule)">删除</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredReplyRules.length === 0">
                  <td class="empty" colspan="5">暂无回调规则</td>
                </tr>
              </tbody>
            </table>
          </section>
        </section>
      </template>

      <template v-else-if="activePage === 'punchRecords'">
        <section class="toolbar">
          <div class="search">
            <span>搜索</span>
            <input v-model="punchKeyword" placeholder="打卡人 / 上级 / 文案 / 地点" />
          </div>
          <button class="ghost" @click="loadPunchRecords">刷新</button>
        </section>

        <section class="table-panel">
          <table>
            <thead>
              <tr>
                <th>打卡人</th>
                <th>上级人</th>
                <th>打卡时间</th>
                <th>地点</th>
                <th>图片</th>
                <th>心语</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="record in filteredPunchRecords" :key="record.id">
                <td><code>{{ record.openid || '-' }}</code></td>
                <td><code>{{ record.parent_openid || '-' }}</code></td>
                <td>{{ record.punched_at || '-' }}</td>
                <td>
                  <span>{{ record.location_label || '-' }}</span>
                  <small v-if="record.latitude || record.longitude">{{ record.latitude || '-' }}, {{ record.longitude || '-' }}</small>
                </td>
                <td>
                  <div class="image-cell" v-if="record.material_image_url">
                    <img :src="record.material_image_url" :alt="record.material_image_title || '打卡图片'" />
                    <div><strong>{{ record.material_image_title || '图片素材' }}</strong></div>
                  </div>
                  <span v-else>-</span>
                </td>
                <td><strong>{{ record.quote_content || '-' }}</strong></td>
              </tr>
              <tr v-if="filteredPunchRecords.length === 0">
                <td class="empty" colspan="6">暂无打卡记录</td>
              </tr>
            </tbody>
          </table>
        </section>
      </template>

      <template v-else>
        <section class="toolbar rules-toolbar">
          <div class="rule-filters">
            <div class="search">
              <span>搜索</span>
              <input v-model="materialKeyword" :placeholder="materialSearchPlaceholder" />
            </div>
            <label v-if="activePage !== 'materialGroups'">分组
              <select v-model="materialGroupFilter">
                <option value="">全部分组</option>
                <option v-for="group in materialGroups" :key="group.id" :value="String(group.id)">{{ group.name }}</option>
              </select>
            </label>
          </div>
        </section>

        <section v-if="activePage === 'materialGroups'" class="rule-page-grid">
          <form class="rule-form-panel" @submit.prevent="saveMaterialGroup">
            <div class="rule-form-head">
              <div>
                <h2>{{ editingGroupId ? '编辑素材分组' : '新增素材分组' }}</h2>
                <p>图片和心语签都可以选择对应分组</p>
              </div>
              <button v-if="editingGroupId" class="ghost" type="button" @click="resetMaterialGroupForm">取消编辑</button>
            </div>
            <div class="form-grid">
              <label>分组名称<input v-model="groupForm.name" required placeholder="例如：早安心语" /></label>
              <label>分组类型
                <select v-model="groupForm.type">
                  <option value="image">图片</option>
                  <option value="quote">心语签</option>
                </select>
              </label>
              <label>排序<input v-model.number="groupForm.sort_order" type="number" /></label>
              <label>状态
                <select v-model.number="groupForm.is_active">
                  <option :value="1">启用</option>
                  <option :value="0">停用</option>
                </select>
              </label>
              <label class="wide">备注<input v-model="groupForm.remark" /></label>
            </div>
            <div class="rule-form-actions">
              <button class="ghost" type="button" @click="resetMaterialGroupForm">重置</button>
              <button class="primary" type="submit">{{ editingGroupId ? '保存修改' : '新增分组' }}</button>
            </div>
          </form>

          <section class="table-panel rules-table-panel">
            <table>
              <thead>
                <tr>
                  <th>分组</th>
                  <th>类型</th>
                  <th>排序</th>
                  <th>状态</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="group in filteredMaterialGroups" :key="group.id">
                  <td><strong>{{ group.name }}</strong><small>{{ group.remark || '无备注' }}</small></td>
                  <td>{{ group.type === 'quote' ? '心语签' : '图片' }}</td>
                  <td>{{ group.sort_order || 0 }}</td>
                  <td><span class="status-pill" :class="{ off: !group.is_active }">{{ group.is_active ? '启用' : '停用' }}</span></td>
                  <td>
                    <div class="row-actions">
                      <button @click="editMaterialGroup(group)">编辑</button>
                      <button class="danger-text" @click="removeMaterialGroup(group)">删除</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredMaterialGroups.length === 0">
                  <td class="empty" colspan="5">暂无素材分组</td>
                </tr>
              </tbody>
            </table>
          </section>
        </section>

        <section v-if="activePage === 'materialImages'" class="rule-page-grid">
          <form class="rule-form-panel" @submit.prevent="saveMaterialImage">
            <div class="rule-form-head">
              <div>
                <h2>{{ editingImageId ? '编辑图片' : '上传图片' }}</h2>
                <p>上传时选择分组，打卡会随机取启用图片</p>
              </div>
              <button v-if="editingImageId" class="ghost" type="button" @click="resetMaterialImageForm">取消编辑</button>
            </div>
            <div class="form-grid">
              <label>图片标题<input v-model="imageForm.title" placeholder="不填则使用文件名" /></label>
              <label>分组
                <select v-model="imageForm.group_id">
                  <option value="">未分组</option>
                  <option v-for="group in imageGroups" :key="group.id" :value="String(group.id)">{{ group.name }}</option>
                </select>
              </label>
              <label v-if="!editingImageId" class="wide">图片文件<input type="file" accept="image/*" @change="onImageFileChange" /></label>
              <label>状态
                <select v-model.number="imageForm.is_active">
                  <option :value="1">启用</option>
                  <option :value="0">停用</option>
                </select>
              </label>
              <label class="wide">备注<input v-model="imageForm.remark" /></label>
            </div>
            <div class="rule-form-actions">
              <button class="ghost" type="button" @click="resetMaterialImageForm">重置</button>
              <button class="primary" type="submit">{{ editingImageId ? '保存修改' : '上传图片' }}</button>
            </div>
          </form>

          <section class="table-panel rules-table-panel">
            <table>
              <thead>
                <tr>
                  <th>图片</th>
                  <th>分组</th>
                  <th>尺寸</th>
                  <th>状态</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="image in filteredMaterialImages" :key="image.id">
                  <td>
                    <div class="image-cell">
                      <img :src="image.file_url" :alt="image.title || '图片素材'" />
                      <div><strong>{{ image.title || '未命名图片' }}</strong><small>{{ image.remark || '无备注' }}</small></div>
                    </div>
                  </td>
                  <td>{{ image.group_name || groupName(image.group_id) }}</td>
                  <td>{{ image.width || 0 }} × {{ image.height || 0 }}</td>
                  <td><span class="status-pill" :class="{ off: !image.is_active }">{{ image.is_active ? '启用' : '停用' }}</span></td>
                  <td>
                    <div class="row-actions">
                      <button @click="editMaterialImage(image)">编辑</button>
                      <button class="danger-text" @click="removeMaterialImage(image)">删除</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredMaterialImages.length === 0">
                  <td class="empty" colspan="5">暂无图片素材</td>
                </tr>
              </tbody>
            </table>
          </section>
        </section>

        <section v-if="activePage === 'heartQuotes'" class="rule-page-grid">
          <form class="rule-form-panel" @submit.prevent="saveHeartQuote">
            <div class="rule-form-head">
              <div>
                <h2>{{ editingQuoteId ? '编辑心语签' : '新增心语签' }}</h2>
                <p>录入一段文案，打卡会随机取启用文案</p>
              </div>
              <button v-if="editingQuoteId" class="ghost" type="button" @click="resetHeartQuoteForm">取消编辑</button>
            </div>
            <div class="form-grid">
              <label class="wide">文案<textarea v-model="quoteForm.content" required placeholder="例如：愿你今天也拥有温柔而坚定的力量。"></textarea></label>
              <label>分组
                <select v-model="quoteForm.group_id">
                  <option value="">未分组</option>
                  <option v-for="group in quoteGroups" :key="group.id" :value="String(group.id)">{{ group.name }}</option>
                </select>
              </label>
              <label>署名<input v-model="quoteForm.author" placeholder="可不填" /></label>
              <label>状态
                <select v-model.number="quoteForm.is_active">
                  <option :value="1">启用</option>
                  <option :value="0">停用</option>
                </select>
              </label>
              <label class="wide">备注<input v-model="quoteForm.remark" /></label>
            </div>
            <div class="rule-form-actions">
              <button class="ghost" type="button" @click="resetHeartQuoteForm">重置</button>
              <button class="primary" type="submit">{{ editingQuoteId ? '保存修改' : '新增文案' }}</button>
            </div>
          </form>

          <section class="table-panel rules-table-panel">
            <table>
              <thead>
                <tr>
                  <th>文案</th>
                  <th>分组</th>
                  <th>状态</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="quote in filteredHeartQuotes" :key="quote.id">
                  <td><strong>{{ quote.content }}</strong><small>{{ quote.author ? `署名：${quote.author}` : '未设置署名' }}</small></td>
                  <td>{{ quote.group_name || groupName(quote.group_id) }}</td>
                  <td><span class="status-pill" :class="{ off: !quote.is_active }">{{ quote.is_active ? '启用' : '停用' }}</span></td>
                  <td>
                    <div class="row-actions">
                      <button @click="editHeartQuote(quote)">编辑</button>
                      <button class="danger-text" @click="removeHeartQuote(quote)">删除</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredHeartQuotes.length === 0">
                  <td class="empty" colspan="4">暂无心语签</td>
                </tr>
              </tbody>
            </table>
          </section>
        </section>
      </template>
    </main>
  </section>

  <div v-if="dialog" class="modal-mask" @click.self="closeDialog">
    <section class="modal">
      <header class="modal-head">
        <h2>{{ dialogTitle }}</h2>
        <button @click="closeDialog">×</button>
      </header>

      <form v-if="dialog === 'account'" class="modal-body form-grid" @submit.prevent="saveAccount">
        <label>名称<input v-model="accountForm.name" required /></label>
        <label>AppID<input v-model="accountForm.app_id" required /></label>
        <label>Secret<input v-model="accountForm.app_secret" required /></label>
        <label>Token<input v-model="accountForm.token" required /></label>
        <label>AES Key<input v-model="accountForm.aes_key" /></label>
        <label>原始 ID<input v-model="accountForm.original_id" /></label>
        <label>加密模式
          <select v-model="accountForm.encoding_type">
            <option value="plaintext">明文</option>
            <option value="compatible">兼容</option>
            <option value="safe">安全</option>
          </select>
        </label>
        <label>备注<input v-model="accountForm.remark" /></label>
      </form>

      <form v-if="dialog === 'menu'" class="modal-body menu-editor" @submit.prevent="saveMenu(false)">
        <div class="editor-note">一级菜单最多 3 个。点击事件 Key 来自当前公众号的 CLICK 回调规则；如果没有可选项，请先到“回调规则”新增一条事件为 CLICK 的规则。</div>
        <section v-for="(button, index) in menuButtons" :key="index" class="menu-card">
          <div class="menu-card-head">
            <strong>一级菜单 {{ index + 1 }}</strong>
            <button class="danger-text" type="button" @click="removeMenuButton(index)">删除</button>
          </div>
          <div class="form-grid">
            <label>菜单名称<input v-model="button.name" placeholder="例如：首页" /></label>
            <label>菜单类型
              <select v-model="button.type">
                <option value="view">跳转网页</option>
                <option value="click">点击事件</option>
                <option value="parent">带二级菜单</option>
              </select>
            </label>
            <label v-if="button.type === 'view'" class="wide">网页链接<input v-model="button.url" placeholder="https://example.com" /></label>
            <label v-if="button.type === 'click'" class="wide">事件 Key
              <select v-model="button.key">
                <option value="" disabled>请选择 CLICK 回调规则</option>
                <option v-for="option in menuClickKeyOptions(button.key)" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
          </div>
          <div v-if="button.type === 'parent'" class="sub-menu-list">
            <article v-for="(sub, subIndex) in button.sub_button" :key="subIndex" class="sub-menu-card">
              <div class="menu-card-head">
                <strong>二级菜单 {{ subIndex + 1 }}</strong>
                <button class="danger-text" type="button" @click="removeSubButton(button, subIndex)">删除</button>
              </div>
              <div class="form-grid">
                <label>菜单名称<input v-model="sub.name" placeholder="例如：联系客服" /></label>
                <label>菜单类型
                  <select v-model="sub.type">
                    <option value="view">跳转网页</option>
                    <option value="click">点击事件</option>
                  </select>
                </label>
                <label v-if="sub.type === 'view'" class="wide">网页链接<input v-model="sub.url" placeholder="https://example.com" /></label>
                <label v-if="sub.type === 'click'" class="wide">事件 Key
                  <select v-model="sub.key">
                    <option value="" disabled>请选择 CLICK 回调规则</option>
                    <option v-for="option in menuClickKeyOptions(sub.key)" :key="option.value" :value="option.value">{{ option.label }}</option>
                  </select>
                </label>
              </div>
            </article>
            <button class="ghost add-line" type="button" @click="addSubButton(button)" :disabled="button.sub_button.length >= 5">添加二级菜单</button>
          </div>
        </section>
        <button class="ghost add-line" type="button" @click="addMenuButton" :disabled="menuButtons.length >= 3">添加一级菜单</button>
      </form>

      <footer class="modal-foot">
        <button class="ghost" @click="closeDialog">取消</button>
        <button v-if="dialog === 'account'" class="primary" @click="saveAccount">保存</button>
        <button v-if="dialog === 'menu'" class="ghost" @click="saveMenu(false)">保存</button>
        <button v-if="dialog === 'menu'" class="primary" @click="publishMenu">发布到微信</button>
      </footer>
    </section>
  </div>

  <div v-if="toast" class="toast">{{ toast }}</div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const origin = window.location.origin;
const authed = ref(false);
const accounts = ref([]);
const replyRules = ref([]);
const keyword = ref('');
const captcha = ref('');
const loginError = ref('');
const toast = ref('');
const dialog = ref('');
const activeAccount = ref(null);
const activePage = ref('accounts');
const editingAccountId = ref(null);
const editingRuleId = ref(null);
const menuButtons = ref([]);
const menuReplyRules = ref([]);
const replyContent = reactive(defaultReplyContent());
const ruleAccounts = ref([]);
const selectedRuleAccountId = ref('');
const ruleKeyword = ref('');
const materialGroups = ref([]);
const materialImages = ref([]);
const heartQuotes = ref([]);
const punchRecords = ref([]);
const materialKeyword = ref('');
const materialGroupFilter = ref('');
const punchKeyword = ref('');
const editingGroupId = ref(null);
const editingImageId = ref(null);
const editingQuoteId = ref(null);
const selectedImageFile = ref(null);

const loginForm = reactive({ username: 'admin', password: '', captcha: '', remember: true });
const accountForm = reactive({ name: '', app_id: '', app_secret: '', token: '', aes_key: '', original_id: '', encoding_type: 'plaintext', remark: '' });
const replyForm = reactive({ name: '', msg_type: '*', event: '', keyword: '', keyword_match: 'contains', reply_type: 'text', priority: 0 });
const groupForm = reactive({ name: '', type: 'image', sort_order: 0, is_active: 1, remark: '' });
const imageForm = reactive({ title: '', group_id: '', is_active: 1, remark: '' });
const quoteForm = reactive({ content: '', group_id: '', author: '', is_active: 1, remark: '' });

const dialogTitle = computed(() => ({ account: editingAccountId.value ? '编辑公众号' : '添加公众号', menu: '编辑菜单' }[dialog.value] || ''));
const pageTitle = computed(() => ({
  accounts: '公众号列表',
  rules: '回调规则',
  materialGroups: '素材分组',
  materialImages: '图片列表',
  heartQuotes: '心语签列表',
  punchRecords: '打卡记录',
}[activePage.value] || '公众号后台'));
const pageSubtitle = computed(() => ({
  accounts: '管理公众号配置、菜单和自动回复规则',
  rules: '按公众号查看并维护回调规则',
  materialGroups: '维护图片和心语签可用分组',
  materialImages: '上传图片素材并设置分组、状态',
  heartQuotes: '录入打卡心语文案并设置分组、状态',
  punchRecords: '查看打卡人、打卡时间、地点和上级来源',
}[activePage.value] || ''));
const currentRuleAccount = computed(() => ruleAccounts.value.find((item) => String(item.id) === String(selectedRuleAccountId.value)) || null);
const imageGroups = computed(() => materialGroups.value.filter((item) => item.type === 'image' && item.is_active));
const quoteGroups = computed(() => materialGroups.value.filter((item) => item.type === 'quote' && item.is_active));
const materialSearchPlaceholder = computed(() => ({
  materialGroups: '分组名称 / 备注',
  materialImages: '图片标题 / 分组 / 备注',
  heartQuotes: '文案 / 分组 / 署名',
}[activePage.value] || '搜索'));
const filteredAccounts = computed(() => {
  const q = keyword.value.trim().toLowerCase();
  if (!q) return accounts.value;
  return accounts.value.filter((item) => `${item.name} ${item.app_id}`.toLowerCase().includes(q));
});
const filteredReplyRules = computed(() => {
  const q = ruleKeyword.value.trim().toLowerCase();
  if (!q) return replyRules.value;
  return replyRules.value.filter((rule) => {
    const fields = [
      rule.name,
      rule.msg_type,
      rule.event,
      rule.keyword,
      rule.reply_type,
      summarizeReplyContent(rule),
    ];
    return fields.filter(Boolean).join(' ').toLowerCase().includes(q);
  });
});
const filteredMaterialGroups = computed(() => {
  const q = materialKeyword.value.trim().toLowerCase();
  if (!q) return materialGroups.value;
  return materialGroups.value.filter((item) => `${item.name} ${item.remark || ''}`.toLowerCase().includes(q));
});
const filteredMaterialImages = computed(() => {
  const q = materialKeyword.value.trim().toLowerCase();
  return materialImages.value.filter((item) => {
    if (materialGroupFilter.value && String(item.group_id || '') !== materialGroupFilter.value) return false;
    if (!q) return true;
    return `${item.title || ''} ${item.group_name || groupName(item.group_id)} ${item.remark || ''}`.toLowerCase().includes(q);
  });
});
const filteredHeartQuotes = computed(() => {
  const q = materialKeyword.value.trim().toLowerCase();
  return heartQuotes.value.filter((item) => {
    if (materialGroupFilter.value && String(item.group_id || '') !== materialGroupFilter.value) return false;
    if (!q) return true;
    return `${item.content || ''} ${item.group_name || groupName(item.group_id)} ${item.author || ''} ${item.remark || ''}`.toLowerCase().includes(q);
  });
});
const filteredPunchRecords = computed(() => {
  const q = punchKeyword.value.trim().toLowerCase();
  if (!q) return punchRecords.value;
  return punchRecords.value.filter((item) => {
    return `${item.openid || ''} ${item.parent_openid || ''} ${item.quote_content || ''} ${item.location_label || ''}`.toLowerCase().includes(q);
  });
});

async function request(url, options = {}) {
  const res = await fetch(url, {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error?.message || '请求失败');
  return json.data;
}

async function requestForm(url, formData, method = 'POST') {
  const res = await fetch(url, {
    method,
    credentials: 'same-origin',
    body: formData,
  });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error?.message || '请求失败');
  return json.data;
}

function makeCaptcha() {
  captcha.value = Math.random().toString(36).slice(2, 6).toUpperCase();
}

function showToast(message) {
  toast.value = message;
  window.setTimeout(() => {
    toast.value = '';
  }, 2200);
}

async function login() {
  loginError.value = '';
  if (loginForm.captcha.toUpperCase() !== captcha.value) {
    loginError.value = '验证码错误';
    makeCaptcha();
    return;
  }
  try {
    await request('/admin-api/login', { method: 'POST', body: JSON.stringify({ password: loginForm.password }) });
    authed.value = true;
    await loadAccounts();
  } catch (error) {
    loginError.value = error.message;
    makeCaptcha();
  }
}

async function logout() {
  await request('/admin-api/logout', { method: 'POST', body: '{}' }).catch(() => {});
  authed.value = false;
  makeCaptcha();
}

async function loadAccounts() {
  const data = await request('/admin-api/wechat/official-accounts');
  accounts.value = data.items;
  ruleAccounts.value = data.items;
}

async function loadMaterials() {
  const [groupsData, imagesData, quotesData] = await Promise.all([
    request('/admin-api/materials/groups'),
    request('/admin-api/materials/images'),
    request('/admin-api/materials/quotes'),
  ]);
  materialGroups.value = groupsData.items;
  materialImages.value = imagesData.items;
  heartQuotes.value = quotesData.items;
}

async function loadPunchRecords() {
  const data = await request('/admin-api/punch-records');
  punchRecords.value = data.items;
}

function openAccountDialog(item = null) {
  editingAccountId.value = item?.id || null;
  Object.assign(accountForm, {
    name: item?.name || '',
    app_id: item?.app_id || '',
    app_secret: item?.app_secret || '',
    token: item?.token || '',
    aes_key: item?.aes_key || '',
    original_id: item?.original_id || '',
    encoding_type: item?.encoding_type || 'plaintext',
    remark: item?.remark || '',
  });
  dialog.value = 'account';
}

async function saveAccount() {
  const url = editingAccountId.value ? `/admin-api/wechat/official-accounts/${editingAccountId.value}` : '/admin-api/wechat/official-accounts';
  const method = editingAccountId.value ? 'PUT' : 'POST';
  await request(url, { method, body: JSON.stringify(accountForm) });
  dialog.value = '';
  editingAccountId.value = null;
  showToast('公众号已保存');
  await loadAccounts();
}

async function removeAccount(item) {
  if (!window.confirm(`确定删除 ${item.name} 吗？`)) return;
  await request(`/admin-api/wechat/official-accounts/${item.id}`, { method: 'DELETE' });
  showToast('公众号已删除');
  await loadAccounts();
}

async function openMenuDialog(item) {
  activeAccount.value = item;
  const [menuData, rulesData] = await Promise.all([
    request(`/admin-api/wechat/official-accounts/${item.id}/menu`),
    request(`/admin-api/wechat/reply-rules?account_id=${item.id}`),
  ]);
  menuReplyRules.value = rulesData.items;
  menuButtons.value = normalizeMenuButtons(menuData.menu_config?.button || []);
  dialog.value = 'menu';
}

async function saveMenu(close = true) {
  try {
    const menu = buildMenuPayload();
    await request(`/admin-api/wechat/official-accounts/${activeAccount.value.id}/menu`, { method: 'PUT', body: JSON.stringify({ menu_config: menu }) });
    showToast('菜单已保存');
    if (close) dialog.value = '';
    await loadAccounts();
    return true;
  } catch (error) {
    showToast(error.message);
    return false;
  }
}

async function publishMenu() {
  try {
    const saved = await saveMenu(false);
    if (!saved) return;
    await request(`/admin-api/wechat/official-accounts/${activeAccount.value.id}/menu/publish`, { method: 'POST', body: '{}' });
    dialog.value = '';
    showToast('菜单已发布');
    await loadAccounts();
  } catch (error) {
    showToast(error.message);
  }
}

async function openReplyEditDialog(rule) {
  if (!currentRuleAccount.value) return;
  editingRuleId.value = rule.id;
  replyForm.name = rule.name || '';
  replyForm.msg_type = rule.msg_type || '*';
  replyForm.event = rule.event || '';
  replyForm.keyword = rule.keyword || '';
  replyForm.keyword_match = rule.keyword_match || 'contains';
  replyForm.reply_type = rule.reply_type || 'text';
  replyForm.priority = rule.priority || 0;
  populateReplyContent(rule.reply_type, rule.reply_content || {});
  activePage.value = 'rules';
}

function resetReplyForm() {
  Object.assign(replyForm, { name: '', msg_type: '*', event: '', keyword: '', keyword_match: 'contains', reply_type: 'text', priority: 0 });
  Object.assign(replyContent, defaultReplyContent());
}

async function saveReplyRule() {
  try {
    const payload = {
      ...replyForm,
      account_id: Number(selectedRuleAccountId.value),
      reply_content: buildReplyContent(),
    };
    const url = editingRuleId.value ? `/admin-api/wechat/reply-rules/${editingRuleId.value}` : '/admin-api/wechat/reply-rules';
    const method = editingRuleId.value ? 'PUT' : 'POST';
    await request(url, { method, body: JSON.stringify(payload) });
    resetReplyForm();
    editingRuleId.value = null;
    showToast('回复规则已保存');
    await reloadRules();
  } catch (error) {
    showToast(error.message);
  }
}

async function removeRule(rule) {
  if (!window.confirm(`确定删除规则 ${rule.name} 吗？`)) return;
  await request(`/admin-api/wechat/reply-rules/${rule.id}`, { method: 'DELETE' });
  showToast('规则已删除');
  await reloadRules();
}

function defaultReplyContent() {
  return {
    text: '欢迎关注',
    media_id: '',
    title: '',
    description: '',
    music_url: '',
    hq_music_url: '',
    thumb_media_id: '',
    articles: [emptyArticle()],
  };
}

function emptyArticle() {
  return { title: '', description: '', pic_url: '', url: '' };
}

function normalizeMenuButtons(buttons) {
  const normalized = buttons.map((button) => {
    const subButtons = Array.isArray(button.sub_button) ? button.sub_button : [];
    if (subButtons.length > 0) {
      return {
        name: button.name || '',
        type: 'parent',
        url: '',
        key: '',
        sub_button: subButtons.map((sub) => ({
          name: sub.name || '',
          type: sub.type === 'click' ? 'click' : 'view',
          url: sub.url || '',
          key: sub.key || '',
        })),
      };
    }

    return {
      name: button.name || '',
      type: button.type === 'click' ? 'click' : 'view',
      url: button.url || '',
      key: button.key || '',
      sub_button: [],
    };
  });

  return normalized.length > 0 ? normalized : [emptyMenuButton()];
}

function emptyMenuButton() {
  return { name: '', type: 'view', url: '', key: '', sub_button: [] };
}

function emptySubButton() {
  return { name: '', type: 'view', url: '', key: '' };
}

function menuClickKeyOptions(currentKey = '') {
  const map = new Map();
  map.set('DAKA', '打卡（DAKA）');
  for (const rule of menuReplyRules.value) {
    if ((rule.msg_type || '') !== 'event' || (rule.event || '').toUpperCase() !== 'CLICK' || !rule.keyword) {
      continue;
    }
    map.set(rule.keyword, `${rule.name}（${rule.keyword}）`);
  }

  if (currentKey && !map.has(currentKey)) {
    map.set(currentKey, `当前菜单 Key（${currentKey}）`);
  }

  return Array.from(map, ([value, label]) => ({ value, label }));
}

function addMenuButton() {
  if (menuButtons.value.length < 3) {
    menuButtons.value.push(emptyMenuButton());
  }
}

function removeMenuButton(index) {
  menuButtons.value.splice(index, 1);
  if (menuButtons.value.length === 0) {
    addMenuButton();
  }
}

function addSubButton(button) {
  if (!Array.isArray(button.sub_button)) {
    button.sub_button = [];
  }
  if (button.sub_button.length < 5) {
    button.sub_button.push(emptySubButton());
  }
}

function removeSubButton(button, index) {
  button.sub_button.splice(index, 1);
}

function buildMenuPayload() {
  const button = menuButtons.value
    .map((item) => buildMenuButton(item))
    .filter(Boolean);

  if (button.length === 0) {
    throw new Error('请至少填写一个菜单');
  }

  return { button };
}

function buildMenuButton(item) {
  const name = item.name.trim();
  if (!name) return null;

  if (item.type === 'parent') {
    const sub_button = (item.sub_button || [])
      .map((sub) => buildMenuButton(sub))
      .filter(Boolean);
    if (sub_button.length === 0) {
      throw new Error(`菜单“${name}”至少需要一个二级菜单`);
    }
    return { name, sub_button };
  }

  if (item.type === 'click') {
    const key = item.key.trim();
    if (!key) {
      throw new Error(`菜单“${name}”需要填写事件 Key`);
    }
    return { type: 'click', name, key };
  }

  const url = item.url.trim();
  if (!url) {
    throw new Error(`菜单“${name}”需要填写网页链接`);
  }
  return { type: 'view', name, url };
}

function buildReplyContent() {
  if (replyForm.reply_type === 'text') {
    if (!replyContent.text.trim()) {
      throw new Error('请填写文本回复内容');
    }
    return { text: replyContent.text };
  }

  if (['image', 'voice'].includes(replyForm.reply_type)) {
    if (!replyContent.media_id.trim()) {
      throw new Error('请填写素材 Media ID');
    }
    return { media_id: replyContent.media_id };
  }

  if (replyForm.reply_type === 'video') {
    if (!replyContent.media_id.trim()) {
      throw new Error('请填写视频 Media ID');
    }
    return {
      media_id: replyContent.media_id,
      title: replyContent.title,
      description: replyContent.description,
    };
  }

  if (replyForm.reply_type === 'music') {
    return {
      title: replyContent.title,
      description: replyContent.description,
      music_url: replyContent.music_url,
      hq_music_url: replyContent.hq_music_url,
      thumb_media_id: replyContent.thumb_media_id,
    };
  }

  const articles = replyContent.articles
    .filter((article) => article.title || article.url || article.pic_url || article.description)
    .map((article) => ({ ...article }));

  if (articles.length === 0) {
    throw new Error('请至少添加一条图文');
  }

  for (const article of articles) {
    if (!article.title.trim() || !article.url.trim()) {
      throw new Error('每条图文都需要标题和跳转链接');
    }
  }

  return { articles };
}

function addArticle() {
  if (replyContent.articles.length < 8) {
    replyContent.articles.push(emptyArticle());
  }
}

function removeArticle(index) {
  replyContent.articles.splice(index, 1);
  if (replyContent.articles.length === 0) {
    addArticle();
  }
}

function summarizeReplyContent(rule) {
  const content = rule.reply_content || {};
  if (rule.reply_type === 'text') return content.text || '-';
  if (['image', 'voice'].includes(rule.reply_type)) return `Media ID: ${content.media_id || '-'}`;
  if (rule.reply_type === 'video') return `${content.title || '视频'} / ${content.media_id || '-'}`;
  if (rule.reply_type === 'music') return content.title || content.music_url || '-';
  if (rule.reply_type === 'news') return `${content.articles?.length || 0} 条图文`;
  return JSON.stringify(content);
}

function displayTrigger(rule) {
  const parts = [];
  if (rule.msg_type && rule.msg_type !== '*') parts.push(`消息：${rule.msg_type}`);
  if (rule.event) parts.push(`事件：${rule.event}`);
  if (rule.keyword) parts.push(`关键词：${rule.keyword}`);
  return parts.length > 0 ? parts.join(' / ') : '全部消息';
}

function displayReplyType(replyType) {
  return {
    text: '文本',
    image: '图片',
    voice: '语音',
    video: '视频',
    music: '音乐',
    news: '图文',
  }[replyType] || replyType || '-';
}

function populateReplyContent(replyType, content) {
  const next = defaultReplyContent();
  if (replyType === 'text') next.text = content.text || '';
  if (['image', 'voice'].includes(replyType)) next.media_id = content.media_id || '';
  if (replyType === 'video') {
    next.media_id = content.media_id || '';
    next.title = content.title || '';
    next.description = content.description || '';
  }
  if (replyType === 'music') {
    next.title = content.title || '';
    next.description = content.description || '';
    next.music_url = content.music_url || '';
    next.hq_music_url = content.hq_music_url || '';
    next.thumb_media_id = content.thumb_media_id || '';
  }
  if (replyType === 'news') {
    const articles = Array.isArray(content.articles) && content.articles.length > 0 ? content.articles : [emptyArticle()];
    next.articles = articles.map((article) => ({
      title: article.title || '',
      description: article.description || '',
      pic_url: article.pic_url || '',
      url: article.url || '',
    }));
  }
  Object.assign(replyContent, next);
}

function closeDialog() {
  dialog.value = '';
  editingAccountId.value = null;
}

async function openRulesPage(accountId = null) {
  activePage.value = 'rules';
  await ensureRuleAccounts();
  if (accountId) selectedRuleAccountId.value = String(accountId);
  if (!selectedRuleAccountId.value && ruleAccounts.value.length > 0) {
    selectedRuleAccountId.value = String(ruleAccounts.value[0].id);
  }
  startCreateRule();
  await reloadRules();
}

async function ensureRuleAccounts(force = false) {
  if (force || ruleAccounts.value.length === 0) {
    const data = await request('/admin-api/wechat/official-accounts');
    ruleAccounts.value = data.items;
  }
}

async function reloadRules() {
  if (!selectedRuleAccountId.value) {
    replyRules.value = [];
    return;
  }
  const data = await request(`/admin-api/wechat/reply-rules?account_id=${selectedRuleAccountId.value}`);
  replyRules.value = data.items;
}

async function onRuleAccountChange() {
  startCreateRule();
  await reloadRules();
}

function startCreateRule() {
  editingRuleId.value = null;
  resetReplyForm();
}

function openMaterialPage(page = 'materialGroups') {
  activePage.value = page;
  loadMaterials().catch((error) => showToast(error.message));
  materialKeyword.value = '';
  materialGroupFilter.value = '';
  resetMaterialGroupForm();
  resetMaterialImageForm();
  resetHeartQuoteForm();
}

function openPunchRecords() {
  activePage.value = 'punchRecords';
  punchKeyword.value = '';
  loadPunchRecords().catch((error) => showToast(error.message));
}

function resetMaterialGroupForm() {
  editingGroupId.value = null;
  Object.assign(groupForm, { name: '', type: 'image', sort_order: 0, is_active: 1, remark: '' });
}

function editMaterialGroup(group) {
  editingGroupId.value = group.id;
  Object.assign(groupForm, {
    name: group.name || '',
    type: group.type || 'image',
    sort_order: group.sort_order || 0,
    is_active: group.is_active ? 1 : 0,
    remark: group.remark || '',
  });
}

async function saveMaterialGroup() {
  try {
    const url = editingGroupId.value ? `/admin-api/materials/groups/${editingGroupId.value}` : '/admin-api/materials/groups';
    const method = editingGroupId.value ? 'PUT' : 'POST';
    await request(url, { method, body: JSON.stringify(groupForm) });
    showToast('分组已保存');
    resetMaterialGroupForm();
    await loadMaterials();
  } catch (error) {
    showToast(error.message);
  }
}

async function removeMaterialGroup(group) {
  if (!window.confirm(`确定删除分组 ${group.name} 吗？`)) return;
  await request(`/admin-api/materials/groups/${group.id}`, { method: 'DELETE' });
  showToast('分组已删除');
  await loadMaterials();
}

function resetMaterialImageForm() {
  editingImageId.value = null;
  selectedImageFile.value = null;
  Object.assign(imageForm, { title: '', group_id: '', is_active: 1, remark: '' });
}

function onImageFileChange(event) {
  selectedImageFile.value = event.target.files?.[0] || null;
}

function editMaterialImage(image) {
  editingImageId.value = image.id;
  selectedImageFile.value = null;
  Object.assign(imageForm, {
    title: image.title || '',
    group_id: image.group_id ? String(image.group_id) : '',
    is_active: image.is_active ? 1 : 0,
    remark: image.remark || '',
  });
}

async function saveMaterialImage() {
  try {
    if (editingImageId.value) {
      await request(`/admin-api/materials/images/${editingImageId.value}`, { method: 'PUT', body: JSON.stringify(imageForm) });
    } else {
      if (!selectedImageFile.value) {
        throw new Error('请选择图片文件');
      }
      const formData = new FormData();
      formData.append('title', imageForm.title || '');
      formData.append('group_id', imageForm.group_id || '');
      formData.append('is_active', String(imageForm.is_active ?? 1));
      formData.append('remark', imageForm.remark || '');
      formData.append('image', selectedImageFile.value);
      await requestForm('/admin-api/materials/images', formData);
    }
    showToast('图片已保存');
    resetMaterialImageForm();
    await loadMaterials();
  } catch (error) {
    showToast(error.message);
  }
}

async function removeMaterialImage(image) {
  if (!window.confirm(`确定删除图片 ${image.title || image.id} 吗？`)) return;
  await request(`/admin-api/materials/images/${image.id}`, { method: 'DELETE' });
  showToast('图片已删除');
  await loadMaterials();
}

function resetHeartQuoteForm() {
  editingQuoteId.value = null;
  Object.assign(quoteForm, { content: '', group_id: '', author: '', is_active: 1, remark: '' });
}

function editHeartQuote(quote) {
  editingQuoteId.value = quote.id;
  Object.assign(quoteForm, {
    content: quote.content || '',
    group_id: quote.group_id ? String(quote.group_id) : '',
    author: quote.author || '',
    is_active: quote.is_active ? 1 : 0,
    remark: quote.remark || '',
  });
}

async function saveHeartQuote() {
  try {
    const url = editingQuoteId.value ? `/admin-api/materials/quotes/${editingQuoteId.value}` : '/admin-api/materials/quotes';
    const method = editingQuoteId.value ? 'PUT' : 'POST';
    await request(url, { method, body: JSON.stringify(quoteForm) });
    showToast('心语签已保存');
    resetHeartQuoteForm();
    await loadMaterials();
  } catch (error) {
    showToast(error.message);
  }
}

async function removeHeartQuote(quote) {
  if (!window.confirm(`确定删除心语签吗？`)) return;
  await request(`/admin-api/materials/quotes/${quote.id}`, { method: 'DELETE' });
  showToast('心语签已删除');
  await loadMaterials();
}

function groupName(groupId) {
  const group = materialGroups.value.find((item) => String(item.id) === String(groupId));
  return group ? group.name : '未分组';
}

onMounted(async () => {
  makeCaptcha();
  try {
    await loadAccounts();
    authed.value = true;
    await ensureRuleAccounts(true);
    await loadMaterials();
    await loadPunchRecords();
    if (ruleAccounts.value.length > 0) {
      selectedRuleAccountId.value = String(ruleAccounts.value[0].id);
      await reloadRules();
    }
  } catch {
    authed.value = false;
  }
});
</script>
