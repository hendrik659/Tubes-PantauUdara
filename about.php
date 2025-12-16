<?php
// Halaman About dengan tema yang selaras dengan index.php (AirCare)
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - AirCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="css/index.css" rel="stylesheet">
    <style>
        /* Layout khusus About agar berbeda dari halaman index */
        .about-wrap {
            padding: 3.5rem 0 3rem;
        }
        .about-header {
            background: linear-gradient(135deg, #e0f2fe, #ecfdf3);
            border-radius: 24px;
            padding: 2rem 2.4rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        .about-header h1 {
            font-size: 2.4rem;
            font-weight: 800;
            color: #0f172a;
        }
        .about-header p {
            color: #334155;
            margin-top: .6rem;
            max-width: 58ch;
        }
        .about-grid {
            margin-top: 2rem;
        }
        .info-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
            height: 100%;
        }
        .info-card h3 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: .4rem;
        }
        .info-card p, .info-card li {
            color: #475569;
        }
        .info-card ul {
            padding-left: 1.1rem;
            margin-bottom: 0;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .9rem;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0ea5e9;
            font-weight: 700;
            font-size: .9rem;
            letter-spacing: .02em;
            margin-bottom: .4rem;
        }
        .steps {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
        }
        .steps li {
            background: #f8fafc;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-weight: 600;
        }
        .values {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1.4rem;
        }
        .value-badge {
            background: #0ea5e9;
            color: #e0f2fe;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            font-weight: 700;
            box-shadow: 0 12px 28px rgba(14,165,233,0.28);
        }
    </style>
</head>
<body>
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
                <li class="nav-item"><a class="nav-link active" href="about.php">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="pencarian.php">Cari Kota</a></li>
                <li class="nav-item"><a class="nav-link" href="edukasi.php">Edukasi</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container about-wrap">
    <div class="about-header mb-4">
        <span class="pill">Tentang AirCare</span>
        <h1>Langkah sederhana memahami kualitas udara</h1>
        <p>
            AirCare dibuat agar informasi udara sehari-hari mudah dipahami dan langsung berguna.
            Halaman ini memberi gambaran singkat misi, cara kerja, serta nilai yang ingin kami bawa
            untuk membantu keputusan aktivitas luar ruang.
        </p>
    </div>

    <div class="about-grid row g-4">
        <div class="col-lg-4">
            <div class="info-card">
                <h3>Tujuan</h3>
                <p>Memberi data kualitas udara yang jernih dan ringkas, tanpa membanjiri pengguna dengan istilah teknis.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="info-card">
                <h3>Cara Kerja</h3>
                <ul>
                    <li>Ambil data AQI dan polutan dari OpenWeather secara berkala.</li>
                    <li>Susun menjadi indikator singkat dan rekomendasi aktivitas.</li>
                    <li>Tampilkan dalam tata letak yang mudah discan di ponsel maupun desktop.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="info-card">
                <h3>Siapa yang terbantu</h3>
                <p>Pelari pagi, pekerja lapangan, orang tua, hingga komuter yang ingin tahu kapan harus memakai masker atau menunda perjalanan.</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="info-card h-100">
                <h3>Langkah cepat memakai AirCare</h3>
                <ul class="steps">
                    <li>Buka AirCare dan pilih kota yang ingin dipantau.</li>
                    <li>Cek angka AQI dan polutan dominan yang muncul.</li>
                    <li>Baca rekomendasi singkat untuk aktivitas hari ini.</li>
                    <li>Gunakan info tersebut untuk menentukan waktu beraktivitas atau perlindungan yang diperlukan.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="info-card h-100">
                <h3>Nilai yang kami pegang</h3>
                <div class="values">
                    <div class="value-badge">Transparan: data dan sumber jelas</div>
                    <div class="value-badge">Relevan: rekomendasi praktis</div>
                    <div class="value-badge">Responsif: nyaman di layar kecil</div>
                    <div class="value-badge">Ringan: cepat diakses kapan saja</div>
                </div>
            </div>
        </div>
    </div>
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

