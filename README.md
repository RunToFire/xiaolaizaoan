# Hyperf WeChat Docker Deploy

This repository contains a Docker deployment skeleton for a Hyperf web service
behind Nginx with MySQL storage. Nginx proxies requests to Hyperf/Swoole on
port `9501`.

## Files

- `docker-compose.yml`: services for PHP/Hyperf, Nginx, and MySQL.
- `docker/php/Dockerfile`: PHP 8.3 CLI image with Swoole, Redis, PDO MySQL, and Composer.
- `docker/nginx/default.conf`: Nginx reverse proxy config.
- `docker/mysql/my.cnf`: MySQL utf8mb4 defaults.
- `.env.example`: deployment environment template.

## First Run

Copy the environment template and fill in database and WeChat values:

```bash
cp .env.example .env
```

If the Hyperf project has not been created yet, create it in the `app`
directory:

```bash
docker compose build app
docker compose run --rm --entrypoint composer app create-project hyperf/hyperf-skeleton .
```

Install EasyWeChat in the Hyperf project:

```bash
docker compose run --rm app composer require w7corp/easywechat
```

Start the service:

```bash
docker compose up -d --build
```

The compose stack includes MySQL, Redis, Hyperf, and Nginx. Redis is used by
the default Hyperf cache/model-cache configuration, and the app connects to it
through the `redis` service name.

The web service will be available at:

```text
http://localhost
```

## Hyperf Configuration Notes

Use the following database connection values inside the Hyperf app:

```text
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
```

For the WeChat Official Account integration, read these environment variables
from your EasyWeChat configuration:

```text
WECHAT_OFFICIAL_ACCOUNT_APPID
WECHAT_OFFICIAL_ACCOUNT_SECRET
WECHAT_OFFICIAL_ACCOUNT_TOKEN
WECHAT_OFFICIAL_ACCOUNT_AES_KEY
```

A typical public account callback route can be exposed from Hyperf, for example:

```text
https://your-domain.example/wechat/official-account
```

Configure that URL in the WeChat Official Account backend with the same token
and AES key that you set in `.env`.

## WeChat Admin

Run migrations after MySQL is running:

```bash
docker compose exec app php bin/hyperf.php migrate
```

Open the management page:

```text
https://your-domain.example/admin/wechat
```

Set `ADMIN_TOKEN` in `.env` before opening the page. The browser will show a
login prompt; use any username and the `ADMIN_TOKEN` value as the password.

The page can add official account AppID, secret, token, AES key, and reply
rules, and can save/publish the official account menu. For multiple official
accounts, configure each WeChat backend callback URL as:

```text
https://your-domain.example/wechat/official-account/{APPID}
```

Reply rules match by message type, event, optional keyword, and priority. Text
replies use JSON like:

```json
{"text":"欢迎关注"}
```

Image and voice replies use:

```json
{"media_id":"MEDIA_ID"}
```

News replies use:

```json
{"articles":[{"title":"标题","description":"摘要","pic_url":"https://example.com/a.jpg","url":"https://example.com"}]}
```

Menu JSON follows the WeChat custom menu format. Example:

```json
{"button":[{"type":"view","name":"官网","url":"https://example.com"},{"name":"服务","sub_button":[{"type":"click","name":"联系客服","key":"CONTACT"}]}]}
```
