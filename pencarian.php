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
        1 => 'bg-gradient-to-br from-green-400 to-green-600',
        2 => 'bg-gradient-to-br from-yellow-400 to-yellow-600',
        3 => 'bg-gradient-to-br from-orange-400 to-orange-600',
        4 => 'bg-gradient-to-br from-red-400 to-red-600',
        5 => 'bg-gradient-to-br from-purple-500 to-purple-700',
        default => 'bg-gradient-to-br from-gray-400 to-gray-600',
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
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --aircare-green: #16a34a;
            --aircare-green-light: #22c55e;
            --aircare-green-soft: #bbf7d0;
            --aircare-bg: #f3f4f6;
        }
        
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background: radial-gradient(circle at top, #e5f9ee 0%, #f9fafb 40%, #e5e7eb 100%);
            min-height: 100vh;
        }
        
        .search-card {
            background: white;
            border-radius: 2rem;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--aircare-green);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        
        .city-card {
            background: white;
            border-radius: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #f3f4f6;
        }
        
        .city-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(22, 163, 74, 0.15);
            border-color: var(--aircare-green-soft);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-search {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            box-shadow: 0 14px 30px rgba(22, 163, 74, 0.35);
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(22, 163, 74, 0.45);
        }
        
        .aqi-badge {
            border-radius: 2rem;
            padding: 1rem 2rem;
            display: inline-block;
        }
        
        .navbar-glass {
            backdrop-filter: blur(18px);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
        }
        
        .logo-dot {
            display: inline-flex;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 20%, #bbf7d0 0%, #22c55e 60%, #16a34a 100%);
        }
    </style>
