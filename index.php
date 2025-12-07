<?php
// ----------------------------------------------------
// Konfigurasi umum
// ----------------------------------------------------
const OW_GEOCODING_URL   = 'https://api.openweathermap.org/geo/1.0/direct';
const OW_AIR_POLLUTION   = 'https://api.openweathermap.org/data/2.5/air_pollution';
const OW_DEFAULT_LIMIT   = 1; // ambil 1 kota teratas

// Kota default: Kediri, Indonesia
const DEFAULT_CITY_QUERY = 'Kediri,ID';

/**
 * Ambil API key dari:
 * 1) Environment variable OPENWEATHER_API_KEY (GitHub Secrets / export env)
 * 2) configlocal.php (fallback untuk lokal)
 */
function getOpenWeatherApiKey(): string
{
    // 1. Coba dari environment
    $key = getenv('OPENWEATHER_API_KEY');

    // 2. Kalau kosong → coba configlocal.php
    if ($key === false || trim((string)$key) === '') {
        $configPath = __DIR__ . '/configlocal.php';
        if (file_exists($configPath)) {
            $config = require $configPath; // harus mengembalikan array
            if (is_array($config) && isset($config['OPENWEATHER_API_KEY'])) {
                $key = $config['OPENWEATHER_API_KEY'];
            }
        }
    }

    $key = trim((string)$key);

    if ($key === '') {
        throw new Exception(
            'API key OpenWeather belum dikonfigurasi. ' .
            'Set environment variable OPENWEATHER_API_KEY atau file configlocal.php terlebih dahulu.'
        );
    }

    return $key;
}

/**
 * Panggil endpoint umum (GET) dan kembalikan array hasil decode JSON.
 */
function callApi(string $url, array $query): array
{
    $fullUrl = $url . '?' . http_build_query($query);

    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Gagal menghubungi API: ' . $error);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode !== 200) {
        $snippet = mb_substr($response, 0, 120);
        throw new Exception('API mengembalikan status code: ' . $statusCode . '. Pesan: ' . $snippet);
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Gagal decode JSON: ' . json_last_error_msg());
    }

    return $data;
}

/**
 * Geocoding: nama kota -> lat, lon, name, country
 */
function geocodeCity(string $city): array
{
    $apiKey = getOpenWeatherApiKey();
    $data = callApi(OW_GEOCODING_URL, [
        'q'      => $city,
        'limit'  => OW_DEFAULT_LIMIT,
        'appid'  => $apiKey,
    ]);

    if (empty($data)) {
        throw new Exception('Kota tidak ditemukan. Coba cek kembali nama kotanya.');
    }

    $first = $data[0];

    return [
        'name'    => $first['name'] ?? $city,
        'lat'     => $first['lat'] ?? null,
        'lon'     => $first['lon'] ?? null,
        'country' => $first['country'] ?? null,
        'state'   => $first['state'] ?? null,
    ];
}

/**
 * Ambil kualitas udara saat ini berdasarkan lat & lon.
 */
function fetchCurrentAirQuality(float $lat, float $lon): array
{
    $apiKey = getOpenWeatherApiKey();
    $data = callApi(OW_AIR_POLLUTION, [
        'lat'   => $lat,
        'lon'   => $lon,
        'appid' => $apiKey,
    ]);

    if (empty($data['list'][0])) {
        throw new Exception('Data kualitas udara tidak tersedia untuk lokasi ini.');
    }

    return $data['list'][0];
}

/**
 * Konversi nilai AQI (1-5) dari OpenWeather ke label singkat.
 */
function aqiLabel(int $aqi): string
{
    return match ($aqi) {
        1 => 'Baik',
        2 => 'Sedang',
        3 => 'Kurang Sehat',
        4 => 'Tidak Sehat',
        5 => 'Berbahaya',
        default => 'Tidak diketahui',
    };
}

/**
 * Rekomendasi kesehatan berdasarkan AQI.
 */
