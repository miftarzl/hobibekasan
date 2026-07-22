<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika user belum login ATAU bukan role 'user'
if (!isset($_SESSION['user'])) {  // <-- PERBAIKAN: TAMBAHKAN ) YANG HILANG
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu untuk melihat riwayat pembelian.";
    header("Location: login.php");
    exit();
}

// Validasi role
if ($_SESSION['user']['role'] !== 'user') { // Ganti 'user' sesuai field role di database
    session_unset();
    session_destroy();
    $_SESSION['error_message'] = "Akses ditolak. Silakan login sebagai user.";
    header("Location: login.php");
    exit();
}

// Database connection
require_once '../config/config.php';

// Ambil user_id dari session
$user_id = $_SESSION['user']['user_id'];

// Ambil data user
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Proses update profile jika ada request POST
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);

        // Update profile photo if uploaded
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($file_ext), $allowed)) {
                $new_filename = uniqid() . '.' . $file_ext;
                $upload_dir = '../assets/img/profiles/';

                // Create directory if not exists
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_dir . $new_filename)) {
                    // Delete old photo if not default
                    if (!empty($user['profile_photo'])) {
                        unlink("../assets/img/profiles/" . $user['profile_photo']);
                    }
                    // Update database with new photo
                    $update_query = "UPDATE users 
                    SET username=?, email=?, phone_number=?, address=?, profile_photo=? 
                    WHERE id=?";

                    $update_stmt = mysqli_prepare($conn, $update_query);

                    mysqli_stmt_bind_param($update_stmt, "sssssi",
                    $username,
                    $email,
                    $phone_number,
                    $address,
                    $new_filename,
                    $user_id
                    );
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_message = "Profile successfully updated!";

                        // Refresh user data
                        $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                        $user = mysqli_fetch_assoc($result);
                    } else {
                        $error_message = "Failed to update profile. Please try again.";
                    }
                } else {
                    $error_message = "Failed to upload image. Please try again.";
                }
            } else {
                $error_message = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed.";
            }
        } else {
            // Update without changing photo
            $update_query = "UPDATE users SET username = ?, email = ?, phone_number = ?, address = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssssi", $username, $email, $phone_number, $address, $user_id);
            
            if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
                $success_message = "Profile successfully updated!";
                
                // Refresh user data
                $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                $user = mysqli_fetch_assoc($result);
            
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        }
    }
    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            if ($new_password == $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_query = "UPDATE users SET password = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    $success_message = "Password successfully updated!";
                } else {
                    $error_message = "Failed to update password. Please try again.";
                }
            } else {
                $error_message = "New password and confirmation do not match.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - hobiBekasan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --blue-primary: #61b2ff;
            --blue-secondary: #1e7fd6;
            --blue-gradient: linear-gradient(135deg, #61b2ff 30%, #1e7fd6 70%);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .profile-header {
            background: var(--blue-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-photo-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto;
        }

        .profile-photo {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }

        .photo-edit-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--blue-secondary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .photo-edit-icon:hover {
            background: #0056b3;
            transform: scale(1.1);
        }

        .profile-username {
            font-size: 32px;
            font-weight: 700;
            margin-top: 15px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .profile-role {
            display: inline-block;
            font-size: 14px;
            background-color: rgba(255, 255, 255, 0.3);
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            font-size: 14px;
            background-color: #28a745;
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 10px;
        }

        .verification-badge.unverified {
            background-color: #dc3545;
        }

        .verification-badge i {
            margin-right: 5px;
        }

        .profile-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border: none;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .profile-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: var(--blue-gradient);
            color: white !important;
            font-weight: 700 !important;
            /* Bold text (600 is semi-bold, 700 is bold) */
            padding: 2.25rem 1.75rem;
            /* Significantly increased padding */
            border-bottom: none;
            font-size: 1.25rem;
            text-align: center;
            letter-spacing: 0.5px;
            /* Sedikit spacing antar huruf */
            text-transform: uppercase;
            /* Huruf kapital semua */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            /* Shadow halus */
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
            border: none;
            padding: 1rem 1.5rem;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link.active {
            color: var(--blue-secondary);
            border-bottom: 3px solid var(--blue-secondary);
            background: none;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--blue-primary);
            border-bottom: 3px solid var(--blue-primary);
        }

        .form-label {
            font-weight: 500;
            color: #495057;
        }

        .form-control {
            padding: 0.75rem;
            border-radius: 10px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--blue-primary);
            box-shadow: 0 0 0 0.25rem rgba(97, 178, 255, 0.25);
        }

        .btn-update {
            background: var(--blue-gradient);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(30, 127, 214, 0.3);
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(30, 127, 214, 0.4);
        }

        .info-item {
            display: flex;
            margin-bottom: 1.5rem;
        }

        .info-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: var(--blue-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0.2rem;
        }

        .info-value {
            font-weight: 500;
            font-size: 1.1rem;
            word-break: break-word;
        }

        .alert {
            border-radius: 10px;
            font-weight: 500;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-photo-container {
                width: 150px;
                height: 150px;
            }

            .profile-photo {
                width: 150px;
                height: 150px;
            }

            .profile-username {
                font-size: 26px;
            }

            .photo-edit-icon {
                width: 35px;
                height: 35px;
            }
        }

        /* Animation for alerts */
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert {
            animation: slideDown 0.3s ease forwards;
        }
    </style>
</head>

<body>
    <?php include '../assets/navbar.php'; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="profile-photo-container">
                        <img src="../assets/img/profiles/<?php echo $user['profile_photo']; ?>" alt="Profile Photo" class="profile-photo">
                        <div class="photo-edit-icon" id="changePhotoBtn">
                            <i class="bi bi-camera"></i>
                        </div>
                    </div>
                    <h1 class="profile-username"><?php echo $user['username']; ?></h1>
                    <div class="profile-role"><?php echo ucfirst($user['role']); ?></div>
                    <div class="verification-badge <?php echo $user['is_verified'] ? '' : 'unverified'; ?>">
                        <?php if ($user['is_verified']): ?>
                            <i class="bi bi-check-circle"></i> Akun Terverifikasi
                        <?php else: ?>
                            <i class="bi bi-x-circle"></i> Akun Tidak Terverifikasi
                        <?php endif; ?>
                    </div>
                    <p class="mt-3 text-white-50">Member sejak <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Alert Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="profile-card">
                    <div class="card-header text-center">
                        <span>Manajemen Profil</span>
                    </div>

                    <div class="card-body p-0">
                        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                    <i class="bi bi-person me-2"></i>Info Akun
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab" aria-controls="edit" aria-selected="false">
                                    <i class="bi bi-pencil me-2"></i>Ubah Profil
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                                    <i class="bi bi-shield-lock me-2"></i>Ganti Password
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-4" id="profileTabsContent">
                            <!-- Account Info Tab -->
                            <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="info-label">Username</div>
                                                <div class="info-value"><?php echo $user['username']; ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="info-label">Alamat Email</div>
                                                <div class="info-value"><?php echo $user['email']; ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="bi bi-telephone"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="info-label">No Telephone</div>
                                                <div class="info-value"><?php echo $user['phone_number']; ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="bi bi-geo-alt"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="info-label">Alamat</div>
                                                <div class="info-value"><?php echo $user['address']; ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-icon">
                                                <i class="bi bi-calendar-check"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="info-label">Akun Dibuat</div>
                                                <div class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Profile Tab -->
                            <div class="tab-pane fade" id="edit" role="tabpanel" aria-labelledby="edit-tab">
                                <form action="profile.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="update_profile" value="1">

                                    <div class="mb-3">
                                        <label for="profile_photo" class="form-label">Foto Profil</label>
                                        <input type="file" class="form-control" id="profile_photo" name="profile_photo">
                                        <div class="form-text">Upload a new profile photo (JPG, JPEG, PNG or GIF).</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Alamat Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone_number" class="form-label">No Telephone</label>
                                        <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo $user['phone_number']; ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">Alamat</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user['address']; ?></textarea>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-update btn-primary">Perbaharui Profil</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <form action="profile.php" method="POST">
                                    <input type="hidden" name="update_password" value="1">

                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Password Lama</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-update btn-primary">Perbaharui Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>

    <!-- Hidden form for changing profile photo -->
    <form id="photoForm" action="profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="hidden" name="update_profile" value="1">
        <input type="file" id="hiddenPhotoInput" name="profile_photo">
        <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
        <input type="hidden" name="email" value="<?php echo $user['email']; ?>">
        <input type="hidden" name="phone_number" value="<?php echo $user['phone_number']; ?>">
        <input type="hidden" name="address" value="<?php echo $user['address']; ?>">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quick change profile photo
        document.getElementById('changePhotoBtn').addEventListener('click', function() {
            document.getElementById('hiddenPhotoInput').click();
        });

        document.getElementById('hiddenPhotoInput').addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('photoForm').submit();
            }
        });

        // Auto-dismiss alerts after 5 seconds
        window.setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                if (alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);
    </script>
</body>

</html>