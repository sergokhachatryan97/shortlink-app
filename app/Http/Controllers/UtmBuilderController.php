<?php

namespace App\Http\Controllers;

use App\Models\UtmLink;
use App\Models\UtmPreset;
use App\Services\ShortenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UtmBuilderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = UtmLink::where('user_id', $user->id)->orderByDesc('created_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_url', 'like', "%{$search}%")
                  ->orWhere('utm_campaign', 'like', "%{$search}%")
                  ->orWhere('utm_source', 'like', "%{$search}%");
            });
        }
        if ($campaign = $request->query('campaign')) {
            $query->where('utm_campaign', $campaign);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $history = $query->paginate(20)->withQueryString();
        $presets = UtmPreset::where('user_id', $user->id)->orderBy('name')->get();
        $campaigns = UtmLink::where('user_id', $user->id)
            ->select('utm_campaign')
            ->distinct()
            ->orderBy('utm_campaign')
            ->pluck('utm_campaign');

        return view('utm.index', compact('history', 'presets', 'campaigns'));
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'utm_source' => ['required', 'string', 'max:255'],
            'utm_medium' => ['required', 'string', 'max:255'],
            'utm_campaign' => ['required', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
        ]);

        $source = $this->normalizeUtmValue($validated['utm_source']);
        $medium = $this->normalizeUtmValue($validated['utm_medium']);
        $campaign = $this->normalizeUtmValue($validated['utm_campaign']);
        $content = $this->normalizeUtmValue($validated['utm_content'] ?? null);
        $term = $this->normalizeUtmValue($validated['utm_term'] ?? null);

        $finalUrl = $this->buildUtmUrl($validated['url'], $source, $medium, $campaign, $content, $term);

        $utmLink = UtmLink::create([
            'user_id' => Auth::id(),
            'original_url' => $validated['url'],
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'utm_content' => $content,
            'utm_term' => $term,
            'final_url' => $finalUrl,
        ]);

        return response()->json([
            'success' => true,
            'final_url' => $finalUrl,
            'id' => $utmLink->id,
        ]);
    }

    public function shorten(Request $request, ShortenService $shortenService): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $utmLink = UtmLink::where('id', $validated['id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($utmLink->short_url) {
            return response()->json(['success' => true, 'short_url' => $utmLink->short_url]);
        }

        $links = $shortenService->shorten($utmLink->final_url, 1);
        $shortUrl = $links[0] ?? null;

        if ($shortUrl) {
            $utmLink->update(['short_url' => $shortUrl]);
        }

        return response()->json(['success' => true, 'short_url' => $shortUrl]);
    }

    public function campaigns(): JsonResponse
    {
        $campaigns = UtmLink::where('user_id', Auth::id())
            ->select('utm_campaign')
            ->distinct()
            ->orderBy('utm_campaign')
            ->pluck('utm_campaign');

        return response()->json($campaigns);
    }

    public function csvImport(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return response()->json(['error' => 'Empty CSV file'], 400);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $required = ['url', 'utm_source', 'utm_medium', 'utm_campaign'];
        foreach ($required as $col) {
            if (! in_array($col, $header)) {
                fclose($handle);
                return response()->json(['error' => "Missing required column: {$col}"], 400);
            }
        }

        $results = [];
        $userId = Auth::id();
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_pad($row, count($header), ''));
            if (! $data || empty($data['url'])) {
                continue;
            }

            $source = $this->normalizeUtmValue($data['utm_source'] ?? '');
            $medium = $this->normalizeUtmValue($data['utm_medium'] ?? '');
            $campaign = $this->normalizeUtmValue($data['utm_campaign'] ?? '');
            $content = $this->normalizeUtmValue($data['utm_content'] ?? null);
            $term = $this->normalizeUtmValue($data['utm_term'] ?? null);

            $finalUrl = $this->buildUtmUrl($data['url'], $source, $medium, $campaign, $content, $term);

            $utmLink = UtmLink::create([
                'user_id' => $userId,
                'original_url' => $data['url'],
                'utm_source' => $source,
                'utm_medium' => $medium,
                'utm_campaign' => $campaign,
                'utm_content' => $content,
                'utm_term' => $term,
                'final_url' => $finalUrl,
            ]);

            $results[] = [
                'id' => $utmLink->id,
                'original_url' => $utmLink->original_url,
                'final_url' => $finalUrl,
            ];
        }
        fclose($handle);

        return response()->json(['success' => true, 'results' => $results, 'count' => count($results)]);
    }

    // Presets CRUD
    public function storePreset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
        ]);

        $preset = UtmPreset::create(array_merge($validated, ['user_id' => Auth::id()]));

        return response()->json(['success' => true, 'preset' => $preset]);
    }

    public function updatePreset(Request $request, UtmPreset $preset): JsonResponse
    {
        if ($preset->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
        ]);

        $preset->update($validated);

        return response()->json(['success' => true, 'preset' => $preset]);
    }

    public function deletePreset(UtmPreset $preset): JsonResponse
    {
        if ($preset->user_id !== Auth::id()) {
            abort(403);
        }

        $preset->delete();

        return response()->json(['success' => true]);
    }

    public function reuse(UtmLink $utmLink): JsonResponse
    {
        if ($utmLink->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'url' => $utmLink->original_url,
            'utm_source' => $utmLink->utm_source,
            'utm_medium' => $utmLink->utm_medium,
            'utm_campaign' => $utmLink->utm_campaign,
            'utm_content' => $utmLink->utm_content,
            'utm_term' => $utmLink->utm_term,
        ]);
    }

    private function normalizeUtmValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Skip normalization if value contains macros like {keyword} or {{campaign.name}}
        if (str_contains($value, '{') || str_contains($value, '}')) {
            return $value;
        }

        // Transliterate cyrillic to latin
        $value = $this->transliterate($value);
        // Lowercase
        $value = mb_strtolower($value);
        // Spaces to underscores
        $value = preg_replace('/\s+/', '_', $value);
        // Remove invalid characters (keep alphanumeric, underscore, hyphen, dot)
        $value = preg_replace('/[^a-z0-9_\-.]/', '', $value);

        return $value;
    }

    private function transliterate(string $str): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ь' => '', 'ы' => 'y',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'a', 'Б' => 'b', 'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e',
            'Ё' => 'yo', 'Ж' => 'zh', 'З' => 'z', 'И' => 'i', 'Й' => 'y', 'К' => 'k',
            'Л' => 'l', 'М' => 'm', 'Н' => 'n', 'О' => 'o', 'П' => 'p', 'Р' => 'r',
            'С' => 's', 'Т' => 't', 'У' => 'u', 'Ф' => 'f', 'Х' => 'kh', 'Ц' => 'ts',
            'Ч' => 'ch', 'Ш' => 'sh', 'Щ' => 'shch', 'Ъ' => '', 'Ь' => '', 'Ы' => 'y',
            'Э' => 'e', 'Ю' => 'yu', 'Я' => 'ya',
        ];

        return strtr($str, $map);
    }

    private function buildUtmUrl(string $url, string $source, string $medium, string $campaign, ?string $content, ?string $term): string
    {
        $params = [];
        if ($source !== '') {
            $params['utm_source'] = $source;
        }
        if ($medium !== '') {
            $params['utm_medium'] = $medium;
        }
        if ($campaign !== '') {
            $params['utm_campaign'] = $campaign;
        }
        if ($content !== null && $content !== '') {
            $params['utm_content'] = $content;
        }
        if ($term !== null && $term !== '') {
            $params['utm_term'] = $term;
        }

        $parsed = parse_url($url);
        $existingQuery = $parsed['query'] ?? '';
        $newQuery = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $base = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').($parsed['path'] ?? '');
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        if ($existingQuery && $newQuery) {
            return $base.'?'.$existingQuery.'&'.$newQuery.$fragment;
        } elseif ($newQuery) {
            return $base.'?'.$newQuery.$fragment;
        }

        return $url;
    }
}
