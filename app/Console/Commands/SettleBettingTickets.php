<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BettingSettlementService;
use Carbon\Carbon;

class SettleBettingTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'betting:settle
                            {date? : Ngày cần quyết toán (YYYY-MM-DD), mặc định là hôm qua}
                            {--region= : Miền cụ thể (bac/trung/nam)}
                            {--ticket= : ID phiếu cược cụ thể}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quyết toán phiếu cược dựa trên kết quả xổ số';

    protected BettingSettlementService $settlementService;

    /**
     * Create a new command instance.
     */
    public function __construct(BettingSettlementService $settlementService)
    {
        parent::__construct();
        $this->settlementService = $settlementService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🎲 Bắt đầu quyết toán phiếu cược...');

        // Xử lý quyết toán cho một ticket cụ thể
        if ($ticketId = $this->option('ticket')) {
            return $this->settleSingleTicket((int)$ticketId);
        }

        // Xử lý quyết toán theo ngày
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))->format('Y-m-d')
            : Carbon::yesterday()->format('Y-m-d');

        $region = $this->option('region');

        $this->info("📅 Ngày quyết toán: {$date}");
        if ($region) {
            $this->info("🗺️ Miền: " . strtoupper($region));
        }

        $this->newLine();

        // Chạy settlement
        $result = $this->settlementService->settleBatchByDate($date, $region);

        // Hiển thị kết quả
        $this->displayResults($result);

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Quyết toán cho một phiếu cược cụ thể
     */
    protected function settleSingleTicket(int $ticketId): int
    {
        $this->info("🎫 Quyết toán phiếu cược ID: {$ticketId}");

        $ticket = \App\Models\BettingTicket::find($ticketId);

        if (!$ticket) {
            $this->error("❌ Không tìm thấy phiếu cược ID: {$ticketId}");
            return self::FAILURE;
        }

        try {
            $result = $this->settlementService->settleTicket($ticket);

            if ($result['settled']) {
                $this->newLine();
                $this->info("✅ Quyết toán thành công!");
                $this->table(
                    ['Thông tin', 'Giá trị'],
                    [
                        ['Kết quả', strtoupper($result['result'])],
                        ['Tiền cược', number_format($ticket->bet_amount, 0, ',', '.') . ' VNĐ'],
                        ['Tiền thắng', number_format($result['win_amount'], 0, ',', '.') . ' VNĐ'],
                        ['Tiền trả', number_format($result['payout_amount'], 0, ',', '.') . ' VNĐ'],
                    ]
                );

                // Hiển thị chi tiết từng bet
                if (!empty($result['details'])) {
                    $this->newLine();
                    $this->info("📋 Chi tiết:");
                    foreach ($result['details'] as $index => $detail) {
                        $this->line("  " . ($index + 1) . ". Loại: {$detail['type']}, Số: " . implode(', ', $detail['numbers']) .
                                   " → " . ($detail['is_win'] ? '✅ TRÚNG' : '❌ TRƯỢT'));
                    }
                }

                return self::SUCCESS;
            } else {
                $this->warn("⚠️ Chưa thể quyết toán: " . ($result['details']['error'] ?? 'Chưa có kết quả'));
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Lỗi: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Hiển thị kết quả quyết toán hàng loạt
     */
    protected function displayResults(array $result): void
    {
        $this->newLine();
        $this->info("📊 Kết quả quyết toán:");
        $this->table(
            ['Chỉ số', 'Số lượng'],
            [
                ['Tổng phiếu cược', $result['total']],
                ['✅ Đã quyết toán', $result['settled']],
                ['❌ Thất bại', $result['failed']],
            ]
        );

        if ($result['settled'] > 0) {
            $this->newLine();
            $this->info("✅ Quyết toán thành công {$result['settled']} phiếu cược!");
        }

        if ($result['failed'] > 0) {
            $this->newLine();
            $this->warn("⚠️ Có {$result['failed']} phiếu cược không thể quyết toán.");

            // Hiển thị lỗi chi tiết
            $errors = array_filter($result['results'] ?? [], fn($r) => !($r['success'] ?? false));
            if (count($errors) > 0) {
                $this->newLine();
                $this->error("📋 Chi tiết lỗi:");
                foreach ($errors as $error) {
                    $ticketId = $error['ticket_id'] ?? 'N/A';
                    $errorMessage = 'Unknown error';
                    
                    // Lấy thông báo lỗi từ các nguồn khác nhau
                    if (isset($error['error'])) {
                        // Lỗi từ exception
                        $errorMessage = $error['error'];
                    } elseif (isset($error['result']['details']['error'])) {
                        // Lỗi từ settleTicket trả về (vd: "Chưa có kết quả xổ số")
                        $errorMessage = $error['result']['details']['error'];
                    } elseif (isset($error['result']['details']) && is_string($error['result']['details'])) {
                        // Nếu details là string
                        $errorMessage = $error['result']['details'];
                    } elseif (isset($error['result'])) {
                        // Fallback: hiển thị toàn bộ result nếu không có error cụ thể
                        $errorMessage = 'Không thể quyết toán (kiểm tra kết quả xổ số hoặc dữ liệu cược)';
                    }
                    
                    $this->line("  ❌ Ticket #{$ticketId}: {$errorMessage}");
                }
            }
        }

        if ($result['total'] === 0) {
            $this->warn("⚠️ Không có phiếu cược nào cần quyết toán.");
        }
    }
}
