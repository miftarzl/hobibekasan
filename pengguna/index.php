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
    <title>Beranda - hobiBekasan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
 /* File: style.css */

/* Import font dari Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');

/* Reset dan Styling Umum */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Nunito', sans-serif;
    scroll-behavior: smooth;
}

/* HERO SECTION STYLING */
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
    padding: 80px 0 120px;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3Cpath d='M6 5V0H5v5H0v1h5v94h1V6h94V5H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.8;
}

.hero-content {
    color: white;
    z-index: 10;
    padding-right: 20px;
}

.hero-content h1 {
    margin-bottom: 1.5rem;
    font-weight: 800;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    animation-duration: 1.2s;
}

.hero-content .highlight {
    color: #ffcc00;
    position: relative;
    display: inline-block;
}

.hero-content .highlight::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 8px;
    background-color: rgba(255, 204, 0, 0.3);
    bottom: 0;
    left: 0;
    z-index: -1;
}

.hero-content p {
    margin-bottom: 1.8rem;
    font-size: 1.25rem;
    font-weight: 400;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    max-width: 90%;
    animation-duration: 1.4s;
}

.hero-buttons {
    margin-bottom: 2rem;
    animation-duration: 1.6s;
}

.btn-hero {
    background-color: #ffcc00;
    color: #003366;
    font-weight: 700;
    padding: 12px 28px;
    border-radius: 50px;
    border: none;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    margin-right: 15px;
}

.btn-hero:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.2);
    background-color: #ffdb4d;
    color: #002244;
}

.btn-hero-secondary {
    background-color: transparent;
    color: white;
    border: 2px solid white;
    font-weight: 600;
    padding: 12px 28px;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-hero-secondary:hover {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    transform: translateY(-3px);
}

.hero-features {
    display: flex;
    margin-top: 2rem;
    animation-duration: 1.8s;
}

.feature-item {
    display: flex;
    align-items: center;
    margin-right: 1.5rem;
    font-size: 0.9rem;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 50px;
    backdrop-filter: blur(5px);
}

.feature-item i {
    margin-right: 6px;
    color: #ffcc00;
}

.hero-image-container {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 5;
}

.hero-image-wrapper {
    position: relative;
    animation-duration: 1.5s;
}

.hero-image {
    max-width: 100%;
    border-radius: 20px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    transition: transform 0.5s ease;
    border: 5px solid rgba(255, 255, 255, 0.2);
}

.hero-image:hover {
    transform: scale(1.03);
}

.hero-shape-divider {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
}

.hero-shape-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 60px;
}

.hero-shape-divider .shape-fill {
    fill: #FFFFFF;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .hero-content {
        text-align: center;
        padding-right: 0;
        margin-bottom: 2rem;
    }
    
    .hero-content p {
        margin-left: auto;
        margin-right: auto;
        max-width: 100%;
    }
    
    .hero-features {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .feature-item {
        margin-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 60px 0 100px;
        min-height: auto;
    }

    .hero-content h1 {
        font-size: 2rem;
        line-height: 1.3;
    }

    .hero-content p {
        font-size: 1rem;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-buttons {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .hero-buttons .btn {
        width: 100%;
        max-width: 280px;
    }

    .hero-image {
        display: none;
    }
}

@media (max-width: 576px) {
    .hero-section {
        padding: 40px 0 80px;
    }

    .hero-content h1 {
        font-size: 1.6rem;
    }

    .hero-content p {
        font-size: 0.95rem;
    }

    .hero-buttons .btn {
        font-size: 0.9rem;
        padding: 10px 20px;
    }
    
    .btn-hero, .btn-hero-secondary {
        margin-right: 0;
        margin-bottom: 15px;
        width: 80%;
    }
    
    .hero-features {
        flex-direction: column;
        align-items: center;
    }
    
    .feature-item {
        margin-right: 0;
        margin-bottom: 10px;
    }
}

/* KEUNGGULAN TOKO STYLING */
.keunggulan-section {
    position: relative;
    background: linear-gradient(180deg, #fff 0%, #e6f2ff 50%, #bbdefb 100%);
    color: #003366;
    padding: 100px 0 100px; /* Mengurangi padding-bottom */
    overflow: hidden;
    margin-top: -20px; /* Ini untuk memastikan tidak ada gap dengan section sebelumnya */
}

.keunggulan-section .highlight {
    color: #1e7fd6;
    position: relative;
    display: inline-block;
}

.keunggulan-section .highlight::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 8px;
    background-color: rgba(30, 127, 214, 0.2);
    bottom: 0;
    left: 0;
    z-index: -1;
}

.keunggulan-section h2 {
    font-weight: 800;
    color: #003366;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 40px;
}

.keunggulan-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 51, 102, 0.08);
}

.keunggulan-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
}

.keunggulan-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    background: #1e7fd6;
    background: linear-gradient(135deg, #64b5f6 30%, #1e7fd6 70%);
    border-radius: 50%;
    margin-bottom: 20px;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.keunggulan-card:hover .keunggulan-icon {
    transform: scale(1.1) rotate(10deg);
}

.keunggulan-icon i {
    font-size: 28px;
    color: white;
}

.keunggulan-card h4 {
    font-weight: 700;
    margin-bottom: 15px;
    color: #003366;
    font-size: 22px;
}

.keunggulan-card p {
    color: #4a5568;
    font-size: 16px;
    line-height: 1.6;
}

.keunggulan-shape-divider {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
}

.keunggulan-shape-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 60px;
}

.keunggulan-shape-divider .shape-fill {
    fill: #90caf9; /* Warna untuk transisi ke bagian berikutnya */
}

