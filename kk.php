<?php

namespace App\Services;

use App\Models\BettingType;
use App\Models\Station;
use Illuminate\Support\Str;

class BettingMessageParser
{
    private $globalDate = null; // Global date for station calculation
    private $lastSelectedStations = null; // Last selected stations for implicit betting
    
    private $stationAbbreviations = [
        // fallback tối thiểu (sẽ hợp nhất với DB ở __construct)
        'tn' => 'tây ninh','t,ninh' => 'tây ninh','tninh' => 'tây ninh',
        'ag' => 'an giang','a,giang' => 'an giang','agiang' => 'an giang',
        'bt' => 'bến tre','b,tre' => 'bến tre','btre' => 'bến tre',
        'hcm' => 'tp.hcm','tphcm' => 'tp.hcm','tp' => 'tp.hcm','sg'=>'tp.hcm',
        'dn' => 'đồng nai','d,nai' => 'đồng nai','dnai' => 'đồng nai',
        'mb' => 'hà nội','hn' => 'hà nội','hanoi' => 'hà nội',
        'vt' => 'vũng tàu','vtau' => 'vũng tàu',
        'bd' => 'bình dương','bduong' => 'bình dương',
        'ct' => 'cần thơ','ctho' => 'cần thơ',
        'la' => 'long an','l.an' => 'long an','l,an' => 'long an','lan' => 'long an',
        'cm' => 'cà mau','cmau' => 'cà mau',
        'dt' => 'đồng tháp','dthap' => 'đồng tháp',
        'bl' => 'bạc liêu','blieu' => 'bạc liêu',
        'st' => 'sóc trăng','strang' => 'sóc trăng',
        'bp' => 'bình phước','bphuoc' => 'bình phước',
        'bth' => 'bình thuận','bthuan' => 'bình thuận',
        'la' => 'long an','lan','l.an','l,an' => 'long an',
        'kg' => 'kiên giang','kgiang' => 'kiên giang',
        'hg' => 'hậu giang','hgiang' => 'hậu giang',
        'tg' => 'tiền giang','tgiang' => 'tiền giang',
        'dl' => 'đà lạt','dlat' => 'đà lạt',
        'vl' => 'vĩnh long','vlong' => 'vĩnh long',
        'tv' => 'trà vinh','tvinh' => 'trà vinh',
    ];

    private function toVnd(string $num): int {
        return (int)round(((float)$num) * 1000); // d35n = 35 * 1000
    }

    private $amountUnits = [
        'k' => 1000,
        'n' => 1000, // mặc định 'n' = nghìn
        'tr' => 1000000,
        'trieu' => 1000000, 'triệu'=>1000000,
        'nghin' => 1000, 'nghìn'=>1000,
        'ngan' => 1000, 'ngàn'=>1000,
    ];

    private $bettingShortcuts = [
        // Đầu/đuôi/đầu-đuôi
        'dau'=>'dau','đầu'=>'dau','d'=>'dau',
        'duoi'=>'duoi','đuôi'=>'duoi','dui'=>'duoi',
        'dauduoi'=>'dau_duoi','đầuđuôi'=>'dau_duoi','dd'=>'dau_duoi',

        // Lô (động theo độ dài số)
        'lo'=>'bao_lo_dynamic','l'=>'bao_lo_dynamic',
        'bao2s'=>'bao_lo','bao2'=>'bao_lo','b2s'=>'bao_lo','b2'=>'bao_lo',
        'bao3s'=>'bao3_lo','bao3'=>'bao3_lo','b3s'=>'bao3_lo','b3'=>'bao3_lo',
        'bao4s'=>'bao4_lo','bao4'=>'bao4_lo','b4s'=>'bao4_lo','b4'=>'bao4_lo',
        'bd'=>'bao_lo_dao','baodao'=>'bao_lo_dao','bdao'=>'bao_lo_dao','bld'=>'bao_lo_dao','bldao'=>'bao_lo_dao','daolo'=>'bao_lo_dao',

        // Xỉu chủ + biến thể
        'xc'=>'xiu_chu','x'=>'xiu_chu','xiu'=>'xiu_chu','tl'=>'xiu_chu',
        'dxc'=>'xiu_chu_dao','xcd'=>'xiu_chu_dao','xd'=>'xiu_chu_dao','daoxc'=>'xiu_chu_dao','xcdao'=>'xiu_chu_dao',
        'xcdau'=>'xiu_chu_dau','dauxc'=>'xiu_chu_dau','xdau'=>'xiu_chu_dau',
        'xcduoi'=>'xiu_chu_duoi','duoixc'=>'xiu_chu_duoi','xcdui'=>'xiu_chu_duoi','xduoi'=>'xiu_chu_duoi','xdui'=>'xiu_chu_duoi',

        // Đá / Xiên
        'da'=>'da_thang','đá'=>'da_thang','dth'=>'da_thang','dathang'=>'da_thang','dat'=>'da_thang',
        'dx'=>'da_xien','xien'=>'da_xien','daxien'=>'da_xien','da xien'=>'da_xien','dacheo'=>'da_xien','da cheo'=>'da_xien','cheo'=>'da_xien',
        'dax'=>'da_xien','đax'=>'da_xien', // <— NEW alias

        'xien2'=>'xien_2','xh'=>'xien_2','xhai'=>'xien_2','xienhai'=>'xien_2',
        'xien3'=>'xien_3','xienba'=>'xien_3','xba'=>'xien_3',
        'xien4'=>'xien_4','xienbon'=>'xien_4','xbon'=>'xien_4',

        // Bảy/tám lô
        'baylo'=>'bay_lo','baylodao'=>'bay_lo_dao','daobaylo'=>'bay_lo_dao',
        'tamlo'=>'tam_lo','tamlodao'=>'tam_lo_dao','daotamlo'=>'tam_lo_dao',

        // Kéo
        'keo'=>'keo','keo'=>'keo','den'=>'keo','toi'=>'keo',

        // Chẵn lẻ & từ khóa phổ biến
        'chan'=>'chan','le'=>'le','lẻ'=>'le',
        'chanchan'=>'chan_chan','lele'=>'le_le','chanle'=>'chan_le','lechan'=>'le_chan',
        'giap'=>'giap_all','kepbang'=>'kep_bang','keplech'=>'kep_lech',

        // Tổng
        'tongto'=>'tong_to','tongbe'=>'tong_be','tongchan'=>'tong_chan','tongle'=>'tong_le',
        
        // Bảy lô, tám lô
        'baylo'=>'bay_lo','baylodao'=>'bay_lo_dao','daobaylo'=>'bay_lo_dao',
        'tamlo'=>'tam_lo','tamlodao'=>'tam_lo_dao','daotamlo'=>'tam_lo_dao',
        
        // Xỉu chủ đảo
        'xcddau'=>'xiu_chu_dao_dau','xcdaud'=>'xiu_chu_dao_dau',
        'xcdduoi'=>'xiu_chu_dao_duoi','xcduoid'=>'xiu_chu_dao_duoi',
        
        // Đề đặc biệt
        'dedaudacbiet'=>'de_dau_dac_biet','dedaugiai1'=>'de_dau_giai_1','degi1'=>'de_giai_1',
        
        // Giáp (12 con giáp)
        'giapty'=>'giap_ty','giapsuu'=>'giap_suu','giapdan'=>'giap_dan','giapmao'=>'giap_mao',
        'giapthin'=>'giap_thin','giapti'=>'giap_ti','giapngo'=>'giap_ngo','giapmui'=>'giap_mui',
        'giapthan'=>'giap_than','giapga'=>'giap_ga','giaptuat'=>'giap_tuat','giaphoi'=>'giap_hoi',
        
        // Dàn số
        'dan05cokep'=>'dan_05_co_kep','dan05bokep'=>'dan_05_bo_kep',
        'dan06cokep'=>'dan_06_co_kep','dan06bokep'=>'dan_06_bo_kep',
        'dan07cokep'=>'dan_07_co_kep','dan07bokep'=>'dan_07_bo_kep',
        'dan08cokep'=>'dan_08_co_kep','dan08bokep'=>'dan_08_bo_kep',
        'dan15cokep'=>'dan_15_co_kep','dan15bokep'=>'dan_15_bo_kep',
        'dan16cokep'=>'dan_16_co_kep','dan16bokep'=>'dan_16_bo_kep',
        'dan17cokep'=>'dan_17_co_kep','dan17bokep'=>'dan_17_bo_kep',
        'dan18cokep'=>'dan_18_co_kep','dan18bokep'=>'dan_18_bo_kep',
        'dan19cokep'=>'dan_19_co_kep','dan19bokep'=>'dan_19_bo_kep',
        'dan26cokep'=>'dan_26_co_kep','dan26bokep'=>'dan_26_bo_kep',
        'dan27cokep'=>'dan_27_co_kep','dan27bokep'=>'dan_27_bo_kep',
        'dan28cokep'=>'dan_28_co_kep','dan28bokep'=>'dan_28_bo_kep',
        'dan29cokep'=>'dan_29_co_kep','dan29bokep'=>'dan_29_bo_kep',
        'dan38cokep'=>'dan_38_co_kep','dan38bokep'=>'dan_38_bo_kep',
        'dan39cokep'=>'dan_39_co_kep','dan39bokep'=>'dan_39_bo_kep',
        'dan49cokep'=>'dan_49_co_kep','dan49bokep'=>'dan_49_bo_kep',
        
        // Dàn cách
        'dan00cach3'=>'dan_00_cach_3','dan00cach4'=>'dan_00_cach_4',
        'dan01cach3'=>'dan_01_cach_3','dan02cach3'=>'dan_02_cach_3',
        
        // Kép và sát kép
        'keplech'=>'kep_lech','kepbang'=>'kep_bang',
        'satkepbang'=>'sat_kep_bang','satkeplech'=>'sat_kep_lech','sathaikep'=>'sat_hai_kep',
        
        // Tổng chi tiết
        'tong0'=>'tong_0','tong1'=>'tong_1','tong2'=>'tong_2','tong3'=>'tong_3',
        'tong4'=>'tong_4','tong5'=>'tong_5','tong6'=>'tong_6','tong7'=>'tong_7',
        'tong8'=>'tong_8','tong9'=>'tong_9',
        'tongtren10'=>'tong_tren_10','tongduoi10'=>'tong_duoi_10',
        'tongchia3du0'=>'tong_chia_3_du_0','tongchia3du1'=>'tong_chia_3_du_1','tongchia3du2'=>'tong_chia_3_du_2',
    ];

