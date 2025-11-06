<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BettingTicket;
use App\Models\Customer;
use App\Models\BettingType;
use App\Models\LotteryResult;
use App\Services\BettingMessageParser;
use App\Services\BetPricingService;
use App\Services\BettingSettlementService;
use App\Support\Region;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BettingTicketController extends Controller
{
    use AuthorizesRequests;
    protected $messageParser;

    public function __construct(BettingMessageParser $messageParser)
    {
        $this->messageParser = $messageParser;
    }

    /**
     * Display a listing of betting tickets
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build query
        $query = $user->bettingTickets()
            ->with(['customer', 'bettingType'])
            ->orderBy('created_at', 'desc');

        // Filter by date (use global date if not specified)
        $filterDate = $request->filled('date') ? $request->date : session('global_date', today());
        $query->whereDate('betting_date', $filterDate);

        // Filter by region (use global region if not specified)
        $filterRegion = $request->filled('region')
                        ? Region::normalizeKey($request->region)
                        : session('global_region', 'nam');
        $query->where('region', $filterRegion);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by result
        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->get();
        $customers = $user->customers()->active()->get();

        // Calculate statistics using global date
        $globalDate = session('global_date', today());
        $globalRegion = session('global_region', 'nam');
        
        // Get all tickets for stats calculation
        $allTodayTickets = $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->get();
        $allMonthTickets = $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->get();
        $allYearTickets = $user->bettingTickets()->whereYear('betting_date', now()->year)->get();
        
        $todayStats = [
            'total_tickets' => $allTodayTickets->count(),
            'total_bet' => $allTodayTickets->sum('bet_amount'),
            'total_xac' => $allTodayTickets->sum(function($t) { return $t->betting_data['total_cost_xac'] ?? 0; }),
            'total_win' => $allTodayTickets->where('result', 'win')->sum('payout_amount'),
        ];

        $monthlyStats = [
            'total_tickets' => $allMonthTickets->count(),
            'total_bet' => $allMonthTickets->sum('bet_amount'),
            'total_xac' => $allMonthTickets->sum(function($t) { return $t->betting_data['total_cost_xac'] ?? 0; }),
            'total_win' => $allMonthTickets->where('result', 'win')->sum('payout_amount'),
        ];

        $yearlyStats = [
            'total_tickets' => $allYearTickets->count(),
            'total_bet' => $allYearTickets->sum('bet_amount'),
            'total_xac' => $allYearTickets->sum(function($t) { return $t->betting_data['total_cost_xac'] ?? 0; }),
            'total_win' => $allYearTickets->where('result', 'win')->sum('payout_amount'),
        ];

        return view('user.betting-tickets.index', compact('tickets', 'customers', 'todayStats', 'monthlyStats', 'yearlyStats', 'globalDate', 'globalRegion', 'filterDate', 'filterRegion'));
    }

    /**
     * Show the form for creating a new betting ticket
     */
    public function create()
    {
        $customers = Auth::user()->customers()->active()->get();
        $bettingTypes = BettingType::active()->ordered()->get();
        
        return view('user.betting-tickets.create', compact('customers', 'bettingTypes'));
    }

    /**
     * Store a newly created betting ticket
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'betting_date' => 'required|date|before_or_equal:today',
            'region' => 'required|string|max:50|in:Bắc,Trung,Nam,bac,trung,nam',
            'station' => 'required|string|max:100',
            'original_message' => 'required|string|max:1000',
        ], [
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'customer_id.exists' => 'Khách hàng không tồn tại.',
            'betting_date.required' => 'Vui lòng chọn ngày cược.',
            'betting_date.date' => 'Ngày cược không đúng định dạng.',
            'betting_date.before_or_equal' => 'Ngày cược không được lớn hơn ngày hiện tại.',
            'region.required' => 'Vui lòng chọn miền.',
            'region.in' => 'Miền không hợp lệ.',
            'station.required' => 'Vui lòng nhập tên đài.',
            'station.max' => 'Tên đài không được vượt quá 100 ký tự.',
            'original_message.required' => 'Vui lòng nhập tin nhắn cược.',
            'original_message.max' => 'Tin nhắn cược không được vượt quá 1000 ký tự.',
        ]);

        // Check if customer belongs to user
        $customer = Auth::user()->customers()->find($request->customer_id);
        if (!$customer) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Khách hàng không thuộc về bạn.'], 400);
            }
            return back()->withErrors(['customer_id' => 'Khách hàng không thuộc về bạn.'])
                        ->withInput();
        }

        // Normalize region to lowercase
        $region = strtolower($request->region);
        $regionMap = ['bắc' => 'bac', 'trung' => 'trung', 'nam' => 'nam'];
        $normalizedRegion = $regionMap[$region] ?? $region;

        // Parse the betting message with proper options
        $parseResult = $this->messageParser->parseMessage($request->original_message, [
            'region' => $normalizedRegion,
            'date' => $request->betting_date,
        ]);

        if (!$parseResult['is_valid']) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => implode(', ', $parseResult['errors'])], 400);
            }
            return back()->withErrors(['original_message' => implode(', ', $parseResult['errors'])])
                        ->withInput();
        }

        // Handle new parser format with multiple bets
        if (isset($parseResult['multiple_bets']) && count($parseResult['multiple_bets']) > 0) {
            return $this->createMultipleTickets($parseResult, $request, $normalizedRegion);
        }

        // Handle legacy single bet format
        // Convert legacy format to new format for cost calculation
        $bettingType = $parseResult['betting_type'];
        $numbers = $parseResult['numbers'];
        $amount = $parseResult['amount'];
        $station = $parseResult['station'];
        $stations = $parseResult['stations'];

        // Use station from parser if found, otherwise use manual input
        $stationName = $station ? $station->name : $request->station;

        // Build bet array for calculateCostXac
        $bet = [
            'type' => $bettingType->code ?? $bettingType,
            'amount' => $amount,
            'numbers' => $numbers,
            'meta' => [],
        ];

        // Calculate cost_xac using BetPricingService
        $costXac = $this->calculateCostXac($bet, $normalizedRegion, $request->customer_id);

        DB::beginTransaction();
        try {
            $ticket = Auth::user()->bettingTickets()->create([
                'customer_id' => $request->customer_id,
                'betting_type_id' => $bettingType->id,
                'betting_date' => $request->betting_date,
                'region' => $normalizedRegion,
                'station' => $stationName,
                'original_message' => $request->original_message,
                'parsed_message' => $parseResult['parsed_message'],
                'betting_data' => [
                    'numbers' => $numbers,
                    'betting_type' => $bettingType->name,
                    'betting_type_code' => $bettingType->code,
                    'stations' => $stations,
                    'total_cost_xac' => $costXac,
                ],
                'bet_amount' => $amount,
                'win_amount' => 0, // Will be calculated on settlement
                'payout_amount' => 0, // Will be calculated on settlement
            ]);

            DB::commit();

            $successMessage = 'Phiếu cược đã được tạo thành công với tiền xác ' . number_format($costXac, 0, ',', '.') . ' VNĐ.';

            // Return JSON if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMessage]);
            }

            return redirect()->route('user.betting-tickets.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo phiếu cược: ' . $e->getMessage()], 500);
            }
            
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi tạo phiếu cược: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Display the specified betting ticket
     */
    public function show(BettingTicket $bettingTicket)
    {
        $this->authorize('view', $bettingTicket);

        $bettingTicket->load(['customer', 'bettingType']);

        return view('user.betting-tickets.show', compact('bettingTicket'));
    }

    /**
     * Show the form for editing the betting ticket
     */
    public function edit(BettingTicket $bettingTicket)
    {
        $this->authorize('update', $bettingTicket);

        $customers = Auth::user()->customers()->active()->get();
        $bettingTypes = BettingType::active()->ordered()->get();

        return view('user.betting-tickets.edit', compact('bettingTicket', 'customers', 'bettingTypes'));
    }

    /**
     * Update the specified betting ticket
     */
    public function update(Request $request, BettingTicket $bettingTicket)
    {
        $this->authorize('update', $bettingTicket);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'betting_date' => 'required|date|before_or_equal:today',
            'region' => 'required|string|max:50|in:Bắc,Trung,Nam,bac,trung,nam',
            'station' => 'required|string|max:100',
            'original_message' => 'required|string|max:1000',
            'betting_type_id' => 'required|exists:betting_types,id',
            'bet_amount' => 'required|numeric|min:0',
            'betting_numbers' => 'nullable|string|max:500',
            'result' => 'required|in:win,lose,pending',
            'payout_amount' => 'nullable|numeric|min:0',
        ], [
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'customer_id.exists' => 'Khách hàng không tồn tại.',
            'betting_date.required' => 'Vui lòng chọn ngày cược.',
            'betting_date.date' => 'Ngày cược không đúng định dạng.',
            'betting_date.before_or_equal' => 'Ngày cược không được lớn hơn ngày hiện tại.',
            'region.required' => 'Vui lòng chọn miền.',
            'region.in' => 'Miền không hợp lệ.',
            'station.required' => 'Vui lòng nhập tên đài.',
            'station.max' => 'Tên đài không được vượt quá 100 ký tự.',
            'result.required' => 'Vui lòng chọn kết quả.',
            'result.in' => 'Kết quả không hợp lệ.',
            'payout_amount.numeric' => 'Tiền trả phải là số.',
            'payout_amount.min' => 'Tiền trả không được âm.',
        ]);

        // Check if customer belongs to user
        $customer = Auth::user()->customers()->find($request->customer_id);
        if (!$customer) {
            return back()->withErrors(['customer_id' => 'Khách hàng không thuộc về bạn.'])
                        ->withInput();
        }

        // Normalize region to lowercase
        $region = strtolower($request->region);
        $regionMap = ['bắc' => 'bac', 'trung' => 'trung', 'nam' => 'nam'];
        $normalizedRegion = $regionMap[$region] ?? $region;

        DB::beginTransaction();
        try {
            $oldResult = $bettingTicket->result;
            
            // Parse numbers from betting_numbers input
            $numbers = [];
            if ($request->filled('betting_numbers')) {
                $numbersInput = preg_replace('/[,\s]+/', ' ', trim($request->betting_numbers));
                $numbers = array_filter(array_map('trim', explode(' ', $numbersInput)));
            }
            
            // Try to parse original_message if changed
            $parsedMessage = $bettingTicket->parsed_message;
            $bettingData = $bettingTicket->betting_data ?? [];
            
            // Get new betting type
            $newBettingType = BettingType::find($request->betting_type_id);
            
            // If original_message changed, try to parse it
            if ($request->original_message !== $bettingTicket->original_message) {
                $parseResult = $this->messageParser->parseMessage($request->original_message, [
                    'region' => $normalizedRegion,
                    'date' => $request->betting_date,
                ]);
                
                if ($parseResult['is_valid']) {
                    $parsedMessage = $parseResult['parsed_message'];
                    
                    // Update betting_data with parsed info
                    if (isset($parseResult['numbers']) && !empty($parseResult['numbers'])) {
                        $numbers = $parseResult['numbers'];
                    }
                }
            }
            
            // Update betting_data with new values
            if (!empty($numbers)) {
                $bettingData['numbers'] = $numbers;
            }
            
            // Update betting_type in betting_data
            if ($newBettingType) {
                $bettingData['betting_type'] = $newBettingType->name;
                $bettingData['betting_type_code'] = $newBettingType->code;
            }
            
            // Always recalculate cost_xac when bet amount, type, or numbers change
            $bet = [
                'type' => $newBettingType->code ?? '',
                'amount' => $request->bet_amount,
                'numbers' => !empty($numbers) ? $numbers : ($bettingData['numbers'] ?? []),
                'meta' => [],
            ];
            $costXac = $this->calculateCostXac($bet, $normalizedRegion, $request->customer_id);
            $bettingData['total_cost_xac'] = $costXac;
            
            $bettingTicket->update([
                'customer_id' => $request->customer_id,
                'betting_type_id' => $request->betting_type_id,
                'betting_date' => $request->betting_date,
                'region' => $normalizedRegion,
                'station' => $request->station,
                'original_message' => $request->original_message,
                'parsed_message' => $parsedMessage,
                'betting_data' => $bettingData,
                'bet_amount' => $request->bet_amount,
                'result' => $request->result,
                'payout_amount' => $request->payout_amount ?? 0,
            ]);

            // Update customer statistics if result changed
            if ($oldResult !== $request->result && $request->result !== 'pending') {
                $this->updateCustomerStats($bettingTicket);
            }

            DB::commit();

            return redirect()->route('user.betting-tickets.index')
                ->with('success', 'Phiếu cược đã được cập nhật.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi cập nhật phiếu cược: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Remove the specified betting ticket
     */
    public function destroy(BettingTicket $bettingTicket)
    {
        $this->authorize('delete', $bettingTicket);

        DB::beginTransaction();
        try {
            // If ticket has result, we need to reverse the customer statistics
            if ($bettingTicket->result !== 'pending') {
                $this->reverseCustomerStats($bettingTicket);
            }

            $bettingTicket->delete();

            DB::commit();

            return redirect()->route('user.betting-tickets.index')
                ->with('success', 'Phiếu cược đã được xóa.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('user.betting-tickets.index')
                ->with('error', 'Có lỗi xảy ra khi xóa phiếu cược: ' . $e->getMessage());
        }
    }

    /**
     * Parse betting message via AJAX
     */
    public function parseMessage(Request $request, BettingMessageParser $parser, BetPricingService $pricing)
    {
        $data = $request->validate([
            'message'     => 'required|string',
            'customer_id' => 'required|exists:customers,id',
        ]);

        // Get region from request or session
        $region = $request->input('region', session('global_region', 'nam'));
        $region = match (strtolower((string)$region)) {
            'bac','mb'   => 'bac',
            'trung','mt' => 'trung',
            default      => 'nam',
        };

        // Gọi parser mới với context
        $context = [
            'customer_id' => $data['customer_id'],
            'region' => $region,
        ];
        $parsed = $parser->parseMessage($data['message'], $context);

        // Initialize pricing service
        $pricing->begin($data['customer_id'], $region);

        // Map hiển thị nhẹ: type code -> label ngắn để bảng dễ đọc
        // Và tính toán pricing (tiền xác, tiền thắng dự kiến)
        if (!empty($parsed['multiple_bets'])) {
            $totalCostXac = 0;
            $totalPotentialWin = 0;

            $parsed['multiple_bets'] = collect($parsed['multiple_bets'])->map(function ($bet, $idx) use ($pricing, &$totalCostXac, &$totalPotentialWin) {
                // ví dụ: da_xien hiển thị "Xiên (2)" nếu có xien_size
                $type = $bet['type'] ?? 'unknown';
                $label = match ($type) {
                    'bao_lo'     => 'Bao lô ' . (($bet['meta']['digits'] ?? 2)) . ' số',
                    'bao3_lo'    => 'Bao lô 3 số',
                    'bao4_lo'    => 'Bao lô 4 số',
                    'bao_lo_dao' => 'Bao lô đảo',
                    'dau'        => 'Đầu',
                    'duoi'       => 'Đuôi',
                    'dau_duoi'   => 'Đầu & Đuôi',
                    'xiu_chu'    => 'Xỉu chủ',
                    'xiu_chu_dau'=> 'Xỉu chủ đầu',
                    'xiu_chu_duoi'=>'Xỉu chủ đuôi',
                    'da_thang'   => 'Đá thẳng',
                    'da_xien'    => 'Đá xiên'.(isset($bet['meta']['xien_size']) ? ' ('.$bet['meta']['xien_size'].')' : ''),
                    'xien'       => 'Xiên '.(isset($bet['meta']['xien_size']) ? $bet['meta']['xien_size'] : ''),
                    default      => $type,
                };

                // numbers: với xiên là 1 tổ hợp -> join luôn
                $numbers = $bet['numbers'] ?? [];
                if (is_array($numbers) && count($numbers) && is_array($numbers[0])) {
                    // phòng trường hợp nhóm xiên lồng mảng (hiếm)
                    $numbers = collect($numbers)->map(fn($g)=>is_array($g)?implode('-', $g):$g)->all();
                }

                // Calculate pricing for this bet
                $pricingData = $pricing->previewForBet($bet);
                $totalCostXac += $pricingData['cost_xac'];
                $totalPotentialWin += $pricingData['potential_win'];

                // Find betting_type_id by code
                $bettingType = BettingType::where('code', $type)->first();
                $bettingTypeId = $bettingType ? $bettingType->id : null;

                return [
                    'station'        => $bet['station'] ?? null,
                    'numbers'        => $numbers,
                    'type'           => $label,
                    'type_code'      => $type,
                    'betting_type_id'=> $bettingTypeId,
                    'amount'         => (int)($bet['amount'] ?? 0),
                    'meta'           => $bet['meta'] ?? [],
                    'cost_xac'       => $pricingData['cost_xac'],
                    'potential_win'  => $pricingData['potential_win'],
                    'buy_rate'       => $pricingData['buy_rate'],
                    'payout'         => $pricingData['payout'],
                ];
            })->values()->all();

            // Add summary totals
            $parsed['summary'] = [
                'total_cost_xac'      => $totalCostXac,
                'total_potential_win' => $totalPotentialWin,
                'total_bets'          => count($parsed['multiple_bets']),
            ];
        }

        // UI đang dùng các key: is_valid, multiple_bets, parsed_message, errors
        return response()->json($parsed);
    }

    /**
     * Create multiple tickets from parsed bets
     */
    private function createMultipleTickets($parseResult, $request, $region)
    {
        DB::beginTransaction();
        try {
            $createdTickets = [];
            $totalCostXac = 0;
            
            foreach ($parseResult['multiple_bets'] as $bet) {
                // Find betting type by code
                $bettingType = BettingType::where('code', $bet['type'])->first();
                if (!$bettingType) {
                    throw new \Exception("Không tìm thấy loại cược: {$bet['type']}");
                }
                
                // Use station from bet or manual input
                $stationName = $bet['station'] ?: $request->station;
                
                // Calculate cost_xac using BetPricingService
                $costXac = $this->calculateCostXac($bet, $region, $request->customer_id);
                $totalCostXac += $costXac;
                
                $ticket = Auth::user()->bettingTickets()->create([
                    'customer_id' => $request->customer_id,
                    'betting_type_id' => $bettingType->id,
                    'betting_date' => $request->betting_date,
                    'region' => $region,
                    'station' => $stationName,
                    'original_message' => $request->original_message,
                    'parsed_message' => $parseResult['parsed_message'],
                    'betting_data' => [
                        'numbers' => $bet['numbers'],
                        'betting_type' => $bettingType->name,
                        'betting_type_code' => $bettingType->code,
                        'meta' => $bet['meta'] ?? [],
                        'total_cost_xac' => $costXac,
                    ],
                    'bet_amount' => $bet['amount'],
                    'win_amount' => 0, // Will be calculated on settlement
                    'payout_amount' => 0, // Will be calculated on settlement
                ]);
                
                $createdTickets[] = $ticket;
            }
            
            DB::commit();
            
            $successMessage = "Đã tạo thành công " . count($createdTickets) . " phiếu cược với tổng tiền xác " . number_format($totalCostXac, 0, ',', '.') . " VNĐ";
            
            // Return JSON if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $successMessage]);
            }
            
            return redirect()->route('user.betting-tickets.index')
                ->with('success', $successMessage);
                
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo phiếu cược: ' . $e->getMessage()], 500);
            }
            
            return back()->withErrors(['error' => 'Có lỗi xảy ra khi tạo phiếu cược: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Update customer statistics
     */
    private function updateCustomerStats(BettingTicket $ticket)
    {
        $customer = $ticket->customer;
        $today = today();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();

        if ($ticket->result === 'win') {
            // Update win amounts
            $customer->increment('total_win_amount', $ticket->win_amount);
            $customer->increment('daily_win_amount', $ticket->win_amount);
            $customer->increment('monthly_win_amount', $ticket->win_amount);
            $customer->increment('yearly_win_amount', $ticket->win_amount);
        } elseif ($ticket->result === 'lose') {
            // Update lose amounts
            $customer->increment('total_lose_amount', $ticket->bet_amount);
            $customer->increment('daily_lose_amount', $ticket->bet_amount);
            $customer->increment('monthly_lose_amount', $ticket->bet_amount);
            $customer->increment('yearly_lose_amount', $ticket->bet_amount);
        }
    }

    /**
     * Reverse customer statistics (when deleting a ticket)
     */
    private function reverseCustomerStats(BettingTicket $ticket)
    {
        $customer = $ticket->customer;

        if ($ticket->result === 'win') {
            // Reverse win amounts
            $customer->decrement('total_win_amount', $ticket->win_amount);
            $customer->decrement('daily_win_amount', $ticket->win_amount);
            $customer->decrement('monthly_win_amount', $ticket->win_amount);
            $customer->decrement('yearly_win_amount', $ticket->win_amount);
        } elseif ($ticket->result === 'lose') {
            // Reverse lose amounts
            $customer->decrement('total_lose_amount', $ticket->bet_amount);
            $customer->decrement('daily_lose_amount', $ticket->bet_amount);
            $customer->decrement('monthly_lose_amount', $ticket->bet_amount);
            $customer->decrement('yearly_lose_amount', $ticket->bet_amount);
        }
    }

    /**
     * Settle (quyết toán) một phiếu cược dựa trên kết quả xổ số
     */
    public function settle(BettingTicket $bettingTicket, BettingSettlementService $settlementService)
    {
        $this->authorize('update', $bettingTicket);

        try {
            $result = $settlementService->settleTicket($bettingTicket);

            if ($result['settled']) {
                return redirect()->back()
                    ->with('success', "Quyết toán thành công! Kết quả: " . strtoupper($result['result']) .
                           ", Tiền trả: " . number_format($result['payout_amount'], 0, ',', '.') . " VNĐ");
            } else {
                return redirect()->back()
                    ->with('warning', "Chưa thể quyết toán: " . ($result['details']['error'] ?? 'Chưa có kết quả xổ số'));
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', "Lỗi khi quyết toán: " . $e->getMessage());
        }
    }

    /**
     * Settle tất cả phiếu cược pending cho một ngày cụ thể
     */
    public function settleBatch(Request $request, BettingSettlementService $settlementService)
    {
        $request->validate([
            'date' => 'required|date',
            'region' => 'nullable|in:bac,trung,nam',
        ]);

        $date = $request->input('date');
        $region = $request->input('region');

        // Kiểm tra quyền: chỉ settle cho tickets của user hiện tại
        $user = Auth::user();

        try {
            // Lấy các tickets pending của user cho ngày này
            $query = $user->bettingTickets()
                ->where('betting_date', $date)
                ->where('result', 'pending');

            if ($region) {
                $query->where('region', $region);
            }

            $tickets = $query->get();
            $settled = 0;
            $failed = 0;

            foreach ($tickets as $ticket) {
                try {
                    $result = $settlementService->settleTicket($ticket);
                    if ($result['settled']) {
                        $settled++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                }
            }

            return redirect()->back()
                ->with('success', "Đã quyết toán {$settled} phiếu cược thành công" .
                       ($failed > 0 ? ", {$failed} phiếu thất bại." : "."));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', "Lỗi khi quyết toán hàng loạt: " . $e->getMessage());
        }
    }

    /**
     * Settle tất cả phiếu cược pending theo global_date và global_region từ session
     */
    public function settleByGlobalFilters(BettingSettlementService $settlementService)
    {
        try {
            $user = Auth::user();
            
            // Lấy global_date và global_region từ session
            $date = session('global_date', today());
            $region = session('global_region', 'nam');
            
            // Kiểm tra xem có kết quả xổ số cho ngày và miền này chưa
            $hasLotteryResult = LotteryResult::whereDate('draw_date', $date)
                ->where('region', $region)
                ->exists();
            
            if (!$hasLotteryResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa có kết quả xổ số',
                    'errors' => ["Chưa có kết quả xổ số cho ngày " . Carbon::parse($date)->format('d/m/Y') . " - miền " . Region::label($region)],
                ], 400);
            }
            
            // Lấy các tickets pending của user cho ngày và miền này
            $tickets = $user->bettingTickets()
                ->whereDate('betting_date', $date)
                ->where('region', $region)
                ->where('result', 'pending')
                ->get();
            
            if ($tickets->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Không có phiếu cược nào cần quyết toán',
                    'result' => [
                        'total' => 0,
                        'settled' => 0,
                        'failed' => 0,
                        'total_win' => 0,
                        'total_payout' => 0,
                    ],
                ]);
            }
            
            $settled = 0;
            $failed = 0;
            $totalWin = 0;
            $totalPayout = 0;
            $errors = [];
            
            foreach ($tickets as $ticket) {
                try {
                    $result = $settlementService->settleTicket($ticket);
                    if ($result['settled']) {
                        $settled++;
                        $totalWin += $result['win_amount'];
                        $totalPayout += $result['payout_amount'];
                    } else {
                        $failed++;
                        $errors[] = "Phiếu #{$ticket->id}: " . ($result['details']['error'] ?? 'Không thể quyết toán');
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Phiếu #{$ticket->id}: " . $e->getMessage();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Đã quyết toán thành công {$settled} phiếu cược" . ($failed > 0 ? ", {$failed} phiếu thất bại" : ''),
                'result' => [
                    'total' => $tickets->count(),
                    'settled' => $settled,
                    'failed' => $failed,
                    'total_win' => $totalWin,
                    'total_payout' => $totalPayout,
                ],
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi quyết toán: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Hiển thị báo cáo theo ngày
     */
    public function report(Request $request)
    {
        $user = Auth::user();
        
        // Lấy ngày từ request, fallback về global_date
        $reportDate = $request->filled('date') 
            ? $request->date 
            : session('global_date', today());
        
        $region = $request->filled('region')
            ? Region::normalizeKey($request->region)
            : session('global_region', 'nam');
        
        // Lấy tất cả tickets của user cho ngày và miền này
        $tickets = $user->bettingTickets()
            ->whereDate('betting_date', $reportDate)
            ->where('region', $region)
            ->where('result', '!=', 'pending')
            ->with(['customer', 'bettingType'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Tính tổng tiền xác user thu (tất cả tickets)
        $totalXac = $tickets->sum(function($ticket) {
            return $ticket->betting_data['total_cost_xac'] ?? 0;
        });
        
        // Tính tổng tiền user trả (từ win tickets)
        $totalThang = $tickets->where('result', 'win')->sum('payout_amount');
        
        // Profit của user = thu xác - trả thắng
        $userProfit = $totalXac - $totalThang;
        
        // Nhóm tickets theo customer để báo cáo chi tiết
        $customerReport = [];
        foreach ($tickets->groupBy('customer_id') as $customerId => $customerTickets) {
            $customer = $customerTickets->first()->customer;
            $customerXac = $customerTickets->sum(function($ticket) {
                return $ticket->betting_data['total_cost_xac'] ?? 0;
            });
            $customerThang = $customerTickets->where('result', 'win')->sum('payout_amount');
            $customerProfit = $customerThang - $customerXac; // Profit của customer
            
            $customerReport[] = [
                'customer' => $customer,
                'tickets' => $customerTickets,
                'total_xac' => $customerXac,
                'total_thang' => $customerThang,
                'profit' => $customerProfit,
            ];
        }
        
        return view('user.betting-tickets.report', compact(
            'reportDate',
            'region',
            'totalXac',
            'totalThang',
            'userProfit',
            'customerReport',
            'tickets'
        ));
    }

    /**
     * Calculate cost_xac for a bet using BetPricingService
     */
    private function calculateCostXac($bet, $region, $customerId): float
    {
        $pricingService = new BetPricingService();
        $pricingService->begin($customerId, $region);
        
        $pricing = $pricingService->previewForBet($bet);
        
        return $pricing['cost_xac'] ?? 0;
    }
}