/* Responsive Styling untuk Keunggulan */
@media (max-width: 992px) {
    .keunggulan-card {
        margin-bottom: 20px;
    }
}

@media (max-width: 768px) {
    .keunggulan-section {
        padding: 60px 0 100px;
    }

    .keunggulan-card {
        padding: 20px;
        margin-bottom: 20px;
    }

    .keunggulan-card h4 {
        font-size: 18px;
    }

    .keunggulan-card p {
        font-size: 14px;
    }

    .keunggulan-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
}

@media (max-width: 576px) {
    .keunggulan-section {
        padding: 40px 0 80px;
    }

    .keunggulan-card {
        padding: 15px;
    }

    .keunggulan-card h4 {
        font-size: 16px;
    }

    .keunggulan-card p {
        font-size: 13px;
    }

    .keunggulan-icon {
        font-size: 2rem;
    }
}

/* GALERI FOTO STYLING */
.gallery-section {
    position: relative;
    background: linear-gradient(180deg, #bbdefb 0%, #90caf9 50%, #b3e5fc 100%);
    color: #003366;
    padding: 100px 0 150px;
    overflow: hidden;
    margin-top: 0; /* Menghapus margin negatif */
}

.gallery-section .highlight {
    color: #1e7fd6;
    position: relative;
    display: inline-block;
}

.gallery-section .highlight::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 8px;
    background-color: rgba(30, 127, 214, 0.2);
    bottom: 0;
    left: 0;
    z-index: -1;
}

.gallery-section h2 {
    font-weight: 800;
    color: #003366;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 40px;
}

/* Desktop Gallery Grid */
.desktop-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    max-width: 1100px;
    margin: 0 auto;
}

.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    aspect-ratio: 3/4;
    background: #fff;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.gallery-item:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.gallery-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.gallery-item:hover .gallery-img {
    transform: scale(1.08);
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 51, 102, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 16px;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

.gallery-icon {
    width: 50px;
    height: 50px;
    background: #ffcc00;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    transform: scale(0);
    transition: transform 0.3s ease;
}

.gallery-item:hover .gallery-icon {
    transform: scale(1);
}

.gallery-icon i {
    color: #003366;
    font-size: 20px;
}

/* Mobile Carousel Gallery */
.mobile-gallery {
    max-width: 80%;
    margin: 0 auto;
}

.gallery-slide-item {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    aspect-ratio: 3/4;
    background: #fff;
    transition: transform 0.3s ease;
}

.gallery-slide-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#galleryCarousel .carousel-indicators {
    margin-bottom: -20px;
}

#galleryCarousel .carousel-indicators button {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.5);
    margin: 0 5px;
}

#galleryCarousel .carousel-indicators button.active {
    background-color: #1e7fd6;
}

#galleryCarousel .carousel-control-prev,
#galleryCarousel .carousel-control-next {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.8;
}

#galleryCarousel .carousel-control-prev {
    left: -50px;
}

#galleryCarousel .carousel-control-next {
    right: -50px;
}

/* Button Styling */
.btn-gallery {
    background-color: #1e7fd6;
    color: white;
    font-weight: 700;
    padding: 12px 30px;
    border-radius: 50px;
    border: none;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.btn-gallery:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
    background-color: #0065c1;
    color: white;
}

.gallery-shape-divider {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
}

.gallery-shape-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 60px;
}

.gallery-shape-divider .shape-fill {
    fill: #b3e5fc;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .gallery-section {
        padding: 80px 0 130px;
    }
    
    .mobile-gallery {
        max-width: 90%;
    }
    
    #galleryCarousel .carousel-control-prev {
        left: -20px;
    }
    
    #galleryCarousel .carousel-control-next {
        right: -20px;
    }
}

@media (max-width: 768px) {
    .gallery-section {
        padding: 60px 0 120px;
    }
    
    .mobile-gallery {
        max-width: 100%;
    }
    
    #galleryCarousel .carousel-control-prev {
        left: 10px;
    }
    
    #galleryCarousel .carousel-control-next {
        right: 10px;
    }
}

    /* TESTIMONI SECTION STYLING */
.testimoni-section {
    background: linear-gradient(180deg, #b3e5fc 0%, #c5e7ff 50%, #d0ecff 100%);
    padding: 100px 0 120px;
    position: relative;
    overflow: hidden;
}

.testimoni-section .highlight {
    color: #1e7fd6;
    position: relative;
    display: inline-block;
}

.testimoni-section .highlight::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 8px;
    background-color: rgba(30, 127, 214, 0.2);
    bottom: 0;
    left: 0;
    z-index: -1;
}

.testimoni-section h2 {
    font-weight: 800;
    color: #003366;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 40px;
}

/* TESTIMONI SLIDE STYLING */
.testimoni-slide {
    padding: 20px 0;
}

/* KARTU TESTIMONI STYLING */
.testimoni-row {
    margin: 0 -15px;
}

.testimoni-col {
    padding: 15px;
    transition: all 0.5s ease;
}

.testimoni-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: auto;
    min-height: 400px;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0, 51, 102, 0.05);
    position: relative;
    opacity: 1;
    transform: translateY(0);
}

.testimoni-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
}

/* FOTO USER STYLING */
.testimoni-img {
    width: 85px;
    height: 85px;
    object-fit: cover;
    border-radius: 50%;
    margin: 0 auto 20px;
    border: 4px solid #ffcc00;
    box-shadow: 0 5px 15px rgba(255, 204, 0, 0.2);
    transition: transform 0.3s ease;
}

