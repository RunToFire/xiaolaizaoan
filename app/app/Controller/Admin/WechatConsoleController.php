<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;

class WechatConsoleController extends AbstractController
{
    public function index(): ResponseInterface
    {
        return $this->response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody(new SwooleStream($this->html()));
    }

    private function html(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>&#20844;&#20247;&#21495;&#31649;&#29702;</title>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, "Microsoft YaHei", sans-serif; color: #1f2937; background: #f6f7f9; }
    header { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 18px 24px; position: sticky; top: 0; z-index: 2; }
    h1 { font-size: 22px; margin: 0; }
    main { max-width: 1180px; margin: 0 auto; padding: 24px; display: grid; gap: 20px; }
    section { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px; }
    h2 { font-size: 17px; margin: 0 0 14px; }
    form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; }
    label { display: grid; gap: 6px; font-size: 13px; color: #4b5563; }
    input, select, textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 9px 10px; font-size: 14px; background: #fff; }
    textarea { min-height: 92px; resize: vertical; font-family: Consolas, monospace; grid-column: span 2; }
    button { border: 0; border-radius: 6px; padding: 10px 12px; font-size: 14px; cursor: pointer; background: #2563eb; color: #fff; }
    button.secondary { background: #4b5563; }
    button.danger { background: #dc2626; }
    table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 14px; }
    th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; vertical-align: top; }
    th { color: #4b5563; font-weight: 600; background: #f9fafb; }
    code { word-break: break-all; }
    .row-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .muted { color: #6b7280; font-size: 13px; }
    .status { min-height: 20px; color: #047857; }
    .wide { grid-column: 1 / -1; }
    @media (max-width: 860px) { form { grid-template-columns: 1fr; } textarea { grid-column: span 1; } main { padding: 14px; } table { display: block; overflow-x: auto; } }
  </style>
</head>
<body>
  <header><h1>&#20844;&#20247;&#21495;&#31649;&#29702;</h1></header>
  <main>
    <section>
      <h2>&#28155;&#21152;&#20844;&#20247;&#21495;</h2>
      <form id="account-form">
        <label>&#21517;&#31216;<input name="name" required></label>
        <label>AppID<input name="app_id" required></label>
        <label>Secret<input name="app_secret" required></label>
        <label>Token<input name="token" required></label>
        <label>AES Key<input name="aes_key"></label>
        <label>&#21407;&#22987; ID<input name="original_id"></label>
        <label>&#21152;&#23494;&#27169;&#24335;<select name="encoding_type"><option value="plaintext">&#26126;&#25991;</option><option value="compatible">&#20860;&#23481;</option><option value="safe">&#23433;&#20840;</option></select></label>
        <label>&#22791;&#27880;<input name="remark"></label>
        <button type="submit">&#20445;&#23384;&#20844;&#20247;&#21495;</button>
      </form>
      <table>
        <thead><tr><th>ID</th><th>&#21517;&#31216;</th><th>AppID</th><th>&#22238;&#35843;&#22320;&#22336;</th><th>&#33756;&#21333;</th><th>&#25805;&#20316;</th></tr></thead>
        <tbody id="accounts"></tbody>
      </table>
    </section>

    <section>
      <h2>&#33756;&#21333;&#35774;&#32622;</h2>
      <form id="menu-form">
        <label>&#20844;&#20247;&#21495;<select name="account_id" required id="menu-account-options"></select></label>
        <textarea class="wide" name="menu_config" id="menu-config" placeholder='{"button":[{"type":"view","name":"官网","url":"https://example.com"}]}'></textarea>
        <button type="button" class="secondary" id="load-menu">&#36733;&#20837;&#33756;&#21333;</button>
        <button type="submit">&#20445;&#23384;&#33756;&#21333;</button>
        <button type="button" id="publish-menu">&#21457;&#24067;&#21040;&#24494;&#20449;</button>
      </form>
      <p class="muted">&#19968;&#32423;&#33756;&#21333;&#26368;&#22810; 3 &#20010;&#65292;&#20108;&#32423;&#33756;&#21333;&#26368;&#22810; 5 &#20010;&#12290;&#21457;&#24067;&#26102;&#20250;&#20351;&#29992;&#35813;&#20844;&#20247;&#21495;&#30340; AppID &#21644; Secret &#35843;&#29992;&#24494;&#20449;&#33756;&#21333;&#25509;&#21475;&#12290;</p>
    </section>

    <section>
      <h2>&#22238;&#22797;&#35268;&#21017;</h2>
      <form id="rule-form">
        <label>&#20844;&#20247;&#21495;<select name="account_id" required id="account-options"></select></label>
        <label>&#35268;&#21017;&#21517;<input name="name" required></label>
        <label>&#28040;&#24687;&#31867;&#22411;<select name="msg_type"><option value="*">&#20840;&#37096;</option><option value="text">&#25991;&#26412;</option><option value="image">&#22270;&#29255;</option><option value="voice">&#35821;&#38899;</option><option value="video">&#35270;&#39057;</option><option value="shortvideo">&#23567;&#35270;&#39057;</option><option value="location">&#20301;&#32622;</option><option value="link">&#38142;&#25509;</option><option value="event">&#20107;&#20214;</option></select></label>
        <label>&#20107;&#20214;<input name="event" placeholder="subscribe / CLICK"></label>
        <label>&#20851;&#38190;&#35789;<input name="keyword"></label>
        <label>&#21305;&#37197;&#26041;&#24335;<select name="keyword_match"><option value="contains">&#21253;&#21547;</option><option value="equals">&#31561;&#20110;</option><option value="prefix">&#21069;&#32512;</option></select></label>
        <label>&#22238;&#22797;&#31867;&#22411;<select name="reply_type"><option value="text">&#25991;&#26412;</option><option value="image">&#22270;&#29255;</option><option value="voice">&#35821;&#38899;</option><option value="video">&#35270;&#39057;</option><option value="music">&#38899;&#20048;</option><option value="news">&#22270;&#25991;</option></select></label>
        <label>&#20248;&#20808;&#32423;<input name="priority" type="number" value="0"></label>
        <textarea name="reply_content" placeholder='{"text":"欢迎关注"}'></textarea>
        <button type="submit">&#20445;&#23384;&#35268;&#21017;</button>
      </form>
      <p class="muted">&#25991;&#26412;&#22238;&#22797;&#20351;&#29992; {"text":"内容"}&#65292;&#22270;&#29255;/&#35821;&#38899;&#20351;&#29992; {"media_id":"素材ID"}&#65292;&#22270;&#25991;&#20351;&#29992; {"articles":[{"title":"标题","description":"摘要","pic_url":"图片URL","url":"链接"}]}&#12290;</p>
      <table>
        <thead><tr><th>ID</th><th>&#20844;&#20247;&#21495;</th><th>&#35268;&#21017;</th><th>&#21305;&#37197;</th><th>&#22238;&#22797;</th><th>&#20248;&#20808;&#32423;</th><th>&#25805;&#20316;</th></tr></thead>
        <tbody id="rules"></tbody>
      </table>
    </section>
    <div class="status" id="status"></div>
  </main>
  <script>
    const api = async (url, options = {}) => {
      const res = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error?.message || 'request failed');
      return json.data;
    };
    const status = (text) => document.querySelector('#status').textContent = text;
    const accounts = new Map();

    async function loadAccounts() {
      const data = await api('/admin/wechat/official-accounts');
      accounts.clear();
      document.querySelector('#accounts').innerHTML = data.items.map(item => {
        accounts.set(String(item.id), item);
        const callback = location.origin + '/wechat/official-account/' + item.app_id;
        const publishedAt = item.menu_published_at ? escapeHtml(item.menu_published_at) : '-';
        return `<tr><td>${item.id}</td><td>${escapeHtml(item.name)}</td><td>${escapeHtml(item.app_id)}</td><td><code>${callback}</code></td><td>${publishedAt}</td><td class="row-actions"><button class="secondary" onclick="selectMenu(${item.id})">&#33756;&#21333;</button><button class="danger" onclick="deleteAccount(${item.id})">&#21024;&#38500;</button></td></tr>`;
      }).join('');
      const options = data.items.map(item => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');
      document.querySelector('#account-options').innerHTML = options;
      document.querySelector('#menu-account-options').innerHTML = options;
    }

    async function loadRules() {
      const data = await api('/admin/wechat/reply-rules');
      document.querySelector('#rules').innerHTML = data.items.map(item => {
        const account = accounts.get(String(item.account_id));
        return `<tr><td>${item.id}</td><td>${escapeHtml(account?.name || item.account_id)}</td><td>${escapeHtml(item.name)}</td><td>${escapeHtml(item.msg_type)} ${escapeHtml(item.event || '')} ${escapeHtml(item.keyword || '')}</td><td>${escapeHtml(item.reply_type)}<br><code>${escapeHtml(JSON.stringify(item.reply_content || {}))}</code></td><td>${item.priority}</td><td class="row-actions"><button class="danger" onclick="deleteRule(${item.id})">&#21024;&#38500;</button></td></tr>`;
      }).join('');
    }

    async function selectMenu(id) {
      document.querySelector('#menu-account-options').value = id;
      await loadMenu();
      document.querySelector('#menu-config').focus();
    }

    async function loadMenu() {
      const id = document.querySelector('#menu-account-options').value;
      if (!id) return;
      const data = await api('/admin/wechat/official-accounts/' + id + '/menu');
      document.querySelector('#menu-config').value = JSON.stringify(data.menu_config || { button: [] }, null, 2);
      status('menu loaded');
    }

    document.querySelector('#load-menu').addEventListener('click', loadMenu);

    document.querySelector('#menu-form').addEventListener('submit', async (event) => {
      event.preventDefault();
      const id = document.querySelector('#menu-account-options').value;
      const menu = JSON.parse(document.querySelector('#menu-config').value || '{"button":[]}');
      await api('/admin/wechat/official-accounts/' + id + '/menu', { method: 'PUT', body: JSON.stringify({ menu_config: menu }) });
      status('menu saved');
      await refresh();
    });

    document.querySelector('#publish-menu').addEventListener('click', async () => {
      const id = document.querySelector('#menu-account-options').value;
      if (!id) return;
      await api('/admin/wechat/official-accounts/' + id + '/menu/publish', { method: 'POST', body: '{}' });
      status('menu published');
      await refresh();
    });

    document.querySelector('#account-form').addEventListener('submit', async (event) => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      await api('/admin/wechat/official-accounts', { method: 'POST', body: JSON.stringify(data) });
      event.target.reset();
      status('account saved');
      await refresh();
    });

    document.querySelector('#rule-form').addEventListener('submit', async (event) => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(event.target).entries());
      data.account_id = Number(data.account_id);
      data.priority = Number(data.priority || 0);
      data.reply_content = data.reply_content ? JSON.parse(data.reply_content) : {};
      await api('/admin/wechat/reply-rules', { method: 'POST', body: JSON.stringify(data) });
      event.target.reset();
      status('reply rule saved');
      await refresh();
    });

    async function deleteAccount(id) {
      await api('/admin/wechat/official-accounts/' + id, { method: 'DELETE' });
      status('account deleted');
      await refresh();
    }

    async function deleteRule(id) {
      await api('/admin/wechat/reply-rules/' + id, { method: 'DELETE' });
      status('rule deleted');
      await refresh();
    }

    async function refresh() {
      await loadAccounts();
      await loadRules();
    }

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    }

    refresh().catch(error => status(error.message));
  </script>
</body>
</html>
HTML;
    }
}