    public function __construct()
    {
        $this->hydrateStationAliasesFromDB();
        $this->hydrateBettingAliasesFromDB();
        
        // Sử dụng global variables từ session (nếu có)
        try {
            if (app()->bound('session') && session()->has('global_date')) {
                $this->globalDate = session('global_date');
            }
        } catch (Exception $e) {
            // Session không khả dụng, sử dụng mặc định
        }
    }
    
    /**
     * Set global date for station calculation
     * @param string $date Date in Y-m-d format
     */
    public function setGlobalDate(string $date): void
    {
        $this->globalDate = $date;
    }

    /**
     * Public method for testing normalization
     */
    public function testNormalize(string $message): string
    {
        return $this->normalize($message);
    }

    /**
     * Public method for testing getDefaultStationByDay
     */
    public function testGetDefaultStationByDay(?string $region = null, ?string $globalDate = null): string
    {
        return $this->getDefaultStationByDay($region, $globalDate);
    }

    /**
     * Public method for testing tokenization
     */
    public function testTokenize(string $message): array
    {
        return $this->tokenize($message);
    }

    /**
     * Public method for testing betting shortcuts
     */
    public function getBettingShortcuts(): array
    {
        return $this->bettingShortcuts;
    }

    public function testParseMessage(string $message): array
    {
        try {
            return $this->parseMessage($message);
        } catch (Exception $e) {
            return ['is_valid' => false, 'error' => $e->getMessage()];
        }
    }

    public function parseMessage($message, $customerId = null)
    {
        $message = trim((string)$message);
        $bets = $this->parseMultipleBets($message);
        return [
            'is_valid'       => !empty($bets),
            'errors'         => empty($bets) ? ['Không phân tích được nội dung.'] : [],
            'parsed_message' => $message,
            'multiple_bets'  => $bets,
        ];
    }

