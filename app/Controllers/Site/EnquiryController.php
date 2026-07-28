<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Activity;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Enquiry;

/**
 * The public consultation request form — the front door of the whole system.
 */
final class EnquiryController extends Controller
{
    protected string $layout = 'site/partials/layout';

    public function request(Request $request): void
    {
        if ($request->isPost()) {
            $this->store($request);
            return;
        }

        $this->view('site/consultation', [
            'services'  => Database::all('SELECT title FROM services WHERE is_active = 1 ORDER BY sort_order, id'),
            'sectors'   => Database::all('SELECT name FROM sectors WHERE is_active = 1 ORDER BY sort_order, id'),
            'pageTitle' => block('quote_heading', 'Submit a Consultation Request') . ' — ' . Settings::get('site_name', 'ExcelBids'),
            'metaDescription' => 'Tell us about your tender opportunity and we will come back with a scope, a fee and a delivery plan.',
        ]);
    }

    private function store(Request $request): void
    {
        // Bots fill hidden fields; humans do not.
        if ($request->input('website_url', '') !== '') {
            $this->redirect('consultation/thank-you');
        }

        if (Enquiry::tooManyRecent(client_ip())) {
            Flash::error('We have already received several requests from this connection. Please email us directly instead.');
            $this->redirect('consultation');
        }

        $input = $this->validate($request, [
            'name'         => 'required|min:2|max:140',
            'email'        => 'required|email|max:190',
            'phone'        => 'nullable|phone|max:40',
            'organisation' => 'nullable|max:190',
            'service'      => 'nullable|max:140',
            'sector'       => 'nullable|max:140',
            'deadline'     => 'nullable|date',
            'message'      => 'required|min:10|max:5000',
            'consent'      => 'required',
        ], 'consultation', [
            'name'         => 'Your name',
            'email'        => 'Email address',
            'phone'        => 'Phone number',
            'organisation' => 'Organisation',
            'message'      => 'About the opportunity',
            'consent'      => 'Consent',
        ]);

        $deadline = $request->nullable('deadline');

        $id = Enquiry::create([
            'name'         => $input['name'],
            'organisation' => (string) $request->input('organisation', ''),
            'email'        => mb_strtolower((string) $input['email']),
            'phone'        => (string) $request->input('phone', ''),
            'service'      => (string) $request->input('service', ''),
            'sector'       => (string) $request->input('sector', ''),
            'deadline'     => $deadline,
            'message'      => (string) $request->raw('message', ''),
            'status'       => 'new',
            'source'       => 'website',
        ]);

        $enquiry = Enquiry::find($id);
        Activity::log('enquiry.created', 'enquiry', $id, 'Consultation request from ' . $input['name']);

        $this->notifyTeam($enquiry);
        $this->sendAutoReply($enquiry);

        $_SESSION['_enquiry_reference'] = $enquiry['reference'];
        $this->redirect('consultation/thank-you');
    }

    public function thankYou(Request $request): void
    {
        $reference = $_SESSION['_enquiry_reference'] ?? null;
        unset($_SESSION['_enquiry_reference']);

        // Reaching this page directly, without submitting, is not an error —
        // just send them back to the form.
        if ($reference === null) {
            $this->redirect('consultation');
        }

        $this->view('site/consultation-thanks', [
            'reference' => $reference,
            'pageTitle' => 'Request received — ' . Settings::get('site_name', 'ExcelBids'),
        ]);
    }

    /** @param array<string,mixed> $enquiry */
    private function notifyTeam(array $enquiry): void
    {
        $to = Settings::get('notify_email') ?? Settings::get('contact_email');
        if (!$to) {
            return;
        }

        Mailer::to($to)
            ->subject('New consultation request — ' . $enquiry['reference'] . ' — ' . $enquiry['name'])
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
            ->subject('We have received your request — ' . $enquiry['reference'])
            ->replyTo((string) (Settings::get('contact_email') ?? ''), (string) Settings::get('site_name', 'ExcelBids'))
            ->view('enquiry-autoreply', ['enquiry' => $enquiry])
            ->send();
    }
}