.testimoni-card:hover .testimoni-img {
    transform: scale(1.05);
}

/* TEKS TESTIMONI */
.testimoni-text {
    font-size: 16px;
    color: #4a5568;
    margin-bottom: 20px;
    font-style: italic;
    line-height: 1.6;
    height: 120px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
}

/* TOMBOL BACA SELENGKAPNYA */
.read-more-btn {
    color: #1e7fd6;
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    cursor: pointer;
    outline: inherit;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s ease;
}

.read-more-btn:hover {
    color: #0056b3;
    text-decoration: underline;
}

/* USER INFO */
.user-info {
    margin-top: auto;
    padding-top: 15px;
    border-top: 1px dashed rgba(0, 0, 0, 0.1);
}

/* RATING BINTANG */
.testimoni-stars {
    color: #ffcc00;
    font-size: 20px;
    letter-spacing: 2px;
    margin-bottom: 8px;
}

/* NAMA PELANGGAN */
.testimoni-name {
    display: block;
    font-weight: 700;
    color: #003366;
    margin-bottom: 5px;
    font-size: 18px;
}

/* PRODUCT INFO */
.testimoni-product {
    display: block;
    font-size: 14px;
    color: #6c757d;
}

/* MODAL STYLING */
.modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 20px 25px;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    padding: 15px 25px;
}

.modal-user-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #ffcc00;
}

/* CAROUSEL STYLING - PEMBARUAN */
#testimoniCarousel {
    /* Menambah padding pada carousel untuk memberi ruang pada navigasi */
    padding: 10px 60px;
}

#testimoniCarousel .carousel-inner {
    overflow: visible;
}

/* Pembaruan: Tombol navigasi diberi jarak lebih terutama pada mobile */
#testimoniCarousel .carousel-control-prev,
#testimoniCarousel .carousel-control-next {
    width: 50px;
    height: 50px;
    background-color: #1e7fd6;
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.9;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

#testimoniCarousel .carousel-control-prev {
    left: 0;
}

#testimoniCarousel .carousel-control-next {
    right: 0;
}

#testimoniCarousel .carousel-control-prev:hover,
#testimoniCarousel .carousel-control-next:hover {
    background-color: #0065c1;
    opacity: 1;
}

#testimoniCarousel .carousel-indicators {
    bottom: -50px;
}

#testimoniCarousel .carousel-indicators button {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: rgba(30, 127, 214, 0.3);
    margin: 0 5px;
    transition: all 0.3s ease;
}

#testimoniCarousel .carousel-indicators button.active {
    background-color: #1e7fd6;
    width: 12px;
    height: 12px;
}

/* Animasi Transisi untuk Carousel */
.carousel-item {
    transition: transform 0.6s ease-in-out;
}

/* RESPONSIVE STYLING - PEMBARUAN */
@media (max-width: 1199.98px) {
    .testimoni-card {
        min-height: 430px;
    }
}

@media (max-width: 991.98px) {
    .testimoni-section {
        padding: 80px 0 100px;
    }
    
    .testimoni-card {
        min-height: 380px;
    }
    
    #testimoniCarousel {
        padding: 10px 50px;
    }
}

@media (max-width: 767.98px) {
    .testimoni-section {
        padding: 60px 0 80px;
    }
    
    .testimoni-card {
        min-height: 350px;
        padding: 25px;
        max-width: 350px;
        margin: 0 auto;
    }
    
    /* Pembaruan: Menambah jarak antara navigasi dan konten pada mobile */
    #testimoniCarousel {
        padding: 0 50px;
    }
    
    #testimoniCarousel .carousel-control-prev {
        left: 5px;
    }
    
    #testimoniCarousel .carousel-control-next {
        right: 5px;
    }
    
    #testimoniCarousel .carousel-control-prev,
    #testimoniCarousel .carousel-control-next {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 575.98px) {
    .testimoni-section {
        padding: 50px 0 70px;
    }
    
    .testimoni-card {
        min-height: 330px;
        padding: 20px;
    }
    
    .testimoni-img {
        width: 70px;
        height: 70px;
    }
    
    .testimoni-text {
        font-size: 15px;
        height: 110px;
    }
    
    .testimoni-stars {
        font-size: 16px;
    }
    
    .testimoni-name {
        font-size: 16px;
    }
    
    /* Pembaruan: Lebih memperbesar jarak pada versi mobile kecil */
    #testimoniCarousel {
        padding: 0 40px;
    }
    
    #testimoniCarousel .carousel-control-prev {
        left: 0;
    }
    
    #testimoniCarousel .carousel-control-next {
        right: 0;
    }
    
    #testimoniCarousel .carousel-control-prev,
    #testimoniCarousel .carousel-control-next {
        width: 35px;
        height: 35px;
    }
}

/* MAPS SECTION (yang Diperbarui) */
.maps-section {
    position: relative;
    background: linear-gradient(180deg, #d0ecff 0%, #c5e7ff 50%, #bbdefb 100%);
    padding: 100px 0 150px;
    overflow: hidden;
    margin-top: 0;
    margin-bottom: 0;
}

/* Style judul dengan highlight yang konsisten */
.maps-section .highlight {
    color: #1e7fd6;
    position: relative;
    display: inline-block;
}

.maps-section .highlight::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 8px;
    background-color: rgba(30, 127, 214, 0.2);
    bottom: 0;
    left: 0;
    z-index: -1;
}

.maps-section h2 {
    font-weight: 800;
    color: #003366;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 15px;
}