function aqiRecommendation(int $aqi): string
{
    return match ($aqi) {
        1 => 'Aman beraktivitas di luar ruangan.',
        2 => 'Masih aman, namun perhatikan kondisi jika memiliki riwayat penyakit pernapasan.',
        3 => 'Kurangi aktivitas fisik berat di luar ruangan, khususnya bagi kelompok sensitif.',
        4 => 'Gunakan masker dan batasi aktivitas di luar ruangan, terutama anak-anak, lansia, dan penderita asma.',
        5 => 'Tetap di dalam ruangan sebisa mungkin dan gunakan perlindungan pernapasan jika harus keluar.',
        default => 'Pantau kondisi udara dan sesuaikan aktivitas seperlunya.',
    };
}

/**
 * Kelas warna gauge berdasarkan AQI.
 */
function aqiColorClass(int $aqi): string
{
    return match ($aqi) {
        1 => 'aqi-good',
        2 => 'aqi-fair',
        3 => 'aqi-moderate',
        4 => 'aqi-poor',
        5 => 'aqi-very-poor',
        default => 'aqi-unknown',
    };
}

/**
 * Format angka dengan 1–2 decimal.
 */
function fmt(?float $value): string
{
    if ($value === null) return '-';
    return number_format($value, 1, ',', '.');
}

/**
 * Metadata tiap polutan untuk tampilan kartu.
 */
function pollutantMeta(string $key): array
{
    return match ($key) {
        'pm2_5' => [
            'code' => 'PM2.5',
            'name' => 'Fine Particles',
            'desc' => '<strong>Partikel sangat halus</strong> yang dapat menembus jauh ke paru-paru dan bahkan masuk ke aliran darah. '
                    . 'Sumber utama: asap kendaraan, pembakaran biomassa, dan industri.',
        ],
        'pm10' => [
            'code' => 'PM10',
            'name' => 'Coarse Particles',
            'desc' => '<strong>Partikel halus</strong> dari debu jalan, konstruksi, dan sumber serupa. '
                    . 'Dapat menyebabkan iritasi saluran pernapasan atas dan memperburuk asma.',
        ],
        'no2' => [
            'code' => 'NO₂',
            'name' => 'Nitrogen Dioxide',
            'desc' => '<strong>Gas NO₂</strong> terutama berasal dari emisi kendaraan dan pembakaran bahan bakar. '
                    . 'Paparan tinggi dapat mengiritasi saluran pernapasan.',
        ],
        'o3' => [
            'code' => 'O₃',
            'name' => 'Ozone',
            'desc' => '<strong>Ozon permukaan</strong> terbentuk dari reaksi kimia polutan lain di udara. '
                    . 'Dapat memicu batuk, iritasi tenggorokan, dan memperburuk penyakit paru-paru.',
        ],
        'so2' => [
            'code' => 'SO₂',
            'name' => 'Sulfur Dioxide',
            'desc' => '<strong>Sulfur dioksida</strong> biasanya berasal dari pembakaran batu bara dan minyak. '
                    . 'Dapat menyebabkan iritasi mata dan saluran pernapasan.',
        ],
        'co' => [
            'code' => 'CO',
            'name' => 'Carbon Monoxide',
            'desc' => '<strong>Karbon monoksida</strong> adalah gas tak berbau dari pembakaran tidak sempurna, '
                    . 'misalnya kendaraan bermotor. Pada kadar tinggi dapat mengganggu distribusi oksigen dalam darah.',
        ],
        'nh3' => [
            'code' => 'NH₃',
            'name' => 'Ammonia',
            'desc' => '<strong>Amonia</strong> banyak berasal dari aktivitas pertanian dan peternakan. '
                    . 'Pada konsentrasi tinggi dapat mengiritasi mata dan saluran napas.',
        ],
        default => [
            'code' => strtoupper($key),
            'name' => 'Pollutant',
            'desc' => 'Polutan udara yang berasal dari berbagai sumber emisi.',
        ],
    };
}

