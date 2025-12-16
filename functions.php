<?php
/**
 * File berisi fungsi-fungsi helper untuk aplikasi AirCare
 * Dipisahkan untuk memudahkan testing
 */

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


