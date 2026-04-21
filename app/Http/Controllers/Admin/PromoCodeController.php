<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    public function index(Request $request): View
    {
        $q = PromoCode::query()->withCount('usages')->orderByDesc('id');

        if ($search = trim((string) $request->query('q', ''))) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $q->where('code', 'like', $like);
        }

        if ($request->query('active') === '1') {
            $q->where('is_active', true);
        } elseif ($request->query('active') === '0') {
            $q->where('is_active', false);
        }

        $promoCodes = $q->paginate(25)->withQueryString();
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        return view('admin.promo-codes.index', [
            'promoCodes' => $promoCodes,
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'auto_generate' => 'sometimes|boolean',
            'code' => 'nullable|string|max:64',
            'discount_type' => ['required', Rule::in([PromoCode::DISCOUNT_FIXED, PromoCode::DISCOUNT_PERCENT])],
            'discount_value' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
            'once_per_user' => 'sometimes|boolean',
            'first_purchase_only' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'applies_to_plan_ids' => 'nullable|array',
            'applies_to_plan_ids.*' => 'integer|exists:subscription_plans,id',
        ]);

        $code = $request->boolean('auto_generate')
            ? $this->generateUniqueCode()
            : PromoCode::normalizeCode((string) ($data['code'] ?? ''));

        if ($code === '') {
            return back()->with('error', 'Promo code cannot be empty.')->withInput();
        }

        if (PromoCode::query()->where('code', $code)->exists()) {
            return back()->with('error', 'That promo code already exists.')->withInput();
        }

        if ($data['discount_type'] === PromoCode::DISCOUNT_PERCENT && (float) $data['discount_value'] > 100) {
            return back()->with('error', 'Percent discount cannot exceed 100.')->withInput();
        }

        $planIds = $data['applies_to_plan_ids'] ?? null;
        if (is_array($planIds) && $planIds === []) {
            $planIds = null;
        }

        PromoCode::create([
            'code' => $code,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'expires_at' => $data['expires_at'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'once_per_user' => $request->boolean('once_per_user'),
            'first_purchase_only' => $request->boolean('first_purchase_only'),
            'is_active' => $request->boolean('is_active', true),
            'applies_to_plan_ids' => $planIds,
        ]);

        return redirect()
            ->route('admin.promo-codes.index')
            ->with('success', 'Promo code '.$code.' created.');
    }

    public function show(PromoCode $promoCode): View
    {
        $promoCode->loadCount('usages');

        $usages = PromoCodeUsage::query()
            ->where('promo_code_id', $promoCode->id)
            ->with(['user', 'subscriptionPlan'])
            ->orderByDesc('id')
            ->paginate(40);

        return view('admin.promo-codes.show', [
            'promoCode' => $promoCode,
            'usages' => $usages,
        ]);
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $promoCode->update(['is_active' => $data['is_active']]);

        return back()->with('success', 'Promo code status saved.');
    }

    private function generateUniqueCode(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $raw = '';
            for ($i = 0; $i < $length; $i++) {
                $raw .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $code = PromoCode::normalizeCode($raw);
            if (! PromoCode::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        return PromoCode::normalizeCode(Str::upper(Str::random(12)));
    }
}
