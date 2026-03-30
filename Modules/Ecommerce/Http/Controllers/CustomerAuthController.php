<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Ecommerce\Entities\EcomCustomer;
use Modules\Ecommerce\Services\StorefrontService;

class CustomerAuthController extends Controller
{
    public function __construct(protected StorefrontService $storefrontService)
    {
    }

    public function showLogin(string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);

        return view('ecommerce::account.login', compact('store', 'settings'));
    }

    public function login(Request $request, string $store_slug)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('ecom_customer')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return redirect()->back()->withErrors([
                'email' => 'Invalid login credentials.',
            ])->withInput($request->except('password'));
        }

        $customer = Auth::guard('ecom_customer')->user();
        $customer->last_login_at = Carbon::now();
        $customer->save();

        return redirect()->intended(route('ecommerce.account.orders', $store_slug));
    }

    public function showRegister(string $store_slug)
    {
        $store = $this->storefrontService->getStoreBySlug($store_slug);
        $settings = $this->storefrontService->getStoreSettings($store);

        return view('ecommerce::account.register', compact('store', 'settings'));
    }

    public function register(Request $request, string $store_slug)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191', 'unique:ecom_customers,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $customer = EcomCustomer::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'last_login_at' => Carbon::now(),
        ]);

        Auth::guard('ecom_customer')->login($customer, true);

        return redirect()->route('ecommerce.account.orders', $store_slug);
    }

    public function logout(Request $request, string $store_slug)
    {
        Auth::guard('ecom_customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('ecommerce.account.login', $store_slug);
    }
}