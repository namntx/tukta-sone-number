<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BettingTicket;
use App\Models\Customer;
use App\Models\BettingType;
use App\Services\BettingMessageParser;
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
        $filterRegion = $request->filled('region') ? $request->region : session('global_region', 'Bắc');
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

        $tickets = $query->paginate(20);
        $customers = $user->customers()->active()->get();

        // Calculate statistics using global date
        $globalDate = session('global_date', today());
        $globalRegion = session('global_region', 'Bắc');
        
        $todayStats = [
            'total_tickets' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->count(),
            'total_bet' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->sum('bet_amount'),
            'total_win' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->where('result', 'win')->sum('win_amount'),
            'total_lose' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->where('result', 'lose')->sum('bet_amount'),
        ];

        $monthlyStats = [
            'total_tickets' => $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->count(),
            'total_bet' => $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->sum('bet_amount'),
            'total_win' => $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->where('result', 'win')->sum('win_amount'),
            'total_lose' => $user->bettingTickets()->whereMonth('betting_date', now()->month)->whereYear('betting_date', now()->year)->where('result', 'lose')->sum('bet_amount'),
        ];

        $yearlyStats = [
            'total_tickets' => $user->bettingTickets()->whereYear('betting_date', now()->year)->count(),
            'total_bet' => $user->bettingTickets()->whereYear('betting_date', now()->year)->sum('bet_amount'),
            'total_win' => $user->bettingTickets()->whereYear('betting_date', now()->year)->where('result', 'win')->sum('win_amount'),
            'total_lose' => $user->bettingTickets()->whereYear('betting_date', now()->year)->where('result', 'lose')->sum('bet_amount'),
        ];

        return view('user.betting-tickets.index', compact('tickets', 'customers', 'todayStats', 'monthlyStats', 'yearlyStats'));
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
            'region' => 'required|string|max:50|in:Bắc,Trung,Nam',
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
            return back()->withErrors(['customer_id' => 'Khách hàng không thuộc về bạn.'])
                        ->withInput();
        }

        // Parse the betting message
        $parseResult = $this->messageParser->parseMessage($request->original_message, $request->customer_id);

        if (!$parseResult['is_valid']) {
            return back()->withErrors(['original_message' => implode(', ', $parseResult['errors'])])
                        ->withInput();
        }

        // Handle new parser format with multiple bets
        if (isset($parseResult['multiple_bets']) && count($parseResult['multiple_bets']) > 0) {
            return $this->createMultipleTickets($parseResult, $request);
        }

        // Handle legacy single bet format
        $bettingType = $parseResult['betting_type'];
        $numbers = $parseResult['numbers'];
        $amount = $parseResult['amount'];
        $station = $parseResult['station'];
        $stations = $parseResult['stations'];

        // Use station from parser if found, otherwise use manual input
        $stationName = $station ? $station->name : $request->station;

        // Calculate win amount
        $winAmount = $this->messageParser->calculateWinAmount(
            $bettingType, 
            $numbers, 
            $amount, 
            $request->customer_id
        );

        DB::beginTransaction();
        try {
            $ticket = Auth::user()->bettingTickets()->create([
                'customer_id' => $request->customer_id,
                'betting_type_id' => $bettingType->id,
                'betting_date' => $request->betting_date,
                'region' => $request->region,
                'station' => $stationName,
                'original_message' => $request->original_message,
                'parsed_message' => $parseResult['parsed_message'],
                'betting_data' => [
                    'numbers' => $numbers,
                    'betting_type' => $bettingType->name,
                    'betting_type_code' => $bettingType->code,
                    'stations' => $stations,
                ],
                'bet_amount' => $amount,
                'win_amount' => $winAmount,
                'payout_amount' => 0, // Will be calculated when result is determined
            ]);

            DB::commit();

            return redirect()->route('user.betting-tickets.index')
                ->with('success', 'Phiếu cược đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollback();
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
            'region' => 'required|string|max:50|in:Bắc,Trung,Nam',
            'station' => 'required|string|max:100',
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

        DB::beginTransaction();
        try {
            $oldResult = $bettingTicket->result;
            
            $bettingTicket->update([
                'customer_id' => $request->customer_id,
                'betting_date' => $request->betting_date,
                'region' => $request->region,
                'station' => $request->station,
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
    public function parseMessage(Request $request, BettingMessageParser $parser)
    {
        $data = $request->validate([
            'message'     => 'required|string',
            'customer_id' => 'required|exists:customers,id',
        ]);

        // Gọi parser mới
        $parsed = $parser->parseMessage($data['message'], $data['customer_id']);

        // Map hiển thị nhẹ: type code -> label ngắn để bảng dễ đọc
        if (!empty($parsed['multiple_bets'])) {
            $parsed['multiple_bets'] = collect($parsed['multiple_bets'])->map(function ($bet, $idx) {
                // ví dụ: da_xien hiển thị "Xiên (2)" nếu có xien_size
                $type = $bet['type'] ?? 'unknown';
                $label = match ($type) {
                    'bao_lo'     => 'Bao lô 2 số',
                    'bao3_lo'    => 'Bao lô 3 số',
                    'bao4_lo'    => 'Bao lô 4 số',
                    'bao_lo_dao' => 'Bao lô đảo',
                    'dau'        => 'Đầu',
                    'duoi'       => 'Đuôi',
                    'dau_duoi'   => 'Đầu & Đuôi',
                    'xiu_chu'    => 'Xỉu chủ',
                    'xiu_chu_dao'=> 'Xỉu chủ đảo',
                    'xiu_chu_dau'=> 'Xỉu chủ đầu',
                    'xiu_chu_duoi'=>'Xỉu chủ đuôi',
                    'da_thang'   => 'Đá thẳng',
                    'da_xien'    => 'Đá xiên'.(isset($bet['meta']['xien_size']) ? ' ('.$bet['meta']['xien_size'].')' : ''),
                    default      => $type,
                };

                // numbers: với xiên là 1 tổ hợp -> join luôn
                $numbers = $bet['numbers'] ?? [];
                if (is_array($numbers) && count($numbers) && is_array($numbers[0])) {
                    // phòng trường hợp nhóm xiên lồng mảng (hiếm)
                    $numbers = collect($numbers)->map(fn($g)=>is_array($g)?implode('-', $g):$g)->all();
                }

                return [
                    'station' => $bet['station'] ?? null,
                    'numbers' => $numbers,
                    'type'    => $label,
                    'amount'  => (int)($bet['amount'] ?? 0),
                    'meta'    => $bet['meta'] ?? [],
                ];
            })->values()->all();
        }

        // UI đang dùng các key: is_valid, multiple_bets, parsed_message, errors
        return response()->json($parsed);
    }

    /**
     * Create multiple tickets from parsed bets
     */
    private function createMultipleTickets($parseResult, $request)
    {
        DB::beginTransaction();
        try {
            $createdTickets = [];
            $totalAmount = 0;
            
            foreach ($parseResult['multiple_bets'] as $bet) {
                // Find betting type by code
                $bettingType = BettingType::where('code', $bet['type'])->first();
                if (!$bettingType) {
                    throw new \Exception("Không tìm thấy loại cược: {$bet['type']}");
                }
                
                // Use station from bet or manual input
                $stationName = $bet['station'] ?: $request->station;
                
                // Calculate win amount (simplified for now)
                $winAmount = $bet['amount'] * 70; // Basic multiplier
                
                $ticket = Auth::user()->bettingTickets()->create([
                    'customer_id' => $request->customer_id,
                    'betting_type_id' => $bettingType->id,
                    'betting_date' => $request->betting_date,
                    'region' => $request->region,
                    'station' => $stationName,
                    'original_message' => $request->original_message,
                    'parsed_message' => $parseResult['parsed_message'],
                    'betting_data' => [
                        'numbers' => $bet['numbers'],
                        'betting_type' => $bettingType->name,
                        'betting_type_code' => $bettingType->code,
                        'meta' => $bet['meta'] ?? [],
                    ],
                    'bet_amount' => $bet['amount'],
                    'win_amount' => $winAmount,
                    'payout_amount' => 0,
                ]);
                
                $createdTickets[] = $ticket;
                $totalAmount += $bet['amount'];
            }
            
            DB::commit();
            
            return redirect()->route('user.betting-tickets.index')
                ->with('success', "Đã tạo thành công " . count($createdTickets) . " phiếu cược với tổng tiền " . number_format($totalAmount, 0, ',', '.') . " VNĐ");
                
        } catch (\Exception $e) {
            DB::rollback();
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
}
