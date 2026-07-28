<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;

/**
 * Staff sign-in, password recovery and the personal account screen.
 */
final class AuthController extends Controller
{
    protected string $layout = 'admin/partials/auth-layout';

    public function login(Request $request): void
    {
        if (!$request->isPost()) {
            $this->view('admin/auth/login', ['pageTitle' => 'Sign in']);
            return;
        }

        $email = (string) $request->input('email', '');
        $password = (string) $request->raw('password', '');

        if ($email === '' || $password === '') {
            Flash::error('Please enter both your email address and password.');
            Flash::setOld(['email' => $email]);
            $this->redirect('admin/login');
        }

        if (Auth::tooManyAttempts(Auth::STAFF, $email)) {
            Flash::error('Too many failed sign-in attempts. Please wait ' . Auth::lockoutMinutes() . ' minutes and try again.');
            $this->redirect('admin/login');
        }

        $user = Auth::attempt(Auth::STAFF, $email, $password);

        if ($user === null) {
            Auth::recordFailedAttempt(Auth::STAFF, $email);
            Activity::log('auth.failed', 'user', null, 'Failed admin sign-in for ' . $email);
            // Deliberately vague: never confirm whether an address exists.
            Flash::error('Those details were not recognised.');
            Flash::setOld(['email' => $email]);
            $this->redirect('admin/login');
        }

        Auth::clearAttempts(Auth::STAFF, $email);
        Auth::login(Auth::STAFF, (int) $user['id']);
        Auth::recordLogin(Auth::STAFF, (int) $user['id']);
        Auth::pruneAttempts();
        Activity::log('auth.login', 'user', (int) $user['id'], 'Signed in');

        if ((int) $user['must_change_pw'] === 1) {
            Flash::warning('Please set a new password before continuing.');
            $this->redirect('admin/account');
        }

        $this->redirect(ltrim(Middleware::intended(Auth::STAFF, '/admin'), '/'));
    }

    public function logout(Request $request): void
    {
        if (Auth::check(Auth::STAFF)) {
            Activity::log('auth.logout', 'user', Auth::id(Auth::STAFF), 'Signed out');
        }
        Auth::logout(Auth::STAFF);
        Flash::success('You have been signed out.');
        $this->redirect('admin/login');
    }

    public function forgotPassword(Request $request): void
    {
        if (!$request->isPost()) {
            $this->view('admin/auth/forgot-password', ['pageTitle' => 'Forgotten password']);
            return;
        }

        $email = (string) $request->input('email', '');
        $user = $email !== '' ? User::findByEmail($email) : null;

        if ($user !== null && (int) $user['is_active'] === 1) {
            $token = User::createResetToken((int) $user['id']);

            Mailer::to((string) $user['email'], (string) $user['name'])
                ->subject('Reset your ExcelBids admin password')
                ->view('password-reset', [
                    'name'           => (string) $user['name'],
                    'link'           => url('admin/reset-password/' . $token),
                    'expiresMinutes' => 60,
                ])
                ->send();

            Activity::log('auth.reset_requested', 'user', (int) $user['id'], 'Password reset requested');
        }

        // Always the same response, so this cannot be used to enumerate accounts.
        Flash::success('If that email address has an account, a reset link is on its way.');
        $this->redirect('admin/login');
    }

    public function resetPassword(Request $request, array $params): void
    {
        $token = (string) ($params['token'] ?? '');
        $user = User::findByResetToken($token);

        if ($user === null) {
            Flash::error('That reset link has expired or has already been used. Please request a new one.');
            $this->redirect('admin/forgot-password');
        }

        if (!$request->isPost()) {
            $this->view('admin/auth/reset-password', [
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
            $this->redirect('admin/reset-password/' . $token);
        }

        User::setPassword((int) $user['id'], (string) $request->raw('password'));
        Auth::clearAttempts(Auth::STAFF, (string) $user['email']);
        Activity::log('auth.reset_completed', 'user', (int) $user['id'], 'Password reset completed');

        Flash::success('Your password has been changed. You can now sign in.');
        $this->redirect('admin/login');
    }

    /** The signed-in user's own profile and password. */
    public function account(Request $request): void
    {
        $user = $this->staff();

        if (!$request->isPost()) {
            $this->view('admin/account', [
                'pageTitle' => 'My account',
                'heading'   => 'My account',
                'crumb'     => 'Account',
                'user'      => $user,
            ], 'admin/partials/layout');
            return;
        }

        $action = (string) $request->input('action', 'profile');

        if ($action === 'password') {
            $this->changePassword($request, $user);
            return;
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|min:2|max:120',
            'email'     => 'required|email|max:190|unique:users,email,' . $user['id'],
            'job_title' => 'nullable|max:120',
            'phone'     => 'nullable|phone|max:40',
        ], ['name' => 'Name', 'email' => 'Email address', 'job_title' => 'Job title']);

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), '/admin/account');
        }

        User::update((int) $user['id'], [
            'name'      => (string) $request->input('name'),
            'email'     => (string) $request->input('email'),
            'job_title' => (string) $request->input('job_title', ''),
            'phone'     => (string) $request->input('phone', ''),
        ]);

        Activity::log('user.updated', 'user', (int) $user['id'], 'Updated own profile');
        Flash::success('Your details have been saved.');
        $this->redirect('admin/account');
    }

    /** @param array<string,mixed> $user */
    private function changePassword(Request $request, array $user): void
    {
        $current = (string) $request->raw('current_password', '');

        if (Auth::attempt(Auth::STAFF, (string) $user['email'], $current) === null) {
            Flash::setErrors(['current_password' => 'That is not your current password.']);
            Flash::error('Please correct the highlighted fields.');
            $this->redirect('admin/account');
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
        ], ['password' => 'New password']);

        if ($validator->fails()) {
            Flash::setErrors($validator->errors());
            Flash::error('Please correct the highlighted fields.');
            $this->redirect('admin/account');
        }

        User::setPassword((int) $user['id'], (string) $request->raw('password'));
        Activity::log('user.password_changed', 'user', (int) $user['id'], 'Changed own password');

        Flash::success('Your password has been changed.');
        $this->redirect('admin/account');
    }
}
