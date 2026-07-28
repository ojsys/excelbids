<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;

/**
 * Staff accounts and their roles. Administrator-only.
 */
final class UserController extends Controller
{
    protected string $layout = 'admin/partials/layout';

    public function index(Request $request): void
    {
        $this->view('admin/users/index', [
            'pageTitle'  => 'Staff accounts',
            'heading'    => 'Staff accounts',
            'crumb'      => 'Configure',
            'active'     => 'users',
            'users'      => User::all(),
            'roles'      => Auth::roles(),
            'currentId'  => Auth::id(Auth::STAFF),
            'topActions' => '<a href="' . e(path('admin/users/create')) . '" class="btn btn-red btn-sm">+ New staff account</a>',
        ]);
    }

    public function create(Request $request): void
    {
        if (!$request->isPost()) {
            $this->view('admin/users/form', [
                'pageTitle' => 'New staff account',
                'heading'   => 'New staff account',
                'crumb'     => 'Staff accounts',
                'active'    => 'users',
                'user'      => null,
                'roles'     => Auth::roles(),
            ]);
            return;
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|min:2|max:120',
            'email'     => 'required|email|max:190|unique:users,email',
            'role'      => 'required|in:' . implode(',', array_keys(Auth::roles())),
            'password'  => 'required|password|confirmed',
            'job_title' => 'nullable|max:120',
            'phone'     => 'nullable|phone|max:40',
        ], ['name' => 'Name', 'email' => 'Email address', 'role' => 'Role', 'password' => 'Password', 'job_title' => 'Job title']);

        // A portal login and a staff account must not share an address.
        if ((int) Database::scalar('SELECT COUNT(*) FROM client_users WHERE email = ?', [mb_strtolower((string) $request->input('email'))], 0) > 0) {
            $validator->addError('email', 'That address is already used by a client portal login.');
        }

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), '/admin/users/create');
        }

        $id = User::create([
            'name'           => (string) $request->input('name'),
            'email'          => (string) $request->input('email'),
            'password_hash'  => Auth::hash((string) $request->raw('password')),
            'role'           => (string) $request->input('role'),
            'job_title'      => (string) $request->input('job_title', ''),
            'phone'          => (string) $request->input('phone', ''),
            'is_active'      => 1,
            'must_change_pw' => $request->boolean('must_change_pw') ? 1 : 0,
        ]);

        Activity::log('user.created', 'user', $id, 'Created staff account for ' . $request->input('email'));

        if ($request->boolean('send_welcome')) {
            $this->sendWelcome($id);
        }

        Flash::success('Staff account created for ' . $request->input('name') . '.');
        $this->redirect('admin/users');
    }

    public function edit(Request $request, array $params): void
    {
        $user = User::find((int) $params['id']);
        if ($user === null) {
            $this->notFound('That staff account could not be found.');
        }
        $id = (int) $user['id'];

        if (!$request->isPost()) {
            $this->view('admin/users/form', [
                'pageTitle' => 'Edit ' . $user['name'],
                'heading'   => 'Edit staff account',
                'crumb'     => 'Staff accounts',
                'active'    => 'users',
                'user'      => $user,
                'roles'     => Auth::roles(),
            ]);
            return;
        }

        $rules = [
            'name'      => 'required|min:2|max:120',
            'email'     => 'required|email|max:190|unique:users,email,' . $id,
            'role'      => 'required|in:' . implode(',', array_keys(Auth::roles())),
            'job_title' => 'nullable|max:120',
            'phone'     => 'nullable|phone|max:40',
        ];

        // The password is optional on edit — blank means "leave it alone".
        $newPassword = (string) $request->raw('password', '');
        if ($newPassword !== '') {
            $rules['password'] = 'password|confirmed';
        }

        $validator = Validator::make($request->all(), $rules, [
            'name' => 'Name', 'email' => 'Email address', 'role' => 'Role', 'password' => 'Password',
        ]);

        // Never let the last administrator lose the role that keeps the door open.
        $newRole = (string) $request->input('role');
        if ($newRole !== 'admin' && User::isLastActiveAdmin($id)) {
            $validator->addError('role', 'This is the only active administrator. Promote someone else first.');
        }

        if ($validator->fails()) {
            Flash::failValidation($validator->errors(), $request->all(), '/admin/users/' . $id . '/edit');
        }

        User::update($id, [
            'name'      => (string) $request->input('name'),
            'email'     => (string) $request->input('email'),
            'role'      => $newRole,
            'job_title' => (string) $request->input('job_title', ''),
            'phone'     => (string) $request->input('phone', ''),
        ]);

        if ($newPassword !== '') {
            User::setPassword($id, $newPassword);
            Activity::log('user.password_reset', 'user', $id, 'Set a new password for ' . $user['email']);
        }

        Activity::log('user.updated', 'user', $id, 'Updated staff account ' . $user['email']);
        Flash::success('Staff account updated.');
        $this->redirect('admin/users');
    }

    public function toggle(Request $request, array $params): void
    {
        $user = User::find((int) $params['id']);
        if ($user === null) {
            $this->notFound('That staff account could not be found.');
        }
        $id = (int) $user['id'];

        if ($id === Auth::id(Auth::STAFF)) {
            Flash::error('You cannot suspend your own account.');
            $this->redirect('admin/users');
        }

        if ((int) $user['is_active'] === 1 && User::isLastActiveAdmin($id)) {
            Flash::error('This is the only active administrator. Promote someone else before suspending them.');
            $this->redirect('admin/users');
        }

        $active = (int) $user['is_active'] === 1 ? 0 : 1;
        User::update($id, ['is_active' => $active]);

        Activity::log('user.toggled', 'user', $id, ($active ? 'Re-enabled ' : 'Suspended ') . $user['email']);
        Flash::success($active ? $user['name'] . ' can sign in again.' : $user['name'] . ' has been suspended.');
        $this->redirect('admin/users');
    }

    public function destroy(Request $request, array $params): void
    {
        $user = User::find((int) $params['id']);
        if ($user === null) {
            $this->notFound('That staff account could not be found.');
        }
        $id = (int) $user['id'];

        if ($id === Auth::id(Auth::STAFF)) {
            Flash::error('You cannot delete your own account.');
            $this->redirect('admin/users');
        }

        if (User::isLastActiveAdmin($id)) {
            Flash::error('This is the only active administrator and cannot be deleted.');
            $this->redirect('admin/users');
        }

        // Owned records are preserved — the foreign keys set the owner to NULL.
        Database::delete('users', ['id' => $id]);

        Activity::log('user.deleted', 'user', $id, 'Deleted staff account ' . $user['email']);
        Flash::success($user['name'] . "'s account has been deleted. Their bids and clients are now unassigned.");
        $this->redirect('admin/users');
    }

    private function sendWelcome(int $userId): void
    {
        $user = User::find($userId);
        if ($user === null) {
            return;
        }

        $token = User::createResetToken($userId);

        Mailer::to((string) $user['email'], (string) $user['name'])
            ->subject('Your ExcelBids admin account')
            ->view('password-reset', [
                'name'           => (string) $user['name'],
                'link'           => url('admin/reset-password/' . $token),
                'expiresMinutes' => 60,
            ])
            ->send();
    }
}
