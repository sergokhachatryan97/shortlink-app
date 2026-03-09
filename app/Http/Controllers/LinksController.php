<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LinksController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $links = $user->generatedLinks()
            ->orderByDesc('created_at')
            ->paginate(25);

        $activeSubscription = $user->activeSubscription();
        $planLimit = $activeSubscription ? (int) $activeSubscription->plan->links_limit : 0;
        $totalCount = $user->generatedLinks()->count();

        return view('links.index', [
            'links' => $links,
            'totalCount' => $totalCount,
            'planLimit' => $planLimit,
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $links = $user->generatedLinks()
            ->orderBy('created_at')
            ->get(['short_url', 'original_url', 'created_at']);

        $filename = 'my_links_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($links) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Short URL', 'Original URL', 'Created']);
            foreach ($links as $i => $link) {
                fputcsv($handle, [
                    $i + 1,
                    $link->short_url,
                    $link->original_url,
                    $link->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
