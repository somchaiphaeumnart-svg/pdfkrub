<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$routesToTest = [
    '/' => 'หน้าแรก (Homepage)',
    '/tools' => 'เครื่องมือทั้งหมด (Tools Index)',
    '/pricing' => 'ราคา (Pricing)',
    '/templates' => 'คลังแบบฟอร์มครู (Templates)',
    '/pdpa' => 'นโยบาย PDPA (PDPA Policy)',
    '/privacy' => 'นโยบายความเป็นส่วนตัว (Privacy Policy)',
    '/terms' => 'ข้อกำหนดการใช้งาน (Terms)',
    '/about' => 'เกี่ยวกับเรา (About)',
    '/contact' => 'ติดต่อเรา (Contact)',
    '/sitemap.xml' => 'XML Sitemap (SEO)',
    '/login' => 'เข้าสู่ระบบ (Login)',
    '/register' => 'สมัครสมาชิก (Register)',
    '/tools/pdf-to-word' => 'เครื่องมือ: PDF to Word',
    '/tools/ocr-pdf' => 'เครื่องมือ: OCR ภาษาไทย',
    '/tools/sign-pdf' => 'เครื่องมือ: เซ็นเอกสาร',
    '/tools/compress-pdf' => 'เครื่องมือ: บีบอัด PDF',
    '/tools/merge-pdf' => 'เครื่องมือ: รวม PDF',
    '/tools/split-pdf' => 'เครื่องมือ: แยก PDF',
    '/api/v1/health' => 'API: Health Check',
    '/api/v1/plans' => 'API: Available Plans',
];

$results = [];
$brokenLinks = [];

echo "===============================================================\n";
echo "🔍 PDFkrub — Automated System Audit & Diagnostic Benchmark\n";
echo "===============================================================\n\n";

foreach ($routesToTest as $uri => $label) {
    $startTime = microtime(true);
    $request = Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    $durationMs = round((microtime(true) - $startTime) * 1000, 2);

    $status = $response->getStatusCode();
    $content = $response->getContent();

    // SEO Checks
    $hasTitle = preg_match('/<title>(.*?)<\/title>/is', $content, $titleMatch);
    $title = $hasTitle ? trim($titleMatch[1]) : 'MISSING';

    $hasMetaDesc = preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $content, $descMatch);
    $metaDesc = $hasMetaDesc ? trim($descMatch[1]) : 'MISSING';

    $h1Count = preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $content, $h1Matches);

    $hasCanonical = preg_match('/<link\s+rel=["\']canonical["\']/is', $content);
    $hasOgTitle = preg_match('/<meta\s+property=["\']og:title["\']/is', $content);

    // Check links inside HTML
    if ($status === 200 && str_starts_with($uri, '/') && ! str_starts_with($uri, '/api')) {
        preg_match_all('/(?<![:a-zA-Z0-9_-])href=["\']([^"\']+)["\']/i', $content, $hrefMatches);
        foreach ($hrefMatches[1] as $href) {
            // Ignore external, anchors, javascript, mailto, tel
            if (str_starts_with($href, '#') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'http')) {
                continue;
            }
            // Check if internal route exists
            try {
                $subReq = Request::create($href, 'GET');
                $subRes = $kernel->handle($subReq);
                if ($subRes->getStatusCode() >= 400 && $subRes->getStatusCode() !== 401 && $subRes->getStatusCode() !== 405) {
                    $brokenLinks[] = [
                        'source' => $uri,
                        'target' => $href,
                        'status' => $subRes->getStatusCode(),
                    ];
                }
            } catch (Exception $e) {
                $brokenLinks[] = [
                    'source' => $uri,
                    'target' => $href,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    $results[] = [
        'uri' => $uri,
        'label' => $label,
        'status' => $status,
        'durationMs' => $durationMs,
        'title' => $title,
        'metaDesc' => $metaDesc,
        'h1Count' => $h1Count,
        'hasCanonical' => $hasCanonical,
        'hasOgTitle' => $hasOgTitle,
    ];

    $statusColor = $status === 200 ? '✅' : '⚠️';
    $timeColor = $durationMs < 100 ? '⚡' : ($durationMs < 300 ? '⏱️' : '🐢');
    echo sprintf("%s %s %-30s [%d] in %6.1f ms %s\n", $statusColor, $timeColor, $label, $status, $durationMs, $uri);
}

echo "\n---------------------------------------------------------------\n";
echo "📊 SEO & Meta Summary:\n";
echo "---------------------------------------------------------------\n";
foreach ($results as $res) {
    if (str_starts_with($res['uri'], '/api')) {
        continue;
    }
    echo sprintf("%-28s | H1: %d | Title: %-35s | Meta Desc: %s\n",
        $res['uri'],
        $res['h1Count'],
        mb_substr($res['title'], 0, 35),
        $res['metaDesc'] !== 'MISSING' ? '✅' : '❌'
    );
}

echo "\n---------------------------------------------------------------\n";
echo '🔗 Broken Links Found: '.count($brokenLinks)."\n";
echo "---------------------------------------------------------------\n";
if (! empty($brokenLinks)) {
    foreach ($brokenLinks as $bl) {
        echo "❌ Source: {$bl['source']} -> Link: {$bl['target']} (Status: ".($bl['status'] ?? 'ERROR').")\n";
    }
} else {
    echo "✅ No internal broken links detected!\n";
}

echo "\n===============================================================\n";
file_put_contents(__DIR__.'/audit_results.json', json_encode([
    'results' => $results,
    'brokenLinks' => $brokenLinks,
    'timestamp' => date('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
