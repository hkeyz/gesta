<?php

namespace App\MobileApi\V1\Http\Controllers;

use App\Contact;
use App\Http\Controllers\Controller;
use App\MobileApi\V1\Http\Requests\ListRequest;
use App\MobileApi\V1\Services\AccessContext;
use App\MobileApi\V1\Services\TransactionPresenter;
use App\MobileApi\V1\Support\ApiResponse;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    use ApiResponse;

    public function index(ListRequest $request, AccessContext $access)
    {
        $user = $request->user();
        if (! $user->can('customer.view')
            && ! $user->can('customer.view_own')
            && ! $user->can('supplier.view')
            && ! $user->can('supplier.view_own')
            && ! $user->can('dashboard.data')) {
            return $this->failure(__('Unauthorized action.'), [], 403, 'forbidden');
        }

        $type = $request->input('contact_type');
        $query = Contact::query()
            ->where('business_id', $access->businessId($request))
            ->where('contact_status', 'active');

        if ($type === 'customer') {
            $query->whereIn('type', ['customer', 'both']);
        } elseif ($type === 'supplier') {
            $query->whereIn('type', ['supplier', 'both']);
        } elseif ($type === 'both') {
            $query->where('type', 'both');
        } else {
            $query->whereIn('type', ['customer', 'supplier', 'both']);
        }
        $this->applyPermissionScope($query, $user);

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($nested) use ($search) {
                $nested->where('name', 'like', $search)
                    ->orWhere('supplier_business_name', 'like', $search)
                    ->orWhere('contact_id', 'like', $search)
                    ->orWhere('mobile', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        $paginator = $query->orderBy('name')->paginate((int) $request->input('per_page', 20));
        $items = $paginator->getCollection()
            ->map(fn (Contact $contact) => $this->presentContact($contact))
            ->values();

        return $this->success($items, array_merge($this->realtimeMeta(30), [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]));
    }

    public function show(
        Request $request,
        int $contact,
        AccessContext $access,
        TransactionPresenter $presenter
    ) {
        $user = $request->user();
        $query = Contact::query()
            ->where('business_id', $access->businessId($request))
            ->where('contact_status', 'active');
        $this->applyPermissionScope($query, $user);
        $model = $query->find($contact);
        if (empty($model)) {
            return $this->failure(__('Contact not found.'), [], 404, 'not_found');
        }

        $locationIds = $access->permittedLocationIds($user);
        $transactions = Transaction::query()
            ->with([
                'contact:id,name,supplier_business_name,mobile',
                'location:id,name',
                'sales_person:id,first_name,last_name',
            ])
            ->where('business_id', $access->businessId($request))
            ->where('contact_id', $model->id)
            ->orderByDesc('transaction_date');
        $access->applyLocationScope($transactions, 'location_id', $locationIds);
        $this->applyTransactionPermissionScope($transactions, $user);

        $recent = (clone $transactions)->limit(20)->get()
            ->map(fn (Transaction $transaction) => $presenter->summary($transaction))
            ->values();
        $stats = (clone $transactions)
            ->reorder()
            ->selectRaw(
                'SUM(CASE WHEN type = "sell" THEN final_total ELSE 0 END) as sales_total, SUM(CASE WHEN type = "purchase" THEN final_total ELSE 0 END) as purchase_total, SUM(CASE WHEN type = "sell" AND payment_status IN ("due", "partial") THEN final_total ELSE 0 END) as sales_due, COUNT(*) as transaction_count'
            )->first();

        return $this->success(array_merge($this->presentContact($model), [
            'pay_term_number' => $model->pay_term_number,
            'pay_term_type' => $model->pay_term_type,
            'created_at' => optional($model->created_at)->toIso8601String(),
            'statistics' => [
                'sales_total' => round((float) ($stats->sales_total ?? 0), 4),
                'purchase_total' => round((float) ($stats->purchase_total ?? 0), 4),
                'sales_due' => round((float) ($stats->sales_due ?? 0), 4),
                'transaction_count' => (int) ($stats->transaction_count ?? 0),
            ],
            'recent_transactions' => $recent,
        ]), $this->realtimeMeta(30));
    }

    protected function applyPermissionScope($query, $user): void
    {
        if ($user->can('dashboard.data')) {
            return;
        }

        $query->where(function ($scope) use ($user) {
            $hasClause = false;

            if ($user->can('customer.view')) {
                $scope->whereIn('contacts.type', ['customer', 'both']);
                $hasClause = true;
            } elseif ($user->can('customer.view_own')) {
                $scope->where(function ($customer) use ($user) {
                    $customer->whereIn('contacts.type', ['customer', 'both'])
                        ->where(function ($owned) use ($user) {
                            $owned->where('contacts.created_by', $user->id)
                                ->orWhereExists(function ($access) use ($user) {
                                    $access->select(DB::raw(1))
                                        ->from('user_contact_access')
                                        ->whereColumn('user_contact_access.contact_id', 'contacts.id')
                                        ->where('user_contact_access.user_id', $user->id);
                                });
                        });
                });
                $hasClause = true;
            }

            $method = $hasClause ? 'orWhere' : 'where';
            if ($user->can('supplier.view')) {
                $scope->{$method}(function ($supplier) {
                    $supplier->whereIn('contacts.type', ['supplier', 'both']);
                });
            } elseif ($user->can('supplier.view_own')) {
                $scope->{$method}(function ($supplier) use ($user) {
                    $supplier->whereIn('contacts.type', ['supplier', 'both'])
                        ->where(function ($owned) use ($user) {
                            $owned->where('contacts.created_by', $user->id)
                                ->orWhereExists(function ($access) use ($user) {
                                    $access->select(DB::raw(1))
                                        ->from('user_contact_access')
                                        ->whereColumn('user_contact_access.contact_id', 'contacts.id')
                                        ->where('user_contact_access.user_id', $user->id);
                                });
                        });
                });
            }
        });
    }

    protected function presentContact(Contact $contact): array
    {
        return [
            'id' => (int) $contact->id,
            'contact_id' => $contact->contact_id,
            'type' => $contact->type,
            'name' => $contact->supplier_business_name ?: $contact->name,
            'person_name' => $contact->full_name ?: $contact->name,
            'mobile' => $contact->mobile,
            'landline' => $contact->landline,
            'alternate_number' => $contact->alternate_number,
            'email' => $contact->email,
            'tax_number' => $contact->tax_number,
            'balance' => round((float) $contact->balance, 4),
            'credit_limit' => $contact->credit_limit !== null
                ? round((float) $contact->credit_limit, 4)
                : null,
            'address' => $contact->contact_address_array,
        ];
    }

    protected function applyTransactionPermissionScope($query, $user): void
    {
        if ($user->can('dashboard.data')) {
            $query->whereIn('type', [
                'sell',
                'purchase',
                'sell_return',
                'purchase_return',
            ]);

            return;
        }

        $globalTypes = [];
        $ownedTypes = [];
        if ($user->can('sell.view')) {
            $globalTypes = array_merge($globalTypes, ['sell', 'sell_return']);
        } elseif ($user->can('sell.view_own')) {
            $ownedTypes = array_merge($ownedTypes, ['sell', 'sell_return']);
        }
        if ($user->can('purchase.view')) {
            $globalTypes = array_merge($globalTypes, ['purchase', 'purchase_return']);
        } elseif ($user->can('view_own_purchase')) {
            $ownedTypes = array_merge($ownedTypes, ['purchase', 'purchase_return']);
        }

        if (empty($globalTypes) && empty($ownedTypes)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($scope) use ($globalTypes, $ownedTypes, $user) {
            if (! empty($globalTypes)) {
                $scope->whereIn('type', $globalTypes);
            }
            if (! empty($ownedTypes)) {
                $method = empty($globalTypes) ? 'where' : 'orWhere';
                $scope->{$method}(function ($owned) use ($ownedTypes, $user) {
                    $owned->whereIn('type', $ownedTypes)
                        ->where('created_by', $user->id);
                });
            }
        });
    }
}