/* === CARD ALAMAT YANG DIPERBARUI === */
.address-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: 100%;
    border: 1px solid rgba(0, 51, 102, 0.05);
    display: flex;
    flex-direction: column;
}

/* Icon header untuk address card */
.address-icon {
    font-size: 36px;
    color: #1e7fd6;
    margin-bottom: 10px;
}

/* Efek Hover yang Responsif */
.address-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
}

/* Info toko styling */
.store-info {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px dashed rgba(0, 0, 0, 0.1);
    text-align: left;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    color: #4a5568;
}

.info-item i {
    color: #1e7fd6;
    width: 20px;
}

/* === GOOGLE MAPS CONTAINER === */
.maps-container {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 51, 102, 0.05);
    height: 100%;
}

/* Iframe khusus untuk maps */
.maps-iframe {
    border-radius: 16px;
    height: 100%;
    min-height: 450px;
}

/* Efek Hover pada Maps yang Lebih Halus */
.maps-container:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
}

/* === BADGE INFO BUKA === */
.store-badge {
    font-size: 14px;
    padding: 8px 15px;
    border-radius: 30px;
    background-color: #ffcc00;
    color: #003366;
    font-weight: 600;
    display: inline-block;
    transition: all 0.3s ease;
}

.store-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 10px rgba(255, 204, 0, 0.2);
}

/* Tombol kontak */
.btn-contact {
    background: #1e7fd6;
    color: white;
    border-radius: 30px;
    padding: 12px 25px;
    font-weight: 600;
    font-size: 16px;
    box-shadow: 0 5px 15px rgba(30, 127, 214, 0.3);
    transition: all 0.3s ease;
    border: none;
}

.btn-contact:hover {
    background: #0065c1;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(30, 127, 214, 0.4);
}

/* Shape divider untuk transisi ke footer */
.maps-shape-divider {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
    transform: rotate(180deg);
}

.maps-shape-divider svg {
    position: relative;
    display: block;
    width: calc(100% + 1.3px);
    height: 60px;
}

.maps-shape-divider .shape-fill {
    fill: #FFFFFF; /* Warna footer - ubah sesuai kebutuhan */
}



/* RESPONSIVE STYLING */
@media (max-width: 991.98px) {
    .maps-section {
        padding: 80px 0 120px;
    }
    
    .address-card {
        margin-bottom: 30px;
    }
    
    .maps-iframe {
        min-height: 400px;
    }
}

@media (max-width: 767.98px) {
    .maps-section {
        padding: 60px 0 100px;
    }
    
    .address-icon {
        font-size: 30px;
    }
    
    .address-card {
        text-align: center;
        padding: 25px;
    }
    
    .store-info {
        text-align: center;
    }
    
    .info-item {
        justify-content: center;
    }
    
    .maps-iframe {
        min-height: 350px;
    }
    
    .maps-shape-divider svg {
        height: 40px;
    }
}

