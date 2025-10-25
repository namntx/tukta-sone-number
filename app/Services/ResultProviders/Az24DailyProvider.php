<?php

namespace App\Services\ResultProviders;

use Carbon\CarbonInterface;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Az24DailyProvider implements DailyResultProviderInterface
{
    public function fetchDaily(string $region, CarbonInterface $date): array
    {
        $region = Str::lower($region);
        $slug   = match ($region) {
            'nam'  => 'xsmn',
            'trung'=> 'xsmt',
            'bac'  => 'xsmb',
            default=> 'xsmn',
        };
        $url = "https://az24.vn/{$slug}-".$date->format('d-m-Y').".html";

        $http = Http::withHeaders([
            'User-Agent' => config('services.az24.user_agent', 'Mozilla/5.0 (compatible; KQXSBot/1.0)'),
                ])
                ->timeout(25)
                ->retry(2, 500)
                ->withOptions([
                    // Nếu bạn cung cấp đường dẫn đến cacert.pem, set verify=that path; nếu không, bool
                    'verify' => config('services.az24.verify_ssl', true) === true
                    ? (config('services.az24.cacert') ?: true)
                    : false,
                ]);

        try {
            $html = $http->get($url)->throw()->body();
        } catch (ConnectionException $e) {
            // Log & trả rỗng để command không vỡ
            \Log::warning('AZ24 SSL/Connection error', ['url'=>$url, 'err'=>$e->getMessage()]);
            return [];
        } catch (\Throwable $e) {
            \Log::warning('AZ24 fetch error', ['url'=>$url, 'err'=>$e->getMessage()]);
            return [];
        }

        if (!$html) return [];


        // Parse DOM
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xp  = new DOMXPath($dom);

        $payloads = [];

        if ($region === 'bac') {
            // MB: 1 đài duy nhất / ngày
            foreach ($this->select($xp, "//table[contains(concat(' ',normalize-space(@class),' '),' kqmb ')]") as $tbl) {
                $p = $this->parseBacTable($xp, $tbl, $date);
                if ($p) $payloads[] = $p;
            }
            return $payloads;
        }

        // MN/MT: có thể 2/3/4 đài
        $tables = [];
        foreach (['coltwocity', 'colthreecity', 'colfourcity'] as $cls) {
            foreach ($this->select($xp, "//table[contains(concat(' ',normalize-space(@class),' '),' colgiai ')][contains(concat(' ',normalize-space(@class),' '),' $cls ')]") as $tbl) {
                $tables[] = $tbl;
            }
        }

        foreach ($tables as $tbl) {
            $payloads = array_merge($payloads, $this->parseMultiStationTable($xp, $tbl, $date, $region));
        }

        return $payloads;
    }

    // ---------- Parsers ----------

    private function parseMultiStationTable(DOMXPath $xp, \DOMElement $table, CarbonInterface $date, string $region): array
    {
        $result = [];

        // Header row: danh sách đài (th) sau cột đầu tiên
        $headerTr = $this->first($xp, ".//tr[contains(@class,'gr-yellow')]", $table);
        if (!$headerTr) return $result;

        $ths = $this->select($xp, ".//th[not(contains(@class,'first'))]", $headerTr);
        if ($ths->length === 0) return $result;

        // index 0..N-1 map sang col index thực (1-based nếu tính cả cột nhãn)
        $stations = []; // [ idx => ['code'=>..., 'name'=>..., 'prizes'=>[]] ]
        $colOffset = 2; // trong table: col1 = nhãn giải (td), col>=2 là theo đài

        foreach ($ths as $i => $th) {
            $a = $this->first($xp, ".//a", $th);
            $name = $a ? $this->cleanText($a->textContent) : $this->cleanText($th->textContent);
            $href = $a?->getAttribute('href') ?? '';
            $code = $this->codeFromHref($href) ?? $this->codeFromName($name);

            $stations[$i] = [
                'code'   => $code,
                'name'   => $this->normalizeStationName($name),
                'prizes' => [
                    'g8'=>[], 'g7'=>[], 'g6'=>[], 'g5'=>[],
                    'g4'=>[], 'g3'=>[], 'g2'=>[], 'g1'=>[],
                    'db'=>[],
                ],
            ];
        }

        // Duyệt từng hàng giải
        $rows = $this->select($xp, ".//tr[not(contains(@class,'gr-yellow'))]", $table);
        foreach ($rows as $tr) {
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length < (1 + count($stations))) continue;

            $label = $this->labelKey($this->cleanText($tds->item(0)->textContent));
            if (!$label) continue;

            // đi theo từng đài
            foreach ($stations as $i => $_st) {
                $td = $tds->item($i + 1); // do cột 0 là nhãn
                $nums = $this->extractNumbersFromCell($td);
                if (empty($nums)) continue;

                // append theo label
                foreach ($nums as $n) {
                    $stations[$i]['prizes'][$label][] = $n;
                }
            }
        }

        // Trả payload theo từng đài
        foreach ($stations as $st) {
            // Nếu không có DB, coi như invalid (bỏ)
            if (empty($st['prizes']['db'])) continue;

            $result[] = [
                'station_code' => $st['code'],
                'station'      => $st['name'],
                'region'       => $region,
                'draw_date'    => $date->toDateString(),
                'prizes'       => $st['prizes'],
            ];
        }

        return $result;
    }

    private function parseBacTable(DOMXPath $xp, \DOMElement $table, CarbonInterface $date): ?array
    {
        $prizes = [
            'g8'=>[], 'g7'=>[], 'g6'=>[], 'g5'=>[], 'g4'=>[], 'g3'=>[], 'g2'=>[], 'g1'=>[], 'db'=>[],
        ];

        // Hàng ĐB
        foreach ($this->select($xp, ".//tr[contains(@class,'db')]", $table) as $tr) {
            $td  = $this->first($xp, ".//td[last()]", $tr);
            $nums = $td ? $this->extractNumbersFromCell($td) : [];
            foreach ($nums as $n) $prizes['db'][] = $n;
        }

        // Các hàng G1..G7,G4,G3,G2...
        $rows = $this->select($xp, ".//tr[not(contains(@class,'db'))]", $table);
        foreach ($rows as $tr) {
            $labelTd = $this->first($xp, ".//td[1]", $tr);  // cột nhãn
            $numsTd  = $this->first($xp, ".//td[last()]", $tr);
            if (!$labelTd || !$numsTd) continue;

            $label = $this->labelKey($this->cleanText($labelTd->textContent));
            if (!$label) continue;

            $nums = $this->extractNumbersFromCell($numsTd);
            foreach ($nums as $n) $prizes[$label][] = $n;
        }

        // Nếu không có DB => bỏ
        if (empty($prizes['db'])) return null;

        return [
            'station_code' => 'mb',
            'station'      => 'mien bac',
            'region'       => 'bac',
            'draw_date'    => $date->toDateString(),
            'prizes'       => $prizes,
        ];
    }

    // ---------- Utils ----------

    private function select(DOMXPath $xp, string $query, \DOMNode $ctx = null): \DOMNodeList
    {
        return $ctx ? $xp->query($query, $ctx) : $xp->query($query);
    }

    private function first(DOMXPath $xp, string $q, \DOMNode $ctx = null): ?\DOMElement
    {
        $n = $this->select($xp, $q, $ctx);
        return $n && $n->length ? $n->item(0) : null;
    }

    private function cleanText(?string $t): string
    {
        if ($t === null) return '';
        // loại bỏ zero-width joiner &zwj; U+200D và khoảng trắng thừa
        $t = preg_replace('/\x{200D}/u', '', $t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = trim(preg_replace('/\s+/u', ' ', $t));
        return $t;
    }

    private function extractNumbersFromCell(\DOMElement $td): array
    {
        $texts = [];
        $walker = function(\DOMNode $n) use (&$texts, &$walker) {
            if ($n->nodeType === XML_TEXT_NODE) {
                $texts[] = $n->nodeValue;
            }
            if ($n->hasChildNodes()) {
                foreach ($n->childNodes as $c) $walker($c);
            }
        };
        $walker($td);

        $nums = [];
        foreach ($texts as $t) {
            $t = $this->cleanText($t);
            if ($t === '') continue;
            // Lấy chuỗi số 2..6 chữ số (giữ leading zero)
            if (preg_match_all('/\d{2,6}/u', $t, $m)) {
                foreach ($m[0] as $v) $nums[] = $v;
            }
        }
        return $nums;
    }

    private function labelKey(string $label): ?string
    {
        $l = Str::lower($label);
        $l = str_replace(['đ',' '], ['d',''], $l);
        // chuẩn hoá vài biến thể
        if ($l === 'g8') return 'g8';
        if ($l === 'g7') return 'g7';
        if ($l === 'g6') return 'g6';
        if ($l === 'g5') return 'g5';
        if ($l === 'g4') return 'g4';
        if ($l === 'g3') return 'g3';
        if ($l === 'g2') return 'g2';
        if ($l === 'g1') return 'g1';
        if ($l === 'db' || $l === 'gdb' || $l === 'ddb') return 'db';
        return null;
    }

    private function codeFromHref(string $href): ?string
    {
        // ví dụ: /xstg-sxtg-xo-so-tien-giang.html -> tg
        if (preg_match('#/xs([a-z]{2,4})\b#i', $href, $m)) {
            return Str::lower($m[1]);
        }
        return null;
    }

    private function codeFromName(string $name): string
    {
        // fallback đơn giản — bạn có thể mở rộng map này để khớp Seeder của bạn
        $n = Str::lower($name);
        return match (true) {
            str_contains($n,'tiền giang') || str_contains($n,'tien giang') => 'tg',
            str_contains($n,'kiên giang') || str_contains($n,'kien giang') => 'kg',
            str_contains($n,'đà lạt')     || str_contains($n,'da lat')     => 'dl',
            str_contains($n,'tp hcm')     || str_contains($n,'tp hcm')     => 'tp',
            str_contains($n,'long an')                                      => 'la',
            str_contains($n,'bình phước')|| str_contains($n,'binh phuoc')  => 'bp',
            str_contains($n,'hậu giang') || str_contains($n,'hau giang')   => 'hg',
            str_contains($n,'huế')       || str_contains($n,'hue')         => 'tth',
            str_contains($n,'phú yên')   || str_contains($n,'phu yen')     => 'py',
            default => Str::slug($n, ''), // "tphochiminh" ...
        };
    }

    private function normalizeStationName(string $name): string
    {
        $n = Str::lower($this->cleanText($name));
        // chuẩn một vài tên hay gặp cho nhất quán với Parser hiện tại
        return match (true) {
            $n === 'tp hcm' || $n === 'tp hcm ' || str_contains($n, 'tp hồ chí minh') || str_contains($n, 'tp ho chi minh') => 'tp.hcm',
            default => $n,
        };
    }
}
