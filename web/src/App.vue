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
      <button class="nav-item active">公众号列表</button>
    </aside>

    <main class="workspace">
      <header class="topbar">
        <div>
          <h1>公众号列表</h1>
          <p>管理公众号配置、菜单和自动回复规则</p>
        </div>
        <button class="ghost" @click="logout">退出登录</button>
      </header>

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
                  <button @click="openMenuDialog(item)">编辑菜单</button>
                  <button @click="openReplyDialog(item)">回复设置</button>
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
    </main>
  </section>

  <div v-if="dialog" class="modal-mask" @click.self="closeDialog">
    <section class="modal" :class="{ large: dialog === 'reply' }">
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

      <form v-if="dialog === 'menu'" class="modal-body" @submit.prevent="saveMenu(false)">
        <label class="stack">菜单 JSON<textarea v-model="menuText"></textarea></label>
        <p class="hint">一级菜单最多 3 个，二级菜单最多 5 个。保存后可发布到微信。</p>
      </form>

      <section v-if="dialog === 'reply'" class="modal-body reply-body">
        <form class="form-grid" @submit.prevent="saveReplyRule">
          <label>规则名<input v-model="replyForm.name" required /></label>
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
          <label>事件<input v-model="replyForm.event" placeholder="subscribe / CLICK" /></label>
          <label>关键词<input v-model="replyForm.keyword" /></label>
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
          <label class="stack wide">回复内容 JSON<textarea v-model="replyContentText" placeholder='{"text":"欢迎关注"}'></textarea></label>
          <button class="primary inline" type="submit">新增规则</button>
        </form>
        <div class="rule-list">
          <article v-for="rule in replyRules" :key="rule.id">
            <div>
              <strong>{{ rule.name }}</strong>
              <span>{{ rule.msg_type }} {{ rule.event || '' }} {{ rule.keyword || '' }}</span>
              <code>{{ JSON.stringify(rule.reply_content || {}) }}</code>
            </div>
            <button class="danger-text" @click="removeRule(rule)">删除</button>
          </article>
        </div>
      </section>

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
const menuText = ref('');
const replyContentText = ref('{"text":"欢迎关注"}');

const loginForm = reactive({ username: 'admin', password: '', captcha: '', remember: true });
const accountForm = reactive({ name: '', app_id: '', app_secret: '', token: '', aes_key: '', original_id: '', encoding_type: 'plaintext', remark: '' });
const replyForm = reactive({ name: '', msg_type: '*', event: '', keyword: '', keyword_match: 'contains', reply_type: 'text', priority: 0 });

const dialogTitle = computed(() => ({ account: '添加公众号', menu: '编辑菜单', reply: '回复设置' }[dialog.value] || ''));
const filteredAccounts = computed(() => {
  const q = keyword.value.trim().toLowerCase();
  if (!q) return accounts.value;
  return accounts.value.filter((item) => `${item.name} ${item.app_id}`.toLowerCase().includes(q));
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
}

function openAccountDialog() {
  Object.assign(accountForm, { name: '', app_id: '', app_secret: '', token: '', aes_key: '', original_id: '', encoding_type: 'plaintext', remark: '' });
  dialog.value = 'account';
}

async function saveAccount() {
  await request('/admin-api/wechat/official-accounts', { method: 'POST', body: JSON.stringify(accountForm) });
  dialog.value = '';
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
  const data = await request(`/admin-api/wechat/official-accounts/${item.id}/menu`);
  menuText.value = JSON.stringify(data.menu_config || { button: [] }, null, 2);
  dialog.value = 'menu';
}

async function saveMenu(close = true) {
  const menu = JSON.parse(menuText.value || '{"button":[]}');
  await request(`/admin-api/wechat/official-accounts/${activeAccount.value.id}/menu`, { method: 'PUT', body: JSON.stringify({ menu_config: menu }) });
  showToast('菜单已保存');
  if (close) dialog.value = '';
  await loadAccounts();
}

async function publishMenu() {
  await saveMenu(false);
  await request(`/admin-api/wechat/official-accounts/${activeAccount.value.id}/menu/publish`, { method: 'POST', body: '{}' });
  dialog.value = '';
  showToast('菜单已发布');
  await loadAccounts();
}

async function openReplyDialog(item) {
  activeAccount.value = item;
  resetReplyForm();
  await loadReplyRules(item.id);
  dialog.value = 'reply';
}

async function loadReplyRules(accountId) {
  const data = await request(`/admin-api/wechat/reply-rules?account_id=${accountId}`);
  replyRules.value = data.items;
}

function resetReplyForm() {
  Object.assign(replyForm, { name: '', msg_type: '*', event: '', keyword: '', keyword_match: 'contains', reply_type: 'text', priority: 0 });
  replyContentText.value = '{"text":"欢迎关注"}';
}

async function saveReplyRule() {
  const payload = {
    ...replyForm,
    account_id: activeAccount.value.id,
    reply_content: replyContentText.value ? JSON.parse(replyContentText.value) : {},
  };
  await request('/admin-api/wechat/reply-rules', { method: 'POST', body: JSON.stringify(payload) });
  resetReplyForm();
  showToast('回复规则已保存');
  await loadReplyRules(activeAccount.value.id);
}

async function removeRule(rule) {
  await request(`/admin-api/wechat/reply-rules/${rule.id}`, { method: 'DELETE' });
  await loadReplyRules(activeAccount.value.id);
}

function closeDialog() {
  dialog.value = '';
}

onMounted(async () => {
  makeCaptcha();
  try {
    await loadAccounts();
    authed.value = true;
  } catch {
    authed.value = false;
  }
});
</script>