    public function parseMultipleBets(string $message): array
    {
        $norm = $this->normalize($message);
        $lines = preg_split('/[\r\n;]+/u', $norm) ?: [$norm];

        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $items = $this->parseLine($line);
            if (!empty($items)) $out = array_merge($out, $items);
        }
        return $out;
    }

    private function parseLine(string $line): array
    {
        $tokens = $this->tokenize($line);

        $currentStation = null;
        $stationBuffer  = [];   // NEW: chứa nhiều đài liên tiếp (vd: tn ag)
        $currentNumbers = [];
        $pending        = [];
        $dToggle        = 0;
        $results        = [];
        $hasStationInMessage = false; // Track if message contains any station
        $lastBettingType = null; // Track last betting type for implicit betting
        $multipleBets = []; // Track implicit bets
        $regionFromMessage = $this->parseRegionFromMessage($line); // Parse region from message

        $flush = function() use (&$results,&$currentStation,&$stationBuffer,&$currentNumbers,&$pending,&$dToggle,&$hasStationInMessage,&$regionFromMessage) {
            if (empty($pending) || empty($currentNumbers)) return;

            // station targets: ưu tiên đài đã lưu trong directive, sau đó mới dùng buffer/currentStation
            $targets = [];
            foreach ($pending as $dir) {
                if (!empty($dir['station'])) {
                    $targets[] = $dir['station'];
                }
            }
            if (empty($targets)) {
                // Kiểm tra xem có selectedStations từ multi-stations không
                if (!empty($this->lastSelectedStations)) {
                    $targets = $this->lastSelectedStations;
                    // Use lastSelectedStations
                } elseif (!empty($stationBuffer)) {
                    $targets = $stationBuffer;
                } elseif ($currentStation) {
                    $targets = [$currentStation];
                } else {
                    // Không có đài trong message -> sử dụng đài chính theo ngày và miền
                    $defaultStation = $this->getDefaultStationByDay($regionFromMessage, $this->globalDate);
                    $targets = [$defaultStation];
                }
            }

            $expandedNumbers = $this->expandNumbers($currentNumbers, $pending);

            foreach ($pending as $dir) {
                // Xử lý multi-stations (2d, 3d, 4d)
                if ($dir['type'] === '__MULTI_STATIONS__') {
                    $n = $dir['meta']['multi_stations'] ?? 2;
                    $region = $dir['meta']['region'] ?? $regionFromMessage ?? 'nam';
                    
                    // Lấy danh sách đài theo miền
                    $availableStations = $this->getStationsByRegion($region);
                    
                    // Lấy ngẫu nhiên n đài, bắt buộc có 1 đài chính
                    $selectedStations = $this->selectRandomStations($availableStations, $n, $region, $this->globalDate);
                    
                    // Lưu selectedStations vào meta để sử dụng cho implicit betting
                    $dir['meta']['selected_stations'] = $selectedStations;
                    
                    // Tạo bet cho từng đài (chỉ khi có amount)
                    if ($dir['amount'] > 0) {
                        foreach ($selectedStations as $station) {
                            foreach ($expandedNumbers as $num) {
                                $type = $this->mapLoTypeByLen($num);
                                $results[] = [
                                    'station' => $station,
                                    'type'    => $type,
                                    'numbers' => [$num],
                                    'amount'  => $dir['amount'],
                                    'meta'    => $dir['meta'] ?? [],
                                ];
                            }
                        }
                    }
                    
                    // Lưu selectedStations vào global để sử dụng cho implicit betting
                    $this->lastSelectedStations = $selectedStations;
                    // Set lastSelectedStations
                    continue;
                }
                
                // Sử dụng đài riêng của directive này
                $dirStation = $dir['station'] ?? null;
                if (!$dirStation && !empty($targets)) {
                    $dirStation = $targets[0]; // fallback
                }
                
                // Xiên?
                if (!empty($dir['xien_size'])) {
                    $groups = $this->combinations($expandedNumbers, (int)$dir['xien_size']);
                    foreach ($groups as $grp) {
                        $results[] = [
                            'station' => $dirStation,
                            'type'    => 'da_xien',
                            'numbers' => $grp,
                            'amount'  => $dir['amount'],
                            'meta'    => array_merge($dir['meta'] ?? [], ['xien_size'=>$dir['xien_size']]),
                        ];
                    }
                    continue;
                }

                // Tạo riêng lẻ cho từng số (trừ xiên)
                if (!empty($dir['xien_size'])) {
                    // Xiên: tạo riêng lẻ cho từng số
                    foreach ($expandedNumbers as $num) {
                        $results[] = [
                            'station' => $dirStation,
                            'type'    => $dir['type'],
                            'numbers' => [$num],
                            'amount'  => $dir['amount'],
                            'meta' => $dir['meta'] ?? [],
                        ];
                    }
                } elseif ($dir['type'] === 'bao_lo_dynamic') {
                    // Bao lô dynamic: tạo riêng lẻ cho từng số
                    foreach ($expandedNumbers as $num) {
                        $type = $this->mapLoTypeByLen($num);
                        $results[] = [
                            'station' => $dirStation,
                            'type'    => $type,
                            'numbers' => [$num],
                            'amount'  => $dir['amount'],
                            'meta' => $dir['meta'] ?? [],
                        ];
                    }
                } else {
                    // Các loại khác: tạo riêng lẻ cho từng số
                    foreach ($expandedNumbers as $num) {
                        $results[] = [
                            'station' => $dirStation,
                            'type'    => $dir['type'],
                            'numbers' => [$num],
                            'amount'  => $dir['amount'],
                            'meta' => $dir['meta'] ?? [],
                        ];
                    }
                }
            }

            $pending = [];
            $stationBuffer = [];  // reset sau khi bắn
            $dToggle = 0;
        };

        for ($i=0; $i<count($tokens); $i++) {
            $tk = $tokens[$i];
            $next = $tokens[$i+1] ?? null;

            // 0) Multi-stations: 2d/3d/4d (+ miền) -> lưu meta trong pending (để layer sau xử lý)
            if (preg_match('/^([234])d(ai)?(m?(bac|trung|nam))?$/u', $tk, $m)) {
                $n = (int)$m[1];
                $region = isset($m[4]) ? $m[4] : null;
                
                // Kiểm tra token tiếp theo có phải là region không
                if (!$region && $next && in_array(strtolower($next), ['bac', 'trung', 'nam'])) {
                    $region = strtolower($next);
                    $i++; // Skip region token
                }
                
                // Set lastSelectedStations ngay lập tức
                $region = $region ?? $regionFromMessage ?? 'nam';
                $availableStations = $this->getStationsByRegion($region);
                
                // Sử dụng globalDate từ session nếu có
                $globalDate = $this->globalDate;
                if (!$globalDate && isset($_SESSION['global_date'])) {
                    $globalDate = $_SESSION['global_date'];
                }
                
                $selectedStations = $this->selectRandomStations($availableStations, $n, $region, $globalDate);
                $this->lastSelectedStations = $selectedStations;
                // Set lastSelectedStations immediately
                
                $pending[] = ['type'=>'__MULTI_STATIONS__','amount'=>0,'meta'=>['multi_stations'=>$n,'region'=>$region]];
                continue;
            }

            // 1) Đài: có thể có nhiều đài liên tiếp
            if ($this->isStationToken($tk)) {
                $station = $this->normalizeStation($tk);
                $stationBuffer[] = $station;
                $currentStation = $station; // last one vẫn là context kế thừa
                $hasStationInMessage = true;
                continue;
            }
            
            // 1.b) Kiểm tra các token liên tiếp có thể tạo thành station (L.an -> l an)
            $stationFromTokens = $this->isStationTokens($tokens, $i);
            if ($stationFromTokens) {
                $stationBuffer[] = $stationFromTokens;
                $currentStation = $stationFromTokens;
                $hasStationInMessage = true;
                // Skip các token đã được sử dụng
                $i += $this->getStationTokenLength($tokens, $i) - 1;
                continue;
            }

            // 2) "kéo" (keo/den/toi): biến dãy số thành cặp [prev,next]
            if ($this->isTypeToken($tk, ['keo','den','toi'])) {
                $prev = null;
                if (!empty($currentNumbers)) {
                    $prev = end($currentNumbers);
                } else {
                    // tìm ngược lại một số trước đó trong tokens
                    for ($p=$i-1; $p>=0; $p--) {
                        if ($this->isNumberToken($tokens[$p])) { $prev = $tokens[$p]; break; }
                        if ($this->isStationToken($tokens[$p])) break;
                    }
                }
                // số kế sau:
                $nextNum = null;
                for ($j=$i+1; $j<count($tokens); $j++) {
                    if ($this->isNumberToken($tokens[$j])) { $nextNum = $tokens[$j]; $i = $j; break; }
                    if ($this->isStationToken($tokens[$j])) break;
                }
                if ($prev && $nextNum) {
                    $currentNumbers = [$prev, $nextNum];
                }
                continue;
            }

            // 3.5) Xử lý cặp d35n d40n (đầu 35n + đuôi 40n) - KIỂM TRA TRƯỚC
            if (preg_match('/^d(\d+(?:\.\d+)?)n$/u', $tk, $m1) && 
                isset($tokens[$i+1]) && preg_match('/^d(\d+(?:\.\d+)?)n$/u', $tokens[$i+1], $m2)) {
                $amount1 = $this->toVnd($m1[1]); // 35n
                $amount2 = $this->toVnd($m2[1]); // 40n
                $pending[] = ['type'=>'dau', 'amount'=>$amount1];
                $pending[] = ['type'=>'duoi', 'amount'=>$amount2];
                $i += 1; // Skip cả 2 tokens
                continue;
            }

            // 3) Directive (lo/d/xc/da/xien/dd/...) – đã hỗ trợ "directive + number + amount"
            if ($ret = $this->parseDirectiveAt($tokens, $i, $dToggle)) {
                // Cập nhật kiểu cược gần nhất cho implicit betting
                $lastBettingType = $ret['directive']['type'];
                
                // Nếu directive này đi kèm "attach_number" (vd: xc 515 20n, d 15 10n, lo 68 5n...)
                // thì TRƯỚC TIÊN phải "khoá" các lệnh đang chờ cho dàn số hiện tại.
                if (!empty($ret['attach_number'])) {
                    $flush(); // áp các pending trước đó lên $currentNumbers hiện tại
                    $currentNumbers = [$ret['attach_number']]; // chuyển ngữ cảnh sang số mới
                }
                
                // Nếu directive có "numbers" array (vd: xc 271 272 274 30n)
                if (!empty($ret['directive']['numbers'])) {
                    $flush(); // áp các pending trước đó lên $currentNumbers hiện tại
                    $currentNumbers = $ret['directive']['numbers']; // chuyển ngữ cảnh sang nhiều số mới
                }
            
                // Lưu trữ đài hiện tại cho directive này
                // Check station for directive
                if (!empty($stationBuffer)) {
                    $currentStationForDirective = end($stationBuffer);
                } elseif ($currentStation) {
                    $currentStationForDirective = $currentStation;
                } elseif (!empty($this->lastSelectedStations)) {
                    // Sử dụng selectedStations từ multi-stations
                    $currentStationForDirective = $this->lastSelectedStations;
                    // Use lastSelectedStations for directive
                } else {
                    // Không có đài trong message -> sử dụng đài chính theo ngày và miền
                    $currentStationForDirective = $this->getDefaultStationByDay($regionFromMessage, $this->globalDate);
                }
                
                // Bây giờ mới xếp lệnh hiện tại vào pending (để áp lên $currentNumbers mới nếu có)
                $directive = $ret['directive'];
                if (is_array($currentStationForDirective)) {
                    // Multi-stations: tạo directive cho từng đài
                    foreach ($currentStationForDirective as $station) {
                        $directiveCopy = $directive;
                        $directiveCopy['station'] = $station;
                        $pending[] = $directiveCopy;
                    }
                } else {
                    $directive['station'] = $currentStationForDirective;
                    $pending[] = $directive;
                }
                
                if (!empty($ret['also'])) {
                    $also = $ret['also'];
                    if (is_array($currentStationForDirective)) {
                        // Multi-stations: tạo also directive cho từng đài
                        foreach ($currentStationForDirective as $station) {
                            $alsoCopy = $also;
                            $alsoCopy['station'] = $station;
                            $pending[] = $alsoCopy;
                        }
                    } else {
                        $also['station'] = $currentStationForDirective;
                        $pending[] = $also;
                    }
                }
            
                $i += $ret['advance'];
                $dToggle = $ret['dToggle'];
                continue;
            }

            // 4) Số (2~4) hoặc từ-khóa-dàn
            if ($this->isNumberToken($tk) || $this->isNumberKeyword($tk)) {
                // kết thúc nhóm trước (nếu có lệnh chờ)
                $flush();

                // Kiểm tra xem có phải là số đơn lẻ + amount không
                $next = $tokens[$i+1] ?? null;
                $nextNext = $tokens[$i+2] ?? null;
                
                // Chỉ xử lý implicit betting nếu là số đơn lẻ + amount (không phải danh sách số)
                $isSingleNumber = !$this->isNumberToken($next);
                
                // Check implicit betting
                if ($isSingleNumber && $next && ($amount = $this->parseAmountOnly($next)) > 0) {
                    // Có pattern số + amount, kiểm tra xem có kiểu cược gần nhất không
                    if (!empty($lastBettingType)) {
                        // Kiểm tra xem có phải xỉu chủ không - chỉ áp dụng cho số 3 chữ số
                        if ($lastBettingType === 'xiu_chu' && !preg_match('/^\d{3}$/', $tk)) {
                            // Không áp dụng xỉu chủ cho số không phải 3 chữ số
                            $i++;
                            continue;
                        }
                        // Kiểm tra xem có selectedStations từ multi-stations không
                        // Check lastSelectedStations
                        if (!empty($this->lastSelectedStations)) {
                            // Sử dụng selectedStations từ multi-stations
                            $selectedStations = $this->lastSelectedStations;
                            // Use selectedStations from multi-stations
                            $betType = $lastBettingType;
                            
                            // Nếu là bao_lo_dynamic, cần map theo độ dài số
                            if ($betType === 'bao_lo_dynamic') {
                                $betType = $this->mapLoTypeByLen($tk);
                            }
                            
                            // Tạo bet cho từng đài đã chọn
                            foreach ($selectedStations as $station) {
                                $bet = [
                                    'station' => $station,
                                    'type' => $betType,
                                    'numbers' => [$tk],
                                    'amount' => $amount,
                                    'meta' => []
                                ];
                                $multipleBets[] = $bet;
                            }
                        } else {
                            // Logic cũ: sử dụng đài hiện tại
                            if (!empty($stationBuffer)) {
                                $currentStationForDirective = end($stationBuffer);
                            } elseif ($currentStation) {
                                $currentStationForDirective = $currentStation;
                            } else {
                                $currentStationForDirective = $this->getDefaultStationByDay($regionFromMessage, $this->globalDate);
                            }
                            
                            // Tạo bet riêng cho số này
                            $betType = $lastBettingType;
                            
                            // Nếu là bao_lo_dynamic, cần map theo độ dài số
                            if ($betType === 'bao_lo_dynamic') {
                                $betType = $this->mapLoTypeByLen($tk);
                            }
                            
                            $bet = [
                                'station' => $currentStationForDirective,
                                'type' => $betType,
                                'numbers' => [$tk],
                                'amount' => $amount,
                                'meta' => []
                            ];
                            $multipleBets[] = $bet;
                        }
                        
                        $i += 1; // Skip amount token
                        continue;
                    }
                }

                // thu dãy liên tiếp (logic cũ)
                $currentNumbers = [];
                for ($j=$i; $j<count($tokens); $j++) {
                    $w = $tokens[$j];
                    if ($this->isNumberToken($w) || $this->isNumberKeyword($w)) {
                        $currentNumbers[] = $w;
                    } else {
                        break;
                    }
                }
                $i = $j-1;
                continue;
            }

            // 5) token rác -> bỏ qua
        }

        // Hết dòng
        $flush();

        // Merge implicit bets vào results
        $results = array_merge($results, $multipleBets);

        return $results;
    }

    /**
     * Parse special betting types (bảy lô, tám lô, xiên, dàn, giáp, etc.)
     */
    private function parseSpecialBetting(string $betType, array $numbers, float $amount, string $station): array
    {
        $bets = [];
        
        switch ($betType) {
            case 'bay_lo':
            case 'bay_lo_dao':
            case 'tam_lo':
            case 'tam_lo_dao':
                // Bảy lô, tám lô: mỗi số một cược
                foreach ($numbers as $number) {
                    $bets[] = [
                        'type' => $betType,
                        'number' => $number,
                        'amount' => $amount,
                        'station' => $station
                    ];
                }
                break;
                
            case 'xien_2':
            case 'xien_3':
            case 'xien_4':
                // Xiên: tạo tất cả tổ hợp
                $count = (int)str_replace('xien_', '', $betType);
                if (count($numbers) >= $count) {
                    $combinations = $this->generateCombinations($numbers, $count);
                    foreach ($combinations as $combo) {
                        $bets[] = [
                            'type' => $betType,
                            'numbers' => $combo,
                            'amount' => $amount,
                            'station' => $station
                        ];
                    }
                }
                break;
                
            case 'de_dau_dac_biet':
            case 'de_dau_giai_1':
            case 'de_giai_1':
                // Đề đặc biệt: mỗi số một cược
                foreach ($numbers as $number) {
                    $bets[] = [
                        'type' => $betType,
                        'number' => $number,
                        'amount' => $amount,
                        'station' => $station
                    ];
                }
                break;
                
            case 'xiu_chu_dao_dau':
            case 'xiu_chu_dao_duoi':
                // Xỉu chủ đảo: mỗi số một cược
                foreach ($numbers as $number) {
                    $bets[] = [
                        'type' => $betType,
                        'number' => $number,
                        'amount' => $amount,
                        'station' => $station
                    ];
                }
                break;
                
            default:
                // Các loại cược khác (dàn, giáp, tổng, kép)
                $bets[] = [
                    'type' => $betType,
                    'numbers' => $numbers,
                    'amount' => $amount,
                    'station' => $station
                ];
                break;
        }
        
        return $bets;
    }
    
    /**
     * Generate combinations of numbers
     */
    private function generateCombinations(array $numbers, int $count): array
    {
        if ($count == 1) {
            return array_map(fn($n) => [$n], $numbers);
        }
        
        $combinations = [];
        $n = count($numbers);
        
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($count == 2) {
                    $combinations[] = [$numbers[$i], $numbers[$j]];
                } elseif ($count == 3) {
                    for ($k = $j + 1; $k < $n; $k++) {
                        $combinations[] = [$numbers[$i], $numbers[$j], $numbers[$k]];
                    }
                } elseif ($count == 4) {
                    for ($k = $j + 1; $k < $n; $k++) {
                        for ($l = $k + 1; $l < $n; $l++) {
                            $combinations[] = [$numbers[$i], $numbers[$j], $numbers[$k], $numbers[$l]];
                        }
                    }
                }
            }
        }
        
        return $combinations;
    }

    /**
     * Phân tích directive tại vị trí $i
     *
     * Lưu ý quan trọng:
     * - d35n / d40n: dùng toggle $dToggle để lần 1 = 'dau', lần 2 = 'duoi'
     * - dd <amount>: sinh HAI directive thật ('dau' và 'duoi')
     * - Trả về mảng có các key: directive, also (optional), advance, dToggle, attach_number (optional)
     */
    private function parseDirectiveAt(array $tokens, int $i, int $dToggle): ?array
    {
        $tk   = $tokens[$i];
        $next = $tokens[$i+1] ?? null;

        // ===== 1) ĐẦU / ĐUÔI / ĐẦU-ĐUÔI =====
    
        // 1.a) Dạng dính: d35n / d40n / d130k / d0k  => LUÂN PHIÊN theo toggle: lần 1 = 'dau', lần 2 = 'duoi'
        if (preg_match('/^d(\d+(?:\.\d+)?)(n|k)$/u', $tk, $m)) {
            $amount = $this->toVnd($m[1]); // "35" => 35k
            $kind   = ($dToggle % 2 === 0) ? 'dau' : 'duoi';
            return [
                'directive' => ['type'=>$kind, 'amount'=>$amount],
                'advance'   => 0,
                'dToggle'   => $dToggle + 1,
            ];
        }
    
        // 1.b) dd <amount> => sinh 2 directive thật: đầu & đuôi
        // Xử lý format "dd140n" - dd với amount dính TRƯỚC
        if (preg_match('/^dd(\d+(?:\.\d+)?)(n|k|tr|trieu|triệu|nghin|nghìn|ngan|ngàn)?$/', $tk, $m)) {
            $amount = $this->toVnd($m[1]);
            return [
                'directive' => ['type'=>'dau',  'amount'=>$amount],
                'also'      => ['type'=>'duoi', 'amount'=>$amount],
                'advance'   => 0,
                'dToggle'   => $dToggle,
            ];
        }
        
        if ($this->isTypeToken($tk, ['dd','dauduoi'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) >= 0 && $this->parseAmountOnly($next) !== false) {
                return [
                    'directive' => ['type'=>'dau',  'amount'=>$v],
                    'also'      => ['type'=>'duoi', 'amount'=>$v],
                    'advance'   => 1,
                    'dToggle'   => $dToggle,
                ];
            }
            
            return null;
        }
    
        // 1.c) d / dau / duoi + <amount>  (riêng 'd' thì cũng dùng toggle)
        if ($this->isTypeToken($tk, ['d','dau','duoi','dui'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) !== false) {
                $kind = $this->isTypeToken($tk, ['dau']) ? 'dau'
                      : ($this->isTypeToken($tk, ['duoi','dui']) ? 'duoi'
                      : (($dToggle % 2 === 0) ? 'dau' : 'duoi'));
                $newToggle = ($this->norm($tk) === 'd') ? $dToggle + 1 : $dToggle;
                return [
                    'directive' => ['type'=>$kind, 'amount'=>$v],
                    'advance'   => 1,
                    'dToggle'   => $newToggle,
                ];
            }
            // pattern: <directive> <number> <amount>
            $n1 = $tokens[$i+1] ?? null;
            $n2 = $tokens[$i+2] ?? null;
            if ($n1 && $this->isNumberToken($n1) && $n2 && ($v = $this->parseAmountOnly($n2)) !== false) {
                $kind = $this->isTypeToken($tk, ['dau']) ? 'dau'
                      : ($this->isTypeToken($tk, ['duoi','dui']) ? 'duoi'
                      : (($dToggle % 2 === 0) ? 'dau' : 'duoi'));
                $newToggle = ($this->norm($tk) === 'd') ? $dToggle + 1 : $dToggle;
                return [
                    'directive'     => ['type'=>$kind, 'amount'=>$v],
                    'advance'       => 2,
                    'dToggle'       => $newToggle,
                    'attach_number' => $n1,
                ];
            }
            return null;
        }
    
        // ===== 2) LÔ / BAO LÔ =====
        {
            // ƯU TIÊN bắt token dính (lo5n, lo2.5tr, ...)
            $attached = $this->parseAmountAttached($tk);

            // Vào nhánh nếu: (a) là alias thuần lo/bao... hoặc (b) là token dính amount
            // NHƯNG KHÔNG phải xc (xỉu chủ) hoặc d (đầu/đuôi)
            if (($attached && $attached['base'] !== 'xc' && $attached['base'] !== 'd') || $this->isTypeToken($tk, ['lo','bao_lo','bao3_lo','bao4_lo','bd','baodao','bdao','bld','bldao','daolo'])) {
                $amount = 0; $adv = 0; $attachNum = null; $typeToken = $tk;

                if ($attached) {
                    // lo5n -> amount lấy từ $attached, type lấy theo base
                    $amount    = $attached['amount'];
                    $typeToken = $attached['base']; // ví dụ 'lo'
                } else {
                    // KIỂM TRA lo 68 5n TRƯỚC (directive + number + amount)
                    $n1 = $tokens[$i+1] ?? null; $n2 = $tokens[$i+2] ?? null;
                    if ($n1 && $this->isNumberToken($n1) && $n2 && ($v = $this->parseAmountOnly($n2)) !== false) {
                        $amount    = $v; $adv = 2; $attachNum = $n1;
                    } elseif ($next && ($v = $this->parseAmountOnly($next)) !== false) {
                        // lo 5n
                        $amount = $v; $adv = 1;
                    }
                }

                if ($amount >= 0) {
                    $type = $this->aliasToType($typeToken); // 'lo' -> 'bao_lo_dynamic'
                    if (in_array($type, ['bao_lo','bao3_lo','bao4_lo','bao_lo_dao'], true)) {
                        return ['directive'=>['type'=>$type, 'amount'=>$amount], 'advance'=>$adv, 'dToggle'=>$dToggle, 'attach_number'=>$attachNum];
                    }
                    return ['directive'=>['type'=>'bao_lo_dynamic', 'amount'=>$amount], 'advance'=>$adv, 'dToggle'=>$dToggle, 'attach_number'=>$attachNum];
                }
                return null;
            }
        }
    
        // ===== 3) XỈU CHỦ + biến thể =====
        // Xử lý xc60n (xỉu chủ dính tiền)
        if (preg_match('/^xc(\d+(?:\.\d+)?)n$/u', $tk, $m)) {
            $amount = $this->toVnd($m[1]);
            return [
                'directive' => ['type'=>'xiu_chu', 'amount'=>$amount],
                'advance'   => 0,
                'dToggle'   => $dToggle,
            ];
        }
        
        if ($this->isTypeToken($tk, ['xc','x','xiu','xcdau','xdau','xcduoi','xduoi','xcdui','xdui','dxc','xcd','xd','daoxc','xcdao'])) {
            // ƯU TIÊN: <xc-variant> <SỐ-3-KÝ-TỰ> <TIỀN>
            // Ví dụ: "xc 515 20n" hoặc "xc 035 10.5"
            $n1 = $tokens[$i+1] ?? null;
            $n2 = $tokens[$i+2] ?? null;

            // Chỉ chấp nhận xỉu chủ với đúng 3 chữ số (kể cả có 0 đầu)
            $isThreeDigit = is_string($n1) && preg_match('/^\d{3}$/', $n1);

            // Xử lý nhiều số xỉu chủ: xc 271 272 274 30n
            if ($isThreeDigit && $n2 && preg_match('/^\d{3}$/', $n2)) {
                // Tìm amount ở cuối (token cuối cùng có dạng số + n/k/tr)
                $amountToken = null;
                $amountIndex = -1;
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (preg_match('/^\d+(?:\.\d+)?(n|k|tr|trieu|triệu|nghin|nghìn|ngan|ngàn)$/', $tokens[$j])) {
                        $amountToken = $tokens[$j];
                        $amountIndex = $j;
                        break;
                    }
                }
                
                if ($amountToken && ($v = $this->parseAmountOnly($amountToken)) > 0) {
                    // Thu thập tất cả số 3 chữ số
                    $numbers = [];
                    for ($j = $i + 1; $j < $amountIndex; $j++) {
                        if (preg_match('/^\d{3}$/', $tokens[$j])) {
                            $numbers[] = $tokens[$j];
                        }
                    }
                    
                    if (!empty($numbers)) {
                        $type = match (true) {
                            $this->isTypeToken($tk, ['xcdau','xdau'])                    => 'xiu_chu_dau',
                            $this->isTypeToken($tk, ['xcduoi','xduoi','xcdui','xdui'])  => 'xiu_chu_duoi',
                            $this->isTypeToken($tk, ['dxc','xcd','xd','daoxc','xcdao']) => 'xiu_chu_dao',
                            default                                                      => 'xiu_chu',
                        };
                        return [
                            'directive'     => ['type'=>$type, 'amount'=>$v, 'numbers'=>$numbers],
                            'advance'       => $amountIndex - $i,  // đã ăn tất cả token từ số đầu đến amount
                            'dToggle'       => $dToggle,
                        ];
                    }
                }
            }

            if ($isThreeDigit && $n2 && ($v = $this->parseAmountOnly($n2)) > 0) {
                // Xác định biến thể xỉu chủ
                $type = match (true) {
                    $this->isTypeToken($tk, ['xcdau','xdau'])                    => 'xiu_chu_dau',
                    $this->isTypeToken($tk, ['xcduoi','xduoi','xcdui','xdui'])  => 'xiu_chu_duoi',
                    $this->isTypeToken($tk, ['dxc','xcd','xd','daoxc','xcdao']) => 'xiu_chu_dao',
                    default                                                      => 'xiu_chu',
                };
                return [
                    'directive'     => ['type'=>$type, 'amount'=>$v],
                    'advance'       => 2,               // đã ăn 2 token: số + tiền
                    'dToggle'       => $dToggle,
                    'attach_number' => $n1,             // GẮN SỐ 3 KÝ TỰ CHO XỈU CHỦ
                ];
            }
            
            // Xử lý format "xc 938 15n" - xc với số 3 chữ số và amount
            if ($isThreeDigit && $n2 && preg_match('/^(\d+(?:\.\d+)?)n$/', $n2, $m)) {
                $amount = $this->toVnd($m[1]);
                $type = match (true) {
                    $this->isTypeToken($tk, ['xcdau','xdau'])                    => 'xiu_chu_dau',
                    $this->isTypeToken($tk, ['xcduoi','xduoi','xcdui','xdui'])  => 'xiu_chu_duoi',
                    $this->isTypeToken($tk, ['dxc','xcd','xd','daoxc','xcdao']) => 'xiu_chu_dao',
                    default                                                      => 'xiu_chu',
                };
                return [
                    'directive'     => ['type'=>$type, 'amount'=>$amount],
                    'advance'       => 2,
                    'dToggle'       => $dToggle,
                    'attach_number' => $n1,
                ];
            }

            // THỨ HAI: <xc-variant> <TIỀN> (áp cho các số đã gom trước đó)
            // Ví dụ: "… 035 055 075 xc 10.5"
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                $type = match (true) {
                    $this->isTypeToken($tk, ['xcdau','xdau'])                    => 'xiu_chu_dau',
                    $this->isTypeToken($tk, ['xcduoi','xduoi','xcdui','xdui'])  => 'xiu_chu_duoi',
                    $this->isTypeToken($tk, ['dxc','xcd','xd','daoxc','xcdao']) => 'xiu_chu_dao',
                    default                                                      => 'xiu_chu',
                };
                return [
                    'directive' => ['type'=>$type, 'amount'=>$v],
                    'advance'   => 1,
                    'dToggle'   => $dToggle,
                ];
            }

            // Không khớp pattern nào
            return null;
        }
    
        // ===== 4) ĐÁ THẲNG =====
        if ($this->isTypeToken($tk, ['da','dth','dathang','dat'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'da_thang', 'amount'=>$v], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            $n1 = $tokens[$i+1] ?? null; $n2 = $tokens[$i+2] ?? null;
            if ($n1 && $this->isNumberToken($n1) && $n2 && ($v = $this->parseAmountOnly($n2)) > 0) {
                return ['directive'=>['type'=>'da_thang', 'amount'=>$v], 'advance'=>2, 'dToggle'=>$dToggle, 'attach_number'=>$n1];
            }
            return null;
        }
    
        // ===== 5) XIÊN 2/3/4 =====
        if ($this->isTypeToken($tk, ['xien','dx','xien2','xien3','xien4','xhai','xba','xbon','dax','đax'])) {
            $size = 2;
            if ($this->isTypeToken($tk, ['xien3','xba'])) $size = 3;
            if ($this->isTypeToken($tk, ['xien4','xbon'])) $size = 4;
    
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'da_xien', 'amount'=>$v, 'xien_size'=>$size], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            
            // Xử lý format "đax 0.8n" với số thập phân
            if ($next && preg_match('/^(\d+(?:\.\d+)?)n$/', $next, $m)) {
                $amount = $this->toVnd($m[1]);
                return ['directive'=>['type'=>'da_xien', 'amount'=>$amount, 'xien_size'=>$size], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            
            return null; // xien <numbers> <amount> -> sẽ gom ở phase thu số
        }
    
        // ===== 6) Bảy lô, tám lô =====
        if ($this->isTypeToken($tk, ['baylo','baylodao','daobaylo','tamlo','tamlodao','daotamlo'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'bay_lo', 'amount'=>$v], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 7) Xỉu chủ đảo =====
        if ($this->isTypeToken($tk, ['xcddau','xcdaud','xcdduoi','xcduoid'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'xiu_chu_dao', 'amount'=>$v], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 8) Đề đặc biệt =====
        if ($this->isTypeToken($tk, ['dedaudacbiet','dedaugiai1','degi1'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'de_dac_biet', 'amount'=>$v], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 10) Dàn số =====
        if ($this->isTypeToken($tk, ['dan05cokep','dan05bokep','dan06cokep','dan06bokep','dan07cokep','dan07bokep','dan08cokep','dan08bokep','dan15cokep','dan15bokep','dan16cokep','dan16bokep','dan17cokep','dan17bokep','dan18cokep','dan18bokep','dan19cokep','dan19bokep','dan26cokep','dan26bokep','dan27cokep','dan27bokep','dan28cokep','dan28bokep','dan29cokep','dan29bokep','dan38cokep','dan38bokep','dan39cokep','dan39bokep','dan49cokep','dan49bokep','dan00cach3','dan00cach4','dan01cach3','dan02cach3'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'dan_so', 'amount'=>$v, 'meta'=>['dan_type'=>$tk]], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 11) Giáp =====
        if ($this->isTypeToken($tk, ['giapty','giapsuu','giapdan','giapmao','giapthin','giapti','giapngo','giapmui','giapthan','giapga','giaptuat','giaphoi'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'giap', 'amount'=>$v, 'meta'=>['giap_type'=>$tk]], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 12) Tổng =====
        if ($this->isTypeToken($tk, ['tongto','tongbe','tongchan','tongle','tong0','tong1','tong2','tong3','tong4','tong5','tong6','tong7','tong8','tong9','tongtren10','tongduoi10','tongchia3du0','tongchia3du1','tongchia3du2'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'tong', 'amount'=>$v, 'meta'=>['tong_type'=>$tk]], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 13) Kép =====
        if ($this->isTypeToken($tk, ['kepbang','keplech','satkepbang','satkeplech','sathaikep'])) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'kep', 'amount'=>$v, 'meta'=>['kep_type'=>$tk]], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            return null;
        }
        
        // ===== 14) Keyword dàn/tổng/kép/giáp =====
        if ($this->isNumberKeyword($tk)) {
            if ($next && ($v = $this->parseAmountOnly($next)) > 0) {
                return ['directive'=>['type'=>'bao_lo_dynamic', 'amount'=>$v, 'meta'=>['keyword'=>$tk]], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            $n1 = $tokens[$i+1] ?? null; $n2 = $tokens[$i+2] ?? null;
            if ($n1 && $this->isNumberToken($n1) && $n2 && ($v = $this->parseAmountOnly($n2)) > 0) {
                return ['directive'=>['type'=>'bao_lo_dynamic', 'amount'=>$v, 'meta'=>['keyword'=>$tk]], 'advance'=>2, 'dToggle'=>$dToggle, 'attach_number'=>$n1];
            }
            return null;
        }
    
        // ===== 7) 2d/3d/4d (+ miền) -> để meta cho layer sau =====
        if (preg_match('/^([234])d(ai)?(m?(bac|trung|nam))?$/u', $tk, $m)) {
            $n      = (int)$m[1];
            $region = $m[4] ?? null;
            
            // Kiểm tra token tiếp theo có phải là region không
            $next = $tokens[$i+1] ?? null;
            if (!$region && $next && in_array(strtolower($next), ['bac', 'trung', 'nam'])) {
                $region = strtolower($next);
                return ['directive'=>['type'=>'__MULTI_STATIONS__','amount'=>0,'meta'=>['multi_stations'=>$n,'region'=>$region]], 'advance'=>1, 'dToggle'=>$dToggle];
            }
            
            return ['directive'=>['type'=>'__MULTI_STATIONS__','amount'=>0,'meta'=>['multi_stations'=>$n,'region'=>$region]], 'advance'=>0, 'dToggle'=>$dToggle];
        }
        
        // ===== 8) Xử lý format "2dai" với số và amount =====
        if (preg_match('/^(\d+)dai$/u', $tk, $m)) {
            $n = (int)$m[1];
            return ['directive'=>['type'=>'__MULTI_STATIONS__','amount'=>0,'meta'=>['multi_stations'=>$n,'region'=>null]], 'advance'=>0, 'dToggle'=>$dToggle];
        }

        return null;
    }


    private function expandNumbers(array $numbers, array $pendingDirectives): array
    {
        $out = [];
        foreach ($numbers as $n) {
            if ($this->isNumberKeyword($n)) $out = array_merge($out, $this->expandKeyword($n));
            else $out[] = $n;
        }
        foreach ($pendingDirectives as $dir) {
            if (!empty($dir['meta']['keyword'])) $out = array_merge($out, $this->expandKeyword($dir['meta']['keyword']));
        }
        // unique giữ thứ tự
        $seen = []; $uniq=[];
        foreach ($out as $s) { if (!isset($seen[$s])) { $seen[$s]=1; $uniq[]=$s; } }
        return $uniq;
    }

    private function isNumberKeyword(string $tk): bool
    {
        $tk = $this->norm($tk);
        if ($tk==='giap'||$tk==='giapall') return true;
        if (in_array($tk,['chan','le','chanchan','lele','chanle','lechan'],true)) return true;
        if (preg_match('/^tong\d{1,2}$/',$tk)) return true;
        if (in_array($tk,['kepbang','keplech'],true)) return true;
        if (preg_match('/^dan\d{2}$/',$tk)) return true;
        return false;
    }

    private function expandKeyword(string $kw): array
    {
        $kw = $this->norm($kw);

        if ($kw==='giap'||$kw==='giapall') {
            $arr=[];
            for($i=0;$i<=9;$i++){ $j=($i+1)%10; $arr[]="{$i}{$j}"; }
            for($i=0;$i<=9;$i++){ $j=($i+9)%10; $arr[]="{$i}{$j}"; }
            return array_values(array_unique($arr));
        }
        if ($kw==='chan'||$kw==='chanchan') return $this->allPairs(fn($a,$b)=>($a%2==0&&$b%2==0));
        if ($kw==='le'||$kw==='lele')     return $this->allPairs(fn($a,$b)=>($a%2==1&&$b%2==1));
        if ($kw==='chanle')               return $this->allPairs(fn($a,$b)=>($a%2==0&&$b%2==1));
        if ($kw==='lechan')               return $this->allPairs(fn($a,$b)=>($a%2==1&&$b%2==0));

        if (preg_match('/^tong(\d{1,2})$/',$kw,$m)) {
            $t=(int)$m[1]; $res=[];
            for($a=0;$a<=9;$a++) for($b=0;$b<=9;$b++) if ($a+$b===$t) $res[]=$a.$b;
            return $res;
        }
        if ($kw==='kepbang') { $res=[]; for($a=0;$a<=9;$a++) $res[]=$a.$a; return $res; }
        if ($kw==='keplech') {
            $res=[]; for($a=0;$a<=9;$a++) for($b=0;$b<=9;$b++) if (abs($a-$b)===1) $res[]=$a.$b; return $res;
        }
        if (preg_match('/^dan(\d{2})$/',$kw,$m)) {
            $digits=str_split($m[1]); $res=[];
            for($a=0;$a<=9;$a++) for($b=0;$b<=9;$b++) {
                if (in_array((string)$a,$digits,true) || in_array((string)$b,$digits,true)) $res[]=$a.$b;
            }
            return array_values(array_unique($res));
        }
        return [];
    }

    private function allPairs(callable $pred): array
    {
        $res=[]; for($a=0;$a<=9;$a++) for($b=0;$b<=9;$b++) if ($pred($a,$b)) $res[]=$a.$b; return $res;
    }


    private function normalize(string $s): string
    {
        $s = Str::of($s)->lower()->toString();
        $s = $this->stripVN($s);

        // 1) Chỉ chuyển dấu phẩy -> chấm khi là số thập phân: 2,5n -> 2.5n
        $s = preg_replace('/(?<=\d),(?=\d)/', '.', $s);

        // 2) Tách dấu chấm làm delimiter NHƯNG giữ lại dấu chấm trong số thập phân (lo2.5k, 515.20n)
        //    "15.55.95.d30n lo5n.xc 515.20n" -> "15 55 95 d30n lo5n xc 515.20n"
        //    "AG2298.0898.0998.1598lo2,5n" -> "AG 2298 0898 0998 1598lo2.5n"
        // Tách dấu chấm nếu:
        // - Sau dấu chấm không phải là chữ số (\.(?!\d))
        // - Hoặc trước dấu chấm không phải là chữ số ((?<!\d)\.)
        // - Hoặc sau dấu chấm là 3-4 chữ số (không phải số thập phân hợp lệ)
        $s = preg_replace('/\.(?!\d)|(?<!\d)\.|\.(?=\d{3,4}(?!\d))/', ' ', $s);

        // 2.5) Tách xc ra khỏi số: xc 515.20n -> xc 515 20n (TRƯỚC khi gom lại)
        $s = preg_replace('/(xc)\s+(\d{3})\.(\d+n)/u', '$1 $2 $3', $s);
        
        // 2.6) Xử lý xc với số 3 chữ số và amount: xc 938 15n -> xc 938 15n
        $s = preg_replace('/(xc)\s+(\d{3})\s+(\d+(?:\.\d+)?n)/u', '$1 $2 $3', $s);

        // 3) Tách chữ-số: tn03 -> tn 03 (NHƯNG giữ "d35n", "lo5n", "xc515", "lo2.5k" liền mạch)
        // Tránh tách xc938 thành xc 938
        // KHÔNG tách khi sau số có dấu chấm + số + đơn vị (lo2.5k)
        $s = preg_replace('/\b(?!xc)([a-z]+)\s*(\d)(?!\d*\.\d+[kn])/u', '$1 $2', $s);

        // 4) Tách số-chữ: 03lo -> 03 lo (NHƯNG giữ "35n", "5n", "515n")
        // Tách số ra khỏi lo: 1598lo2.5n -> 1598 lo2.5n
        $s = preg_replace('/(\d+)(lo)(\d+(?:\.\d+)?[kn]?)/u', '$1 $2$3', $s);
        // Tách xc ra khỏi số: 319xc60n -> 319 xc 60n
        $s = preg_replace('/(\d+)(xc)(\d+(?:\.\d+)?n?)/u', '$1 $2 $3', $s);
        // Tách xc ra khỏi số: xc515 -> xc 515
        $s = preg_replace('/(xc)(\d{3})/u', '$1 $2', $s);
        // Tách d ra khỏi số: 51d0k -> 51 d 0 k
        $s = preg_replace('/(\d+)(d)(\d+(?:\.\d+)?[kn]?)/u', '$1 $2 $3', $s);
        // Tách dd ra khỏi số: 98dd140n -> 98 dd140n
        $s = preg_replace('/(\d+)(dd)(\d+(?:\.\d+)?[kn]?)/u', '$1 $2$3', $s);
        // Không tách số thập phân: 515.20n -> 515.20n
        // KHÔNG tách 15n, 20n, etc. - bỏ qua bước này vì gây ra vấn đề
        // $s = preg_replace('/(\d)\s*([a-z])(?!\d)/u', '$1 $2', $s);

        // 5) Gom lại các pattern đặc biệt
        $s = preg_replace('/\bd\s+(\d+(?:\.\d+)?)\s*n\b/u', 'd$1n', $s); // d <số> n -> d<số>n
        $s = preg_replace('/\bd\s+(\d+(?:\.\d+)?)\s*k\b/u', 'd$1k', $s); // d <số> k -> d<số>k
        $s = preg_replace('/\blo\s+(\d+(?:\.\d+)?)\s*n\b/u', 'lo$1n', $s); // lo <số> n -> lo<số>n
        $s = preg_replace('/\blo\s+(\d+(?:\.\d+)?)\s*k\b/u', 'lo$1k', $s); // lo <số> k -> lo<số>k
        // GOM xc <3số> <số> n -> xc 938 15n (GIẢI NGỘ NGUYÊN)
        // KHÔNG gom xc <số> n -> xc<số>n vì sẽ gây ra vấn đề với xc 938 15n
        // $s = preg_replace('/\bxc\s+(\d+(?:\.\d+)?)\s*n\b/u', 'xc$1n', $s); // xc <số> n -> xc<số>n
        $s = preg_replace('/\bxc\s+(\d+(?:\.\d+)?)\s*k\b/u', 'xc$1k', $s); // xc <số> k -> xc<số>k
        // $s = preg_replace('/\bxc\s+(\d{3})\s+(\d+(?:\.\d+)?)\s*n\b/u', 'xc$1 $2n', $s); // xc <3số> <số> n -> xc<3số> <số>n
        
        // 5.1) Xử lý format "2dai 89.98dd140n lo10n"
        $s = preg_replace('/\b(\d+)dai\s+([\d.]+)\s+dd(\d+(?:\.\d+)?)n\s+lo(\d+(?:\.\d+)?)n\b/u', '$1dai $2 dd$3n lo$4n', $s);

        // 6) Dấu chấm giữa số-thực thụ (2298.0898) mới tách: "2298 0898"
        // NHƯNG KHÔNG tách khi sau dấu chấm có số + đơn vị (2.5k, 2.5n, 2.5tr)
        $s = preg_replace('/(?<=\d)\.(?=\d+(?![kn])\b)/', ' ', $s);

        // 7) Chuẩn hoá space
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return $s;
    }

    private function tokenize(string $norm): array
    {
        return preg_split('/\s+/u', $norm) ?: [];
    }

    private function stripVN(string $s): string
    {
        $acc = 'àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ';
        $rep = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';
        return strtr($s, array_combine(preg_split('//u',$acc,-1,PREG_SPLIT_NO_EMPTY), preg_split('//u',$rep,-1,PREG_SPLIT_NO_EMPTY)));
    }

    private function isNumberToken(string $tk): bool
    {
        return (bool)preg_match('/^\d{2,4}$/', $tk);
    }

    private function mapLoTypeByLen(string $num): string
    {
        return match (strlen($num)) { 2=>'bao_lo', 3=>'bao3_lo', 4=>'bao4_lo', default=>'bao_lo' };
    }

    /**
     * Lấy danh sách đài theo miền
     */
    private function getStationsByRegion(string $region): array
    {
        $stations = [
            'bac' => ['hà nội', 'hải phòng', 'quảng ninh', 'bắc ninh', 'hải dương', 'hưng yên', 'thái bình', 'hà nam', 'nam định', 'ninh bình', 'thanh hóa', 'nghệ an', 'hà tĩnh', 'quảng bình', 'quảng trị', 'thừa thiên huế'],
            'trung' => ['đà nẵng', 'quảng nam', 'quảng ngãi', 'bình định', 'phú yên', 'khánh hòa', 'ninh thuận', 'bình thuận'],
            'nam' => ['tp.hcm', 'bình dương', 'đồng nai', 'tây ninh', 'bình phước', 'long an', 'tiền giang', 'bến tre', 'trà vinh', 'vĩnh long', 'đồng tháp', 'an giang', 'kiên giang', 'cà mau', 'bạc liêu', 'sóc trăng', 'hậu giang', 'cần thơ']
        ];
        
        return $stations[$region] ?? $stations['nam'];
    }

    /**
     * Chọn n đài theo lịch xổ số, bắt buộc có 1 đài chính và đài phụ theo thứ
     */
    private function selectRandomStations(array $availableStations, int $n, string $region, ?string $globalDate = null): array
    {
        // Lấy đài chính theo ngày
        $mainStation = $this->getDefaultStationByDay($region, $globalDate);
        
        // Bắt buộc có đài chính
        $selectedStations = [$mainStation];
        
        // Lấy đài phụ theo lịch xổ số (không phải ngẫu nhiên)
        $subStations = $this->getSubStationsByDay($region, $globalDate);
        
        // Lấy n-1 đài phụ từ danh sách đài phụ theo lịch
        if (count($subStations) >= $n - 1) {
            $selectedSubStations = array_slice($subStations, 0, $n - 1);
            $selectedStations = array_merge($selectedStations, $selectedSubStations);
        } else {
            // Nếu không đủ đài phụ theo lịch, lấy tất cả đài phụ
            $selectedStations = array_merge($selectedStations, $subStations);
        }
        
        return array_unique($selectedStations);
    }

    /**
     * Lấy đài phụ theo lịch xổ số cho ngày cụ thể
     */
    private function getSubStationsByDay(string $region, ?string $globalDate = null): array
    {
        // Xác định thứ trong tuần
        $dayOfWeek = $this->getDayOfWeek($globalDate);
        
        // Lịch đài phụ theo thứ (từ DOC_FUNC.md)
        $subStationsSchedule = [
            'monday' => [
                'nam' => ['đồng tháp', 'cà mau'],
                'trung' => ['thừa thiên huế'],
                'bac' => []
            ],
            'tuesday' => [
                'nam' => ['bến tre', 'bạc liêu'],
                'trung' => ['đắk lắk'],
                'bac' => []
            ],
            'wednesday' => [
                'nam' => ['cần thơ', 'sóc trăng'],
                'trung' => ['đà nẵng'],
                'bac' => []
            ],
            'thursday' => [
                'nam' => ['an giang', 'bình thuận'],
                'trung' => ['bình định', 'quảng trị'],
                'bac' => []
            ],
            'friday' => [
                'nam' => ['vĩnh long', 'kiên giang'],
                'trung' => ['nghệ an', 'hà tĩnh'],
                'bac' => []
            ],
            'saturday' => [
                'nam' => ['long an', 'trà vinh'],
                'trung' => ['quảng ngãi', 'phú yên'],
                'bac' => []
            ],
            'sunday' => [
                'nam' => ['tiền giang', 'bình dương'],
                'trung' => ['thừa thiên huế', 'khánh hòa'],
                'bac' => []
            ]
        ];
        
        return $subStationsSchedule[$dayOfWeek][$region] ?? [];
    }

    /**
     * Lấy thứ trong tuần từ globalDate hoặc ngày hiện tại
     */
    private function getDayOfWeek(?string $globalDate = null): string
    {
        if ($globalDate) {
            $date = \DateTime::createFromFormat('Y-m-d', $globalDate);
        } else {
            $date = new \DateTime();
        }
        
        $dayNames = [
            1 => 'monday',
            2 => 'tuesday', 
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday'
        ];
        
        return $dayNames[$date->format('N')];
    }

    private function parseAmountOnly(string $tk)
    {
        // Hỗ trợ cả dấu phẩy và dấu chấm: 2,5k hoặc 2.5k
        if (preg_match('/^(\d+(?:[.,]\d+)?)(k|n|tr|trieu|triệu|nghin|nghìn|ngan|ngàn)?$/u', $tk, $m)) {
            // Thay dấu phẩy bằng dấu chấm để parse đúng
            $num = (float)str_replace(',', '.', $m[1]);
            $unit = isset($m[2]) ? $this->norm($m[2]) : 'n';
            $mul = $this->amountUnits[$unit] ?? 1000;
            return (int)round($num * $mul);
        }
        return false;
    }

    private function parseAmountAttached(string $tk): ?array
    {
        // base gồm: lo | d | bao | bld? ... (giữ regex cũ nhưng lấy thêm $m[1] làm base)
        // Hỗ trợ cả dấu phẩy và dấu chấm: lo2,5k hoặc lo2.5k
        if (preg_match('/^(xc|lo|d|bao|b[ld]?(?:ao)?|blo|baol)(\d+(?:[.,]\d+)?)(k|n|tr|trieu|triệu|nghin|nghìn|ngan|ngàn)?$/u', $tk, $m)) {
            // Thay dấu phẩy bằng dấu chấm để parse đúng
            $num  = (float)str_replace(',', '.', $m[2]);
            $unit = isset($m[3]) ? $this->norm($m[3]) : 'n';
            $mul  = $this->amountUnits[$unit] ?? 1000;
            return [
                'amount' => (int) round($num * $mul),
                'base'   => $this->norm($m[1]), // <— thêm base để map type
            ];
        }
        return null;
    }

    private function combinations(array $arr, int $k): array
    {
        $arr = array_values(array_unique($arr));
        $n = count($arr);
        if ($k < 2 || $k > $n) return [];
        $res = [];
        $this->combRec($arr,$k,0,[],$res);
        return $res;
    }

    private function combRec(array $arr, int $k, int $start, array $path, array &$res)
    {
        if (count($path)===$k) { $res[]=$path; return; }
        for ($i=$start; $i<count($arr); $i++) {
            $path[]=$arr[$i];
            $this->combRec($arr,$k,$i+1,$path,$res);
            array_pop($path);
        }
    }

    private function isTypeToken(string $tk, array $aliasesOrCodes): bool
    {
        $type = $this->aliasToType($tk);
        if (!$type) return false;
        foreach ($aliasesOrCodes as $x) {
            if ($x === $tk) return true;
            if ($x === $type) return true;
            if (($this->aliasToType($x)??null) === $type) return true;
        }
        return in_array($type, $aliasesOrCodes, true);
    }

    private function aliasToType(string $tk): ?string
    {
        $key = $this->norm($tk);
        return $this->bettingShortcuts[$key] ?? null;
    }

    private function norm(string $s): string
    {
        $s = strtolower($s);
        $s = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
        return preg_replace('/\s+/','', $s);
    }

    private function isStationToken(string $tk): bool
    {
        // Kiểm tra token gốc
        if (isset($this->stationAbbreviations[$this->norm($tk)])) {
            return true;
        }
        
        // Kiểm tra token với dấu chấm được thay thế bằng khoảng trắng (L.an -> L an)
        $tkWithSpace = str_replace('.', ' ', $tk);
        if (isset($this->stationAbbreviations[$this->norm($tkWithSpace)])) {
            return true;
        }
        
        return false;
    }

    /**
     * Kiểm tra xem các token liên tiếp có thể tạo thành station abbreviation không
     */
    private function isStationTokens(array $tokens, int $startIndex, int $maxLength = 3): ?string
    {
        for ($len = 1; $len <= $maxLength && $startIndex + $len <= count($tokens); $len++) {
            $combined = implode(' ', array_slice($tokens, $startIndex, $len));
            $normalized = $this->norm($combined);
            
            if (isset($this->stationAbbreviations[$normalized])) {
                return $this->stationAbbreviations[$normalized];
            }
        }
        
        return null;
    }

    /**
     * Lấy độ dài của station token từ vị trí startIndex
     */
    private function getStationTokenLength(array $tokens, int $startIndex, int $maxLength = 3): int
    {
        for ($len = 1; $len <= $maxLength && $startIndex + $len <= count($tokens); $len++) {
            $combined = implode(' ', array_slice($tokens, $startIndex, $len));
            $normalized = $this->norm($combined);
            
            if (isset($this->stationAbbreviations[$normalized])) {
                return $len;
            }
        }
        
        return 1; // Default to 1 if not found
    }

    private function normalizeStation(string $tk): string
    {
        // Kiểm tra token gốc
        $normalized = $this->stationAbbreviations[$this->norm($tk)] ?? null;
        if ($normalized) {
            return $normalized;
        }
        
        // Kiểm tra token với dấu chấm được thay thế bằng khoảng trắng (L.an -> L an)
        $tkWithSpace = str_replace('.', ' ', $tk);
        $normalized = $this->stationAbbreviations[$this->norm($tkWithSpace)] ?? null;
        if ($normalized) {
            return $normalized;
        }
        
        return $tk;
    }

    private function hydrateStationAliasesFromDB(): void
    {
        try {
            $stations = Station::query()->get(['name','code','aliases']);
            foreach ($stations as $st) {
                $name = mb_strtolower($st->name);
                $normName = $this->norm($name);
                $code = $st->code ? $this->norm($st->code) : null;

                $this->stationAbbreviations[$normName] = $name;
                $this->stationAbbreviations[str_replace(' ','',$normName)] = $name;

                $initials = $this->initials($name);
                if ($initials) $this->stationAbbreviations[$initials] = $name;

                if ($code) $this->stationAbbreviations[$code] = $name;

                foreach ((array)($st->aliases ?? []) as $a) {
                    $this->stationAbbreviations[$this->norm($a)] = $name;
                }

                if (str_contains($normName,'hochiminh') || str_contains($normName,'tphcm') || str_contains($normName,'tphochiminh')) {
                    foreach (['tp','hcm','sg','saigon'] as $al) $this->stationAbbreviations[$al] = $name;
                }
                if (str_contains($normName,'dalat') || str_contains($normName,'lamdong')) {
                    $this->stationAbbreviations['dl'] = $name;
                }
            }
        } catch (\Throwable $e) { /* fallback vẫn đủ dùng */ }
    }

    /**
     * Lấy đài chính theo ngày trong tuần và miền
     * @param string|null $region Miền: 'bac', 'trung', 'nam' hoặc null (tự động detect)
     * @param string|null $globalDate Ngày global (format: Y-m-d) hoặc null (dùng ngày hiện tại)
     */
    private function getDefaultStationByDay(?string $region = null, ?string $globalDate = null): string
    {
        // Ưu tiên: parameter > session > ngày hiện tại
        if ($globalDate) {
            $dayOfWeek = (int)date('N', strtotime($globalDate));
        } else {
            $dayOfWeek = (int)date('N'); // 1=Monday, 7=Sunday
        }
        
        // Nếu không có region, mặc định là miền Nam
        if (!$region) {
            $region = 'nam';
        }
        
        $defaultStations = [
            'bac' => [
                1 => 'hà nội',     // Thứ 2: Hà Nội
                2 => 'quảng ninh', // Thứ 3: Quảng Ninh
                3 => 'bắc ninh',    // Thứ 4: Bắc Ninh
                4 => 'hà nội',     // Thứ 5: Hà Nội
                5 => 'hải phòng',  // Thứ 6: Hải Phòng
                6 => 'nam định',   // Thứ 7: Nam Định
                7 => 'thái bình',  // Chủ nhật: Thái Bình
            ],
            'trung' => [
                1 => 'phú yên',    // Thứ 2: Phú Yên
                2 => 'quảng nam',  // Thứ 3: Quảng Nam
                3 => 'khánh hòa',  // Thứ 4: Khánh Hòa
                4 => 'quảng bình', // Thứ 5: Quảng Bình
                5 => 'gia lai',    // Thứ 6: Gia Lai
                6 => 'quảng ngãi', // Thứ 7: Quảng Ngãi
                7 => 'khánh hòa',  // Chủ nhật: Khánh Hòa
            ],
            'nam' => [
                1 => 'tp.hcm',     // Thứ 2: TP.HCM
                2 => 'vũng tàu',    // Thứ 3: Vũng tàu
                3 => 'đồng nai',   // Thứ 4: Đồng Nai
                4 => 'tây ninh',   // Thứ 5: Tây Ninh
                5 => 'bình dương', // Thứ 6: Bình Dương
                6 => 'tp.hcm',     // Thứ 7: TP.HCM
                7 => 'tiền giang', // Chủ nhật: Tiền Giang
            ]
        ];
        
        return $defaultStations[$region][$dayOfWeek] ?? 'tây ninh';
    }
    
    /**
     * Tự động detect miền từ session hoặc mặc định
     */
    private function detectRegionByTime(): string
    {
        // Lấy từ session global_region nếu có
        try {
            if (app()->bound('session') && session()->has('global_region')) {
                return session('global_region');
            }
        } catch (Exception $e) {
            // Session không khả dụng, sử dụng mặc định
        }
        
        // Mặc định là miền Nam
        return 'nam';
    }
    
    /**
     * Parse miền từ message (vd: "2dai bac", "3d trung", "4d nam")
     */
    private function parseRegionFromMessage(string $message): ?string
    {
        // Tìm pattern: số + d + miền
        if (preg_match('/\b(\d+)d(ai)?\s*(bac|trung|nam)\b/i', $message, $m)) {
            return strtolower($m[3]);
        }
        
        // Tìm pattern: miền đơn lẻ
        if (preg_match('/\b(bac|trung|nam)\b/i', $message, $m)) {
            return strtolower($m[1]);
        }
        
        return null;
    }

    private function initials(string $name): ?string
    {
        $name = $this->stripVN(mb_strtolower($name));
        $parts = preg_split('/\s+/u', trim($name));
        $ini = '';
        foreach ($parts as $p) { if ($p!=='') $ini .= $p[0]; }
        return strlen($ini)>=2 ? $ini : null;
    }

    private function hydrateBettingAliasesFromDB(): void
    {
        try {
            $types = BettingType::query()->get(['code','aliases']);
            foreach ($types as $t) {
                $code = $this->norm($t->code);
                $this->bettingShortcuts[$code] = $t->code;
                foreach ((array)$t->aliases as $al) {
                    $this->bettingShortcuts[$this->norm($al)] = $t->code;
                }
            }
        } catch (\Throwable $e) { /* fallback vẫn chạy */ }
    }
}
