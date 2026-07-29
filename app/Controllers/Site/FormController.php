<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Activity;
use App\Core\Content;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Core\Validator;
use App\Models\Enquiry;
use App\Models\Page;

/**
 * Handles submissions from `form` blocks on builder pages.
 *
 * They are filed as consultation requests so everything a visitor sends arrives
 * in one inbox, whichever page it came from.
 */
final class FormController extends Controller
{
    protected string $layout = 'site/partials/layout';

    public function submit(Request $request, array $params): void
    {
        $blockId = (int) ($params['blockId'] ?? 0);

        // The block must exist, be a form, be visible, and sit on a live page.
        $block = Database::first(
            "SELECT b.*, p.slug, p.title AS page_title, p.is_published
             FROM page_blocks b
             JOIN pages p ON p.id = b.page_id
             WHERE b.id = ? AND b.block_type = 'form' AND b.is_visible = 1",
            [$blockId]
        );

        if ($block === null || (int) $block['is_published'] !== 1) {
            $this->notFound('That form is no longer available.');
        }

        $settings = Page::decode($block['settings'] ?? null);
        $returnTo = '/' . ltrim((string) $block['slug'], '/') . '#form-' . $blockId;

        // Bots fill hidden fields; people do not.
        if ($request->input('website_url', '') !== '') {
            $_SESSION['_form_success_block'] = $blockId;
            Response::redirect($returnTo);
        }

        if (Enquiry::tooManyRecent(client_ip())) {
            Flash::error('We have already received several messages from this connection. Please email us directly instead.');
            Response::redirect($returnTo);
        }

        $rules = [
            'name'    => 'required|min:2|max:140',
            'email'   => 'required|email|max:190',
            'message' => 'required|min:10|max:5000',
            'consent' => 'required',
        ];

        // Only validate the optional fields this particular form actually shows.
        if ($this->enabled($settings, 'show_phone', true)) {
            $rules['phone'] = 'nullable|phone|max:40';
        }
        if ($this->enabled($settings, 'show_org', true)) {
            $rules['organisation'] = 'nullable|max:190';
        }
        if ($this->enabled($settings, 'show_deadline', false)) {
            $rules['deadline'] = 'nullable|date';
        }

        $validator = Validator::make($request->all(), $rules, [
            'name'    => 'Your name',
            'email'   => 'Email address',
            'message' => trim((string) ($settings['message_label'] ?? '')) ?: 'Your message',
            'consent' => 'Consent',
            'phone'   => 'Phone number',
        ]);

        if ($validator->fails()) {
            Flash::setErrors($validator->errors());
            Flash::setOld($request->all());
            Flash::error('Please correct the highlighted fields and try again.');
            Response::redirect($returnTo);
        }

        $id = Enquiry::create([
            'name'         => (string) $request->input('name'),
            'organisation' => (string) $request->input('organisation', ''),
            'email'        => mb_strtolower((string) $request->input('email')),
            'phone'        => (string) $request->input('phone', ''),
            'service'      => (string) $request->input('service', ''),
            'sector'       => (string) $request->input('sector', ''),
            'deadline'     => $request->nullable('deadline'),
            'message'      => (string) $request->raw('message', ''),
            'status'       => 'new',
            // Records which page the enquiry came from, visible in the admin inbox.
            'source'       => mb_substr((string) $block['slug'], 0, 60),
        ]);

        $enquiry = Enquiry::find($id);
        Activity::log('enquiry.created', 'enquiry', $id, 'Enquiry from the ' . $block['page_title'] . ' page');

        $this->notifyTeam($enquiry);
        $this->sendAutoReply($enquiry);

        $_SESSION['_form_success_block'] = $blockId;
        Response::redirect($returnTo);
    }

    /** @param array<string,mixed> $settings */
    private function enabled(array $settings, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $settings) || $settings[$key] === '') {
            return $default;
        }
        return (string) $settings[$key] === '1';
    }

    /** @param array<string,mixed> $enquiry */
    private function notifyTeam(array $enquiry): void
    {
        $to = Settings::get('notify_email') ?? Settings::get('contact_email');
        if (!$to) {
            return;
        }

        Mailer::to($to)
            ->subject('New enquiry — ' . $enquiry['reference'] . ' — ' . $enquiry['name'])
            ->replyTo((string) $enquiry['email'], (string) $enquiry['name'])
            ->view('enquiry-notification', ['enquiry' => $enquiry])
            ->send();
    }

    /** @param array<string,mixed> $enquiry */
    private function sendAutoReply(array $enquiry): void
    {
        if (!Settings::bool('enquiry_autoreply', true)) {
            return;
        }

        Mailer::to((string) $enquiry['email'], (string) $enquiry['name'])
            ->subject('We have received your message — ' . $enquiry['reference'])
            ->replyTo((string) (Settings::get('contact_email') ?? ''), (string) Settings::get('site_name', 'ExcelBids'))
            ->view('enquiry-autoreply', ['enquiry' => $enquiry])
            ->send();
    }
}
