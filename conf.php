<?php
date_default_timezone_set('Asia/Jakarta');
//koneksi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sik');
define('ENVIRONMENT', 'PRODUCTION'); // DEVELOPMENT || PRODUCTION
//user static bpjs
define('USERNAME', 'admin');
define('PASSWORD', 'bpjs');
define('HARI', '2'); // izinkan buka hari dari hari sekarang
define('BATAS', '1'); // izinkan buka hari dari hari sekarang



function bukakoneksi()
{
    $a = "<pre>";
    $response = array(
        'metadata' => array(
            'titile' => 'Configurasi Not Found !',
            'message' => 'Anda Belum Melakukan Configurasi !',
            'code' => 404
        )
    );
    $konektor = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME) or die("" . json_encode($response, true) . "");
    return $konektor;
}

function fetch_array($sql)
{
    $while = mysqli_fetch_array($sql);
    return $while;
}

function fetch_assoc($sql)
{
    $while = mysqli_fetch_assoc($sql);
    return $while;
}

function num_rows($sql)
{
    $while = mysqli_num_rows($sql);
    return $while;
}

function tutupkoneksi()
{
    global $konektor;
    mysqli_close($konektor);
}

function bukaquery($sql)
{
    $response = array(
        'metadata' => array(
            'message' => 'Data Sudah Pernah Di Buat Atau Sudah Ada !',
            'code' => 201
        )
    );
    $result = mysqli_query(bukakoneksi(), $sql) or die(mysqli_error(bukakoneksi()) . "" . json_encode($response) . "");
    return $result;
}

function getOne($sql)
{
    $hasil = bukaquery($sql);
    list($result) = fetch_array($hasil);
    return $result;
}

function escape($string)
{
    return mysqli_real_escape_string(bukakoneksi(), $string);
}

function noRegPoli($kd_poli, $tanggal)
{
    $no_reg_akhir = fetch_array(bukaquery("SELECT max(no_reg) FROM booking_registrasi WHERE kd_poli='$kd_poli' and tanggal_periksa='$tanggal'"));
    $no_urut_reg = substr($no_reg_akhir[0], 0, 3);
    $no_reg = sprintf('%03s', ($no_urut_reg + 1));
    return $no_reg;
}

function FormatTgl($format, $tanggal)
{
    return date($format, strtotime($tanggal));
}

function hariindo($x)
{
    $hari = FormatTgl("D", $x);

    switch ($hari) {
        case 'Sun':
            $hari_ini = "Akhad";
            break;

        case 'Mon':
            $hari_ini = "Senin";
            break;

        case 'Tue':
            $hari_ini = "Selasa";
            break;

        case 'Wed':
            $hari_ini = "Rabu";
            break;

        case 'Thu':
            $hari_ini = "Kamis";
            break;

        case 'Fri':
            $hari_ini = "Jumat";
            break;

        case 'Sat':
            $hari_ini = "Sabtu";
            break;

        default:
            $hari_ini = "Tidak di ketahui";
            break;
    }

    return $hari_ini;
}

function hash_pass($pass, $int)
{
    $options = [
        'cost' => $int,
    ];
    return password_hash($pass, PASSWORD_DEFAULT, $options);
}
function query($sql) {
    global $connection;
    $query = mysqli_query($connection, $sql);
    confirm($query);
    return $query;
}
function getToken()
{
    global $username, $password;
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

    // Create token payload as a JSON string
    $payload = json_encode(['username' => USERNAME, 'password' => PASSWORD, 'date' => strtotime(date('Y-m-d H:')) * 1000]);

    // Encode Header to Base64Url String
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

    // Encode Payload to Base64Url String
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'b155m1774H', true);

    // Encode Signature to Base64Url String
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    return $jwt;
}
// Get date and time
date_default_timezone_set('Asia/Jakarta');
$month      = date('Y-m');
$date       = date('Y-m-d');
$time       = date('H:i:s');
$date_time  = date('Y-m-d H:i:s');

if (ENVIRONMENT == 'PRODUCTION') {
    error_reporting(0);
}
