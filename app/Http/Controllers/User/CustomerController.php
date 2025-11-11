<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\BettingType;
use App\Models\BettingRate;
use App\Http\Requests\CustomerRequest;
use Illuminate\Http\Request;
use App\Services\BettingRateResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Map bet_key (trong form) -> type_code + meta cho BettingRateResolver.
     * Dùng chung cho create/edit để lấy default/override.
     */
    protected function betKeyToResolverArgs(string $betKey): array
    {
        // type_code phải trùng với BettingMessageParser và BettingSettlementService
        // Parser và Settlement dùng: 'dau', 'duoi', 'dau_duoi', 'bao_lo', 'xien', etc.
        return match ($betKey) {
            // MB – Đề (phải match với parser: 'dau', 'duoi')
            'de_dau'        => ['type_code' => 'dau',         'meta' => []],
            'de_duoi'       => ['type_code' => 'duoi',        'meta' => []],
            'de_duoi_4so'   => ['type_code' => 'duoi',        'meta' => ['digits' => 4]],

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
        
        // Calculate daily stats for each customer based on global_date and global_region
        $customerIds = $customers->pluck('id');
        $dailyStatsByCustomer = [];
        
        if ($customerIds->isNotEmpty()) {
            // Get all tickets for these customers on this date/region
            $tickets = \App\Models\BettingTicket::query()
                ->whereIn('customer_id', $customerIds)
                ->whereDate('betting_date', $globalDate)
                ->where('region', $globalRegion)
                ->get(['customer_id', 'result', 'win_amount', 'payout_amount', 'betting_data']);
            
            // Calculate stats per customer
            foreach ($tickets->groupBy('customer_id') as $customerId => $customerTickets) {
                $dailyStatsByCustomer[$customerId] = [
                    'daily_win' => $customerTickets->where('result', 'win')->sum('payout_amount'), // Tiền thắng customer nhận
                    'daily_lose' => $customerTickets->sum(function($ticket) {
                        return $ticket->betting_data['total_cost_xac'] ?? 0; // Tiền xác customer trả
                    }),
                ];
            }
        }
        
        // Attach daily stats to each customer
        $customers->each(function ($customer) use ($dailyStatsByCustomer) {
            $stats = $dailyStatsByCustomer[$customer->id] ?? ['daily_win' => 0, 'daily_lose' => 0];
            $customer->daily_win_for_date = $stats['daily_win'];
            $customer->daily_lose_for_date = $stats['daily_lose'];
        });
        
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

        return view('user.customers.index', compact('customers', 'todayStats', 'monthlyStats', 'yearlyStats', 'globalDate'));
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
            // Build resolver cho region này với customer_id = null (default)
            $resolver->build(null, $regionKey);
            
            $initialRates[$regionKey] = [];
            foreach ($rateGroups as $groupTitle => $pairs) {
                $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
                foreach ($pairs as $betKey => $lbl) {
                    if ($regionKey === 'bac' && $isBayLoGroup) continue;

                    $map = $this->betKeyToResolverArgs($betKey);
                    if (!$map['type_code']) continue;

                    // Resolve rate - trả về [buy_rate, payout]
                    $rate = $resolver->resolve(
                        $map['type_code'],
                        $map['meta']['digits'] ?? null,
                        $map['meta']['xien_size'] ?? null,
                        $map['meta']['dai_count'] ?? null
                    );
                    
                    // Map từ [buy_rate, payout] sang ['buy_rate' => ..., 'payout' => ...]
                    $initialRates[$regionKey][$betKey] = [
                        'commission'   => $rate[0] ?? null,
                        'payout_times' => $rate[1] ?? null,
                    ];
                }
            }
        }

        return view('user.customers.create', compact('regions','rateGroups','initialRates'));
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        // Lấy validated data (không bao gồm rates)
        $validated = $request->validated();
        $rates = $validated['rates'] ?? [];
        unset($validated['rates']);

        // Tạo customer
        $customer = Customer::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        // Xử lý rates nếu có
        $ratesJson = [];
        $regions = ['bac', 'trung', 'nam'];

        foreach ($regions as $region) {
            if (!isset($rates[$region]) || !is_array($rates[$region])) {
                continue;
            }

            foreach ($rates[$region] as $betKey => $rateData) {
                // Map betKey -> type_code + meta
                $map = $this->betKeyToResolverArgs($betKey);
                if (!$map['type_code']) {
                    continue;
                }

                $typeCode = $map['type_code'];
                $meta = $map['meta'];

                // Lấy giá trị từ form (có thể là string)
                $commission = $rateData['commission'] ?? null;
                $payoutTimes = $rateData['payout_times'] ?? null;
                
                // Convert empty string hoặc string "0" thành null
                if ($commission === '' || $commission === '0' || $commission === 0) {
                    $commission = null;
                }
                if ($payoutTimes === '' || $payoutTimes === '0' || $payoutTimes === 0) {
                    $payoutTimes = null;
                }
                
                // Convert sang float nếu có giá trị
                if ($commission !== null) {
                    $commission = is_numeric($commission) ? (float)$commission : null;
                }
                if ($payoutTimes !== null) {
                    $payoutTimes = is_numeric($payoutTimes) ? (float)$payoutTimes : null;
                }
                
                // Skip nếu cả hai đều null (dùng default)
                if ($commission === null && $payoutTimes === null) {
                    continue;
                }

                // Build composite key: "region:type_code" hoặc "region:type_code:d2:x3:c4"
                $keyParts = [$region, $typeCode];
                if (isset($meta['digits']) && $meta['digits'] !== null) {
                    $keyParts[] = "d{$meta['digits']}";
                }
                if (isset($meta['xien_size']) && $meta['xien_size'] !== null) {
                    $keyParts[] = "x{$meta['xien_size']}";
                }
                if (isset($meta['dai_count']) && $meta['dai_count'] !== null) {
                    $keyParts[] = "c{$meta['dai_count']}";
                }
                $compositeKey = implode(':', $keyParts);

                // Nếu một trong hai null, lấy từ default
                if ($commission === null || $payoutTimes === null) {
                    $resolver = app(\App\Services\BettingRateResolver::class);
                    $resolver->build(null, $region); // Load defaults
                    $defaultRate = $resolver->resolve(
                        $typeCode,
                        $meta['digits'] ?? null,
                        $meta['xien_size'] ?? null,
                        $meta['dai_count'] ?? null
                    );
                    
                    $commission = $commission ?? $defaultRate[0];
                    $payoutTimes = $payoutTimes ?? $defaultRate[1];
                }

                // Lưu vào JSON
                $ratesJson[$compositeKey] = [
                    'buy_rate' => (float)$commission,
                    'payout' => (float)$payoutTimes,
                ];
            }
        }

        // Lưu rates vào JSON column
        if (!empty($ratesJson)) {
            $customer->betting_rates = $ratesJson;
            $customer->save();
            
            \Log::info('Customer Store - Rates saved', [
                'customer_id' => $customer->id,
                'rates_count' => count($ratesJson),
                'sample_keys' => array_slice(array_keys($ratesJson), 0, 5)
            ]);
        } else {
            \Log::warning('Customer Store - No rates to save', [
                'customer_id' => $customer->id,
                'rates_received' => $rates
            ]);
        }

        return redirect()
            ->route('user.customers.index')
            ->with('success', 'Tạo khách hàng và bảng giá thành công.');
    }

    /**
     * Display the specified customer
     */
    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        // Lấy ngày và miền từ request hoặc session
        $reportDate = $request->filled('date') 
            ? $request->date 
            : session('global_date', today());
        
        $region = $request->filled('region')
            ? \App\Support\Region::normalizeKey($request->region)
            : session('global_region', 'nam');

        // Lấy tất cả tickets của customer cho ngày và miền này, sắp xếp theo thứ tự nhập (created_at ASC)
        $tickets = $customer->bettingTickets()
            ->whereDate('betting_date', $reportDate)
            ->where('region', $region)
            ->with('bettingType')
            ->orderBy('created_at', 'asc') // Thứ tự user nhập
            ->get();

        // Group tickets theo loại cược (giống betting-tickets.index)
        // Tách riêng bao lô 2, 3, 4 số
        $ticketsByType = $tickets->groupBy(function($ticket) {
            $bettingType = $ticket->bettingType;
            $typeId = $ticket->betting_type_id;
            
            // Nếu là bao lô, thêm digits vào key để tách riêng
            if ($bettingType && $bettingType->code === 'bao_lo') {
                $bettingData = $ticket->betting_data ?? [];
                $digits = null;
                
                // Tìm digits trong betting_data
                if (is_array($bettingData)) {
                    if (isset($bettingData[0]) && is_array($bettingData[0])) {
                        $digits = $bettingData[0]['meta']['digits'] ?? null;
                    } elseif (isset($bettingData['meta']['digits'])) {
                        $digits = $bettingData['meta']['digits'];
                    }
                }
                
                $digits = $digits ?? 2;
                return $typeId . '_bao_lo_' . $digits;
            }
            
            return $typeId;
        });

        // Tính toán theo từng loại cược
        $statsByType = [];
        foreach ($ticketsByType as $groupKey => $typeTickets) {
            $bettingType = $typeTickets->first()->bettingType;
            $rawBetAmount = $typeTickets->sum('bet_amount');
            
            // Tính tiền cược đã nhân cho đá xiên (2 đài: * 1, 3 đài: * 3, 4 đài: * 6)
            $typeBetAmount = $rawBetAmount;
            if ($bettingType && $bettingType->code === 'da_xien') {
                $typeBetAmount = $typeTickets->sum(function($ticket) {
                    $bettingData = $ticket->betting_data ?? [];
                    $meta = [];
                    
                    if (is_array($bettingData) && isset($bettingData[0]) && is_array($bettingData[0])) {
                        $meta = $bettingData[0]['meta'] ?? [];
                    } elseif (isset($bettingData['meta'])) {
                        $meta = $bettingData['meta'];
                    }
                    
                    $stationCount = (int)($meta['dai_count'] ?? 0);
                    if (!$stationCount && !empty($meta['station_pairs']) && is_array($meta['station_pairs'])) {
                        $names = [];
                        foreach ($meta['station_pairs'] as $p) {
                            if (is_array($p) && count($p) === 2) {
                                $names[$p[0]] = true; $names[$p[1]] = true;
                            }
                        }
                        $stationCount = count($names);
                    }
                    
                    $multiplier = match($stationCount) {
                        2 => 1,
                        3 => 3,
                        4 => 6,
                        default => 1,
                    };
                    
                    return ($ticket->bet_amount ?? 0) * $multiplier;
                });
            }
            
            $typeWinAmount = $typeTickets->where('result', 'win')->sum('win_amount');
            $typePayoutAmount = $typeTickets->where('result', 'win')->sum('payout_amount');
            $typeXacAmount = $typeTickets->sum(function($ticket) {
                return $ticket->betting_data['total_cost_xac'] ?? 0;
            });
            
            // Tính Ăn/Thua: nếu có payout thì = payout - xac, nếu không thì = -xac
            if ($typePayoutAmount > 0) {
                $typeEatThua = $typePayoutAmount - $typeXacAmount;
            } else {
                $typeEatThua = -$typeXacAmount;
            }
            
            // Tạo label cho loại cược
            $label = $this->getBettingTypeLabel($bettingType, $typeTickets->first());
            
            $statsByType[$groupKey] = [
                'label' => $label,
                'tien_cuoc' => $typeBetAmount,
                'cuoc_an' => $typeWinAmount,
                'xac' => $typeXacAmount,
                'an_thua' => $typeEatThua,
                'an_thua_color' => $typeEatThua >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400',
            ];
        }

        // Tổng kết - tính bằng cách cộng từng loại cược (giống betting-tickets.index)
        $totalTienCuoc = array_sum(array_column($statsByType, 'tien_cuoc'));
        $totalCuocAn = array_sum(array_column($statsByType, 'cuoc_an'));
        $totalXac = array_sum(array_column($statsByType, 'xac'));
        $totalAnThua = array_sum(array_column($statsByType, 'an_thua'));

        // Group tickets theo original_message (mỗi original_message là 1 input cược)
        // Giữ thứ tự theo created_at của ticket đầu tiên trong mỗi group
        $ticketsByMessage = $tickets->groupBy('original_message')
            ->sortBy(function($group) {
                return $group->first()->created_at ?? now();
            })
            ->values();

        return view('user.customers.show', compact(
            'customer',
            'tickets',
            'ticketsByMessage',
            'statsByType',
            'totalTienCuoc',
            'totalCuocAn',
            'totalXac',
            'totalAnThua',
            'reportDate',
            'region'
        ));
    }

    /**
     * Get betting type label (giống betting-tickets.index)
     */
    private function getBettingTypeLabel($bettingType, $ticket): string
    {
        if (!$bettingType) {
            return 'Không xác định';
        }

        $code = $bettingType->code;
        $bettingData = $ticket->betting_data ?? [];
        $meta = [];

        if (is_array($bettingData) && isset($bettingData[0]) && is_array($bettingData[0])) {
            $meta = $bettingData[0]['meta'] ?? [];
        } elseif (isset($bettingData['meta'])) {
            $meta = $bettingData['meta'];
        }

        // Bao lô: thêm số chữ số
        if ($code === 'bao_lo') {
            $digits = $meta['digits'] ?? 2;
            return "Bao lô {$digits} số";
        }

        // Đá xiên: thêm số đài
        if ($code === 'da_xien') {
            $daiCount = $meta['dai_count'] ?? 2;
            return "Đá xiên {$daiCount} đài";
        }

        // Xiên: thêm size
        if ($code === 'xien') {
            $xienSize = $meta['xien_size'] ?? 2;
            return "Xiên {$xienSize}";
        }

        // Các loại khác: dùng name từ BettingType
        return $bettingType->name ?? $code;
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
            // Build resolver cho customer này và region này
            $resolver->build($customer->id, $regionKey);
            
            $initialRates[$regionKey] = [];
            foreach ($rateGroups as $groupTitle => $pairs) {
                $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
                foreach ($pairs as $betKey => $lbl) {
                    if ($regionKey === 'bac' && $isBayLoGroup) continue;

                    $map = $this->betKeyToResolverArgs($betKey);
                    if (!$map['type_code']) continue;

                    // Resolve rate - trả về [buy_rate, payout]
                    $rate = $resolver->resolve(
                        $map['type_code'],
                        $map['meta']['digits'] ?? null,
                        $map['meta']['xien_size'] ?? null,
                        $map['meta']['dai_count'] ?? null
                    );
                    
                    // Map từ [buy_rate, payout] sang ['buy_rate' => ..., 'payout' => ...]
                    $initialRates[$regionKey][$betKey] = [
                        'commission'   => $rate[0] ?? null,
                        'payout_times' => $rate[1] ?? null,
                    ];
                }
            }
        }

        return view('user.customers.edit', compact('customer','regions','rateGroups','initialRates'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        // Lấy validated data (không bao gồm rates)
        $validated = $request->validated();
        $rates = $validated['rates'] ?? [];
        unset($validated['rates']);

        // Update customer info
        $customer->update($validated);

        // Xử lý rates nếu có
        $ratesJson = [];
        $regions = ['bac', 'trung', 'nam'];

        foreach ($regions as $region) {
            if (!isset($rates[$region]) || !is_array($rates[$region])) {
                continue;
            }

            foreach ($rates[$region] as $betKey => $rateData) {
                // Map betKey -> type_code + meta
                $map = $this->betKeyToResolverArgs($betKey);
                if (!$map['type_code']) {
                    continue;
                }

                $typeCode = $map['type_code'];
                $meta = $map['meta'];

                // Lấy giá trị từ form (có thể là string)
                $commission = $rateData['commission'] ?? null;
                $payoutTimes = $rateData['payout_times'] ?? null;
                
                // Convert empty string hoặc string "0" thành null
                if ($commission === '' || $commission === '0' || $commission === 0) {
                    $commission = null;
                }
                if ($payoutTimes === '' || $payoutTimes === '0' || $payoutTimes === 0) {
                    $payoutTimes = null;
                }
                
                // Convert sang float nếu có giá trị
                if ($commission !== null) {
                    $commission = is_numeric($commission) ? (float)$commission : null;
                }
                if ($payoutTimes !== null) {
                    $payoutTimes = is_numeric($payoutTimes) ? (float)$payoutTimes : null;
                }
                
                // Skip nếu cả hai đều null (dùng default)
                if ($commission === null && $payoutTimes === null) {
                    continue;
                }

                // Build composite key: "region:type_code" hoặc "region:type_code:d2:x3:c4"
                $keyParts = [$region, $typeCode];
                if (isset($meta['digits']) && $meta['digits'] !== null) {
                    $keyParts[] = "d{$meta['digits']}";
                }
                if (isset($meta['xien_size']) && $meta['xien_size'] !== null) {
                    $keyParts[] = "x{$meta['xien_size']}";
                }
                if (isset($meta['dai_count']) && $meta['dai_count'] !== null) {
                    $keyParts[] = "c{$meta['dai_count']}";
                }
                $compositeKey = implode(':', $keyParts);

                // Nếu một trong hai null, lấy từ default
                if ($commission === null || $payoutTimes === null) {
                    $resolver = app(\App\Services\BettingRateResolver::class);
                    $resolver->build(null, $region); // Load defaults
                    $defaultRate = $resolver->resolve(
                        $typeCode,
                        $meta['digits'] ?? null,
                        $meta['xien_size'] ?? null,
                        $meta['dai_count'] ?? null
                    );
                    
                    $commission = $commission ?? $defaultRate[0];
                    $payoutTimes = $payoutTimes ?? $defaultRate[1];
                }

                // Lưu vào JSON
                $ratesJson[$compositeKey] = [
                    'buy_rate' => (float)$commission,
                    'payout' => (float)$payoutTimes,
                ];
            }
        }

        // Lưu rates vào JSON column
        // Lấy rates hiện có
        $existingRates = $customer->betting_rates ?? [];
        
        // Nếu có rates mới, merge vào (overwrite các key trùng)
        if (!empty($ratesJson)) {
            $existingRates = array_merge($existingRates, $ratesJson);
            
            \Log::info('Customer Update - Rates saved', [
                'customer_id' => $customer->id,
                'rates_count' => count($ratesJson),
                'total_rates' => count($existingRates),
                'sample_keys' => array_slice(array_keys($ratesJson), 0, 5)
            ]);
        } else {
            \Log::warning('Customer Update - No rates to save', [
                'customer_id' => $customer->id,
                'rates_received' => $rates
            ]);
        }
        
        // Luôn lưu (có thể là empty array hoặc merged rates)
        $customer->betting_rates = $existingRates;
        $customer->save();

        return redirect()
            ->route('user.customers.edit', $customer)
            ->with('success', 'Cập nhật khách hàng và bảng giá thành công.');
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        try {
            $customerName = $customer->name;
            $customer->delete();

            return redirect()->route('user.customers.index')
                ->with('success', "Đã xóa khách hàng '{$customerName}' thành công.");

        } catch (\Exception $e) {
            return redirect()->route('user.customers.index')
                ->with('error', 'Có lỗi xảy ra khi xóa khách hàng: ' . $e->getMessage());
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
