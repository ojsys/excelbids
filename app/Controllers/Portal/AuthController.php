<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Validator;

/**
 * Client portal authentication: activation from an invite, sign-in, password
 * reset, and the client's own profile.
 */
final class AuthController extends Controller
{
    protected string $layout = 'portal/partials/auth-layout';

    public function login(Request $request): void
    {
        if (!$request->isPost()) {
            $this->view('portal/auth/login', ['pageTitle' => 'Client login']);
            return;
        }

        $email = (string) $request->input('email', '');
        $password = (string) $request->raw('password', '');

        if ($email === '' || $password === '') {
            Flash::error('Please enter both your email address and password.');
            Flash::setOld(['email' => $email]);
            $this->redirect('portal/login');
        }

        if (Auth::tooManyAttempts(Auth::CLIENT, $email)) {
            Flash::error('Too many failed attempts. Please wait ' . Auth::lockoutMinutes() . ' minutes and try again.');
            $this->redirect('portal/login');
        }

        $user = Auth::attempt(Auth::CLIENT, $email, $password);

        if ($user === null) {
            Auth::recordFailedAttempt(Auth::CLIENT, $email);
            Flash::error('Those details were not recognised.');
            Flash::setOld(['email' => $email]);
            $this->redirect('portal/login');
        }

        // An archived client keeps its records but loses portal access.
        $clientStatus = (string) Database::scalar('SELECT status FROM clients WHERE id = ?', [$user['client_id']], '');
        if ($clientStatus === 'archived') {
            Flash::error('This account is no longer active. Please contact us if you think that is wrong.');
            $this->redirect('portal/login');
        }

        Auth::clearAttempts(Auth::CLIENT, $email);
        Auth::login(Auth::CLIENT, (int) $user['id']);
        Auth::recordLogin(Auth::CLIENT, (int) $user['id']);
        Activity::log('portal.login', 'client', (int) $user['client_id'], $user['name'] . ' signed in to the portal');

        $this->redirect(ltrim(Middleware::intended(Auth::CLIENT, '/portal'), '/'));
    }

    public function logout(Request $request): void
    {
        Auth::logout(Auth::CLIENT);
        Flash::success('You have been signed out.');
        $this->redirect('portal/login');
    }

    /** First sign-in: the client chooses their password from an invite link. */
    public function activate(Request $request, array $params): void
    {
        $token = (string) ($params['token'] ?? '');

        $user = Database::first(
            'SELECT cu.*, c.organisation FROM client_users cu
             JOIN clients c ON c.id = cu.client_id
             WHERE cu.invite_token = ? AND cu.invite_expires > ? AND cu.is_active = 1',
            [$token, date('Y-m-d H:i:s')]
        );

        if ($user === null) {
            Flash::error('That invitation link has expired or has already been used. Please ask us for a new one.');
            $this->redirect('portal/login');
        }

        if (!$request->isPost()) {
            $this->view('portal/auth/activate', [
                'pageTitle'    => 'Set up your account',
                'token'        => $token,
                'name'         => (string) $user['name'],
                'organisation' => (string) $user['organisation'],
            ]);
            return;
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
        ], ['password' => 'Password']);

        if ($validator->fails()) {
            Flash::setErrors($validator->errors());
            Flash::error('Please correct the highlighted fields.');
            $this->redirect('portal/activate/' . $token);
        }

