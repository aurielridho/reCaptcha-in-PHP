<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recaptchaSecretKey = '6LeHOconAAAAAOrI6DPSMGNvHGFnb2_BNFWPwbxl';
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Verifikasi reCAPTCHA response
    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptchaData = [
        'secret' => $recaptchaSecretKey,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $recaptchaOptions = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($recaptchaData)
        ]
    ];

    $recaptchaContext = stream_context_create($recaptchaOptions);
    $recaptchaResult = file_get_contents($recaptchaUrl, false, $recaptchaContext);
    $recaptchaResultData = json_decode($recaptchaResult);

    if ($recaptchaResultData->success) {
        $email = $_POST["email"];

        // Jika email adalah "admin", role sebagai "premium"
        $role = ($email === "admin") ? "premium" : "user";

        // ... validasi email ...

        if ($_FILES["file"]["error"] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png'];

            if ($role === "premium") {
                $allowedTypes[] = 'application/pdf';
            }

            if (in_array($_FILES["file"]["type"], $allowedTypes)) {
                $targetDir = "fileuploads/";
                $targetFile = $targetDir . basename($_FILES["file"]["name"]);

                if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
                    $pdo = new PDO('mysql:host=localhost;dbname=fileuploads', 'root');

                    // Implementasi RBAC
                    if ($role === "premium") {
                        // Izinkan akses hanya untuk peran "premium"
                        $stmt = $pdo->prepare("INSERT INTO fileuploads (email, file_name, role) VALUES (?, ?, ?)");
                        $stmt->execute([$email, $_FILES["file"]["name"], $role]);

                        echo "File berhasil diunggah dan data disimpan di database.";
                    } else {
                        echo "Hanya pengguna dengan role premium yang dapat mengunggah file PDF.";
                    }
                } else {
                    echo "Gagal mengunggah file.";
                }
            } else {
                echo "Hanya file JPEG, PNG, dan PDF yang diizinkan.";
            }
        } else {
            echo "Terjadi kesalahan saat mengunggah file.";
        }
    } else {
        echo "reCAPTCHA verification failed.";
    }
}
?>
