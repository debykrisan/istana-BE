<?php

namespace App\Http\Controllers;

use App\Models\PermintaanHasilDokumentasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;


class PermintaanHasilDokumentasiController extends Controller
{
    public function addpermintaan(Request $request) 
    {
        $input = $request->all();

        $token = array(
            "key" => -1,
            "pesan" => "Upload Gagal",
            "kode" => 500
        );

        if (!empty($input)) {

            $id = DB::table('tm_dokumentasi')->insertGetId([
                'i_id_istana' => $request->idIstana,
                'i_id_istura' => $request->idIstura,
                'n_rombongan' => $request->rombongan,
                'n_penanggung_jawab' => $request->penanggungJawab,
                'c_telp_penanggung_jawab' => $request->telpon,
                'c_kategori_rombongan' => $request->kategori,
                'd_tanggal' => $request->tanggalKedatangan,
                'd_jam' => $request->jamKedatangan,
                'v_jumlah_peserta' => $request->jumlahPeserta,
                'c_jenis_dokumentasi' => $request->jenisDokumentasi
            ]);

            $hasil = $this->addFilePermintaan($request->key, $id);

            $token = array(
                "key" => $hasil,
                "pesan" => "Simpan berhasil",
                "kode" => 200
            );
        }
        return response()->json($token);
    }

