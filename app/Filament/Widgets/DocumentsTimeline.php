<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DocumentsTimeline extends Widget
{
    protected static ?int $sort = 15;
    protected static string $view = 'filament.widgets.documents-timeline';

    public function getDocuments()
    {
        return Auth::user()->isStudent()
            ? Document::where('student_id', Auth::user()->student->id)
            ->latest()
            ->take(5)
            ->get()
            : Document::latest()
            ->take(5)
            ->get();
    }
}
