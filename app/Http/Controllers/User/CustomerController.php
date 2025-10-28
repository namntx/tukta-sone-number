<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\BettingType;
use App\Models\BettingRate;
use Illuminate\Http\Request;
use App\Services\BettingRateResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Map bet_key (trong form) -> type_code + meta cho BettingRateResolver.
     * Dùng chung cho create/edit để lấy default/override.
     */
    protected function betKeyToResolverArgs(string $betKey): array
    {
        // type_code phải trùng seed/DB của bạn
        return match ($betKey) {
            // MB – Đề
            'de_dau'        => ['type_code' => 'de_dau',      'meta' => []],
            'de_duoi'       => ['type_code' => 'de_duoi',     'meta' => []],
            'de_duoi_4so'   => ['type_code' => 'de_duoi_4',   'meta' => []], // hoặc 'de_duoi' + ['digits'=>4] nếu DB bạn đang dùng vậy

            // Bao lô
            'bao_lo_2'      => ['type_code' => 'bao_lo',      'meta' => ['digits'=>2]],
            'bao_lo_3'      => ['type_code' => 'bao_lo',      'meta' => ['digits'=>3]],
            'bao_lo_4'      => ['type_code' => 'bao_lo',      'meta' => ['digits'=>4]],

            // Xiên đá
            'da_thang_1dai' => ['type_code' => 'da_thang',    'meta' => ['dai_count'=>1]],
            'da_cheo_2dai'  => ['type_code' => 'da_xien',     'meta' => ['dai_count'=>2]],
            'xien_2'        => ['type_code' => 'xien',        'meta' => ['xien_size'=>2]],
            'xien_3'        => ['type_code' => 'xien',        'meta' => ['xien_size'=>3]],
            'xien_4'        => ['type_code' => 'xien',        'meta' => ['xien_size'=>4]],

            // Xỉu chủ
            'xiu_chu'       => ['type_code' => 'xiu_chu',     'meta' => []],

            // Bảy lô (MT/MN)
            'baylo_2'       => ['type_code' => 'bay_lo',      'meta' => ['digits'=>2]],
            'baylo_3'       => ['type_code' => 'bay_lo',      'meta' => ['digits'=>3]],

            default         => ['type_code' => null,          'meta' => []],
        };
    }

    // ====== cấu hình chuẩn bet_key theo nhóm (để render view & validate) ======
    protected function rateGroups(): array
    {
        // bet_key => label
        return [
            'Giá đề' => [
                'de_dau'       => 'Đề đầu',
                'de_duoi'      => 'Đề đuôi (GĐB)',
                'de_duoi_4so'  => 'Đề đuôi 4 số',
            ],
            'Giá bao lô' => [
                'bao_lo_2'     => 'Bao lô 2 số',
                'bao_lo_3'     => 'Bao lô 3 số',
                'bao_lo_4'     => 'Bao lô 4 số',
            ],
            'Giá xiên đá' => [
                'da_thang_1dai'=> 'Đá thẳng (1 đài)',
                'da_cheo_2dai' => 'Đá chéo (2 đài)',
                'xien_2'       => 'Xiên 2',
                'xien_3'       => 'Xiên 3',
                'xien_4'       => 'Xiên 4',
            ],
            'Giá Xỉu chủ' => [
                'xiu_chu'      => 'Xỉu chủ',
            ],
            'Giá Bảy lô (7 giải cuối) — chỉ MT/MN' => [
                'baylo_2'      => 'Bảy lô 2 số',
                'baylo_3'      => 'Bảy lô 3 số',
            ],
        ];
    }

    protected function regions(): array
    {
        // key => label
        return [
            'bac'  => 'Miền Bắc',
            'trung'=> 'Miền Trung',
            'nam'  => 'Miền Nam',
        ];
    }
    
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build query
        $query = $user->customers();
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        } else {
            // Default to active customers
            $query->where('is_active', true);
        }
        
        // Apply sorting
        $sort = $request->get('sort', 'name');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'asc');
                break;
            case 'created_at_desc':
                $query->orderBy('created_at', 'desc');
                break;
            case 'net_profit':
                $query->orderByRaw('(total_win_amount - total_lose_amount) ASC');
                break;
            case 'net_profit_desc':
                $query->orderByRaw('(total_win_amount - total_lose_amount) DESC');
                break;
            default:
                $query->orderBy('name', 'asc');
        }
        
        $customers = $query->paginate(20);
        
        // Calculate statistics using global date and region
        $globalDate = session('global_date', today());
        $globalRegion = session('global_region', 'bac');
        
        $todayStats = [
            'total_win' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->where('result', 'win')->sum('win_amount'),
            'total_lose' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->where('result', 'lose')->sum('bet_amount'),
        ];
        
        $monthlyStats = [
            'total_win' => $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->where('result', 'win')->sum('win_amount'),
            'total_lose' => $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->where('result', 'lose')->sum('bet_amount'),
        ];
        
        $yearlyStats = [
            'total_win' => $user->bettingTickets()->whereYear('betting_date', now()->year)->where('result', 'win')->sum('win_amount'),
            'total_lose' => $user->bettingTickets()->whereYear('betting_date', now()->year)->where('result', 'lose')->sum('bet_amount'),
        ];

        return view('user.customers.index', compact('customers', 'todayStats', 'monthlyStats', 'yearlyStats'));
    }

    // ====== CREATE (đã sửa) ======
    public function create()
    {
        $regions    = $this->regions();     // ['bac'=>'Miền Bắc', ...]
        $rateGroups = $this->rateGroups();  // group -> [bet_key=>label]
        $resolver   = app(BettingRateResolver::class);

        // Prefill từ DEFAULT (customer_id=null)
        $initialRates = [];
        foreach ($regions as $regionKey => $label) {
            $initialRates[$regionKey] = [];
            foreach ($rateGroups as $groupTitle => $pairs) {
                $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
                foreach ($pairs as $betKey => $lbl) {
                    if ($regionKey === 'bac' && $isBayLoGroup) continue;

                    $map = $this->betKeyToResolverArgs($betKey);
                    if (!$map['type_code']) continue;

                    $rate = $resolver->get(null, $regionKey, $map['type_code'], $map['meta']);
                    // Resolver trả ['buy_rate'=>..., 'payout'=>...] → map về commission/payout_times cho form
                    $initialRates[$regionKey][$betKey] = [
                        'commission'   => $rate['buy_rate'] ?? null,
                        'payout_times' => $rate['payout']   ?? null,
                    ];
                }
            }
        }

        return view('user.customers.create', compact('regions','rateGroups','initialRates'));
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        // Không xử lý bảng giá ở đây. Chuyển sang màn riêng để cấu hình.
        return redirect()
            ->route('user.customers.rates.edit', $customer->id)
            ->with('success', 'Tạo khách hàng thành công. Hãy cấu hình bảng giá.');
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load(['bettingRates.bettingType', 'bettingTickets.bettingType']);
        
        $recentTickets = $customer->bettingTickets()
            ->with('bettingType')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('user.customers.show', compact('customer', 'recentTickets'));
    }

    // ====== EDIT (đã sửa) ======
    public function edit(Customer $customer)
    {
        $regions    = $this->regions();
        $rateGroups = $this->rateGroups();
        $resolver   = app(BettingRateResolver::class);

        // Prefill từ GIÁ HIỆU LỰC (override KH if any → else default)
        $initialRates = [];
        foreach ($regions as $regionKey => $label) {
            $initialRates[$regionKey] = [];
            foreach ($rateGroups as $groupTitle => $pairs) {
                $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
                foreach ($pairs as $betKey => $lbl) {
                    if ($regionKey === 'bac' && $isBayLoGroup) continue;

                    $map = $this->betKeyToResolverArgs($betKey);
                    if (!$map['type_code']) continue;

                    $rate = $resolver->get($customer->id, $regionKey, $map['type_code'], $map['meta']);
                    $initialRates[$regionKey][$betKey] = [
                        'commission'   => $rate['buy_rate'] ?? null,
                        'payout_times' => $rate['payout']   ?? null,
                    ];
                }
            }
        }

        return view('user.customers.edit', compact('customer','regions','rateGroups','initialRates'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        // Không đụng vào bảng giá ở đây.
        return redirect()
            ->route('user.customers.rates.edit', $customer->id)
            ->with('success', 'Cập nhật khách hàng thành công.');
    }

    /**
     * Remove the specified customer (toggle active status)
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        try {
            $newStatus = !$customer->is_active;
            $customer->update(['is_active' => $newStatus]);

            $message = $newStatus ? 'Khách hàng đã được kích hoạt.' : 'Khách hàng đã được vô hiệu hóa.';
            
            return redirect()->route('user.customers.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('user.customers.index')
                ->with('error', 'Có lỗi xảy ra khi cập nhật trạng thái khách hàng: ' . $e->getMessage());
        }
    }

    /**
     * Get customer betting rates for API
     */
    public function getRates(Customer $customer)
    {
        $this->authorize('view', $customer);

        $rates = $customer->bettingRates()
            ->with('bettingType')
            ->active()
            ->get();

        return response()->json($rates);
    }
}
