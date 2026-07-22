<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Preview</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        .content {
            flex: 1;
            padding: 20px;
        }

        /* === FOOTER === */
        .footer {
            background-color: #003366 !important;
            color: white;
            width: 100%;
            margin-top: auto;
        }

        .footer-top {
            padding: 50px 0 30px;
        }

        .footer-bottom {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 15px 0;
            font-size: 14px;
        }

        /* Logo Footer */
        .footer-logo img {
            width: 120px !important;
            height: auto;
            display: block;
            margin-bottom: 15px;
            max-width: 100%;
        }

        /* Menu Footer */
        .footer-menu h5 {
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-menu h5:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: #4dabf7;
        }

        .footer-menu ul {
            list-style: none;
            padding-left: 0;
        }

        .footer-menu ul li {
            margin-bottom: 10px;
        }

        .footer-menu a {
            color: #e9ecef;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-menu a:hover {
            color: #4dabf7;
            transform: translateX(5px);
        }

        /* Deskripsi Toko */
        .footer-about p {
            color: #dee2e6;
            line-height: 1.6;
        }

        /* Sosial Media */
        .footer-social {
            display: flex;
            margin-top: 20px;
        }

        .footer-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 10px;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            transform: translateY(-5px);
        }

        .footer-social a.instagram:hover {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }

        .footer-social a.whatsapp:hover {
            background-color: #25D366;
        }

        /* Responsif untuk Mobile */
        @media (max-width: 768px) {
            .footer-logo {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .footer-logo img {
                margin: 0 auto 15px;
                width: 100px !important;
            }
            
            .footer-menu {
                margin-bottom: 30px;
            }
            
            .footer-social {
                justify-content: center;
            }
            
            .footer-menu h5 {
                text-align: center;
            }
            
            .footer-menu h5:after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .footer-menu ul {
                text-align: center;
            }

            .footer-about {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<!-- Footer -->
<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row">
                <!-- Logo dan Deskripsi -->
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <div class="footer-logo">
                        <img src="../assets/img/logo.jpg" alt="Logo Toko">
                    </div>
                    <div class="footer-about">
                        <p>Toko kami menyediakan produk berkualitas dengan harga terbaik. Kepuasan pelanggan adalah prioritas utama kami.</p>
                        <div class="footer-social">
                            <a href="https://wa.me/+6285138958914" target="_blank" class="whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://www.instagram.com/itsyourthriftt.id/" target="_blank" class="instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Menu Navigasi -->
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <div class="footer-menu">
                        <h5>Navigasi</h5>
                        <ul>
                            <li><a href="index.php"><i class="fas fa-home me-2"></i>Beranda</a></li>
                            <li><a href="tentangkita.php"><i class="fas fa-info-circle me-2"></i>Tentang Kita</a></li>
                            <li><a href="kategori.php"><i class="fas fa-tags me-2"></i>Kategori</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Kontak Informasi -->
                <div class="col-lg-4 col-md-12">
                    <div class="footer-menu">
                        <h5>Hubungi Kami</h5>
                        <ul>
                            <li><i class="fas fa-map-marker-alt me-2"></i> JL. Bintara Jaya Gang Masjid sebrang komplek Puri Idaman , kec. Bintara Jaya, Kota Bekasi, Jawa Barat</li>
                            <li><i class="fas fa-phone me-2"></i> +62 812-8109-6157</li>
                            <li><i class="fas fa-envelope me-2"></i> hobibekasan_@gmail.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="text-center">
                <p class="mb-0"> 2025 by <strong>SIMON LEONARDO SIMANJUNTAK</strong>. All Rights Reserved</p>
            </div>
        </div>
    </div>
</footer>

<!-- AI Chatbot Widget -->
<?php
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    echo '<script src="http://localhost:3001/widget.js"></script>';
}
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>