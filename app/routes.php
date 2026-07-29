<?php
/**
 * Route table. All three surfaces (public site, admin panel, client portal)
 * are served by the single front controller at public_html/index.php.
 */

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */

// ---------------------------------------------------------------------------
// Public website
// ---------------------------------------------------------------------------

$router->get('/', 'Site\HomeController@index');
$router->form('/consultation', 'Site\EnquiryController@request');
$router->get('/consultation/thank-you', 'Site\EnquiryController@thankYou');
$router->get('/sitemap.xml', 'Site\HomeController@sitemap');
$router->get('/robots.txt', 'Site\HomeController@robots');

// Logo, favicon and share image. Stored outside the web root, so streamed.
$router->get('/branding/{file}', 'Site\BrandingController@show');

// Page builder assets and form submissions.
$router->get('/media/{id}', 'Site\MediaController@show');
$router->post('/forms/{blockId}', 'Site\FormController@submit');

// ---------------------------------------------------------------------------
// Admin panel
// ---------------------------------------------------------------------------

$router->group('/admin', ['csrf'], static function (Router $router): void {

    // -- Guest routes --
    $router->group('', ['guest.staff'], static function (Router $router): void {
        $router->form('/login', 'Admin\AuthController@login');
        $router->form('/forgot-password', 'Admin\AuthController@forgotPassword');
        $router->form('/reset-password/{token}', 'Admin\AuthController@resetPassword');
    });

    $router->get('/logout', 'Admin\AuthController@logout');
    $router->post('/logout', 'Admin\AuthController@logout');

    // -- Authenticated routes --
    $router->group('', ['auth.staff'], static function (Router $router): void {

        $router->get('/', 'Admin\DashboardController@index');
        $router->get('/activity', 'Admin\DashboardController@activity');

        // Account
        $router->form('/account', 'Admin\AuthController@account');

        // Clients
        $router->group('/clients', ['can:clients.view'], static function (Router $router): void {
            $router->get('/', 'Admin\ClientController@index');
            $router->get('/export', 'Admin\ClientController@export');
            $router->form('/create', 'Admin\ClientController@create');
            $router->get('/{id}', 'Admin\ClientController@show');
            $router->form('/{id}/edit', 'Admin\ClientController@edit');
            $router->post('/{id}/delete', 'Admin\ClientController@destroy');
            $router->post('/{id}/portal-users', 'Admin\ClientController@addPortalUser');
            $router->post('/{id}/portal-users/{userId}/toggle', 'Admin\ClientController@togglePortalUser');
            $router->post('/{id}/portal-users/{userId}/resend', 'Admin\ClientController@resendInvite');
            $router->post('/{id}/portal-users/{userId}/delete', 'Admin\ClientController@deletePortalUser');
        });

        // Bids
        $router->group('/bids', ['can:bids.view'], static function (Router $router): void {
            $router->get('/', 'Admin\BidController@index');
            $router->get('/board', 'Admin\BidController@board');
            $router->get('/calendar', 'Admin\BidController@calendar');
            $router->get('/export', 'Admin\BidController@export');
            $router->form('/create', 'Admin\BidController@create');
            $router->get('/{id}', 'Admin\BidController@show');
            $router->form('/{id}/edit', 'Admin\BidController@edit');
            $router->post('/{id}/delete', 'Admin\BidController@destroy');
            $router->post('/{id}/stage', 'Admin\BidController@updateStage');
            $router->post('/{id}/status', 'Admin\BidController@updateStatus');
            $router->post('/{id}/notes', 'Admin\BidController@addNote');
            $router->post('/{id}/qa/{checkId}', 'Admin\BidController@updateQaCheck');
            $router->post('/{id}/tasks', 'Admin\BidController@addTask');
            $router->post('/{id}/tasks/{taskId}/toggle', 'Admin\BidController@toggleTask');
            $router->post('/{id}/tasks/{taskId}/delete', 'Admin\BidController@deleteTask');
            $router->post('/{id}/documents', 'Admin\DocumentController@upload');
        });

        // Documents
        $router->get('/documents/{id}/download', 'Admin\DocumentController@download');
        $router->post('/documents/{id}/visibility', 'Admin\DocumentController@toggleVisibility');
        $router->post('/documents/{id}/delete', 'Admin\DocumentController@destroy');

        // Messages
        $router->group('/messages', ['can:messages.manage'], static function (Router $router): void {
            $router->get('/', 'Admin\MessageController@index');
            $router->form('/{clientId}', 'Admin\MessageController@thread');
        });

        // Enquiries
        $router->group('/enquiries', ['can:enquiries.view'], static function (Router $router): void {
            $router->get('/', 'Admin\EnquiryController@index');
            $router->get('/export', 'Admin\EnquiryController@export');
            $router->get('/{id}', 'Admin\EnquiryController@show');
            $router->post('/{id}/status', 'Admin\EnquiryController@updateStatus');
            $router->post('/{id}/notes', 'Admin\EnquiryController@updateNotes');
            $router->post('/{id}/convert', 'Admin\EnquiryController@convert');
            $router->post('/{id}/delete', 'Admin\EnquiryController@destroy');
        });

        // Reports
        $router->group('/reports', ['can:reports.view'], static function (Router $router): void {
            $router->get('/', 'Admin\ReportController@index');
            $router->get('/pipeline', 'Admin\ReportController@pipeline');
            $router->get('/clients', 'Admin\ReportController@clients');
            $router->get('/performance', 'Admin\ReportController@performance');
            $router->get('/export/{type}', 'Admin\ReportController@export');
        });

        // Website content (CMS)
        $router->group('/cms', ['can:cms.manage'], static function (Router $router): void {
            $router->get('/', 'Admin\CmsController@index');
            $router->form('/sections', 'Admin\CmsController@sections');
            $router->form('/content/{section}', 'Admin\CmsController@content');
            $router->form('/list/{type}', 'Admin\CmsController@collection');
            $router->post('/list/{type}/save', 'Admin\CmsController@saveCollectionItem');
            $router->post('/list/{type}/{id}/delete', 'Admin\CmsController@deleteCollectionItem');
            $router->form('/pages', 'Admin\CmsController@pages');
            $router->form('/pages/create', 'Admin\CmsController@createPage');
            $router->form('/pages/{id}/edit', 'Admin\CmsController@editPage');
            $router->post('/pages/{id}/delete', 'Admin\CmsController@deletePage');

            // Page builder
            $router->get('/pages/{id}/build', 'Admin\PageBuilderController@index');
            $router->post('/pages/{id}/blocks', 'Admin\PageBuilderController@addBlock');
            $router->post('/pages/{id}/blocks/{blockId}/save', 'Admin\PageBuilderController@saveBlock');
            $router->post('/pages/{id}/blocks/{blockId}/{action}', 'Admin\PageBuilderController@blockAction');

            // Media library
            $router->get('/media', 'Admin\PageBuilderController@media');
            $router->post('/media/upload', 'Admin\PageBuilderController@uploadMedia');
            $router->post('/media/{id}/delete', 'Admin\PageBuilderController@deleteMedia');

            $router->form('/menus', 'Admin\CmsController@menus');
        });

        // Settings and staff accounts
        $router->group('/settings', ['can:settings.manage'], static function (Router $router): void {
            $router->get('/', 'Admin\SettingsController@index');
            $router->post('/mail/test', 'Admin\SettingsController@sendTestEmail');
            $router->post('/branding/{key}/remove', 'Admin\SettingsController@removeBrandImage');
            // Registered last so "mail" and "branding" are not read as group names.
            $router->form('/{group}', 'Admin\SettingsController@group');
        });

        $router->group('/users', ['can:users.manage'], static function (Router $router): void {
            $router->get('/', 'Admin\UserController@index');
            $router->form('/create', 'Admin\UserController@create');
            $router->form('/{id}/edit', 'Admin\UserController@edit');
            $router->post('/{id}/toggle', 'Admin\UserController@toggle');
            $router->post('/{id}/delete', 'Admin\UserController@destroy');
        });

        $router->get('/logs/email', 'Admin\SettingsController@emailLog');
    });
});

