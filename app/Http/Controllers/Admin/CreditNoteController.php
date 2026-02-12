<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNoteController extends Controller
{
    public function __construct(
        private BusinessSetting $businessSetting
    ) {
    }

    public function show($id)
    {
        $creditNote = CreditNote::with(['items.product', 'order.customer', 'order.branch', 'order.store'])
            ->findOrFail($id);

        // Business Info
        $business_name = 'Sunrise Family Mart';
        $business_address = 'Bangalore, Karnataka';
        $business_phone = '9999999999';
        $business_email = 'admin@sunrisefamilymart.com';
        $business_gst = '29ABCDE1234F1Z5';

        // Load Logo
        $logoPath = public_path('logo.png');
        $business_logo = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        // Load Paytm QR Code
        $qrPath = public_path('qr.jpeg');
        $paytm_qr_code = file_exists($qrPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath))
            : null;

        // Footer text
        $footer_text = optional(
            $this->businessSetting->where('key', 'footer_text')->first()
        )->value ?? '';

        return view('admin.credit_note.show', compact(
            'creditNote',
            'business_name',
            'business_address',
            'business_phone',
            'business_email',
            'business_gst',
            'business_logo',
            'paytm_qr_code',
            'footer_text'
        ));
    }

    public function pdf($id)
    {
        $creditNote = CreditNote::with(['items.product', 'order.customer', 'order.branch', 'order.store'])
            ->findOrFail($id);

        // Business Info
        $business_name = 'Sunrise Family Mart';
        $business_address = 'Bangalore, Karnataka';
        $business_phone = '9999999999';
        $business_email = 'admin@sunrisefamilymart.com';
        $business_gst = '29ABCDE1234F1Z5';

        // Bank Details (same as bulk-invoice defaults)
        $business_bank_name = 'HDFC Bank';
        $business_bank_account = '50200058934605';
        $business_bank_ifsc = 'HDFC0001753';
        $business_bank_branch = 'Kanakpura Road';
        $business_upi = 'paytmqr5jjsna@ptys';

        // Load Logo
        $logoPath = public_path('logo.png');
        $business_logo = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        // Load Paytm QR Code
        $qrPath = public_path('qr.jpeg');
        $paytm_qr_code = file_exists($qrPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath))
            : null;

        // Footer text
        $footer_text = optional(
            $this->businessSetting->where('key', 'footer_text')->first()
        )->value ?? '';

        $pdf = Pdf::loadView('admin.credit_note.pdf', compact(
            'creditNote',
            'business_name',
            'business_address',
            'business_phone',
            'business_email',
            'business_gst',
            'business_bank_name',
            'business_bank_account',
            'business_bank_ifsc',
            'business_bank_branch',
            'business_upi',
            'business_logo',
            'paytm_qr_code',
            'footer_text'
        ))->setPaper('A4', 'portrait');

        return $pdf->download('CreditNote-' . $creditNote->credit_note_no . '.pdf');
    }
}
