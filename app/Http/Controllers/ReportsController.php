<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanRequest;
use App\Models\SavingsTransaction;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    /**
     * Export all loans to CSV format
     */
    public function exportLoansCsv()
    {
        $fileName = 'loans_export_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $loans = LoanRequest::with(['member', 'repayments'])->orderBy('id', 'desc')->get();

        $callback = function() use($loans) {
            $file = fopen('php://output', 'w');
            
            // Header Row
            fputcsv($file, [
                'Loan ID', 
                'Member Code', 
                'Borrower Name', 
                'Amount ($)', 
                'Duration (Months)', 
                'Status', 
                'Outstanding Balance ($)', 
                'Repayment Due Date', 
                'Disbursed At',
                'Date Created'
            ]);

            foreach ($loans as $loan) {
                fputcsv($file, [
                    $loan->id,
                    $loan->member->member_code,
                    $loan->member->name,
                    number_format($loan->amount, 2, '.', ''),
                    $loan->duration_months,
                    ucfirst(str_replace('_', ' ', $loan->status)),
                    number_format($loan->remaining_balance, 2, '.', ''),
                    $loan->repayment_due_date ? $loan->repayment_due_date->format('Y-m-d') : '-',
                    $loan->disbursed_at ? $loan->disbursed_at->format('Y-m-d H:i:s') : '-',
                    $loan->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export all savings transactions to CSV format
     */
    public function exportSavingsCsv()
    {
        $fileName = 'savings_export_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $transactions = SavingsTransaction::with('member')
            ->where('status', 'approved')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $callback = function() use($transactions) {
            $file = fopen('php://output', 'w');
            
            // Header Row
            fputcsv($file, [
                'Transaction ID', 
                'Member Code', 
                'Member Name', 
                'Amount ($)', 
                'Type', 
                'Transaction Date', 
                'Notes',
                'Date Recorded'
            ]);

            foreach ($transactions as $t) {
                fputcsv($file, [
                    $t->id,
                    $t->member->member_code,
                    $t->member->name,
                    number_format($t->amount, 2, '.', ''),
                    ucfirst($t->type),
                    $t->transaction_date->format('Y-m-d'),
                    $t->notes ?? '',
                    $t->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
