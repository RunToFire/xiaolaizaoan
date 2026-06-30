<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use App\Middleware\AdminTokenMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::addRoute(['GET', 'POST'], '/wechat/official-account', 'App\Controller\WechatOfficialAccountController@callback');
Router::addRoute(['GET', 'POST'], '/wechat/official-account/{appId}', 'App\Controller\WechatOfficialAccountController@callback');

Router::addGroup('/admin', function () {
    Router::get('/wechat', 'App\Controller\Admin\WechatConsoleController@index');

    Router::get('/wechat/official-accounts', 'App\Controller\Admin\WechatOfficialAccountAdminController@index');
    Router::post('/wechat/official-accounts', 'App\Controller\Admin\WechatOfficialAccountAdminController@store');
    Router::get('/wechat/official-accounts/{id:\d+}', 'App\Controller\Admin\WechatOfficialAccountAdminController@show');
    Router::put('/wechat/official-accounts/{id:\d+}', 'App\Controller\Admin\WechatOfficialAccountAdminController@update');
    Router::delete('/wechat/official-accounts/{id:\d+}', 'App\Controller\Admin\WechatOfficialAccountAdminController@destroy');

    Router::get('/wechat/reply-rules', 'App\Controller\Admin\WechatReplyRuleAdminController@index');
    Router::post('/wechat/reply-rules', 'App\Controller\Admin\WechatReplyRuleAdminController@store');
    Router::put('/wechat/reply-rules/{id:\d+}', 'App\Controller\Admin\WechatReplyRuleAdminController@update');
    Router::delete('/wechat/reply-rules/{id:\d+}', 'App\Controller\Admin\WechatReplyRuleAdminController@destroy');

    Router::get('/wechat/official-accounts/{accountId:\d+}/menu', 'App\Controller\Admin\WechatMenuAdminController@show');
    Router::put('/wechat/official-accounts/{accountId:\d+}/menu', 'App\Controller\Admin\WechatMenuAdminController@save');
    Router::post('/wechat/official-accounts/{accountId:\d+}/menu/publish', 'App\Controller\Admin\WechatMenuAdminController@publish');
}, ['middleware' => [AdminTokenMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});