@media (max-width: 575.98px) {
    .maps-section {
        padding: 50px 0 80px;
    }
    
    .address-card {
        padding: 20px;
    }
    
    .maps-iframe {
        min-height: 300px;
    }
}

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            box-shadow: 0px 5px 15px rgba(97, 178, 255, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            z-index: 1000;
        }
        
        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0px 8px 20px rgba(97, 178, 255, 0.7);
            color: white;
        }
        
        .back-to-top i {
            font-size: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .back-to-top {
                width: 45px;
                height: 45px;
                font-size: 16px;
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>

<body>

    <?php include '../assets/navbar.php'; ?>

<!-- Hero Section yang Diperbarui (Tanpa Diskon) -->
<section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="fw-bold display-4 animate__animated animate__fadeInUp">Temukan Style Unikmu di <span class="highlight">hobiBekasan</span></h1>
                    <p class="lead animate__animated animate__fadeInUp animate__delay-1s">Fashion berkualitas, harga terjangkau, gaya tak terbatas. Karena fashion terbaik adalah yang mencerminkan dirimu.</p>
                    <!-- <div class="hero-buttons animate__animated animate__fadeInUp animate__delay-2s">
                        <a href="kategori.php" class="btn btn-primary btn-hero">Telusuri Koleksi</a>
                        <a href="#gallery" class="btn btn-outline-light btn-hero-secondary">Lihat Galeri</a>
                    </div> -->
                    <div class="hero-features animate__animated animate__fadeInUp animate__delay-3s">
                        <div class="feature-item">
                            <i class="fas fa-truck"></i>
                            <span>Pengiriman Cepat</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Kualitas Terjamin</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-tags"></i>
                            <span>Harga Terbaik</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 hero-image-container">
                    <div class="hero-image-wrapper animate__animated animate__fadeInRight">
                        <img src="../assets/img/logo.jpg" alt="Logo Toko" class="img-fluid hero-image">
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-shape-divider">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            </svg>
        </div>
    </section>

    <!-- AI Random Recommendation Section -->
    <?php
    // Include helper untuk random recommendation
    require_once '../ai/random_integration_helper.php';
    
    // Cek user login
    if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
        $userId = $_SESSION['user']['user_id'];
        $recommendation = getRandomRecommendation($userId);
        
        if ($recommendation['success']) {
            ?>
            <section class="random-recommendation-section py-5" style="background: #ffffff;">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-12 text-center mb-4">
                            <h2 class="fw-bold text-dark animate__animated animate__fadeInUp">
                                <i class="fas fa-robot me-2"></i>
                                Rekomendasi AI Khusus Untuk Anda
                            </h2>
                            <p class="text-muted animate__animated animate__fadeInUp animate__delay-1s">
                                Produk pilihan cerdas berdasarkan preferensi <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!
                            </p>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 mb-4">
                            <?php echo displayRecommendationCard($recommendation); ?>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-12 text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-sync-alt me-1"></i>
                                Produk akan berbeda setiap kali Anda refresh halaman
                            </small>
                        </div>
                    </div>
                </div>
            </section>
            
            <style>
            .random-recommendation-section {
                background: #ffffff;
                border-bottom: 1px solid #dee2e6;
            }
            
            .recommendation-card {
                border: none;
                border-radius: 12px;
                overflow: hidden;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }
            
            .recommendation-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 12px 25px rgba(0,123,255,0.15);
            }
            
            .product-image-container {
                position: relative;
                height: 200px;
                overflow: hidden;
                background: #f8f9fa;
            }
            
            .product-image {
                width: 100%;
                height: 100%;
                object-fit: contain;
                transition: transform 0.3s ease;
            }
            
            .product-image:hover {
                transform: scale(1.05);
            }
            
            .recommendation-badge {
                position: absolute;
                top: 10px;
                left: 10px;
                z-index: 10;
                font-size: 0.75rem;
                padding: 4px 8px;
                border-radius: 20px;
                font-weight: 600;
            }
            
            .card-title {
                font-size: 1rem;
                font-weight: 600;
                line-height: 1.4;
                margin-bottom: 8px;
            }
            
            .card-text {
                font-size: 1.25rem;
                font-weight: 700;
                margin-bottom: 8px;
            }
            
            .add-to-cart-btn {
                transition: all 0.3s ease;
                border-radius: 8px;
            }
            
            .add-to-cart-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0,123,255,0.4);
            }
            
            .shadow-sm {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            </style>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add to cart functionality
                const addToCartBtn = document.querySelector('.add-to-cart-btn');
                if (addToCartBtn) {
                    addToCartBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const productId = this.dataset.productId;
                        
                        // Track interaction
                        trackRecommendationClick(<?php echo $userId; ?>, productId);
                        
                        // Show success message
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check me-1"></i> Ditambahkan!';
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-success');
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.classList.remove('btn-success');
                            this.classList.add('btn-outline-primary');
                        }, 2000);
                        
                        // Add actual cart logic here - submit form to keranjang.php
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'keranjang.php';
                        
                        const productIdInput = document.createElement('input');
                        productIdInput.type = 'hidden';
                        productIdInput.name = 'product_id';
                        productIdInput.value = productId;
                        
                        const quantityInput = document.createElement('input');
                        quantityInput.type = 'hidden';
                        quantityInput.name = 'quantity';
                        quantityInput.value = '1';
                        
                        const addToCartInput = document.createElement('input');
                        addToCartInput.type = 'hidden';
                        addToCartInput.name = 'add_to_cart';
                        addToCartInput.value = '1';
                        
                        form.appendChild(productIdInput);
                        form.appendChild(quantityInput);
                        form.appendChild(addToCartInput);
                        
                        document.body.appendChild(form);
                        form.submit();
                    });
                }
            });
            
            function trackRecommendationClick(userId, productId) {
                // Track user interaction with recommendation
                fetch('http://localhost:5000/api/track-click', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        product_id: productId,
                        action: 'click'
                    })
                }).catch(err => console.log('Tracking error:', err));
            }
            </script>
            <?php
        }
    }
    ?>

    <!-- Keunggulan Toko Section -->
<section class="keunggulan-section">
    <div class="container position-relative">
        <h2 class="mb-5 fw-bold text-center animate__animated animate__fadeInUp">Kenapa Memilih <span class="highlight">hobibekasan</span>?</h2>
        <p class="text-center mb-5 animate__animated animate__fadeInUp animate__delay-1s" style="max-width: 800px; margin-left: auto; margin-right: auto;">
            hobibekasan adalah pilihan tepat untuk fashion berkualitas dengan harga terjangkau. Kami menghadirkan pengalaman belanja thrift yang modern, aman, dan menyenangkan dengan dukungan teknologi AI untuk rekomendasi produk yang dipersonalisasi.
        </p>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="keunggulan-card animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="keunggulan-icon">
                    <i class="fas fa-shoe-prints"></i>
                    </div>
                    <h4>Kualitas Terjamin</h4>
                    <p>Kami hanya menjual barang thrift pilihan dengan kondisi terbaik dan telah melewati proses seleksi ketat untuk memastikan kualitas.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="keunggulan-card animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="keunggulan-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h4>Harga Terjangkau</h4>
                    <p>Dapatkan fashion berkualitas dengan harga ramah di kantong. Style keren tanpa perlu menguras dompet!</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="keunggulan-card animate__animated animate__fadeInUp animate__delay-3s">
                    <div class="keunggulan-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h4>Pengiriman Cepat</h4>
                    <p>Barang dikirim dengan cepat dan aman sampai ke depan pintu rumahmu. Lacak pesanan dengan mudah!</p>
                </div>
            </div>
        </div>
        <div class="row mt-4 justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="keunggulan-card animate__animated animate__fadeInUp animate__delay-4s">
                    <div class="keunggulan-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <h4>Ramah Lingkungan</h4>
                    <p>Berbelanja thrift berarti ikut mendukung fashion berkelanjutan dan mengurangi limbah tekstil di lingkungan.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="keunggulan-card animate__animated animate__fadeInUp animate__delay-5s">
                    <div class="keunggulan-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Layanan Pelanggan</h4>
                    <p>Tim kami siap membantu dengan ramah dan responsif untuk memastikan pengalaman belanja terbaikmu.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="keunggulan-card animate__animated animate__fadeInUp animate__delay-6s">
                    <div class="keunggulan-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h4>Rekomendasi AI</h4>
                    <p>Sistem rekomendasi cerdas yang dipersonalisasi berdasarkan preferensi dan riwayat belanja Anda untuk pengalaman yang lebih baik.</p>
                </div>
            </div>
        </div>
    </div>
