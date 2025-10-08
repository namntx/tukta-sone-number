<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\BettingType;
use App\Models\BettingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerController extends Controller
{
    use AuthorizesRequests;
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
        $globalRegion = session('global_region', 'Bắc');
        
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

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        $bettingTypes = BettingType::active()->ordered()->get();
        return view('user.customers.create', compact('bettingTypes'));
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone|max:20|regex:/^[0-9+\-\s()]+$/',
            'betting_rates' => 'array',
            'betting_rates.*.betting_type_id' => 'required|exists:betting_types,id',
            'betting_rates.*.win_rate' => 'required|numeric|min:0|max:1',
            'betting_rates.*.lose_rate' => 'required|numeric|min:0|max:1',
        ], [
            'name.required' => 'Tên khách hàng là bắt buộc.',
            'name.max' => 'Tên khách hàng không được vượt quá 255 ký tự.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'betting_rates.*.win_rate.required' => 'Hệ số thu là bắt buộc.',
            'betting_rates.*.win_rate.numeric' => 'Hệ số thu phải là số.',
            'betting_rates.*.win_rate.min' => 'Hệ số thu phải lớn hơn hoặc bằng 0.',
            'betting_rates.*.win_rate.max' => 'Hệ số thu phải nhỏ hơn hoặc bằng 1.',
            'betting_rates.*.lose_rate.required' => 'Hệ số trả là bắt buộc.',
            'betting_rates.*.lose_rate.numeric' => 'Hệ số trả phải là số.',
            'betting_rates.*.lose_rate.min' => 'Hệ số trả phải lớn hơn hoặc bằng 0.',
            'betting_rates.*.lose_rate.max' => 'Hệ số trả phải nhỏ hơn hoặc bằng 1.',
        ]);

        try {
            $customer = Auth::user()->customers()->create([
                'name' => $request->name,
                'phone' => $request->phone,
            ]);

            // Create betting rates
            if ($request->has('betting_rates')) {
                foreach ($request->betting_rates as $rateData) {
                    // Validate that win_rate + lose_rate = 1
                    $totalRate = $rateData['win_rate'] + $rateData['lose_rate'];
                    if (abs($totalRate - 1.0) > 0.01) {
                        throw new \Exception("Tổng hệ số thu và trả phải bằng 1.0 cho loại cược ID: {$rateData['betting_type_id']}");
                    }

                    $customer->bettingRates()->create([
                        'user_id' => Auth::id(),
                        'betting_type_id' => $rateData['betting_type_id'],
                        'win_rate' => $rateData['win_rate'],
                        'lose_rate' => $rateData['lose_rate'],
                    ]);
                }
            }

            return redirect()->route('user.customers.index')
                ->with('success', 'Khách hàng đã được tạo thành công.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo khách hàng: ' . $e->getMessage());
        }
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

    /**
     * Show the form for editing the customer
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        $bettingTypes = BettingType::active()->ordered()->get();
        $customer->load('bettingRates');

        return view('user.customers.edit', compact('customer', 'bettingTypes'));
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id . '|regex:/^[0-9+\-\s()]+$/',
            'betting_rates' => 'array',
            'betting_rates.*.betting_type_id' => 'required|exists:betting_types,id',
            'betting_rates.*.win_rate' => 'required|numeric|min:0|max:1',
            'betting_rates.*.lose_rate' => 'required|numeric|min:0|max:1',
        ], [
            'name.required' => 'Tên khách hàng là bắt buộc.',
            'name.max' => 'Tên khách hàng không được vượt quá 255 ký tự.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'betting_rates.*.win_rate.required' => 'Hệ số thu là bắt buộc.',
            'betting_rates.*.win_rate.numeric' => 'Hệ số thu phải là số.',
            'betting_rates.*.win_rate.min' => 'Hệ số thu phải lớn hơn hoặc bằng 0.',
            'betting_rates.*.win_rate.max' => 'Hệ số thu phải nhỏ hơn hoặc bằng 1.',
            'betting_rates.*.lose_rate.required' => 'Hệ số trả là bắt buộc.',
            'betting_rates.*.lose_rate.numeric' => 'Hệ số trả phải là số.',
            'betting_rates.*.lose_rate.min' => 'Hệ số trả phải lớn hơn hoặc bằng 0.',
            'betting_rates.*.lose_rate.max' => 'Hệ số trả phải nhỏ hơn hoặc bằng 1.',
        ]);

        try {
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
            ]);

            // Update betting rates
            if ($request->has('betting_rates')) {
                // Delete existing rates
                $customer->bettingRates()->delete();

                // Create new rates
                foreach ($request->betting_rates as $rateData) {
                    // Validate that win_rate + lose_rate = 1
                    $totalRate = $rateData['win_rate'] + $rateData['lose_rate'];
                    if (abs($totalRate - 1.0) > 0.01) {
                        throw new \Exception("Tổng hệ số thu và trả phải bằng 1.0 cho loại cược ID: {$rateData['betting_type_id']}");
                    }

                    $customer->bettingRates()->create([
                        'user_id' => Auth::id(),
                        'betting_type_id' => $rateData['betting_type_id'],
                        'win_rate' => $rateData['win_rate'],
                        'lose_rate' => $rateData['lose_rate'],
                    ]);
                }
            }

            return redirect()->route('user.customers.index')
                ->with('success', 'Thông tin khách hàng đã được cập nhật.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi cập nhật khách hàng: ' . $e->getMessage());
        }
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
