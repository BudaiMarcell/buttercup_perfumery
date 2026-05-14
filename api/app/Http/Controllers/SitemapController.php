<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dynamic XML sitemap. Bots fetch /sitemap.xml; Caddy/Traefik routes
 * that path to this controller (see infrastructure routing). We cache
 * the result for an hour because crawlers will hit it often and the
 * underlying query is the full product catalog.
 *
 * The sitemap covers:
 *   - The static landing pages (/, /shop, /about)
 *   - One entry per active, non-deleted product
 *
 * Account / auth / checkout URLs are intentionally absent (also
 * excluded in robots.txt) because they have no SEO value and would
 * waste crawl budget.
 */
class SitemapController extends Controller
{
    public function index(Request $request): Response
    {
        // Frontend origin for the public URLs. Fall back to the
        // request host when FRONTEND_URL isn't set (dev convenience).
        $frontend = rtrim(
            (string) config('app.frontend_url', env('FRONTEND_URL', $request->getSchemeAndHttpHost())),
            '/'
        );

        $xml = Cache::remember('sitemap.xml', 3600, function () use ($frontend) {
            $urls = [
                ['loc' => $frontend . '/',      'priority' => '1.0', 'lastmod' => now()->toDateString()],
                ['loc' => $frontend . '/shop',  'priority' => '0.9', 'lastmod' => now()->toDateString()],
                ['loc' => $frontend . '/about', 'priority' => '0.5', 'lastmod' => now()->toDateString()],
            ];

            // Active products only. Soft-deleted rows are excluded
            // automatically by the model's global scope.
            Product::where('is_active', true)
                ->select(['slug', 'updated_at'])
                ->chunkById(500, function ($products) use (&$urls, $frontend) {
                    foreach ($products as $p) {
                        $urls[] = [
                            'loc'      => $frontend . '/shop/' . $p->slug,
                            'priority' => '0.7',
                            'lastmod'  => optional($p->updated_at)->toDateString() ?? now()->toDateString(),
                        ];
                    }
                });

            $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $body .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
            foreach ($urls as $u) {
                $body .= "  <url>\n";
                $body .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
                $body .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
                $body .= "    <priority>" . $u['priority'] . "</priority>\n";
                $body .= "  </url>\n";
            }
            $body .= "</urlset>\n";
            return $body;
        });

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
