<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LotteryResult;
use App\Services\LotteryResultScraper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Carbon\Carbon;

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
        // Luôn lấy ngày từ global_date trong session
        $date = $req->session()->get('global_date', now()->toDateString());
    
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
            'filters'  => ['date' => $date], // bind ngày cho form
        ]);
    }
    

    // Chi tiết 1 đài/ngày
    public function show($id)
    {
        $row = LotteryResult::findOrFail($id);
        return view('user.lottery_results.show', compact('row'));
    }

    // Lấy kết quả xổ số cho cả 3 miền từ global_date
    public function scrape(Request $req, LotteryResultScraper $scraper)
    {
        try {
            // Lấy ngày từ global_date trong session
            $date = Carbon::parse($req->session()->get('global_date', now()->toDateString()));
            
            $results = [];
            $errors = [];
            
            // Scrape cho cả 3 miền
            $regions = ['nam', 'trung', 'bac'];
            foreach ($regions as $region) {
                try {
                    $saved = $scraper->scrapeDaily($date, $region);
                    $results[$region] = count($saved);
                } catch (\Exception $e) {
                    $errors[$region] = $e->getMessage();
                    $results[$region] = 0;
                }
            }
            
            $total = array_sum($results);
            
            if (empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "Đã lấy kết quả xổ số cho ngày {$date->format('d/m/Y')}",
                    'results' => $results,
                    'total' => $total,
                    'date' => $date->toDateString(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Đã lấy kết quả với một số lỗi",
                    'results' => $results,
                    'errors' => $errors,
                    'total' => $total,
                    'date' => $date->toDateString(),
                ], 207); // 207 Multi-Status
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy kết quả: ' . $e->getMessage(),
            ], 500);
        }
    }
}
