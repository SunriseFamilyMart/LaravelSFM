<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNoteController extends Controller
{
    public function show($id)
    {
        $creditNote = CreditNote::with([
            'items.product',
            'order.branch',
            'order.customer'
        ])->findOrFail($id);

        return view('admin.credit_notes.show', compact('creditNote'));
    }

    public function pdf($id)
    {
        $creditNote = CreditNote::with([
            'items.product',
            'order.branch',
            'order.customer'
        ])->findOrFail($id);

        $pdf = Pdf::loadView(
            'admin.credit_notes.gst_pdf',
            compact('creditNote')
        )->setPaper('A4', 'portrait');

        return $pdf->download(
            'Credit-Note-' . $creditNote->credit_note_no . '.pdf'
        );
    }
}
