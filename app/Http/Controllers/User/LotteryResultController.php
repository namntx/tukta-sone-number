<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LotteryResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LotteryResultController extends Controller
{
    use AuthorizesRequests;
    // Lấy theo session global_date & global_station
    public function bySession(Request $req)
    {
        $date = $req->session()->get('global_date', now()->toDateString());
        $stationCode = strtolower($req->session()->get('global_station'));

        $q = LotteryResult::query()->whereDate('draw_date', $date);
        if ($stationCode) $q->where('station_code', $stationCode);

        $rows = $q->orderBy('station')->get();
        return response()->json([
            'date'    => $date,
            'station' => $stationCode,
            'results' => $rows,
        ]);
    }

    // Trang filter theo ngày/tháng/năm/đài/miền
    public function index(Request $req)
    {
        // Mặc định hiển thị theo global_date trong session (nếu có), không sửa session khi user lọc
        $sessionDate = $req->session()->get('global_date', now()->toDateString());
        $date        = $req->input('date', $sessionDate);
    
        // Lấy đủ 3 miền của ngày đang chọn
        $rows = \App\Models\LotteryResult::query()
            ->whereDate('draw_date', $date)
            ->whereIn('region', ['nam','trung','bac'])
            ->orderBy('station')
            ->get()
            ->groupBy('region');
    
        // Bảo đảm có đủ key cho 3 miền
        $byRegion = collect([
            'nam'   => collect(),
            'trung' => collect(),
            'bac'   => collect(),
        ]);
        foreach ($rows as $reg => $col) $byRegion[$reg] = $col;
    
        return view('user.lottery_results.index', [
            'date'     => $date,
            'byRegion' => $byRegion,
            'filters'  => ['date' => $date], // chỉ bind ngày cho form
        ]);
    }
    

    // Chi tiết 1 đài/ngày
    public function show($id)
    {
        $row = LotteryResult::findOrFail($id);
        return view('user.lottery_results.show', compact('row'));
    }
}