</section>

   <!-- Galeri Foto Section -->
<section class="gallery-section" id="gallery">
    <div class="container position-relative">
        <h2 class="text-center mb-5 fw-bold animate__animated animate__fadeInUp">
            <span class="highlight">Galeri</span> Koleksi Kami
        </h2>
        
        <!-- Desktop Gallery (3 kolom untuk tampilan desktop) -->
        <div class="desktop-gallery d-none d-lg-grid animate__animated animate__fadeInUp animate__delay-1s">
            <?php
            $images = ["foto1.jpg", "foto2.jpg", "foto3.jpg", "foto4.jpg", "foto5.jpg", "foto6.jpg"];
            foreach ($images as $img) {
                echo "
                <div class='gallery-item'>
                    <img src='../assets/img/$img' class='gallery-img' alt='Galeri Koleksi ItsYourThriftt' onclick='openGalleryModal(\"../assets/img/$img\", \"foto$img\")' style='cursor: pointer;'>
                    <div class='gallery-overlay'>
                        <div class='gallery-icon'><i class='fas fa-search'></i></div>
                    </div>
                </div>
                ";
            }
            ?>
        </div>
        
        <!-- Mobile Gallery (Carousel untuk tampilan mobile) -->
        <div id="galleryCarousel" class="carousel slide mobile-gallery d-block d-lg-none animate__animated animate__fadeInUp animate__delay-1s" data-bs-ride="carousel" data-bs-interval="3000">
            <div class="carousel-inner">
                <?php
                $totalImages = count($images);
                for ($i = 0; $i < $totalImages; $i++) {
                    $activeClass = ($i === 0) ? 'active' : '';
                    echo "<div class='carousel-item $activeClass'>
                            <div class='gallery-slide-item'>
                                <img src='../assets/img/{$images[$i]}' class='gallery-slide-img' alt='Galeri Koleksi ItsYourThriftt' onclick='openGalleryModal(\"../assets/img/{$images[$i]}\", \"foto{$i}\")' style='cursor: pointer;'>
                                <div class='gallery-overlay'>
                                    <div class='gallery-icon'><i class='fas fa-search'></i></div>
                                </div>
                            </div>
                          </div>";
                }
                ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
            <div class="carousel-indicators">
                <?php
                for ($i = 0; $i < $totalImages; $i++) {
                    $activeClass = ($i === 0) ? 'active' : '';
                    echo "<button type='button' data-bs-target='#galleryCarousel' data-bs-slide-to='$i' class='$activeClass' aria-current='true' aria-label='Slide ".($i+1)."'></button>";
                }
                ?>
            </div>
        </div>
        
        <!-- <div class="text-center mt-5 animate__animated animate__fadeInUp animate__delay-2s">
            <a href="kategori.php" class="btn btn-gallery">Lihat Semua Koleksi <i class="fas fa-arrow-right ms-2"></i></a>
        </div> -->
    </div>
    <div class="gallery-shape-divider">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
        </svg>
    </div>
</section>

<!-- Modal untuk popup gambar galeri -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="galleryModalLabel">Galeri Koleksi Kami</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="galleryModalImage" src="" alt="" style="max-width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px;">
                <h6 id="galleryModalTitle" class="mt-3"></h6>
            </div>
        </div>
    </div>
</div>

