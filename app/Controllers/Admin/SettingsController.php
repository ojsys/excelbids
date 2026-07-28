<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Branding;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Settings;
use App\Core\Uploader;
use RuntimeException;

/**
 * System settings, grouped into tabs, plus the email log for diagnosing
 * delivery problems on shared hosting.
 */
final class SettingsController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    private const GROUPS = [
        'general'  => ['label' => 'General',       'intro' => 'Site name, public contact details and how references are numbered.'],
        'branding' => ['label' => 'Logo & favicon','intro' => 'The logo, browser-tab icon and social sharing image used across the website, admin panel, client portal and emails.'],
        'mail'     => ['label' => 'Email',         'intro' => 'How the system sends notifications, invitations and password resets.'],
        'portal'   => ['label' => 'Client portal', 'intro' => 'Whether clients can sign in, upload files and message the team.'],
        'seo'      => ['label' => 'SEO',           'intro' => 'Default meta tags and analytics.'],
    ];

    public function index(Request $request): void
    {
        $this->redirect('admin/settings/general');
    }

    public function group(Request $request, array $params): void
    {
        $group = (string) $params['group'];
        if (!isset(self::GROUPS[$group])) {
            $this->notFound('That settings group does not exist.');
        }

        // Self-heal an installation that predates the branding settings.
        if ($group === 'branding') {
            Branding::ensureSettings();
        }

        if ($request->isPost()) {
            $this->save($request, $group);
            return;
        }

        $this->view('admin/settings/group', [
            'pageTitle' => self::GROUPS[$group]['label'] . ' settings',
            'heading'   => 'Settings',
            'crumb'     => 'Configure',
            'active'    => 'settings',
            'group'     => $group,
            'groups'    => self::GROUPS,
            'settings'  => Settings::group($group),
            'diagnostics' => $group === 'general' ? $this->diagnostics() : null,
        ]);
    }

    private function save(Request $request, string $group): void
    {
        $uploadErrors = [];

        foreach (Settings::group($group) as $definition) {
            $key = (string) $definition['key'];
            $type = (string) ($definition['type'] ?? 'text');

            if ($type === 'bool') {
                Settings::set($key, $request->boolean($key) ? '1' : '0');
                continue;
            }

            if ($type === 'image') {
                $error = $this->saveImage($key);
                if ($error !== null) {
                    $uploadErrors[$key] = $error;
                }
                continue;
            }

            if (!array_key_exists($key, $request->all())) {
                continue;
            }

            $value = (string) $request->raw($key, '');

            // A blank password field means "keep what is stored", not "clear it".
            if ($type === 'password' && trim($value) === '') {
                continue;
            }

            Settings::set($key, mb_substr(trim($value), 0, 65535));
        }

        if ($uploadErrors) {
            Settings::flush();
            Flash::setErrors($uploadErrors);
            Flash::error('Some images could not be uploaded. Everything else on this page was saved.');
            $this->redirect('admin/settings/' . $group);
        }

        Settings::flush();
        Activity::log('settings.updated', 'settings', null, 'Updated ' . self::GROUPS[$group]['label'] . ' settings');

        Flash::success(self::GROUPS[$group]['label'] . ' settings saved.');
        $this->redirect('admin/settings/' . $group);
    }

    /**
     * Store one uploaded brand image, replacing whatever it had before.
     *
     * @return string|null An error message safe to show the user, or null on success.
     */
    private function saveImage(string $key): ?string
    {
        $file = $_FILES[$key] ?? null;

        // No file chosen is the normal case — the field keeps its current value.
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        try {
            $stored = Uploader::storeImage($file, 'branding');
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        // Only remove the old file once the new one is safely on disk.
        $previous = (string) Settings::get($key, '');
        if ($previous !== '' && $previous !== $stored['stored_name']) {
            Uploader::delete($previous);
        }

        Settings::set($key, $stored['stored_name']);
        Activity::log('settings.brand_image', 'settings', null, 'Uploaded a new ' . str_replace('_', ' ', $key));

        return null;
    }

    /** Clear a brand image and delete the file behind it. */
    public function removeBrandImage(Request $request, array $params): void
    {
        $key = (string) ($params['key'] ?? '');

        if (!array_key_exists($key, Branding::FIELDS)) {
            $this->notFound('That image does not exist.');
        }

        Branding::remove($key);
        Settings::flush();
        Activity::log('settings.brand_image_removed', 'settings', null, 'Removed the ' . str_replace('_', ' ', $key));

        Flash::success('Image removed. The default will be used instead.');
        $this->redirect('admin/settings/branding');
    }

    /** Send a test message to the signed-in user to prove mail works. */
    public function sendTestEmail(Request $request): void
    {
        $user = $this->staff();

        $sent = Mailer::to((string) $user['email'], (string) $user['name'])
            ->subject('ExcelBids test email')
            ->html(
                '<h2 style="font-family:Georgia,serif;">Your email settings work.</h2>'
                . '<p>This test message was sent from your ExcelBids installation using the <strong>'
                . e((string) Settings::get('mail_transport', 'mail'))
                . '</strong> transport at ' . e(date('j M Y, H:i')) . '.</p>'
                . '<p>Consultation request notifications, portal invitations and password resets will use the same route.</p>'
            )
            ->send();

        if ($sent) {
            Flash::success('Test email sent to ' . $user['email'] . '. Check your inbox, and your spam folder.');
        } else {
            Flash::error('The test email could not be sent. Check the email log below for the reason.');
        }

        $this->redirect('admin/settings/mail');
    }

    public function emailLog(Request $request): void
    {
        Auth::authorize('settings.manage');

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 40;
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::scalar('SELECT COUNT(*) FROM email_log', [], 0);
        $entries = Database::all(
            sprintf(
                'SELECT id, to_email, subject, status, transport, error, created_at
                 FROM email_log ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d',
                $perPage,
                $offset
            )
        );

        $this->view('admin/settings/email-log', [
            'pageTitle' => 'Email log',
            'heading'   => 'Email log',
            'crumb'     => 'Settings',
            'active'    => 'settings',
            'entries'   => $entries,
            'page'      => $page,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'total'     => $total,
        ]);
    }

    /**
     * Environment facts worth surfacing on a shared host, where the customer
     * cannot see php.ini and support tickets start with these questions.
     *
     * @return array<int,array{label:string,value:string,ok:bool,note:string}>
     */
    private function diagnostics(): array
    {
        $storage = Config::storagePath('uploads');
        $storageWritable = is_dir($storage) ? is_writable($storage) : is_writable(dirname($storage));
        $https = Config::origin();

        return [
            [
                'label' => 'PHP version',
                'value' => PHP_VERSION,
                'ok'    => version_compare(PHP_VERSION, '8.0.0', '>='),
                'note'  => 'PHP 8.0 or newer is required. 8.1+ is recommended.',
            ],
            [
                'label' => 'Database',
                'value' => (string) Database::scalar('SELECT VERSION()', [], 'unknown'),
                'ok'    => true,
                'note'  => 'MySQL 5.7+ or MariaDB 10.2+.',
            ],
            [
                'label' => 'Upload storage',
                'value' => $storageWritable ? 'Writable' : 'Not writable',
                'ok'    => $storageWritable,
                'note'  => $storageWritable
                    ? $storage
                    : 'Set the storage folder to permission 755 in the cPanel File Manager.',
            ],
            [
                'label' => 'Maximum upload size',
                'value' => filesize_human(Uploader::maxBytes()),
                'ok'    => Uploader::maxBytes() >= 2 * 1024 * 1024,
                'note'  => 'The lower of your setting and the server limits (upload_max_filesize, post_max_size).',
            ],
            [
                'label' => 'HTTPS',
                'value' => str_starts_with($https, 'https') ? 'Active' : 'Not detected',
                'ok'    => str_starts_with($https, 'https'),
                'note'  => str_starts_with($https, 'https')
                    ? 'Client data is encrypted in transit.'
                    : 'Turn on AutoSSL in cPanel, then uncomment the HTTPS redirect in public_html/.htaccess.',
            ],
            [
                'label' => 'Installer removed',
                'value' => is_dir(EB_ROOT . '/public_html/install') ? 'Still present' : 'Removed',
                'ok'    => !is_dir(EB_ROOT . '/public_html/install'),
                'note'  => is_dir(EB_ROOT . '/public_html/install')
                    ? 'Delete public_html/install now that setup is finished.'
                    : 'Good — the installer cannot be re-run.',
            ],
            [
                'label' => 'Debug mode',
                'value' => Config::isDebug() ? 'On' : 'Off',
                'ok'    => !Config::isDebug(),
                'note'  => Config::isDebug()
                    ? 'Set "debug" to false in app/config.php on a live site — it exposes error details.'
                    : 'Errors are logged, not displayed.',
            ],
        ];
    }
}
