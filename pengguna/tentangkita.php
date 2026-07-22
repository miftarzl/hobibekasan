<?php
session_start(); // Mulai session

// Cek apakah user login atau belum
$isLoggedIn = isset($_SESSION['user']['user_id']);


include '../config/config.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kita - Hobibekasan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* === Tentang Kita === */
        body {
            font-family: 'Poppins', sans-serif;
            background: url('../assets/img/bg-vintage.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            scroll-behavior: smooth;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-header h2 {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 36px;
            margin-bottom: 8px;
        }

        .section-header p {
            font-size: 19px;
            color: #555;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .section-header .line-decor {
            width: 140px;
            height: 4px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            margin: 18px auto 0;
            border-radius: 4px;
        }

        .section-title {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #444;
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            padding-bottom: 18px;
            font-size: 1.8rem;
        }

        .section-title:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            border-radius: 4px;
        }

        .highlight-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0px 15px 35px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: none;
        }

        .highlight-box:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            border-radius: 20px 0 0 20px;
        }

        .highlight-box:hover {
            transform: translateY(-10px);
            box-shadow: 0px 25px 50px rgba(97, 178, 255, 0.15);
        }

        .custom-icon {
            font-size: 52px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 25px;
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .highlight-box:hover .custom-icon {
            transform: scale(1.1);
        }

        .equal-height {
            display: flex;
            align-items: stretch;
        }

        .equal-height .highlight-box {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-align: center;
            height: 100%;
        }

        .highlight-box h4 {
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
            font-size: 1.4rem;
        }

        .highlight-box p, .highlight-box ul {
            font-size: 16px;
            line-height: 1.9;
            color: #555;
            margin-bottom: 0;
        }

        iframe {
            border-radius: 15px;
            width: 100%;
            height: 320px;
            border: none;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.12);
            margin-top: 20px;
            transition: transform 0.3s ease;
        }

        iframe:hover {
            transform: scale(1.02);
        }

        /* === Jarak Antar Konten === */
        .content-section {
            padding: 100px 0;
        }

        .content-gap {
            margin-bottom: 50px;
        }

        /* === Background Section === */
        .full-bg {
            background: linear-gradient(135deg, rgba(97, 178, 255, 0.12) 30%, rgba(30, 127, 214, 0.12) 70%);
            width: 100%;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .full-bg:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
        }

        /* Logo styling */
        .logo-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0px 20px 40px rgba(0, 0, 0, 0.12);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logo-container:hover {
            transform: scale(1.02);
            box-shadow: 0px 25px 50px rgba(0, 0, 0, 0.15);
        }

        .logo-container img {
            transition: all 0.5s ease;
        }

        .logo-container:hover img {
            transform: scale(1.05);
        }
        
        /* List styling */
        .highlight-box ul {
            text-align: left;
            padding-left: 25px;
            margin-top: 15px;
        }

        .highlight-box ul li {
            margin-bottom: 12px;
            position: relative;
            transition: transform 0.2s ease;
        }

        .highlight-box ul li:hover {
            transform: translateX(5px);
        }

        .highlight-box ul li:before {
            content: "•";
            color: #61b2ff;
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
        
        /* Button styling */
        .btn-gradient {
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            color: white !important;
            border: none;
            padding: 14px 30px;
            border-radius: 30px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-size: 14px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0px 8px 20px rgba(97, 178, 255, 0.35);
        }

        .btn-gradient:hover {
            transform: translateY(-4px);
            box-shadow: 0px 12px 25px rgba(97, 178, 255, 0.45);
            color: white;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 35px;
            right: 35px;
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            box-shadow: 0px 8px 20px rgba(97, 178, 255, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }

        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-8px);
            box-shadow: 0px 12px 25px rgba(97, 178, 255, 0.7);
            color: white;
        }

        .back-to-top i {
            font-size: 22px;
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .content-section {
                padding: 40px 20px;
            }

            .section-header {
                text-align: center;
                margin-bottom: 30px;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .highlight-box {
                padding: 25px;
            }

            .highlight-box h3 {
                font-size: 1.5rem;
            }

            .highlight-box p {
                font-size: 0.95rem;
            }
        }

        @media (max-width: 767.98px) {
            .content-section {
                padding: 30px 15px;
            }

            .section-header h2 {
                font-size: 1.8rem;
            }

            .section-header p {
                font-size: 1rem;
            }

            .content-gap {
                margin-bottom: 30px;
            }

            .logo-container {
                max-width: 250px;
                margin: 0 auto;
            }

            .highlight-box {
                padding: 20px;
            }

            .highlight-box h3 {
                font-size: 1.3rem;
            }

            .highlight-box p {
                font-size: 0.9rem;
            }

            .back-to-top {
                width: 45px;
                height: 45px;
                font-size: 16px;
                bottom: 20px;
                right: 20px;
            }
        }

        @media (max-width: 576px) {
            .content-section {
                padding: 20px 10px;
            }

            .section-header h2 {
                font-size: 1.5rem;
            }

            .section-header p {
                font-size: 0.9rem;
            }

            .logo-container {
                max-width: 200px;
            }

            .highlight-box {
                padding: 15px;
            }

            .highlight-box h3 {
                font-size: 1.1rem;
            }

            .highlight-box p {
                font-size: 0.85rem;
            }

            .back-to-top {
                width: 40px;
                height: 40px;
                font-size: 14px;
                bottom: 15px;
                right: 15px;
            }
        }
    </style>
</head>

<body>

    <?php include '../assets/navbar.php'; ?>

    <!-- SECTION TENTANG KITA -->
    <div class="container content-section">
        <div class="section-header">
            <h2>Tentang Hobibekasan</h2>
            <p>Thrift Shop & Vintage Store</p>
            <div class="line-decor"></div>
        </div>

        <div class="row align-items-center">
            <div class="col-md-6 text-center content-gap">
                <div class="logo-container">
                    <img src="../assets/img/logo.jpg" class="img-fluid" alt="Toko hobibekasan">
                </div>
            </div>
            <div class="col-md-6 content-gap">
                <div class="highlight-box">
                    <h3 class="fw-semibold">Apa Itu hobibekasan?</h3>
                    <p>
                        <strong>Hobibekasan</strong> adalah toko thrifting yang berfokus pada penjualan sepatu bekas berkualitas tinggi dengan harga yang terjangkau.
                        Kami menyediakan berbagai jenis sepatu, mulai dari sepatu sneakers, sepatu boots, kidsboots hingga jaket impor berkualitas.
                    </p>
                    <!-- <a href="kategori.php" class="btn btn-gradient mt-3">Lihat Koleksi Kami</a> -->
                </div>
            </div>
        </div>
    </div>

    <!-- VISI & MISI -->
    <div class="full-bg">
        <div class="container content-section">
            <h3 class="section-title text-center">Visi & Misi</h3>
            <div class="row mt-4 equal-height">
                <div class="col-md-6 content-gap">
                    <div class="highlight-box">
                        <i class="fa-solid fa-eye custom-icon"></i>
                        <h4 class="fw-semibold">Visi</h4>
                        <p>
                            Menjadi toko thrift terbaik yang menyediakan sepatu berkualitas dengan harga terjangkau, dan dengan barang impor
                        </p>
                    </div>
                </div>
                <div class="col-md-6 content-gap">
                    <div class="highlight-box">
                        <i class="fa-solid fa-bullseye custom-icon"></i>
                        <h4 class="fw-semibold">Misi</h4>
                        <ul class="text-start">
                            <li>Menyediakan produk thrift berkualitas tinggi.</li>
                            <li>Mendukung gaya hidup ramah lingkungan melalui fashion reuse.</li>
                            <li>Memberikan pengalaman belanja yang nyaman dan terpercaya bagi pelanggan.</li>
                            <li>Menawarkan produk dengan harga yang lebih murah dibandingkan retail baru.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEJARAH & LOKASI -->
    <div class="container content-section">
        <h3 class="section-title text-center">Sejarah & Lokasi</h3>
        <div class="row mt-4 equal-height">
            <div class="col-md-6 content-gap">
                <div class="highlight-box">
                    <i class="fa-solid fa-book-open custom-icon"></i>
                    <h4 class="fw-semibold">Sejarah</h4>
                    <p>
                        <strong>hobibekasan<strong> pertama kali didirikan di tanjung priok, jakarta utara selama 3 tahun <strong>kemudian, pindah ke bintara 1 tahun</strong> dengan tujuan menyediakan sepatu thrift berkualitas tinggi yang tetap fashionable.
                        Dengan semakin meningkatnya minat masyarakat terhadap barang thrift, kami terus berkembang hingga saat ini.
                    </p>
                </div>
            </div>
            <div class="col-md-6 content-gap">
                <div class="highlight-box">
                    <i class="fa-solid fa-map-location-dot custom-icon"></i>
                    <h4 class="fw-semibold">Lokasi</h4>
                    <p>
                        <strong>Alamat:</strong> JL. Bintara Jaya Gang Masjid sebrang komplek Puri Idaman , kec. Bintara Jaya, Kota Bekasi, Jawa Barat
                    </p>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.123612074674!2d106.94473757476408!3d-6.247437493740961!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698d0044da0669%3A0x63db5335f05b4b64!2sHobi%20Bekasan!5e0!3m2!1sid!2sid!4v1774324611883!5m2!1sid!2sid" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- KENAPA MEMILIH KAMI -->
    <div class="full-bg">
        <div class="container content-section">
            <h3 class="section-title text-center">Kenapa Memilih Kami</h3>
            <div class="row mt-4">
                <div class="col-md-4 content-gap">
                    <div class="highlight-box">
                        <i class="fa-solid fa-shirt custom-icon"></i>
                        <h4 class="fw-semibold">Kualitas Terjamin</h4>
                        <p>
                            Setiap barang yang kami jual telah melalui proses seleksi ketat untuk memastikan kualitas terbaik.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 content-gap">
                    <div class="highlight-box">
                        <i class="fa-solid fa-leaf custom-icon"></i>
                        <h4 class="fw-semibold">Ramah Lingkungan</h4>
                        <p>
                            Dengan berbelanja thrift, Anda turut berkontribusi dalam mengurangi sampah tekstil dan mendukung fashion berkelanjutan.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 content-gap">
                    <div class="highlight-box">
                        <i class="fa-solid fa-tag custom-icon"></i>
                        <h4 class="fw-semibold">Harga Terjangkau</h4>
                        <p>
                            Dapatkan pakaian berkualitas dengan harga yang jauh lebih murah dibandingkan membeli baru.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

    <?php include '../assets/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk tombol Back to Top
        const backToTopButton = document.getElementById('backToTop');
        
        // Fungsi untuk menampilkan atau menyembunyikan tombol berdasarkan posisi scroll
        window.onscroll = function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                backToTopButton.classList.add('active');
            } else {
                backToTopButton.classList.remove('active');
            }
        };
        
        // Event listener untuk tombol scroll ke atas
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>

</html>