    private function addFilePermintaan($keyArr, $id)
    {
        foreach ($keyArr as $v) {
            $param = array(
                'c_file_bukti_kunjungan' => $v['value'],
                'i_id_dokumentasi' => $id
            );
            $hasil = DB::update("UPDATE tm_dokumentasi 
                        SET c_file_bukti_kunjungan = :c_file_bukti_kunjungan
                        WHERE i_id_dokumentasi = :i_id_dokumentasi", $param);
        }
        return $hasil;
    }

    public function listDokumentasiDt(Request $request) 
    {
        $hasil = $this->listByIdUser($request);
        $banyak = $this->getBanyakDokumentasi($request);
        $token = array(
            'recordsTotal' => $banyak,
            'data' => $hasil
        );
        
        return response()->json($token);
    }

    public function listByIdUser(Request $request)
    {

        $where = "";

        if (isset($request->columnFilters)) {
            if (
                !empty($request->columnFilters["rombongan"]) &&
                $request->columnFilters["rombongan"] !== "null"
            ) {
                $where .= " and upper(tir.n_rombongan)  like concat('%',upper('" .
                $request->columnFilters["rombongan"] . "'),'%') ";
            }
        }

        $orderField = " order by FIELD(td.c_kirim_status, 0) desc, td.d_created_date desc, FIELD(td.c_kirim_status, 3)";
        if (!empty($request->sort[0]['field']) && !empty($request->sort[0]['type'])) {
            $typesort = $request->sort[0]['type'] === "none" ? "asc" : $request->sort[0]['type'];
            $orderField = " order by  " . $request->sort[0]['field'] . "      " . $typesort;
        }

        $hasil = DB::select(
            "SELECT
                ROW_NUMBER() OVER (order by FIELD(td.c_kirim_status, 0) desc, td.d_created_date desc, FIELD(td.c_kirim_status, 3)) AS rn, 
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
                td.c_kirim_status as kirim_status,
                td.c_status as status_dokumentasi,
                td.i_id_dokumentasi as id_dokumentasi,
                td.n_fotografer as fotografer
            from tm_istura tir
            join tm_dokumentasi td on td.i_id_istura = tir.i_id_istura
            join tr_istana ti on ti.i_id = tir.i_id_istana
            join tr_jam tjam on tjam.i_id_jam = tir.d_jam_kedatangan
            where tir.i_id_pemohon = (:id)
            and td.c_status != '0'
            $where
            $orderField
            LIMIT " . $request->perPage . "  OFFSET  " . $request->page, 
            [':id' => $request->idUser]
        );
        
        // tambah link foto untuk thumbnail di list dokumentasi
        foreach ($hasil as $value) {
            $value->tanggal_kedatangan = Carbon::createFromFormat('Y-m-d', $value->tanggal_kedatangan)->locale("id")->translatedFormat('j F Y');
            if ($value->status_dokumentasi != 0) {
                $foto = DB::selectOne("SELECT tdf.c_key_foto as key_foto
                                    FROM tm_dokumentasi td
                                    JOIN tm_dokumentasi_foto tdf
                                    ON td.i_id_dokumentasi = tdf.i_id_dokumentasi
                                    WHERE td.i_id_dokumentasi = (:id_dokumentasi)",
                                    [':id_dokumentasi' => $value->id_dokumentasi]);
                
                $value->link_foto = $this->getpresignedurl(null, $foto->key_foto)->getData()->imgProxyUrl;
            }
        }

        return $hasil;
    }

    private function getBanyakDokumentasi(Request $request)
    {
        $where = "";

        if (isset($request->columnFilters)) {
            if (
                !empty($request->columnFilters["rombongan"]) &&
                $request->columnFilters["rombongan"] !== "null"
            ) {
                $where .= " and upper(tir.n_rombongan)  like concat('%',upper('" .
                $request->columnFilters["rombongan"] . "'),'%') ";
            }
        }

        $hasil = DB::selectOne(
            "SELECT 
                COUNT(*) as banyak
            from tm_istura tir
            join tm_dokumentasi td on td.i_id_istura = tir.i_id_istura
            join tr_istana ti on ti.i_id = tir.i_id_istana
            join tr_jam tjam on tjam.i_id_jam = tir.d_jam_kedatangan
            where tir.i_id_pemohon = (:id)
            and td.c_status != '0'
            $where", 
            [':id' => $request->idUser]
        );
        return $hasil->banyak;
    }

    public function getDokumentasiById(string $id)
    {
        $hasil = DB::selectOne("SELECT  
        tir.i_id_istura as id_istura,
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
        tir.d_tanggal_surat as tanggal_surat,
        td.i_id_dokumentasi as id_dokumentasi,
        td.c_kirim_status as kirim_status
        from tm_istura tir
        join tm_dokumentasi td
        on td.i_id_istura = tir.i_id_istura
        join tr_istana ti 
        on ti.i_id = tir.i_id_istana
        join tr_jam tjam
        on tjam.i_id_jam = tir.d_jam_kedatangan 
        where td.i_id_dokumentasi = :id ", [':id' => $id]);

        $foto = $this->getFotoByIdDokumentasi($id);
        $token = array(
            "data" => $hasil,
            "foto" => $foto
        );

        return response()->json($token);
    }

    private function getFotoByIdDokumentasi($id_dokumentasi)
    {
        $query = 
            "SELECT  
                c_key_foto,
                c_user_pilih
            FROM 
                tm_dokumentasi_foto  
            WHERE 
                i_id_dokumentasi =  ? 
            ORDER BY 
                c_user_pilih DESC ";

        $hasil = DB::select($query, [$id_dokumentasi]);
        return $hasil;
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

    public function doUpload(Request $request): \Illuminate\Http\JsonResponse
    {
        $token = array(
            "key" => -1,
            "pesan" => "Upload kosong",
            "kode" => 500
        );

        $finalArray = array();
        $a = array(
            'c_file_bukti_kunjungan' => $request->file('c_file_bukti_kunjungan')
        );
        $fileExtensionsAllowed = ['jpeg', 'jpg', 'png', 'JPG', 'JPEG', 'PNG', 'pdf'];
        foreach ($a as $key => $value) {
            $files = $a[$key];
            $fileName = $files->getClientOriginalName();
            $fileSize = $files->getSize();
            $fileExtension =  $files->getClientOriginalExtension();
            $errors = [];

            if (!in_array($fileExtension, $fileExtensionsAllowed)) {
                $errors[] = "This file extension is not allowed. Please upload a JPEG, PNG, or pdf file";
                $token['pesan'] = $errors;
                $token['key'] = -1;
            }

            if ($fileSize > 3000000) {
                $errors[] = "File exceeds maximum size (400Kb)";
                $token['pesan'] = $errors;
                $token['key'] = -1;
            }

            if (empty($errors)) {
                $didUpload = $this->uploadFile($files, $fileName, $fileExtension);
                if ($didUpload) {
                    $data = array(
                        'column' => $key,
                        'value' => $didUpload
                    );
                    array_push($finalArray, $data);
                }
            }
        }
        $token = array(
            "key" => $finalArray,
            "pesan" => "Upload berhasil",
            "kode" => 200
        );

        return response()->json($token);
    }

    private function uploadFile($files, $fileName, $fileExtension): string
    {

        $credentials = new Credentials(env('MINIO_KEY'), env('MINIO_SECRET'));

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

        $key = $this->gen_uuid() . "." . $fileExtension;
        $s3->putObject(array(
            'Bucket' => getenv('MINIO_BUCKET'),
            'Key' => $key,
            'SourceFile' => $files,
            'Metadata' => array(
                'fileName' => $fileName
            )
        ));
        return $key;
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

    public function getpresignedurl(Request $request = null, $keyFoto = null): \Illuminate\Http\JsonResponse
    {
        $key = $request["c_key_foto"] ?? $keyFoto;
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

        $originalFileName = $s3->headObject([
            'Bucket' => getenv('MINIO_BUCKET'),
            'Key' => $key
        ])->get('Metadata');

        if ($originalFileName != null) {
            $originalFileName = $originalFileName['filename'];
        } else {
            $originalFileName = $key;
        }

        $request = $s3->createPresignedRequest($cmd, '+20 minutes');

        $proxyImg = $this->imageProxyF($request->getUri());

        $url = $s3->getObjectUrl(getenv('MINIO_BUCKET'), $key);

        $token = array(
            "key" => $key,
            "url" => $url,
            "presignedUrl" => $request->getUri(),
            "imgProxyUrl" => $proxyImg,
            "originalFileName" => $originalFileName,
            "pesan" => "Preview berhasil",
            "kode" => 200
        );
        return response()->json($token);
    }

    public function listPermintaanFoto(Request $request): \Illuminate\Http\JsonResponse
    {

        $listFoto = $request->listPilihFoto;
        
        foreach ($listFoto as $value){
            DB::update(
                "UPDATE 
                    tm_dokumentasi_foto
                SET 
                    c_user_pilih = 1
                WHERE 
                    c_key_foto = '$value'
                ");
        }

        $hasil = 
            DB::update(
                "UPDATE
                    tm_dokumentasi
                SET
                    c_kirim_status = 1,
                    d_user_submit = CURRENT_TIMESTAMP()
                WHERE
                    i_id_istura = '$request->idIstura'
            ");

        $idUser = DB::selectOne("SELECT i_id_pemohon FROM tm_istura WHERE i_id_istura = '$request->idIstura'")->i_id_pemohon;

        if ($hasil > 0) {
            DB::table('tm_log_istura')->insert([
                'i_id_user' => $idUser,
                'i_id_istura' => $request->idIstura,                   
                'n_aktivitas' => 'User menambahkan permohonan dokumentasi',
                'c_status' => 1
            ]);
        }            

        $token = array(
            "key" => $hasil,
            "pesan" => "Permintaan Foto Telah Terkirim",
            "kode" => 200
        );

        return response()->json($token);
    }

    public function updateListPermintaanFoto(Request $request): \Illuminate\Http\JsonResponse
    {
        $kirimStatus = $request->kirimStatus;
        $listPilihFoto = $request->listPilihFoto;

        $listFoto = DB::select("SELECT c_key_foto FROM tm_dokumentasi_foto WHERE i_id_dokumentasi = '$request->idDokumentasi'");
        $idUser = DB::selectOne("SELECT i_id_pemohon FROM tm_istura WHERE i_id_istura = '$request->idIstura'")->i_id_pemohon;
        $fotografer = DB::selectOne("SELECT n_fotografer FROM tm_dokumentasi WHERE i_id_dokumentasi = '$request->idDokumentasi'")->n_fotografer;

        $token = array();

        // Tambah record baru jika status kirim = 2(Selesai) atau 3(Ditolak) 
        if ($kirimStatus == 2 || $kirimStatus == 3) { 
            $idDokumentasi = $this->gen_uuid();

            $sql = "INSERT INTO tm_dokumentasi 
                    (i_id_dokumentasi, i_id_istura, c_status, n_fotografer, c_kirim_status, d_user_submit) 
                    VALUES 
                    (:i_id_dokumentasi, :i_id_istura, 1, :fotografer, 1, CURRENT_TIMESTAMP())";

            $params = array(
                'i_id_dokumentasi' => $idDokumentasi,
                'i_id_istura' => $request->idIstura,
                'fotografer' => $fotografer
            );

            DB::insert($sql, $params);

            foreach ($listFoto as $value) {
                DB::insert(
                    "INSERT INTO 
                        tm_dokumentasi_foto
                    (
                        i_id_dokumentasi,
                        c_key_foto,
                        c_user_pilih
                    )
                    VALUES
                    (
                        '$idDokumentasi',
                        '$value->c_key_foto',
                        0
                    )"
                );
            }

            foreach ($listPilihFoto as $value) {
                DB::update(
                    "UPDATE 
                        tm_dokumentasi_foto
                    SET 
                        c_user_pilih = 1
                    WHERE 
                        c_key_foto = '$value'
                    ");
            }

            DB::table('tm_log_istura')->insert([
                'i_id_user' => $idUser,
                'i_id_istura' => $request->idIstura,                   
                'n_aktivitas' => 'User mengirim ulang permohonan dokumentasi',
                'c_status' => 1
            ]);

            $token = array(
                "key" => 1,
                "pesan" => "Permohonan Ulang Dokumentasi Berhasil",
                "kode" => 200
            );

        } else {
            DB::update(
                "UPDATE
                    tm_dokumentasi_foto
                SET 
                    c_user_pilih = 0
                WHERE
                    i_id_dokumentasi = '$request->idDokumentasi'
            ");
            
            foreach ($listPilihFoto as $value){
                DB::update(
                    "UPDATE 
                        tm_dokumentasi_foto
                    SET 
                        c_user_pilih = 1
                    WHERE 
                        c_key_foto = '$value'
                    ");
            }
    
            DB::table('tm_log_istura')->insert([
                'i_id_user' => $idUser,
                'i_id_istura' => $request->idIstura,                   
                'n_aktivitas' => 'User mengedit permohonan dokumentasi',
                'c_status' => 1
            ]);

            $token = array(
                "key" => 1,
                "pesan" => "Ubah Permintaan Foto Berhasil",
                "kode" => 200
            );
        }

        return response()->json($token);
    }
}
