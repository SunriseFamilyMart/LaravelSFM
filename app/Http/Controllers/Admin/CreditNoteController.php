<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditNoteController extends Controller
{
    public function show($id)
    {
        $creditNote = CreditNote::with(['items.product', 'order.customer', 'order.branch'])
            ->findOrFail($id);

        return view('admin.credit_note.show', compact('creditNote'));
    }

    public function pdf($id)
    {
        $creditNote = CreditNote::with(['items.product', 'order.customer', 'order.branch'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('admin.credit_note.pdf', compact('creditNote'))
            ->setPaper('A4', 'portrait');

        return $pdf->download('CreditNote-' . $creditNote->credit_note_no . '.pdf');
    }
}
