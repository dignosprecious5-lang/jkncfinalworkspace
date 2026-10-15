<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EngagementsExport;
use App\Exports\RequirementsExport;
use App\Imports\RequirementsImport;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;

class ImportExportController extends Controller
{
    // Export Engagements to Excel
    public function exportEngagementsExcel()
    {
        // RECORD AUDIT LOG
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                'EXPORT_EXCEL',
                'Engagements',
                'Exported operational engagements list to Excel format.'
            );
        }

        return Excel::download(new EngagementsExport, 'engagements_report.xlsx');
    }

    // Export Engagements to PDF (With Report Content Scope Filtering)
    public function exportEngagementsPDF(Request $request)
    {
        // RECORD AUDIT LOG
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                'EXPORT_PDF',
                'Engagements',
                'Exported operational engagements report to PDF format.'
            );
        }

        // Kunin ang napiling scopes mula sa checkboxes (form input array or query string)
        // Default values: kung walang napili, halos lahat ay nakatsek bilang fallback
        $scopes = $request->input('scope', [
            'activities',
            'completed_tasks',
            'deliverables',
            'client_requests',
            'next_period_work'
        ]);

        // Kunin ang engagements data
        $engagements = DB::table('engagements')->get();

        // I-pass ang $engagements at $scopes sa PDF view
        $pdf = Pdf::loadView('reports.engagements_pdf', compact('engagements', 'scopes'));

        return $pdf->download('engagements_report.pdf');
    }

    // Export Requirements to Excel
    public function exportRequirementsExcel()
    {
        // RECORD AUDIT LOG
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                'EXPORT_EXCEL',
                'Requirements',
                'Exported client requirements catalog to Excel format.'
            );
        }

        return Excel::download(new RequirementsExport, 'requirements_report.xlsx');
    }

    // Bulk Import Requirements from Excel/CSV
    public function importRequirements(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:2048',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        Excel::import(new RequirementsImport, $request->file('file'));

        // RECORD AUDIT LOG
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                'IMPORT_EXCEL',
                'Requirements',
                "Successfully imported client requirements from file: {$fileName}."
            );
        }

        return back()->with('success', 'Requirements imported successfully!');
    }
}