<script>
function openGalleryModal(imageSrc, title) {
    document.getElementById('galleryModalImage').src = imageSrc;
    document.getElementById('galleryModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('galleryModal')).show();
}
</script>

<!-- Spacer Section untuk memisahkan Galeri dan Testimoni -->
<section class="spacer-section" style="padding: 40px 0; background: linear-gradient(180deg, #b3e5fc 0%, #c5e7ff 100%);">
    <div class="container">
        <div class="text-center">
            <div class="d-inline-flex align-items-center gap-3">
                <div class="line-separator" style="height: 2px; width: 60px; background: linear-gradient(90deg, transparent, #1e7fd6, transparent);"></div>
                <i class="fas fa-quote-left text-primary" style="font-size: 24px; color: #1e7fd6;"></i>
                <div class="line-separator" style="height: 2px; width: 60px; background: linear-gradient(90deg, transparent, #1e7fd6, transparent);"></div>
            </div>
        </div>
    </div>
</section>

<!-- Testimoni Section -->
<section class="testimoni-section">
    <div class="container">
        <h2 class="text-center mb-5 fw-bold animate__animated animate__fadeInUp">
            <span class="highlight">Apa Kata</span> Pelanggan Kami?
        </h2>
        
        <div id="testimoniCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-inner">
                <?php
                // Koneksi database
                if (!isset($conn)) {
                    require_once '../config/config.php';
                }
                
                // Fungsi untuk memotong teks
                function truncateText($text, $limit = 150) {
                    if (strlen($text) <= $limit) {
                        return $text;
                    }
                    return substr($text, 0, $limit) . '...';
                }
                
                // Query untuk testimoni
                $query = "SELECT pr.*, p.name, u.username, u.profile_photo
                FROM product_reviews pr
                JOIN products p ON pr.product_id = p.product_id
                JOIN users u ON pr.user_id = u.id";
                
                $result = mysqli_query($conn, $query);

$result = mysqli_query($conn, $query);
                $reviewCount = mysqli_num_rows($result);
                $allReviews = [];
                $modalContent = '';
                
                if ($reviewCount > 0) {
                    $reviewId = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['review_id'] = $reviewId++; // Tambahkan ID unik untuk modal
                        $allReviews[] = $row;
                        
                        // Buat konten modal untuk review ini
                        $modalContent .= '
                        <div class="modal fade" id="reviewModal'.$row['review_id'].'" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Review dari '.htmlspecialchars($row['username']).'</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="../assets/img/profiles/'.($row['profile_photo'] ? $row['profile_photo'] : 'default.png').'" 
                                                 class="modal-user-img me-3" alt="'.htmlspecialchars($row['username']).'">
                                            <div>
                                                <strong>'.htmlspecialchars($row['username']).'</strong>
                                                <div class="text-warning">';
                        
                        // Bintang rating
                        for ($i = 1; $i <= 5; $i++) {
                            $modalContent .= ($i <= $row['rating']) ? '★' : '☆';
                        }
                        
                        $modalContent .= '
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card p-3 mb-3">
                                            <p class="mb-0">'.htmlspecialchars($row['review']).'</p>
                                        </div>
                                        <small class="text-muted">Tentang: '.htmlspecialchars($row['name']).'</small>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                    
                    // Pembaruan: Menampilkan satu testimoni per slide
                    for ($i = 0; $i < $reviewCount; $i++) {
                        $review = $allReviews[$i];
                        $activeClass = ($i === 0) ? 'active' : '';
                        
                        echo '<div class="carousel-item ' . $activeClass . '">';
                        echo '<div class="testimoni-slide">';
                        echo '<div class="row testimoni-row justify-content-center">';
                        echo '<div class="col-lg-6 col-md-8 testimoni-col">';
                        echo '<div class="testimoni-card animate__animated animate__fadeIn">';
                        
                        // Foto profil
                        echo '<img src="../assets/img/profiles/'.($review['profile_photo'] ? $review['profile_photo'] : 'default.png').'" 
                               class="testimoni-img" alt="'.htmlspecialchars($review['username']).'">';
                        
                        // Teks review
                        echo '<div class="testimoni-text">
                                "'.htmlspecialchars(truncateText($review['review'])).'"
                              </div>';
                        
                        // Tombol baca selengkapnya
                        if (strlen($review['review']) > 150) {
                            echo '<button class="read-more-btn" data-bs-toggle="modal" data-bs-target="#reviewModal'.$review['review_id'].'">
                                    Baca Selengkapnya
                                  </button>';
                        }
                        
                        // Informasi user
                        echo '<div class="user-info">
                                <div class="testimoni-stars">';
                        
                        // Rating bintang
                        for ($star = 1; $star <= 5; $star++) {
                            echo ($star <= $review['rating']) ? '★' : '☆';
                        }
                        
                        echo '</div>
                              <strong class="testimoni-name">- '.htmlspecialchars($review['username']).'</strong>
                              <span class="testimoni-product">Tentang: '.htmlspecialchars($review['name']).'</span>
                            </div>';
                        
                        echo '</div>'; // Close testimoni-card
                        echo '</div>'; // Close testimoni-col
                        echo '</div>'; // Close row
                        echo '</div>'; // Close testimoni-slide
                        echo '</div>'; // Close carousel-item
                    }
                } else {
                    // Jika tidak ada review, tampilkan testimoni default
                    ?>
                    <div class="carousel-item active">
                        <div class="testimoni-slide">
                            <div class="row testimoni-row justify-content-center">
                                <div class="col-lg-6 col-md-8 testimoni-col">
                                    <div class="testimoni-card animate__animated animate__fadeIn">
                                        <img src="../assets/img/user1.jpg" class="testimoni-img" alt="User 1">
                                        <div class="testimoni-text">
                                            "Barang thrift-nya berkualitas banget! Harga murah tapi kualitas mantap."
                                        </div>
                                        <div class="user-info">
                                            <div class="testimoni-stars">
                                                ★★★★☆
                                            </div>
                                            <strong class="testimoni-name">- Dimas, Jakarta</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="carousel-item">
                        <div class="testimoni-slide">
                            <div class="row testimoni-row justify-content-center">
                                <div class="col-lg-6 col-md-8 testimoni-col">
                                    <div class="testimoni-card animate__animated animate__fadeIn">
                                        <img src="../assets/img/user2.jpg" class="testimoni-img" alt="User 2">
                                        <div class="testimoni-text">
                                            "Pengirimannya cepat, barang sesuai foto. Recommended!"
                                        </div>
                                        <div class="user-info">
                                            <div class="testimoni-stars">
                                                ★★★★★
                                            </div>
                                            <strong class="testimoni-name">- Siti, Bekasi</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="carousel-item">
                        <div class="testimoni-slide">
                            <div class="row testimoni-row justify-content-center">
                                <div class="col-lg-6 col-md-8 testimoni-col">
                                    <div class="testimoni-card animate__animated animate__fadeIn">
                                        <img src="../assets/img/user3.jpg" class="testimoni-img" alt="User 3">
                                        <div class="testimoni-text">
                                            "Senang bisa nemu toko ini, banyak pilihan fashion unik!"
                                        </div>
                                        <div class="user-info">
                                            <div class="testimoni-stars">
                                                ★★★★☆
                                            </div>
                                            <strong class="testimoni-name">- Albert, Bandung</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="testimoni-slide">
                            <div class="row testimoni-row justify-content-center">
                                <div class="col-lg-6 col-md-8 testimoni-col">
                                    <div class="testimoni-card animate__animated animate__fadeIn">
                                        <img src="../assets/img/user4.jpg" class="testimoni-img" alt="User 4">
                                        <div class="testimoni-text">
                                            "Packaging rapi dan bersih, sepatu yang datang juga masih bagus banget. Puas belanja di sini!"
                                        </div>
                                        <div class="user-info">
                                            <div class="testimoni-stars">
                                                ★★★★★
                                            </div>
                                            <strong class="testimoni-name">- Andi, Depok</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="testimoni-slide">
                            <div class="row testimoni-row justify-content-center">
                                <div class="col-lg-6 col-md-8 testimoni-col">
                                    <div class="testimoni-card animate__animated animate__fadeIn">
                                        <img src="../assets/img/user5.jpg" class="testimoni-img" alt="User 5">
                                        <div class="testimoni-text">
                                            "Adminnya fast response, bantuin pilih ukuran sampai pas. Barangnya original dan harga ramah."
                                        </div>
                                        <div class="user-info">
                                            <div class="testimoni-stars">
                                                ★★★★☆
                                            </div>
                                            <strong class="testimoni-name">- Maya, Tangerang</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- Tombol navigasi carousel yang diperbarui -->
            <button class="carousel-control-prev" type="button" data-bs-target="#testimoniCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimoniCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
            
            <!-- Indikator -->
            <div class="carousel-indicators">
                <?php
                $defaultReviewCount = 5;
                $indicatorCount = (isset($reviewCount) && $reviewCount > 0) ? $reviewCount : $defaultReviewCount;
                for ($i = 0; $i < $indicatorCount; $i++) {
                    echo '<button type="button" data-bs-target="#testimoniCarousel" data-bs-slide-to="'.$i.'" '.($i === 0 ? 'class="active"' : '').' aria-label="Slide '.($i+1).'"></button>';
                }
                ?>
            </div>
        </div>
        
        <!-- Cetak semua modal -->
        <?php echo isset($modalContent) ? $modalContent : ''; ?>
    </div>
</section>

    <!-- Maps Section (Diperbarui) -->
<section class="maps-section">
    <div class="container position-relative">
        <h2 class="text-center mb-4 fw-bold animate__animated animate__fadeInUp">
            <span class="highlight">Lokasi</span> Toko Kami
        </h2>
        <p class="intro-text text-center animate__animated animate__fadeInUp animate__delay-1s">
            Kunjungi langsung <span class="highlight-word">toko kami</span> dan temukan koleksi <span class="highlight-word">thrifting terbaik</span> untuk gaya unikmu!
        </p>

        <div class="row justify-content-center">
            <!-- Info Alamat dengan Card (Kiri) -->
            <div class="col-lg-5 mb-4">
                <div class="address-card p-4 animate__animated animate__fadeInLeft animate__delay-2s">
                    <div class="address-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h4 class="fw-bold mt-3">hobibekasan</h4>
                    <p class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>JL. Bintara Jaya Gang Masjid sebrang komplek Puri Idaman , kec. Bintara Jaya, Kota Bekasi, Jawa Barat</p>
                    <div class="store-info">
                        <div class="info-item">
                            <i class="fas fa-clock me-2"></i> 
                            <span>Buka Setiap Hari: 17.00 - 23.30 WIB</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone-alt me-2"></i>
                            <span>+62 812-8109-6157</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope me-2"></i>
                            <span>hobibekasan_@gmail.com</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="badge bg-warning text-dark store-badge">
                            <i class="fas fa-check-circle me-1"></i> Buka Hari Ini
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Google Maps (Kanan) -->
            <div class="col-lg-7">
                <div class="maps-container animate__animated animate__fadeInRight animate__delay-2s">
                    <iframe
                        class="maps-iframe"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.123612074674!2d106.94473757476408!3d-6.247437493740961!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698d0044da0669%3A0x63db5335f05b4b64!2sHobi%20Bekasan!5e0!3m2!1sid!2sid!4v1774324611883!5m2!1sid!2sid"
                        width="100%"
                        height="450"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>
        
        <!-- CTA ke WhatsApp -->
        <!-- <div class="text-center mt-5 animate__animated animate__fadeInUp animate__delay-3s">
            <a href="https://wa.me/6281234567890" class="btn btn-contact">
                <i class="fab fa-whatsapp me-2"></i> Hubungi Kami
            </a>
        </div> -->
    </div>
</section>

    <!-- Tombol Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

    <?php include '../assets/footer.php'; ?>

    <!-- Di bagian bawah sebelum </body> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Tambahkan ini di bawah sebelum tag penutup </body>
        document.addEventListener('DOMContentLoaded', function() {
            // Script untuk testimoni carousel
            var testimoniCarousel = document.getElementById('testimoniCarousel');
            var carousel = new bootstrap.Carousel(testimoniCarousel, {
                interval: 5000,
                wrap: true,
                touch: true
            });
            
            // Tambahkan efek transisi yang lebih halus
            testimoniCarousel.addEventListener('slide.bs.carousel', function(e) {
                var activeItem = e.relatedTarget;
                var items = document.querySelectorAll('.carousel-item');
                
                // Tambahkan kelas untuk animasi transisi
                items.forEach(function(item) {
                    item.classList.remove('active-sliding');
                });
                
                activeItem.classList.add('active-sliding');
            });
        });
        
            </script>

    <!-- AI Chatbot Widget -->
    <?php
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        echo '<script src="http://localhost:3001/widget.js"></script>';
    }
    ?>

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