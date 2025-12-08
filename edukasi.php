<?php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edukasi Detail Konsentrasi Polutan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/index.css" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #14213d;
            --muted: #5f6b7a;
            --border: #e4e9f0;
            --accent: linear-gradient(120deg, #4cb8c4, #3cd3ad);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top left, #eef2f9, #f9fbff 50%, #f4f7fb);
            color: var(--text);
            line-height: 1.6;
            padding: 104px 20px 48px;
        }
        .page {
            max-width: 1180px;
            margin: 0 auto;
        }
        header { margin-bottom: 24px; }
        h1 { font-size: 26px; margin-bottom: 6px; letter-spacing: -0.02em; }
        .subtitle { color: var(--muted); font-size: 15px; }
        .location {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin: 14px 0;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(76, 184, 196, 0.12);
            color: #147b8a;
        }
        .card-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        .card {
            position: relative;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 18px 16px;
            box-shadow: 0 10px 30px rgba(20, 33, 61, 0.08);
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--accent);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 38px rgba(20, 33, 61, 0.12);
            border-color: rgba(76, 184, 196, 0.35);
        }
        .pollutant-name { font-size: 18px; font-weight: 800; letter-spacing: -0.01em; }
        .secondary { color: var(--muted); font-weight: 600; }
        .value {
            font-size: 30px;
            font-weight: 800;
        }
        .unit { color: var(--muted); margin-left: 6px; font-size: 14px; }
        .desc { color: var(--muted); font-size: 14px; }
        .note-footer { margin-top: 28px; color: var(--muted); font-size: 13px; text-align: center; }
        /* Footer full-width */
        .site-footer {
            width: 100%;
            background: #070b1c;
            color: #cfd6e4;
            margin-top: 40px;
        }
        .site-footer .footer-inner {
            padding: 18px 24px 22px;
        }
        .site-footer hr {
            border-color: rgba(255,255,255,0.14);
            opacity: 1;
        }
        .site-footer a { color: #9ed7ff; }
        .site-footer a:hover { color: #c2e7ff; }
    </style>
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link" href="pencarian.php">Cari Kota</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="edukasi.php">Edukasi</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page">
        <header>
            <h1>Detail Konsentrasi Polutan</h1>
            <p class="subtitle">Nilai terkini udara ambien dan penjelasan dampak kesehatan.</p>
            <div class="location">📍 Kediri, East Java • Satuan: mikrogram per meter kubik (µg/m³)</div>
        </header>

        <section class="card-grid">
            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">PM2.5</div>
                        <div class="secondary">Fine Particles</div>
                    </div>
                    <div>
                        <span class="value">2,0</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Partikel sangat halus yang dapat menembus jauh ke paru-paru dan bahkan masuk ke aliran darah. Sumber utama: asap kendaraan, pembakaran biomassa, dan industri.</p>
            </article>

            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">PM10</div>
                        <div class="secondary">Coarse Particles</div>
                    </div>
                    <div>
                        <span class="value">2,8</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Partikel halus dari debu jalan, konstruksi, dan sumber serupa. Dapat menyebabkan iritasi saluran pernapasan atas dan memperburuk asma.</p>
            </article>

            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">NO₂</div>
                        <div class="secondary">Nitrogen Dioxide</div>
                    </div>
                    <div>
                        <span class="value">0,0</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Gas NO₂ terutama berasal dari emisi kendaraan dan pembakaran bahan bakar. Paparan tinggi dapat mengiritasi saluran pernapasan.</p>
            </article>

            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">O₃</div>
                        <div class="secondary">Ozone</div>
                    </div>
                    <div>
                        <span class="value">33,9</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Ozon permukaan terbentuk dari reaksi kimia polutan lain di udara. Dapat memicu batuk, iritasi tenggorokan, dan memperburuk penyakit paru-paru.</p>
            </article>

            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">SO₂</div>
                        <div class="secondary">Sulfur Dioxide</div>
                    </div>
                    <div>
                        <span class="value">0,0</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Sulfur dioksida biasanya berasal dari pembakaran batu bara dan minyak. Dapat menyebabkan iritasi mata dan saluran pernapasan.</p>
            </article>

            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">CO</div>
                        <div class="secondary">Carbon Monoxide</div>
                    </div>
                    <div>
                        <span class="value">78,9</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Karbon monoksida adalah gas tak berbau dari pembakaran tidak sempurna, misalnya kendaraan bermotor. Pada kadar tinggi dapat mengganggu distribusi oksigen dalam darah.</p>
            </article>

            <article class="card">
                <div class="card-header">
                    <div>
                        <div class="pollutant-name">NH₃</div>
                        <div class="secondary">Ammonia</div>
                    </div>
                    <div>
                        <span class="value">0,0</span><span class="unit">µg/m³</span>
                    </div>
                </div>
                <p class="desc">Amonia banyak berasal dari aktivitas pertanian dan peternakan. Pada konsentrasi tinggi dapat mengiritasi mata dan saluran napas.</p>
            </article>
        </section>

        <footer class="note-footer">
            Data statis untuk keperluan edukasi. Sesuaikan styling atau integrasikan dengan API kualitas udara bila diperlukan.
        </footer>
    </div>
    <footer class="site-footer">
        <div class="footer-inner">
            <hr class="mb-3">
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