</head>
<body class="antialiased">
    
    <!-- Navbar -->
    <nav class="navbar-glass sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-2 hover:opacity-80 transition">
                    <span class="logo-dot"></span>
                    <span class="text-xl font-bold text-gray-800">AirCare</span>
                </a>
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="text-gray-600 hover:text-gray-900 transition font-medium">Beranda</a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 transition font-medium">Tentang</a>
                    <a href="pencarian.php" class="text-green-600 hover:text-green-700 transition font-semibold border-b-2 border-green-600 pb-1">Cari Kota</a>
                    <a href="edukasi.php" class="text-gray-600 hover:text-gray-900 transition font-medium">Edukasi</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-12">
        
        <!-- Header Section -->
        <div class="text-center mb-12 fade-in">
            <div class="text-sm uppercase tracking-widest font-semibold text-gray-500 mb-3">PENCARIAN KOTA</div>
            <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-4">
                Cari Kualitas <span class="text-green-600">Udara</span> 🔍
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Temukan informasi kualitas udara real-time untuk kota di seluruh dunia
            </p>
        </div>

        <!-- Search Form -->
        <div class="max-w-3xl mx-auto mb-12 fade-in">
            <form method="POST" action="" class="relative">
                <div class="search-card p-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                name="search_query" 
                                id="search_query"
                                value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Masukkan nama kota (contoh: Jakarta, London, Tokyo)..." 
                                class="search-input w-full px-6 py-4 bg-gray-50 text-gray-900 placeholder-gray-400 rounded-xl border-2 border-gray-200 text-lg transition"
                                required
                                autocomplete="off"
                            >
                        </div>
                        <button 
                            type="submit" 
                            class="btn-search px-8 py-4 text-white font-bold rounded-xl"
                        >
                            🔍 Cari
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($error): ?>
                <div class="mt-6 bg-white rounded-xl p-6 border-l-4 border-red-500 shadow-lg">
                    <div class="flex items-start space-x-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="text-gray-900 font-semibold text-lg mb-1">Terjadi Kesalahan</h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Search Results -->
        <?php if (!empty($searchResults) && !$selectedCity): ?>
            <div class="max-w-5xl mx-auto fade-in">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">
                    📍 Hasil Pencarian untuk "<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>"
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                        <a href="?city=<?php echo urlencode($cityData); ?>" class="city-card p-6 shadow-lg">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-1">
                                        <?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?>
                                    </h3>
                                    <p class="text-gray-600">
                                        <?php 
                                            $location = [];
                                            if ($state) $location[] = $state;
                                            if ($country) $location[] = $country;
                                            echo htmlspecialchars(implode(', ', $location), ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </p>
                                </div>
                                <span class="text-3xl">🌍</span>
                            </div>
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <span>📍 Lat: <?php echo number_format($lat, 4); ?></span>
                                <span>Lon: <?php echo number_format($lon, 4); ?></span>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <span class="text-green-600 font-semibold inline-flex items-center">
                                    Lihat Kualitas Udara 
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
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
            <div class="max-w-6xl mx-auto fade-in">
                
                <a href="pencarian.php" class="inline-flex items-center text-gray-700 hover:text-green-600 transition mb-6 text-lg font-medium">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Pencarian
                </a>

                <div class="search-card p-8 mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-4xl font-bold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($selectedCity['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h2>
                            <p class="text-xl text-gray-600">
                                <?php 
                                    $location = [];
                                    if ($selectedCity['state']) $location[] = $selectedCity['state'];
                                    if ($selectedCity['country']) $location[] = $selectedCity['country'];
                                    echo htmlspecialchars(implode(', ', $location), ENT_QUOTES, 'UTF-8');
                                ?>
                            </p>
                            <p class="text-gray-500 mt-2">
                                📍 Koordinat: <?php echo number_format($selectedCity['lat'], 4); ?>, <?php echo number_format($selectedCity['lon'], 4); ?>
                            </p>
                        </div>
                        <div class="text-6xl">�️</div>
                    </div>
                </div>

                <div class="search-card p-8 mb-8">
                    <div class="text-center">
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6">Indeks Kualitas Udara (AQI)</h3>
                        <div class="inline-block <?php echo $colorClass; ?> rounded-3xl p-12 shadow-2xl transform hover:scale-105 transition">
                            <div class="text-8xl font-bold text-white mb-4"><?php echo $aqi; ?></div>
                            <div class="text-3xl font-semibold text-white mb-2"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-white/90 text-sm">Skala 1 - 5</div>
                        </div>
                        <div class="mt-8 max-w-2xl mx-auto">
                            <div class="bg-gray-50 rounded-xl p-6 border-l-4 border-green-500">
                                <div class="flex items-start space-x-3">
                                    <span class="text-3xl">💡</span>
                                    <div class="text-left">
                                        <h4 class="text-gray-900 font-semibold text-lg mb-2">Rekomendasi</h4>
                                        <p class="text-gray-700"><?php echo htmlspecialchars($reco, ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="search-card p-8">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">📊 Detail Polutan (µg/m³)</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
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
                            <div class="bg-gray-50 rounded-xl p-6 hover:bg-gray-100 transition border border-gray-200">
                                <div class="text-4xl mb-3"><?php echo $meta['icon']; ?></div>
                                <div class="text-gray-600 text-sm mb-1"><?php echo $meta['name']; ?></div>
                                <div class="text-3xl font-bold text-green-600"><?php echo fmt($value); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($searchResults) && !$selectedCity && empty($error) && empty($searchQuery)): ?>
            <div class="max-w-2xl mx-auto text-center fade-in">
                <div class="search-card p-12">
                    <div class="text-8xl mb-6">🌏</div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-4">Mulai Pencarian Anda</h3>
                    <p class="text-xl text-gray-600 mb-8">
                        Masukkan nama kota di kolom pencarian di atas untuk melihat kualitas udara real-time
                    </p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <span class="px-4 py-2 bg-gray-100 rounded-full text-gray-700 text-sm font-medium">Jakarta</span>
                        <span class="px-4 py-2 bg-gray-100 rounded-full text-gray-700 text-sm font-medium">Surabaya</span>
                        <span class="px-4 py-2 bg-gray-100 rounded-full text-gray-700 text-sm font-medium">Bandung</span>
                        <span class="px-4 py-2 bg-gray-100 rounded-full text-gray-700 text-sm font-medium">London</span>
                        <span class="px-4 py-2 bg-gray-100 rounded-full text-gray-700 text-sm font-medium">Tokyo</span>
                        <span class="px-4 py-2 bg-gray-100 rounded-full text-gray-700 text-sm font-medium">New York</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($searchResults) && !empty($searchQuery) && !$selectedCity && !$error): ?>
            <div class="max-w-2xl mx-auto text-center fade-in">
                <div class="search-card p-12">
                    <div class="text-8xl mb-6">🔍</div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-4">Tidak Ada Hasil</h3>
                    <p class="text-xl text-gray-600">
                        Tidak ditemukan kota dengan nama "<strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong>"
                    </p>
                    <p class="text-gray-500 mt-4">
                        Coba periksa ejaan atau gunakan nama kota dalam bahasa Inggris
                    </p>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <footer class="mt-16 bg-gray-900 py-6">
        <div class="container mx-auto px-4">
            <p class="text-center text-gray-300">
                © <?php echo date('Y'); ?> AirCare. Data powered by 
                <a href="https://openweathermap.org/api" target="_blank" rel="noopener noreferrer" class="text-green-400 font-semibold hover:underline">
                    OpenWeatherMap API
                </a>
            </p>
        </div>
    </footer>

</body>
</html>