// ---------------------------------------------------------------------------
// Client portal
// ---------------------------------------------------------------------------

$router->group('/portal', ['csrf', 'portal.enabled'], static function (Router $router): void {

    $router->group('', ['guest.client'], static function (Router $router): void {
        $router->form('/login', 'Portal\AuthController@login');
        $router->form('/forgot-password', 'Portal\AuthController@forgotPassword');
        $router->form('/reset-password/{token}', 'Portal\AuthController@resetPassword');
        $router->form('/activate/{token}', 'Portal\AuthController@activate');
    });

    $router->get('/logout', 'Portal\AuthController@logout');
    $router->post('/logout', 'Portal\AuthController@logout');

    $router->group('', ['auth.client'], static function (Router $router): void {
        $router->get('/', 'Portal\DashboardController@index');
        $router->get('/bids', 'Portal\BidController@index');
        $router->get('/bids/{id}', 'Portal\BidController@show');
        $router->post('/bids/{id}/documents', 'Portal\DocumentController@upload');
        $router->get('/documents', 'Portal\DocumentController@index');
        $router->get('/documents/{id}/download', 'Portal\DocumentController@download');
        $router->form('/messages', 'Portal\MessageController@index');
        $router->form('/account', 'Portal\AuthController@account');
    });
});

// ---------------------------------------------------------------------------
// CMS pages (privacy policy, terms, anything added later).
// Registered last so a slug can never shadow a real route.
// ---------------------------------------------------------------------------

$router->get('/{slug}', 'Site\HomeController@page');
