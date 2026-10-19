<?php
session_start();
require_once "conn.php";

$_SESSION['errors'] = [];
$_SESSION['success'] = [];

$newemail = trim($_POST['email'] ?? '');
$newname = trim($_POST['fullname'] ?? '');
$newpassword = trim($_POST['password'] ?? '');
$newpasswordconfirm = trim($_POST['confirmpassowrd'] ?? '');
$newphone = trim($_POST['phone'] ?? '');
$birth_day = trim($_POST['birth_date'] ?? '');

$patientfullname = trim($_SESSION['full_name'] ?? '');
$patientPhone = trim($_SESSION['phone'] ?? '');
$patientEmail = trim($_SESSION['email'] ?? '');
$patientBirthDate = trim($_SESSION['birth_date'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    header("Location: ../pages/login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| AVATAR
|--------------------------------------------------------------------------
*/
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $avatar_file = $_FILES['avatar_file'];

    if ($avatar_file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['errors'][] = "Could not upload profile photo.";
    } else {
        $image_info = @getimagesize($avatar_file['tmp_name']);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($avatar_file['name'], PATHINFO_EXTENSION));

        if ($image_info === false || !in_array($extension, $allowed_extensions, true)) {
            $_SESSION['errors'][] = "Please upload a valid image file.";
        } else {
            $upload_dir = realpath(__DIR__ . '/../images');

            if ($upload_dir === false) {
                $_SESSION['errors'][] = "Image folder was not found.";
            } else {
                $filename = 'patient_' . $user_id . '_' . time() . '.' . $extension;
                $target_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;

                if (!move_uploaded_file($avatar_file['tmp_name'], $target_path)) {
                    $_SESSION['errors'][] = "Could not save profile photo.";
                } else {
                    $avatar_path = '../../images/' . $filename;
                    $sql = "UPDATE patient SET avatar = ? WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $avatar_path, $user_id);

                    if ($stmt->execute()) {
                        $_SESSION['avatar'] = $avatar_path;
                        $_SESSION['success'][] = "Profile photo updated successfully.";
                    } else {
                        $_SESSION['errors'][] = "Error updating profile photo.";
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| EMAIL
|--------------------------------------------------------------------------
*/
if (!empty($newemail)) {
    if (!filter_var($newemail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errors'][] = "Invalid email format.";
    } elseif ($newemail === $patientEmail) {
        $_SESSION['errors'][] = "Please enter a different email.";
    } else {
        $checkSql = "SELECT user_id FROM patient WHERE email = ? AND user_id != ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $newemail, $user_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['errors'][] = "This email is already used by another account.";
        } else {
            $sql = "UPDATE patient SET email = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newemail, $user_id);

            if ($stmt->execute()) {
                $_SESSION['email'] = $newemail;
                $_SESSION['success'][] = "Email updated successfully.";
            } else {
                $_SESSION['errors'][] = "Error updating email.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FULL NAME
|--------------------------------------------------------------------------
*/
if (!empty($newname)) {
    if ($newname === $patientfullname) {
        $_SESSION['errors'][] = "Please enter a different name.";
    } else {
        $sql = "UPDATE patient SET full_name = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newname, $user_id);

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $newname;
            $_SESSION['success'][] = "Name updated successfully.";
        } else {
            $_SESSION['errors'][] = "Error updating name.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| PHONE
|--------------------------------------------------------------------------
*/
if (!empty($newphone)) {
    if ($newphone === $patientPhone) {
        $_SESSION['errors'][] = "Please enter a different phone number.";
    } else {
        $checkSql = "SELECT user_id FROM patient WHERE phone = ? AND user_id != ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("si", $newphone, $user_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['errors'][] = "This phone number is already used by another account.";
        } else {
            $sql = "UPDATE patient SET phone = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newphone, $user_id);

            if ($stmt->execute()) {
                $_SESSION['phone'] = $newphone;
                $_SESSION['success'][] = "Phone number updated successfully.";
            } else {
                $_SESSION['errors'][] = "Error updating phone number.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| BIRTH DATE
|--------------------------------------------------------------------------
*/
if (!empty($birth_day)) {
    if ($birth_day === $patientBirthDate) {
        $_SESSION['errors'][] = "Please enter a different birth date.";
    } elseif ($birth_day > date('Y-m-d')) {
        $_SESSION['errors'][] = "Birth date cannot be in the future.";
    } else {
        $sql = "UPDATE patient SET birth_date = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $birth_day, $user_id);

        if ($stmt->execute()) {
            $_SESSION['birth_date'] = $birth_day;
            $_SESSION['success'][] = "Birth date updated successfully.";
        } else {
            $_SESSION['errors'][] = "Error updating birth date.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| PASSWORD
|--------------------------------------------------------------------------
*/
if (!empty($newpassword) || !empty($newpasswordconfirm)) {
    if (empty($newpassword) || empty($newpasswordconfirm)) {
        $_SESSION['errors'][] = "Please fill both password fields.";
    } elseif ($newpassword !== $newpasswordconfirm) {
        $_SESSION['errors'][] = "Passwords do not match.";
    } elseif (strlen($newpassword) < 6) {
        $_SESSION['errors'][] = "Password must be at least 6 characters.";
    } else {
        $hashedPassword = password_hash($newpassword, PASSWORD_DEFAULT);

        $sql = "UPDATE patient SET password_hash = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'][] = "Password updated successfully.";
        } else {
            $_SESSION['errors'][] = "Error updating password.";
        }
    }
}

header("Location: ../pages/patient_dashboard.php");
exit();
?>
