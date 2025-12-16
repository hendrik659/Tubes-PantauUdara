<?php

const OW_GEOCODING_URL   = 'https://api.openweathermap.org/geo/1.0/direct';
const OW_AIR_POLLUTION   = 'https://api.openweathermap.org/data/2.5/air_pollution';
const OW_DEFAULT_LIMIT   = 5; 

function getOpenWeatherApiKey(): string
{
    $key = getenv('OPENWEATHER_API_KEY');

    if ($key === false || trim((string)$key) === '') {
        $configPath = __DIR__ . '/configlocal.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
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

function callApi(string $url, array $query): array
{
    $fullUrl = $url . '?' . http_build_query($query);

    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false, 
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

function searchCities(string $query): array
{
    $apiKey = getOpenWeatherApiKey();
    $data = callApi(OW_GEOCODING_URL, [
        'q'      => $query,
        'limit'  => OW_DEFAULT_LIMIT,
        'appid'  => $apiKey,
    ]);

    return $data;
}

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

function aqiColorClass(int $aqi): string
{
    return match ($aqi) {
        1 => 'bg-success',
        2 => 'bg-warning',
        3 => 'bg-warning',
        4 => 'bg-danger',
        5 => 'bg-danger',
        default => 'bg-secondary',
    };
}

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

function fmt(?float $value): string
{
    if ($value === null) return '-';
    return number_format($value, 1, ',', '.');
}

$searchQuery = '';
$searchResults = [];
$selectedCity = null;
$aqData = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['city'])) {
    try {
        if (isset($_POST['search_query']) && !empty(trim($_POST['search_query']))) {
            $searchQuery = trim($_POST['search_query']);
            $searchResults = searchCities($searchQuery);
        } elseif (isset($_GET['city'])) {
            // Jika ada parameter city dari URL, langsung ambil data
            $cityData = json_decode(base64_decode($_GET['city']), true);
            if ($cityData && isset($cityData['lat']) && isset($cityData['lon'])) {
                $selectedCity = $cityData;
                $aqData = fetchCurrentAirQuality($selectedCity['lat'], $selectedCity['lon']);
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Kota - AirCare</title>
    <meta name="description" content="Cari dan pantau kualitas udara di berbagai kota di seluruh dunia">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="css/index.css" rel="stylesheet">


</head>
<body class="antialiased" style="padding-top: 72px;">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg top-nav fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="fa-solid fa-wind"></i>
                <strong>AirCare</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pencarian.php">Cari Kota</a></li>
                    <li class="nav-item"><a class="nav-link" href="edukasi.php">Edukasi</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-5">
        
        <!-- Header Section -->
        <div class="text-center mb-5">
            <div class="text-muted text-uppercase fw-semibold mb-3">PENCARIAN KOTA</div>
            <h1 class="display-4 fw-bold text-dark mb-4">
                Cari Kualitas <span class="text-success">Udara</span> 🔍
            </h1>
            <p class="lead text-muted max-w-2xl mx-auto">
                Temukan informasi kualitas udara real-time untuk kota di seluruh dunia
            </p>
        </div>

        <!-- Search Form -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <form method="POST" action="" class="card p-4 shadow">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="search_query" 
                            id="search_query"
                            value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Masukkan nama kota (contoh: Jakarta, London, Tokyo)..." 
                            class="form-control form-control-lg"
                            required
                            autocomplete="off"
                        >
                        <button 
                            type="submit" 
                            class="btn btn-success btn-lg"
                        >
                            🔍 Cari
                        </button>
                    </div>
                </form>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger mt-4">
                        <strong>Terjadi Kesalahan:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Results -->
        <?php if (!empty($searchResults) && !$selectedCity): ?>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h2 class="h3 fw-bold text-dark mb-4">
                        📍 Hasil Pencarian untuk "<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                    </h2>
                    <div class="row g-4">
                        <?php foreach ($searchResults as $city): ?>
                            <?php
                                $cityName = $city['name'] ?? 'Unknown';
                                $country = $city['country'] ?? '';
                                $state = $city['state'] ?? '';
                                $lat = $city['lat'] ?? 0;
                                $lon = $city['lon'] ?? 0;
                                
                                // Encode data kota untuk dikirim via URL
                                $cityData = base64_encode(json_encode([
                                    'name' => $cityName,
                                    'country' => $country,
                                    'state' => $state,
                                    'lat' => $lat,
                                    'lon' => $lon
                                ]));
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <a href="?city=<?php echo urlencode($cityData); ?>" class="card h-100 shadow-sm border-0 text-decoration-none">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between mb-3">
                                            <div>
                                                <h3 class="h5 fw-bold text-dark mb-1">
                                                    <?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>
                                                </h3>
                                                <p class="text-muted small">
                                                    <?php 
                                                        $location = [];
                                                        if ($state) $location[] = $state;
                                                        if ($country) $location[] = $country;
                                                        echo htmlspecialchars(implode(', ', $location), ENT_QUOTES, 'UTF-8');
                                                    ?>
                                                </p>
                                            </div>
                                            <span class="fs-1">🌍</span>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small mb-3">
                                            <span>📍 Lat: <?php echo number_format($lat, 4); ?></span>
                                            <span>Lon: <?php echo number_format($lon, 4); ?></span>
                                        </div>
                                        <div class="border-top pt-3">
                                            <span class="text-success fw-semibold d-inline-flex align-items-center">
                                                Lihat Kualitas Udara 
                                                <svg class="ms-2" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($selectedCity && $aqData): ?>
            <?php
                $aqi = (int)($aqData['main']['aqi'] ?? 0);
                $label = aqiLabel($aqi);
                $colorClass = aqiColorClass($aqi);
                $reco = aqiRecommendation($aqi);
                $components = $aqData['components'] ?? [];
            ?>
            <div class="row justify-content-center">
                
                <div class="col-12 mb-4">
                    <a href="pencarian.php" class="btn btn-outline-secondary d-inline-flex align-items-center">
                        <svg class="me-2" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                        </svg>
                        Kembali ke Pencarian
                    </a>
                </div>

                <div class="col-lg-10 mb-5">
                    <div class="card p-4 shadow">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="h2 fw-bold text-dark mb-2">
                                    <?php echo htmlspecialchars($selectedCity['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </h2>
                                <p class="h5 text-muted mb-2">
                                    <?php 
                                        $location = [];
                                        if ($selectedCity['state']) $location[] = $selectedCity['state'];
                                        if ($selectedCity['country']) $location[] = $selectedCity['country'];
                                        echo htmlspecialchars(implode(', ', $location), ENT_QUOTES, 'UTF-8');
                                    ?>
                                </p>
                                <p class="text-muted">
                                    📍 Koordinat: <?php echo number_format($selectedCity['lat'], 4); ?>, <?php echo number_format($selectedCity['lon'], 4); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="fs-1">🌤️</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-10 mb-5">
                    <div class="card p-5 shadow text-center">
                        <h3 class="h4 fw-semibold text-dark mb-4">Indeks Kualitas Udara (AQI)</h3>
                        <div class="d-inline-block p-5 rounded-4 shadow <?php echo $colorClass; ?> text-white">
                            <div class="display-1 fw-bold mb-3"><?php echo $aqi; ?></div>
                            <div class="h4 fw-semibold mb-2"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="small opacity-75">Skala 1 - 5</div>
                        </div>
                        <div class="mt-4">
                            <div class="alert alert-light border-success">
                                <div class="d-flex align-items-start">
                                    <span class="fs-2 me-3">💡</span>
                                    <div class="text-start">
                                        <h5 class="alert-heading fw-semibold">Rekomendasi</h5>
                                        <p class="mb-0"><?php echo htmlspecialchars($reco, ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-10">
                    <div class="card p-4 shadow">
                        <h3 class="h4 fw-semibold text-dark mb-4">📊 Detail Polutan (µg/m³)</h3>
                        <div class="row g-4">
                            <?php
                                $pollutants = [
                                    'pm2_5' => ['name' => 'PM2.5', 'icon' => '💨'],
                                    'pm10' => ['name' => 'PM10', 'icon' => '🌫️'],
                                    'no2' => ['name' => 'NO₂', 'icon' => '🏭'],
                                    'o3' => ['name' => 'O₃', 'icon' => '☀️'],
                                    'so2' => ['name' => 'SO₂', 'icon' => '🏭'],
                                    'co' => ['name' => 'CO', 'icon' => '🚗'],
                                    'nh3' => ['name' => 'NH₃', 'icon' => '🌾'],
                                ];
                                
                                foreach ($pollutants as $key => $meta):
                                    if (!isset($components[$key])) continue;
                                    $value = (float)$components[$key];
                            ?>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card h-100 border-0 bg-light">
                                        <div class="card-body text-center">
                                            <div class="fs-1 mb-2"><?php echo $meta['icon']; ?></div>
                                            <div class="text-muted small mb-1"><?php echo $meta['name']; ?></div>
                                            <div class="h4 fw-bold text-success"><?php echo fmt($value); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($searchResults) && !$selectedCity && empty($error) && empty($searchQuery)): ?>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card p-5 text-center shadow">
                        <div class="fs-1 mb-4">🌏</div>
                        <h3 class="h3 fw-bold text-dark mb-3">Mulai Pencarian Anda</h3>
                        <p class="lead text-muted mb-4">
                            Masukkan nama kota di kolom pencarian di atas untuk melihat kualitas udara real-time
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <span class="badge bg-light text-dark px-3 py-2">Jakarta</span>
                            <span class="badge bg-light text-dark px-3 py-2">Surabaya</span>
                            <span class="badge bg-light text-dark px-3 py-2">Bandung</span>
                            <span class="badge bg-light text-dark px-3 py-2">London</span>
                            <span class="badge bg-light text-dark px-3 py-2">Tokyo</span>
                            <span class="badge bg-light text-dark px-3 py-2">New York</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($searchResults) && !empty($searchQuery) && !$selectedCity && !$error): ?>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card p-5 text-center shadow">
                        <div class="fs-1 mb-4">🔍</div>
                        <h3 class="h3 fw-bold text-dark mb-3">Tidak Ada Hasil</h3>
                        <p class="lead text-muted">
                            Tidak ditemukan kota dengan nama "<strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong>"
                        </p>
                        <p class="text-muted mt-3">
                            Coba periksa ejaan atau gunakan nama kota dalam bahasa Inggris
                        </p>
                    </div>
                </div>
            </div>
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