/**
 * Kategori kualitas untuk polutan (Sangat Baik / Perlu Diwaspadai / Buruk).
 * Ini hanya pendekatan sederhana, bukan standar resmi.
 */
function pollutantCategory(float $value, string $key): array
{
    $label = 'Sangat Baik';
    $class = 'status-good';

    switch ($key) {
        case 'pm2_5':
            if ($value <= 15)        { $label = 'Sangat Baik'; $class = 'status-good'; }
            elseif ($value <= 55)    { $label = 'Perlu Diwaspadai'; $class = 'status-medium'; }
            else                     { $label = 'Buruk'; $class = 'status-bad'; }
            break;
        case 'pm10':
            if ($value <= 50)        { $label = 'Sangat Baik'; $class = 'status-good'; }
            elseif ($value <= 150)   { $label = 'Perlu Diwaspadai'; $class = 'status-medium'; }
            else                     { $label = 'Buruk'; $class = 'status-bad'; }
            break;
        default:
            if ($value <= 50)        { $label = 'Sangat Baik'; $class = 'status-good'; }
            elseif ($value <= 150)   { $label = 'Perlu Diwaspadai'; $class = 'status-medium'; }
            else                     { $label = 'Buruk'; $class = 'status-bad'; }
            break;
    }

    return ['label' => $label, 'class' => $class];
}

// ----------------------------------------------------
// Logic utama halaman (langsung kota Kediri)
// ----------------------------------------------------
$error      = null;
$location   = null;
$aqData     = null;
$aqi        = 0;
$label      = '';
$reco       = '';
$colorClass = '';

