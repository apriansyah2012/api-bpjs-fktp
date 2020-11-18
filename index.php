<?php

/***
 * SIMRS Khanza JKN Mobile API from version 0.2
 * About : Simple JKN Mobile API for SIMKES Khanza based on A. Fauzan RS Kemayoran Jakarta Pusa initial script.
 * Last modified: 23 June 2020
 * Author : A. Fauzan
 ***/
require_once 'conf.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
$url = isset($_GET['url']) ? $_GET['url'] : '/';
$url = explode("/", $url);
$header = apache_request_headers();
$method = $_SERVER['REQUEST_METHOD'];

//metode GET
if ($method == 'GET' && !empty($header['x-username'])) {
    $hash_user = hash_pass($header['x-username'], 12);
    $hash_pass = hash_pass($header['x-password'], 12);
    switch ($url[0]) {

        case "auth":
            //verivikasi user dan pass
            if (!empty($header['x-password'])) {
                if (password_verify(USERNAME, $hash_user) and password_verify(PASSWORD, $hash_pass)) {
                    $response = array(
                        'response' => array(
                            'token' => getToken()
                        ),
                        'metadata' => array(
                            'message' => 'Ok',
                            'code' => 200
                        )
                    );
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Username Atau Password Tidak Sesuai',
                            'code' => 201
                        )
                    );
                }
            }
            break;

        case "antrean":
            //status mengambil dari data registrasi berdasarkan poli dan tgl periksa
            if (!empty($url[1]) and $url[1] == "status") {
                if (password_verify(USERNAME, $hash_user) and $header['x-token'] == getToken() and !empty($url[2]) and !empty($url[3])) {
                    $data = fetch_array(bukaquery("SELECT 
                    poliklinik.nm_poli,
                    COUNT(reg_periksa.kd_poli) as total_antrean,
                    CONCAT(00,COUNT(reg_periksa.kd_poli)) as antrean_panggil,
                    SUM(CASE WHEN reg_periksa.stts!='Sudah' THEN 1 ELSE 0 END) as sisa_antrean,
                    ('Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke bagian Pendaftaran atau Perawat Poli, Terima Kasih ..') as keterangan
                    FROM reg_periksa 
                    INNER JOIN poliklinik ON poliklinik.kd_poli=reg_periksa.kd_poli
                    INNER JOIN maping_poliklinik_pcare ON maping_poliklinik_pcare.kd_poli_rs=reg_periksa.kd_poli
                    WHERE reg_periksa.tgl_registrasi='" . $url[3] . "' AND maping_poliklinik_pcare.kd_poli_pcare='" . $url[2] . "'"));
                    if ($data['nm_poli'] != '') {
                        $response = array(
                            'response' => array(
                                'namapoli' => $data['nm_poli'],
                                'totalantrean' => $data['total_antrean'],
                                'sisaantrean' => $data['sisa_antrean'],
                                'antreanpanggil' => $data['antrean_panggil'],
                                'keterangan' => $data['keterangan']
                            ),
                            'metadata' => array(
                                'message' => 'Ok',
                                'code' => 200
                            )
                        );
                    } else {
                        $response = array(
                            'metadata' => array(
                                'message' => 'Maaf belum Ada Antrian ditanggal ' . $url[3],
                                'code' => 201
                            )
                        );
                    }
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Access denied ',
                            'code' => 401
                        )
                    );
                }
            }

            //sisa peserta mengambil data dari registrasi poli
            if (!empty($url[1]) and $url[1] == "sisapeserta") {
                if (password_verify(USERNAME, $hash_user) and $header['x-token'] == getToken() and !empty($url[2]) and !empty($url[3]) and !empty($url[4])) {
                    $data = fetch_array(bukaquery("SELECT 
                    poliklinik.nm_poli,
                    reg_periksa.no_reg,
                    COUNT(reg_periksa.kd_poli) as total_antrean,
                    CONCAT(00,COUNT(reg_periksa.kd_poli)) as antrean_panggil,
                    SUM(CASE WHEN reg_periksa.stts ='Belum' THEN 1 ELSE 0 END) as sisa_antrean,
					SUM(CASE WHEN reg_periksa.stts ='Sudah' THEN 1 ELSE 0 END) as sudah_selesai,
                    ('Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke bagian Pendaftaran atau Perawat Poli, Terima Kasih ..') as keterangan
                    FROM reg_periksa 
                    INNER JOIN poliklinik ON poliklinik.kd_poli=reg_periksa.kd_poli
                    INNER JOIN maping_poliklinik_pcare ON maping_poliklinik_pcare.kd_poli_rs=reg_periksa.kd_poli
                    INNER JOIN pasien on pasien.no_rkm_medis=reg_periksa.no_rkm_medis
                    WHERE pasien.no_peserta='" . $url[2] . "' and reg_periksa.tgl_registrasi='" . $url[4] . "' AND maping_poliklinik_pcare.kd_poli_pcare='" . $url[3] . "'"));
                    //cek apakah ada di table registrasi, jika tidak ada cek di booking
                    if ($data['nm_poli'] == '') {
                        $booking = fetch_array(bukaquery("SELECT 
                        poliklinik.nm_poli,
                        booking_registrasi.no_reg,
                        SUM(CASE WHEN booking_registrasi.status ='Belum' THEN 1 ELSE 0 END) as sisa_antrean,
                        ('Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke bagian Pendaftaran atau Perawat Poli, Berikan Bukti Pendaftaran dari Mobile JKN ke Bagian Pendaftaran, Terima Kasih ..') as keterangan
                        FROM booking_registrasi 
                        INNER JOIN poliklinik ON poliklinik.kd_poli=booking_registrasi.kd_poli
                        INNER JOIN maping_poliklinik_pcare ON maping_poliklinik_pcare.kd_poli_rs=booking_registrasi.kd_poli
                        INNER JOIN pasien on pasien.no_rkm_medis=booking_registrasi.no_rkm_medis
                        WHERE pasien.no_peserta='" . $url[2] . "' and booking_registrasi.tanggal_periksa='" . $url[4] . "' AND maping_poliklinik_pcare.kd_poli_pcare='" . $url[3] . "'"));
                        //cek apakah ada di data booking, jika da ada maka tampilkan, jika ada data maka tampilkan data tidak ditemukan
                        if ($booking['nm_poli'] != '') {
                            $response = array(
                                'response' => array(
                                    'nomorantrean' => $booking['no_reg'],
                                    'namapoli' => $booking['nm_poli'],
                                    'sisaantrean' => $booking['sisa_antrean'],
                                    'antreanpanggil' => $booking['no_reg'],
                                    'keterangan' => $booking['keterangan']
                                ),
                                'metadata' => array(
                                    'message' => 'Ok',
                                    'code' => 200
                                )
                            );
                        } else {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Maaf tidak Ada data yang ditemukan !',
                                    'code' => 201
                                )
                            );
                        }
                    }
                    //cek diregistrasi
                    else {
                        $response = array(
                            'response' => array(
                                'nomorantrean' => $data['no_reg'],
                                'namapoli' => $data['nm_poli'],
                                'sisaantrean' => $data['sisa_antrean'],
                                'antreanpanggil' => $data['no_reg'],
                                'keterangan' => $data['keterangan']
                            ),
                            'metadata' => array(
                                'message' => 'Ok',
                                'code' => 200
                            )
                        );
                    }
                }
            }
            break;
    }
}
//metode POST
if ($method == 'POST' && !empty($header['x-username']) && !empty($header['x-token'])) {
    $hash_user = hash_pass($header['x-username'], 12);
    switch ($url[0]) {

        case "antrean":
            $header = apache_request_headers();
            $konten = trim(file_get_contents("php://input"));
            $decode = json_decode($konten, true);
			$h1 = strtotime('+1 days' , strtotime($date)) ;
            $h1 = date('Y-m-d', $h1);
            $_h1 = date('d-m-Y', strtotime($h1));
			$h7 = strtotime('+1 days', strtotime($date)) ;
			$poli = bukaquery("SELECT kd_poli_pcare, kd_poli_rs FROM maping_poliklinik_pcare WHERE kd_poli_pcare='$decode[kodepoli]'");
            if (password_verify(USERNAME, $hash_user) and $header['x-token'] == getToken()) {
			
                //validasi
            if (!empty($decode['nomorkartu']) && mb_strlen($decode['nomorkartu'], 'UTF-8') <> 13){
                    $response = array(
                        'metadata' => array(
                            'message' => 'Nomor kartu harus 13 digit',
                            'code' => 201
                        )
                    );
                }
			elseif(empty($decode['nomorkartu'])) {
				$response = array(
                        'metadata' => array(
                            'message' => 'Nomor kartu Tidak Boleh Kosong',
                            'code' => 201
                        )
                    );
                }
			elseif(empty($decode['nik'])) {
				$response = array(
                        'metadata' => array(
                            'message' => 'NIK Tidak Boleh Kosong',
                            'code' => 201
                        )
                    );
                }
			elseif (empty($decode['nik']) or strlen($decode['nik']) <= 15) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'NIK harus 16 digit ',
                            'code' => 201
                        )
                    ); 
                }
				
			if (!empty($decode['nik']) && !ctype_digit($decode['nik']) ){
				$response = array(
                        'metadata' => array(
                            'message' => 'Format NIK Tidak Sesuai  ',
                            'code' => 201
                        )
                    ); 
                }
			elseif (!empty($decode['nomorkartu']) && !ctype_digit($decode['nomorkartu']) ){
				$response = array(
                        'metadata' => array(
                            'message' => 'Format tidak sesuai',
                            'code' => 201
                        )
                    );
                }
				
			elseif(empty($decode['kodepoli'])) {
				$response = array(
                        'metadata' => array(
                            'message' => 'Kode Poli Tidak Boleh Kosong',
                            'code' => 201
                        )
                    );
                }
			elseif(empty($decode['tanggalperiksa'])) {
            
				$response = array(
                        'metadata' => array(
                            'message' => 'Anda Belum Memilih Tanggal',
                            'code' => 201
                        )
                    );
                }
			elseif (!empty($decode['tanggalperiksa']) && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$decode['tanggalperiksa'])) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Format tanggal Tidak Sesuai, Format Tanggal Yang Benar YYYY-mm-dd',
                            'code' => 201
                        )
                    );
                }
			elseif (strtotime($decode['tanggalperiksa']) < strtotime(date('Y-m-d')) or strtotime($decode['tanggalperiksa']) >= strtotime(date('Y-m-d', strtotime('+' . HARI . ' day', strtotime(date('Y-m-d'))))) ) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Tanggal Periksa Tidak Berlaku',
                            'code' => 201
                        )
                    );
					
				}
				
			else {
				if (!empty($decode['nomorkartu']) && !empty($decode['nik']) && !empty($decode['kodepoli']) && !empty($decode['tanggalperiksa'])) {
                    $hari = hariindo($decode['tanggalperiksa']);
                    $cek_kouta = fetch_array(bukaquery("SELECT jadwal.kuota - COALESCE((select COUNT(booking_registrasi.tanggal_periksa) FROM booking_registrasi 
                                WHERE booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]' AND booking_registrasi.kd_dokter=jadwal.kd_dokter )) as sisa_kouta, jadwal.kd_dokter, jadwal.kd_poli, 
                                jadwal.jam_mulai + INTERVAL '10' MINUTE as jam_mulai, poliklinik.nm_poli,dokter.nm_dokter,
                                ('Datang 30 Menit sebelum pelayanan, Konfirmasi kehadiran dibagian pendaftaran dengan menunjukan bukti pendaftaran melalui Mobile JKN, Terima Kasih..') as keterangan
                                FROM jadwal
                                INNER JOIN maping_poliklinik_pcare ON maping_poliklinik_pcare.kd_poli_rs=jadwal.kd_poli
                                INNER JOIN poliklinik ON poliklinik.kd_poli=jadwal.kd_poli
                                INNER JOIN dokter ON dokter.kd_dokter=jadwal.kd_dokter
                                WHERE jadwal.hari_kerja='$hari' AND  maping_poliklinik_pcare.kd_poli_pcare='$decode[kodepoli]'
                                GROUP BY jadwal.kd_dokter
                                HAVING sisa_kouta > 0
                                ORDER BY sisa_kouta DESC LIMIT 1"));
                    //validasi kouta
				
                if (!empty($cek_kouta['sisa_kouta']) and $cek_kouta['sisa_kouta'] > 0) {
                        //cek data di SIMRS
                        $data = fetch_array(bukaquery("SELECT pasien.no_rkm_medis, pasien.no_ktp, pasien.no_peserta FROM pasien where pasien.no_ktp='$decode[nik]' and pasien.no_peserta='$decode[nomorkartu]'"));
                        // jika data valid atau ditemukan di SIMRS
                        if ($data['no_ktp'] != '') {
                            $noReg = noRegPoli($cek_kouta['kd_poli'], $decode['tanggalperiksa']);
                            $query = bukaquery("insert into booking_registrasi set tanggal_booking=CURDATE(),jam_booking=CURTIME(), no_rkm_medis='$data[no_rkm_medis]',tanggal_periksa='$decode[tanggalperiksa]',"
                                . "kd_dokter='$cek_kouta[kd_dokter]',kd_poli='$cek_kouta[kd_poli]',no_reg='$noReg',kd_pj='BPJ',limit_reg='1',waktu_kunjungan='$cek_kouta[jam_mulai]',status='Belum'");
                            if ($query) {
                                $response = array(
                                    'response' => array(
                                        'nomorantrean' => $noReg,
                                        'angkaantrean' => $noReg,
                                        'namapoli' => $cek_kouta['nm_poli'],
                                        'sisaantrean' => strtotime($cek_kouta['jam_mulai']) * 1000,
                                        'antreanpanggil' => $noReg,
                                        'keterangan' => $cek_kouta['keterangan']
                                    ),
                                    'metadata' => array(
                                        'message' => 'Ok',
                                        'code' => 200
                                    )
                                );
                            } else {
                                $response = array(
                                    'metadata' => array(
                                        'message' => "Maaf Terjadi Kesalahan, Hubungi Admnistrator..",
                                        'code' => 401
                                    )
                                );
                            }
                        }
                        //jika data tidak ditemukan
                        elseif ($data['no_ktp'] == '') {
                            $instansi = getOne("select nama_instansi from setting");
                            $response = array(
                                'metadata' => array(
                                    'message' => "Pasien tidak ditemukan/belum terdaftar di " . $instansi,
                                    'code' => 401
                                )
                            );
                        }
                    }
                    //kouta sudah habis
                    else {
                        $response = array(
                            'metadata' => array(
                                'message' => "Poli Tidak Ditemukan",
                                'code' => 201
                            )
                        );
                    }
                }
			}	
            } else {
                $response = array(
                    'metadata' => array(
                        'message' => 'Token Expired',
                        'code' => 201
                    )
                );
            }
			
            break;
					
        case "peserta":
            $response = array(
                'metadata' => array(
                    'message' => 'Cooming Soon',
                    'code' => 505
                )
            );
            break;
		
    }
}
//metode PUT
if ($method == 'PUT' && !empty($header['x-username']) && !empty($header['x-token'])) {
    $hash_user = hash_pass($header['x-username'], 12);
    switch ($url[0]) {

        case "antrean":
            if (!empty($url[1]) and $url[1] == "batal") {
				$header = apache_request_headers();
            $konten = trim(file_get_contents("php://input"));
            $decode = json_decode($konten, true);
				
                if (password_verify(USERNAME, $hash_user) and $header['x-token'] == getToken()) {
                    if (!empty($decode['nomorkartu']) && !empty($decode['kodepoli']) && !empty($decode['tanggalperiksa'])) {
                        $cek = fetch_array(bukaquery("SELECT * FROM booking_registrasi 
                        WHERE  booking_registrasi.no_rkm_medis in (SELECT pasien.no_rkm_medis FROM pasien WHERE pasien.no_peserta ='$decode[nomorkartu]') AND 
                        booking_registrasi.kd_poli IN (SELECT maping_poliklinik_pcare.kd_poli_rs FROM maping_poliklinik_pcare WHERE maping_poliklinik_pcare.kd_poli_pcare='$decode[kodepoli]')AND 
                        booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]' AND booking_registrasi.status='Belum'   "));
                        if ($cek ['no_rkm_medis']) {
                            $query = bukaquery("DELETE FROM booking_registrasi 
                            WHERE  booking_registrasi.no_rkm_medis in (SELECT pasien.no_rkm_medis FROM pasien WHERE pasien.no_peserta ='$decode[nomorkartu]') AND 
                            booking_registrasi.kd_poli IN (SELECT maping_poliklinik_pcare.kd_poli_rs FROM maping_poliklinik_pcare WHERE maping_poliklinik_pcare.kd_poli_pcare='$decode[kodepoli]')AND 
                            booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]'  ");
                            if ($query) {
                                $response = array(
                                    
                                    'metadata' => array(
                                        'message' => 'Ok',
                                        'code' => 200
                                    )
                                );
                            } else {
                                $response = array(
                            'metadata' => array(
                                'message' => "Maaf Terjadi kesalahan, Silahkan hubungi Administrator!",
                                'code' => 401
                            )
                        );
                            }
                        } 
						else  {
                            $response = array(
                                    'metadata' => array(
                                        'message' => 'Data ini  Tidak bisa Di Hapus Karena Sudah Terdaftar !',
                                        'code' => 201
                                    )
                                );
                        }
                    }
                }
            }
            break;
    }
}
if (!empty($response)) {
    echo json_encode($response);
} else {
    $instansi = getOne("select nama_instansi from setting");
    echo "Selamat Datang di API " . $instansi . " Antrean BPJS Mobile JKN..";
    echo "\n\n\n";
    echo "@" . $instansi . " - 2020";
}
