<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\BettingTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BackupRestoreController extends Controller
{
    /**
     * Show backup form
     */
    public function index()
    {
        return view('user.backup-restore.index');
    }

    /**
     * Export backup data
     */
    public function backup(Request $request)
    {
        $request->validate([
            'backup_type' => 'required|in:customers,customers_and_tickets',
        ]);

        $user = Auth::user();
        $backupType = $request->backup_type;

        $data = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'exported_by_user_id' => $user->id,
            'backup_type' => $backupType,
        ];

        // Get customers
        $customers = $user->customers()->get();
        
        $customersData = [];
        foreach ($customers as $customer) {
            $customerData = [
                'old_id' => $customer->id, // Lưu ID cũ để map khi restore
                'name' => $customer->name,
                'phone' => $customer->phone,
                'betting_rates' => $customer->betting_rates,
                'is_active' => $customer->is_active,
                'created_at' => $customer->created_at->toIso8601String(),
                'updated_at' => $customer->updated_at->toIso8601String(),
            ];

            // Nếu backup cả tickets
            if ($backupType === 'customers_and_tickets') {
                $tickets = $customer->bettingTickets()->get();
                $ticketsData = [];
                
                foreach ($tickets as $ticket) {
                    $ticketsData[] = [
                        'old_customer_id' => $ticket->customer_id, // Lưu để map
                        'betting_type_id' => $ticket->betting_type_id,
                        'betting_date' => $ticket->betting_date->format('Y-m-d'),
                        'region' => $ticket->region,
                        'station' => $ticket->station,
                        'original_message' => $ticket->original_message,
                        'parsed_message' => $ticket->parsed_message,
                        'betting_data' => $ticket->betting_data,
                        'result' => $ticket->result,
                        'bet_amount' => (string)$ticket->bet_amount,
                        'win_amount' => (string)$ticket->win_amount,
                        'payout_amount' => (string)$ticket->payout_amount,
                        'status' => $ticket->status,
                        'created_at' => $ticket->created_at->toIso8601String(),
                        'updated_at' => $ticket->updated_at->toIso8601String(),
                    ];
                }
                
                $customerData['tickets'] = $ticketsData;
            }

            $customersData[] = $customerData;
        }

        $data['customers'] = $customersData;

        // Generate filename
        $filename = 'backup_' . $backupType . '_' . now()->format('Y-m-d_His') . '.json';

        // Return JSON download
        return response()->streamDownload(function() use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Show restore form
     */
    public function restoreForm()
    {
        return view('user.backup-restore.restore');
    }

    /**
     * Process restore
     */
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:json|max:10240', // Max 10MB
        ]);

        $user = Auth::user();
        $file = $request->file('backup_file');
        
        // Read JSON file
        $content = file_get_contents($file->getRealPath());
        $backupData = json_decode($content, true);

        if (!$backupData || !isset($backupData['customers'])) {
            return back()->withErrors(['backup_file' => 'File backup không hợp lệ.']);
        }

        DB::beginTransaction();
        try {
            $customerIdMap = []; // Map old_id => new_id
            $importedCustomers = 0;
            $importedTickets = 0;

            foreach ($backupData['customers'] as $customerData) {
                $oldCustomerId = $customerData['old_id'] ?? null;

                // Create customer với user_id mới
                $newCustomer = Customer::create([
                    'user_id' => $user->id,
                    'name' => $customerData['name'],
                    'phone' => $customerData['phone'],
                    'betting_rates' => $customerData['betting_rates'] ?? null,
                    'is_active' => $customerData['is_active'] ?? true,
                    'total_win_amount' => 0,
                    'total_lose_amount' => 0,
                    'daily_win_amount' => 0,
                    'daily_lose_amount' => 0,
                    'monthly_win_amount' => 0,
                    'monthly_lose_amount' => 0,
                    'yearly_win_amount' => 0,
                    'yearly_lose_amount' => 0,
                ]);

                // Map old_id => new_id
                if ($oldCustomerId) {
                    $customerIdMap[$oldCustomerId] = $newCustomer->id;
                }

                $importedCustomers++;

                // Restore tickets nếu có
                if (isset($customerData['tickets']) && is_array($customerData['tickets'])) {
                    foreach ($customerData['tickets'] as $ticketData) {
                        BettingTicket::create([
                            'user_id' => $user->id,
                            'customer_id' => $newCustomer->id, // Dùng customer_id mới
                            'betting_type_id' => $ticketData['betting_type_id'],
                            'betting_date' => $ticketData['betting_date'],
                            'region' => $ticketData['region'],
                            'station' => $ticketData['station'],
                            'original_message' => $ticketData['original_message'],
                            'parsed_message' => $ticketData['parsed_message'],
                            'betting_data' => $ticketData['betting_data'],
                            'result' => $ticketData['result'] ?? 'pending',
                            'bet_amount' => $ticketData['bet_amount'],
                            'win_amount' => $ticketData['win_amount'] ?? 0,
                            'payout_amount' => $ticketData['payout_amount'] ?? 0,
                            'status' => $ticketData['status'] ?? 'active',
                        ]);

                        $importedTickets++;
                    }
                }
            }

            DB::commit();

            $message = "Đã restore thành công {$importedCustomers} khách hàng";
            if ($importedTickets > 0) {
                $message .= " và {$importedTickets} phiếu cược";
            }
            $message .= ".";

            return redirect()->route('user.backup-restore.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors(['backup_file' => 'Lỗi khi restore: ' . $e->getMessage()]);
        }
    }
}