try {
    $location = geocodeCity(DEFAULT_CITY_QUERY);

    if ($location['lat'] === null || $location['lon'] === null) {
        throw new Exception('Koordinat lokasi tidak valid.');
    }

    $aqData = fetchCurrentAirQuality($location['lat'], $location['lon']);
    $aqi   = (int)($aqData['main']['aqi'] ?? 0);
    $label = aqiLabel($aqi);
    $reco  = aqiRecommendation($aqi);
    $colorClass = aqiColorClass($aqi);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>AirCare - Pantau Kualitas Udara Kediri</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS custom -->
    <link href="css/index.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg top-nav fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span class="logo-dot"></span>
            <strong>AirCare</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-lg-3">
                <li class="nav-item"><a class="nav-link active" href="index.php">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Cari Kota</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Edukasi</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container hero">
    <div class="row align-items-center g-5">
        <!-- KIRI: TEKS -->
        <div class="col-lg-6">
            <div class="hero-title">Pantau kualitas udara</div>
            <h1 class="hero-heading mt-2">
                Pantau Kualitas <span class="accent">Udara</span><br />
                Secara Real-Time
            </h1>
            <p class="hero-subtitle">
                Dapatkan informasi akurat tentang kualitas udara di Kediri, Indonesia.
                Pantau polusi, partikel berbahaya, dan ambil keputusan aktivitas harian
                dengan data yang selalu diperbarui.
            </p>

            <div class="hero-actions">
                <a href="#" class="btn btn-primary-air">
                    Cek Kota Lain
                </a>
                <a href="#" class="btn btn-outline-air">
                    Pelajari Polutan
                </a>
            </div>
        </div>

        <!-- KANAN: KARTU DATA -->
        <div class="col-lg-6">
            <div class="air-card <?php echo htmlspecialchars($colorClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($error): ?>
                    <p class="text-danger mb-0">
                        Terjadi kesalahan saat mengambil data:<br>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                <?php elseif ($aqData && $location): ?>
                    <?php
                        $components = $aqData['components'] ?? [];
                        $pm25 = $components['pm2_5'] ?? null;
                        $pm10 = $components['pm10'] ?? null;
                        $o3   = $components['o3']   ?? null;
                        $percent = $aqi > 0 ? ($aqi / 5) * 100 : 0;
                    ?>

                    <div class="air-card-location">
                        <span class="pin">📍</span>
                        <div>
                            Kediri, Indonesia
                            <small>
                                Lat: <?php echo $location['lat']; ?>,
                                Lon: <?php echo $location['lon']; ?>
                            </small>
                        </div>
                    </div>

                    <div class="gauge-wrapper mt-3">
                        <div class="gauge-circle" style="--percent: <?php echo $percent; ?>%;">
                            <div class="gauge-inner">
                                <div class="gauge-value"><?php echo $aqi; ?></div>
                                <div class="gauge-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="gauge-caption">Indeks AQI (1 – 5)</div>
                            </div>
                        </div>
                        <p class="text-muted small mt-2 mb-0 text-center">
                            <?php echo htmlspecialchars($reco, ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>

                    <div class="pollutant-cards">
                        <div class="pollutant-card">
                            <div class="pollutant-value"><?php echo fmt($pm25); ?></div>
                            <div class="pollutant-name">PM2.5</div>
                        </div>
                        <div class="pollutant-card">
                            <div class="pollutant-value"><?php echo fmt($pm10); ?></div>
                            <div class="pollutant-name">PM10</div>
                        </div>
                        <div class="pollutant-card">
                            <div class="pollutant-value"><?php echo fmt($o3); ?></div>
                            <div class="pollutant-name">O₃</div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="mb-0 text-muted">Data sedang diproses…</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$error && $aqData && $location): ?>
        <?php $c = $aqData['components'] ?? []; ?>
        <?php
            $locName  = $location['name'] ?? 'Kediri';
            $locState = $location['state'] ?? 'Jawa Timur';
            $pollutantOrder = ['pm2_5','pm10','no2','o3','so2','co','nh3'];
        ?>
        <section id="fitur" class="row">
            <div class="col-12">
                <div class="detail-shell">
                    <h2 class="detail-title">Detail Konsentrasi Polutan</h2>
                    <div class="detail-location-wrapper">
                        <span class="detail-location-pill">
                            <span class="pin">📍</span>
                            <?php echo htmlspecialchars($locName, ENT_QUOTES, 'UTF-8'); ?>,
                            <?php echo htmlspecialchars($locState, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                    <p class="detail-unit">
                        Satuan: mikrogram per meter kubik (µg/m³)
                    </p>

                    <?php foreach ($pollutantOrder as $key): ?>
                        <?php if (!isset($c[$key])) continue; ?>
                        <?php
                            $value      = (float)$c[$key];
                            $meta       = pollutantMeta($key);
                            $category   = pollutantCategory($value, $key);
                            $statusClass= $category['class'];
                            $statusLabel= $category['label'];
                            $barWidth   = max(8, min(100, $value * 2));
                        ?>
                        <div class="pol-card <?php echo $statusClass; ?>">
                            <div class="pol-col-left">
                                <div class="pol-code">
                                    <?php echo $meta['code']; ?>
                                </div>
                                <div class="pol-name-label">
                                    <?php echo $meta['name']; ?>
                                </div>
                            </div>
                            <div class="pol-col-middle">
                                <div class="pol-value-big">
                                    <?php echo fmt($value); ?>
                                </div>
                                <div class="pol-unit">µg/m³</div>
                                <div class="pol-bar">
                                    <span style="width: <?php echo $barWidth; ?>%;"></span>
                                </div>
                            </div>
                            <div class="pol-col-right">
                                <p class="mb-2">
                                    <?php echo $meta['desc']; ?>
                                </p>
                                <span class="pol-badge">
                                    ✔ <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<footer class="site-footer mt-4">
    <div class="container py-3">
        <hr class="border-secondary opacity-50 mb-3">
        <p class="mb-0 text-center small">
            © <?php echo date('Y'); ?> AirCare. Data powered by
            <a href="https://openweathermap.org/api" target="_blank" rel="noopener noreferrer">
                OpenWeatherMap API
            </a>.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
