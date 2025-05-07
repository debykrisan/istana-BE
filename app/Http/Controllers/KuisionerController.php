<?php

namespace App\Http\Controllers;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class KuisionerController extends Controller
{
    public function sendLinkKuisioner()
    {
        $hasil = DB::select("SELECT 
                                tis.i_id_istura as id,
                                tis.i_id_istana as id_istana,
                                ti.n_nama_istana as nama_istana,
                                tis.d_tanggal_kedatangan as tanggal_kedatangan,
                                tis.c_status as status,
                                tu.c_nama_lengkap as nama_pemohon,
                                tu.n_email as email_pemohon
                             FROM
                                tm_istura tis
                             JOIN
                                tr_user tu ON tu.i_id = tis.i_id_pemohon
                             JOIN
                                tr_istana ti ON ti.i_id = tis.i_id_istana                                    
                             WHERE
                                tis.c_status = 3 -- status selesai
                             AND
                                d_tanggal_kedatangan = DATE(NOW()) -- ambil data istura selesai hari ini     
                                ");
        
        if ($hasil != null) {
            foreach ($hasil as $data) {
                $this->createMail($data);
            }
            return response()->json([
                'pesan' => 'email terkirim!',
                'data' => $hasil
            ], 200);
        } else {
            return response()->json([
                'pesan' => 'tidak ada data'
            ], 200);
        }
    }

    private function createMail($data)
    {        
        try {
            $email = $data->email_pemohon;
            $nama = $data->nama_pemohon;
            $idIstura = $data->id;
            $idIstana = $data->id_istana;
            $namaIstana = $data->nama_istana;                                  

            $original_string = $idIstura . "+" . $idIstana;  // Plain text/String
            $cipher_algo = "AES-128-CTR"; //The cipher method, in our case, AES 
            $option = 0; //Bitwise disjunction of flags
            $encrypt_iv = '8746376827619797'; //Initialization vector, non-null
            $encrypt_key = "tabeldata!"; // The encryption key
            
            $encryptedToken = openssl_encrypt($original_string, $cipher_algo, $encrypt_key, $option, $encrypt_iv);
            $encoded = base64_encode($encryptedToken);

            //link url to kuisioner
            $url = url(env('PORTAL_BASE_URL') . '/kuisioner/' . $encoded);

            $token = array(
                "data" => [
                    'url' => $url,
                    'email' => $email,
                    'nama' => $nama,
                    'istana' => $namaIstana
                ]
            );

            $html = view('kuisionermail', $token)->render();

            $client = new Client();

            $response = $client->post(env('SPRING_BOOT_SMTP_ENDPOINT'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'recipient' => $email,
                    'msgBody' => $html,
                    'subject' => 'Kuisioner',
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (\Throwable $th) {
            return response()->json([
                'pesan' => 'email gagal terkirim!',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function validTokenKuisioner(string $token)
    {
        //decrypt token
        $cipher_algo = "AES-128-CTR"; //The cipher method, in our case, AES 
        $option = 0; //Bitwise disjunction of flags
        $encrypt_iv = '8746376827619797'; //Initialization vector, non-null
        $encrypt_key = "tabeldata!"; // The encryption key

        try {
            $decoded = base64_decode($token);
            $decryptedToken = openssl_decrypt($decoded, $cipher_algo, $encrypt_key, $option, $encrypt_iv);

            $tokenParts = explode("+", $decryptedToken);
            $idIstura = $tokenParts[0];
            $idIstana = $tokenParts[1];

            $status = DB::selectOne("SELECT 
                                        c_status as status
                                     FROM 
                                        tm_istura
                                     WHERE
                                        i_id_istura = '$idIstura'");
                                                    
            if ($status->status == 5) { // status selesai dan sudah isi kuisioner
                return response()->json([
                    'status' => 'invalid',
                    'pesan' => 'Kuisioner sudah diisi',
                    'kode' => $status->status
                ]);
            } else if ($status->status == 3) { // status selesai dan belum isi kuisioner
                return response()->json([
                    'status' => 'valid',
                    'pesan' => 'token', 
                    'id_istura' => $idIstura,
                    'id_istana' => $idIstana,
                    'kode' => $status->status
                ], 200);
            } else {
                return response()->json([
                    'status' => 'invalid',
                    'pesan' => 'Kuisioner tidak bisa diisi',
                    'kode' => $status->status ?? null
                ]);
            }        
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'invalid',
                'pesan' => 'error' 
            ], 403);
        }
    }

    public function getPertanyaanKuisioner(string $idIstana, string $idIstura)
    {
        $hasil = DB::select("SELECT 
                                i_id as id,
                                i_id_istana as id_istana,
                                n_pertanyaan as pertanyaan,
                                i_opsi_jawaban as opsi_jawaban
                             FROM 
                                tm_kuisioner_pertanyaan
                             WHERE
                                i_id_istana = '$idIstana'");

        $istura = DB::select("SELECT  
        tir.i_id_istura as id,
        ti.i_id as id_istana,
        ti.n_nama_istana as nama_istana,
        tir.n_rombongan as rombongan,
        tir.n_penanggung_jawab as penanggung_jawab,
        tir.c_telpon as telpon,
        tir.c_kategori as kategori,
        tir.d_tanggal_kedatangan as tanggal_kedatangan,
        tjam.i_id_jam as id_jam,
        tjam.d_jam as jam_kedatangan,
        tir.c_jumlah_peserta as jumlah_peserta,
        tir.c_status as status_istura,
        tir.c_file_peserta as file_peserta,
        tir.c_file_surat_permohonan as file_surat_permohonan,
        tir.c_no_permohonan as no_permohonan,
        tir.d_tanggal_surat as tanggal_surat
        from tm_istura tir
        join tr_istana ti 
        on ti.i_id = tir.i_id_istana
        join tr_jam tjam
        on tjam.i_id_jam = tir.d_jam_kedatangan 
        where tir.i_id_istura = :id ", [':id' => $idIstura]);

        foreach($istura as $value) {
            if ($value->file_peserta != null && $value->file_surat_permohonan != null) {
                if (
                    pathinfo($value->file_peserta, PATHINFO_EXTENSION) == "jpeg" || 
                    pathinfo($value->file_peserta, PATHINFO_EXTENSION) == "JPEG" || 
                    pathinfo($value->file_peserta, PATHINFO_EXTENSION) == "jpg" || 
                    pathinfo($value->file_peserta, PATHINFO_EXTENSION) == "png" 
                    ) 
                {
                    $value->file_peserta = $this->_getpresignedurl($value->file_peserta)->getData()->imgProxyUrl;
                } else {
                    $value->file_peserta = $this->_getpresignedurl($value->file_peserta)->getData()->presignedUrl;
                }
                
                if (
                    pathinfo($value->file_surat_permohonan, PATHINFO_EXTENSION) == "jpeg" || 
                    pathinfo($value->file_surat_permohonan, PATHINFO_EXTENSION) == "JPEG" || 
                    pathinfo($value->file_surat_permohonan, PATHINFO_EXTENSION) == "jpg" || 
                    pathinfo($value->file_surat_permohonan, PATHINFO_EXTENSION) == "png" 
                    ) 
                {
                    $value->file_surat_permohonan = $this->_getpresignedurl($value->file_surat_permohonan)->getData()->imgProxyUrl;
                } else {
                    $value->file_surat_permohonan = $this->_getpresignedurl($value->file_surat_permohonan)->getData()->presignedUrl;
                }
            }

            $value->tanggal_kedatangan = Carbon::createFromFormat('Y-m-d', $value->tanggal_kedatangan)->locale("id")->translatedFormat('j F Y');
        }                                

        $token = array(
            "data" => $hasil,
            "istura" => $istura
        );                  
        
        return response()->json($token, 200);
    }

    public function submitKuisioner(Request $request)
    {
        $pertanyaan = $request->pertanyaan;
        $i = 0;
        $hasil = false;
        $idIstura = "";

        try {
            foreach ($pertanyaan as $value) {
                $idIstura = $value["idIstura"];
                $idPertanyaan = $value["idPertanyaan"];
                $pertanyaanText = DB::selectOne("SELECT n_pertanyaan FROM tm_kuisioner_pertanyaan WHERE i_id = '$idPertanyaan'")->n_pertanyaan;
                $status = DB::selectOne("SELECT 
                                            c_status as status
                                         FROM 
                                            tm_istura
                                         WHERE
                                            i_id_istura = '$idIstura'");

                if ($status->status == 5) { // status selesai dan sudah isi kuisioner
                    return response()->json([
                        'status' => 'invalid',
                        'pesan' => 'Kuisioner sudah diisi',
                        'kode' => $status->status
                    ]);
                }    

                $data = array(
                    'i_id' => $this->gen_uuid(),
                    'i_id_istura' => $value["idIstura"],
                    'i_id_pertanyaan' => $value["idPertanyaan"],
                    'n_pertanyaan' => $pertanyaanText,
                    'c_jawaban' => $value["jawaban"], // 1 = ya, 2 = tidak, 3 = lainnya
                    'n_jawaban' => $value["jawabanText"]  
                );
                
                $hasil = DB::table('tm_kuisioner_jawaban')->insert($data);
                if ($hasil) {
                    $i++;
                }
            }

            $dataKritikSaran = array(
                'i_id' => $this->gen_uuid(),
                'i_id_istura' => $idIstura,
                'n_kritik_saran' => $request->kritikSaran
            );
    
            if ($i > 0) {
                DB::update("UPDATE 
                                tm_istura
                            SET
                                c_status = 5
                            WHERE
                                i_id_istura = '$idIstura'");
                
                DB::table('tm_kritik_saran')->insert($dataKritikSaran);

                return response()->json([
                    'key' => $hasil,
                    'pesan' => 'Data berhasil disimpan'
                ], 200);
            } else {
                return response()->json([
                    'key' => $hasil,
                    'pesan' => 'Data Kosong'
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'key' => $hasil,
                'pesan' => 'Data gagal disimpan',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    private function _getpresignedurl($key): \Illuminate\Http\JsonResponse
    {
        $credentials = new Credentials(getenv('MINIO_KEY'), getenv('MINIO_SECRET'));

        $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'endpoint' => getenv('MINIO_ENDPOINT'),
        'use_path_style_endpoint' => true,
        'credentials' => $credentials,
        'http'    => [
            'verify' => false
        ],
        ]);


        $cmd = $s3->getCommand('GetObject', [
        'Bucket' => getenv('MINIO_BUCKET'),
        'Key' => $key
        ]);

        $request = $s3->createPresignedRequest($cmd, '+20 minutes');

        $proxyImg = $this->imageProxyF($request->getUri());

        $url = $s3->getObjectUrl(getenv('MINIO_BUCKET'), $key);

        $token = array(
            "url" => $url,
            "presignedUrl" => $request->getUri(),
            "imgProxyUrl" => $proxyImg,
            "pesan" => "Preview berhasil",
            "kode" => 200
        );
        return response()->json($token);
    }

    private function imageProxyF($imgPath): string
    {
        $key = '6e45924d20fef273429090a206eb737c86ce1c7dccc8275cb313fc8a0d1ddcf38481535ad556c41a566a5c3eebda84c44271a3f2ee1bee48728689a307469c15';
        $salt = '182ef2016fc483dcac88666ae9813469b94707f903d8f83efdc2f22ebbf9c5521c20330d9a6c326054c3524d5e08f8ba2e1a957dbdfc7777a4b1843e21fd2d14';

        $keyBin = pack("H*", $key);
        if (empty($keyBin)) {
        die('Key expected to be hex-encoded string');
        }

        $saltBin = pack("H*", $salt);
        if (empty($saltBin)) {
        die('Salt expected to be hex-encoded string');
        }

        $urlEncode = urlencode($imgPath);

        $encodedSourceImage = $urlEncode;
        $path = "/rs:fit:700:700/plain/" . $encodedSourceImage;

        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $saltBin . $path, $keyBin, true)), '+/', '-_'), '=');

        $imgProxyServer = getenv('IMG_PROXY_SERVER');

        return $imgProxyServer . sprintf("/%s%s", $signature, $path);
    }

    private function gen_uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
