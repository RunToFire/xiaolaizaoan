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

Router::post('/admin/login', 'App\Controller\Admin\AdminAuthController@login');
Router::post('/admin/logout', 'App\Controller\Admin\AdminAuthController@logout');

Router::addGroup('/admin', function () {
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

    Router::get('/materials/groups', 'App\Controller\Admin\MaterialAdminController@groups');
    Router::post('/materials/groups', 'App\Controller\Admin\MaterialAdminController@storeGroup');
    Router::put('/materials/groups/{id:\d+}', 'App\Controller\Admin\MaterialAdminController@updateGroup');
    Router::delete('/materials/groups/{id:\d+}', 'App\Controller\Admin\MaterialAdminController@destroyGroup');
    Router::get('/materials/images', 'App\Controller\Admin\MaterialAdminController@images');
    Router::post('/materials/images', 'App\Controller\Admin\MaterialAdminController@storeImage');
    Router::put('/materials/images/{id:\d+}', 'App\Controller\Admin\MaterialAdminController@updateImage');
    Router::delete('/materials/images/{id:\d+}', 'App\Controller\Admin\MaterialAdminController@destroyImage');
    Router::get('/materials/images/{id:\d+}/file', 'App\Controller\Admin\MaterialAdminController@imageFile');
    Router::get('/materials/quotes', 'App\Controller\Admin\MaterialAdminController@quotes');
    Router::post('/materials/quotes', 'App\Controller\Admin\MaterialAdminController@storeQuote');
    Router::put('/materials/quotes/{id:\d+}', 'App\Controller\Admin\MaterialAdminController@updateQuote');
    Router::delete('/materials/quotes/{id:\d+}', 'App\Controller\Admin\MaterialAdminController@destroyQuote');

    Router::get('/punch-records', 'App\Controller\Admin\PunchRecordAdminController@index');
}, ['middleware' => [AdminTokenMiddleware::class]]);

Router::get('/favicon.ico', function () {
    return '';
});
