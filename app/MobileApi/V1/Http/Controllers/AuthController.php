<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Http\Controllers\Controller;
use App\MobileApi\V1\Http\Requests\LoginRequest;
use App\MobileApi\V1\Support\ApiResponse;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $user = User::with('business')
            ->where('username', (string) $request->input('username'))
            ->first();

        if (empty($user) || ! Hash::check($request->input('password'), $user->password)) {
            return $this->failure(
                __('The username or password is incorrect.'),
                [],
                401,
                'invalid_credentials'
            );
        }

        if (empty($user->business)) {
            return $this->failure(__('No business is associated with this account.'), [], 403, 'business_not_found');
        }

        if (! $user->business->is_active) {
            return $this->failure(__('The business is inactive.'), [], 403, 'business_inactive');
        }

        if ($user->status !== 'active' || ! $user->allow_login) {
            return $this->failure(__('This account is not allowed to sign in.'), [], 403, 'account_inactive');
        }

        try {
            $deviceName = $request->input('device_name') ?: 'Gesta mobile';
            $tokenResult = $user->createToken($deviceName);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure(
                __('Mobile authentication is not configured on the server.'),
                [],
                503,
                'mobile_tokens_not_configured'
            );
        }

        return $this->success([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($tokenResult->token->expires_at)->toIso8601String(),
            'user' => $this->userPayload($user),
        ], $this->realtimeMeta());
    }

    public function me(Request $request)
    {
        return $this->success([
            'user' => $this->userPayload($request->user()->loadMissing('business')),
        ], $this->realtimeMeta());
    }

    public function logout(Request $request)
    {
        $token = $request->user()->token();
        if (! empty($token)) {
            $token->revoke();
        }

        return $this->success([
            'message' => __('You have been signed out.'),
        ]);
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'full_name' => trim(implode(' ', array_filter([
                $user->surname,
                $user->first_name,
                $user->last_name,
            ]))),
            'email' => $user->email,
            'language' => $user->language,
            'business_id' => (int) $user->business_id,
            'business_name' => optional($user->business)->name,
            'roles' => $user->getRoleNames()->map(fn ($role) => preg_replace('/#\d+$/', '', $role))->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'image_url' => $user->image_url,
        ];
    }
}