        Database::update('client_users', [
            'password_hash'  => Auth::hash((string) $request->raw('password')),
            'invite_token'   => null,
            'invite_expires' => null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        Auth::login(Auth::CLIENT, (int) $user['id']);
        Auth::recordLogin(Auth::CLIENT, (int) $user['id']);
        Activity::log('portal.activated', 'client', (int) $user['client_id'], $user['name'] . ' activated their portal account');

        Flash::success('Welcome. Your account is ready.');
        $this->redirect('portal');
    }

    public function forgotPassword(Request $request): void
    {
        if (!$request->isPost()) {
            $this->view('portal/auth/forgot-password', ['pageTitle' => 'Forgotten password']);
            return;
        }

        $email = mb_strtolower((string) $request->input('email', ''));

        $user = $email === '' ? null : Database::first(
            'SELECT * FROM client_users WHERE email = ? AND is_active = 1 AND password_hash IS NOT NULL',
            [$email]
        );

        if ($user !== null) {
            $token = random_token();
            Database::update('client_users', [
                'reset_token'   => $token,
                'reset_expires' => date('Y-m-d H:i:s', time() + 3600),
            ], ['id' => $user['id']]);

            Mailer::to((string) $user['email'], (string) $user['name'])
                ->subject('Reset your ExcelBids portal password')
                ->view('password-reset', [
                    'name'           => (string) $user['name'],
                    'link'           => url('portal/reset-password/' . $token),
                    'expiresMinutes' => 60,
                ])
                ->send();
        }

        Flash::success('If that email address has a portal account, a reset link is on its way.');
        $this->redirect('portal/login');
    }

    public function resetPassword(Request $request, array $params): void
    {
        $token = (string) ($params['token'] ?? '');

        $user = Database::first(
            'SELECT * FROM client_users WHERE reset_token = ? AND reset_expires > ? AND is_active = 1',
            [$token, date('Y-m-d H:i:s')]
        );

        if ($user === null) {
            Flash::error('That reset link has expired or has already been used. Please request a new one.');
            $this->redirect('portal/forgot-password');
        }

        if (!$request->isPost()) {
            $this->view('portal/auth/reset-password', [
                'pageTitle' => 'Choose a new password',
                'token'     => $token,
                'name'      => (string) $user['name'],
            ]);
            return;
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
        ], ['password' => 'Password']);

        if ($validator->fails()) {
            Flash::setErrors($validator->errors());
            Flash::error('Please correct the highlighted fields.');
            $this->redirect('portal/reset-password/' . $token);
        }

        Database::update('client_users', [
            'password_hash' => Auth::hash((string) $request->raw('password')),
            'reset_token'   => null,
            'reset_expires' => null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        Auth::clearAttempts(Auth::CLIENT, (string) $user['email']);
        Flash::success('Your password has been changed. You can now sign in.');
        $this->redirect('portal/login');
    }

    /** The client's own contact details and password. */
    public function account(Request $request): void
    {
        $user = $this->client();

        if (!$request->isPost()) {
            $this->view('portal/account', [
                'pageTitle' => 'My details',
                'heading'   => 'My details',
                'active'    => 'account',
                'user'      => $user,
            ], 'portal/partials/layout');
            return;
        }

        if ((string) $request->input('action', 'profile') === 'password') {
            $this->changePassword($request, $user);
            return;
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|min:2|max:140',
            'job_title' => 'nullable|max:120',
            'phone'     => 'nullable|phone|max:40',
        ], ['name' => 'Name', 'job_title' => 'Job title']);

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), '/portal/account');
        }

        // The email address is the account identifier, so only staff change it.
        Database::update('client_users', [
            'name'       => (string) $request->input('name'),
            'job_title'  => (string) $request->input('job_title', ''),
            'phone'      => (string) $request->input('phone', ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        Flash::success('Your details have been saved.');
        $this->redirect('portal/account');
    }

    /** @param array<string,mixed> $user */
    private function changePassword(Request $request, array $user): void
    {
        $current = (string) $request->raw('current_password', '');

        if (Auth::attempt(Auth::CLIENT, (string) $user['email'], $current) === null) {
            Flash::setErrors(['current_password' => 'That is not your current password.']);
            Flash::error('Please correct the highlighted fields.');
            $this->redirect('portal/account');
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
        ], ['password' => 'New password']);

        if ($validator->fails()) {
            Flash::setErrors($validator->errors());
            Flash::error('Please correct the highlighted fields.');
            $this->redirect('portal/account');
        }

        Database::update('client_users', [
            'password_hash' => Auth::hash((string) $request->raw('password')),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], ['id' => $user['id']]);

        Flash::success('Your password has been changed.');
        $this->redirect('portal/account');
    }
}
