<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->paginate(20);
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
            'features' => 'nullable|string',
            'sort_order' => 'integer|min:0'
        ]);

        $data = $request->all();
        
        // Parse features from JSON string
        if ($request->has('features') && $request->features) {
            $data['features'] = json_decode($request->features, true);
        } else {
            $data['features'] = null;
        }

        Plan::create($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Gói subscription đã được tạo thành công.');
    }

    public function show(Plan $plan)
    {
        $plan->loadCount('subscriptions');
        return view('admin.plans.show', compact('plan'));
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
            'features' => 'nullable|string',
            'sort_order' => 'integer|min:0'
        ]);

        $data = $request->all();
        
        // Parse features from JSON string
        if ($request->has('features') && $request->features) {
            $data['features'] = json_decode($request->features, true);
        } else {
            $data['features'] = null;
        }

        $plan->update($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Gói subscription đã được cập nhật thành công.');
    }

    public function destroy(Plan $plan)
    {
        // Kiểm tra xem có subscription nào đang sử dụng plan này không
        if ($plan->subscriptions()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Không thể xóa gói này vì đang có subscription đang sử dụng.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Gói subscription đã được xóa thành công.');
    }

    public function toggleStatus(Plan $plan)
    {
        $plan->update([
            'is_active' => !$plan->is_active
        ]);

        $status = $plan->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        
        return redirect()->back()
            ->with('success', "Gói đã được {$status} thành công.");
    }
}