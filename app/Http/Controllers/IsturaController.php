<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class IsturaController extends Controller
{
    public function addisturadt(Request $request)
    {
        $hasil = null;
        if (!empty($request->id)) {
        $hasil = $this->editistura($request);
        } else {
        $hasil = $this->addistura($request);
        }

        return $hasil;
    }
    
    public function addistura(Request $request)
    {
        $input = $request->all();

        $token = array(
            "key" => -1,
            "pesan" => "Upload Gagal",
            "kode" => 500
        );

        if (!empty($input)) {

            $id = $this->gen_uuid(); 
            DB::table('tm_istura')->insert([
                'i_id_istura' => $id,
                'i_id_istana' => $request->idIstana,
                'n_rombongan' => $request->rombongan,
                'n_penanggung_jawab' => $request->penanggungJawab,
                'c_telpon' => $request->telpon,
                'c_kategori' => $request->kategori,
                'd_tanggal_kedatangan' => $request->tanggalKedatangan,
                'd_jam_kedatangan' => $request->jamKedatangan,
                'c_jumlah_peserta' => $request->jumlahPeserta,
                'c_no_permohonan' => $request->noPermohonan,
                'd_tanggal_surat' => $request->tanggalSurat,
                'c_status' => 0,
                'i_id_pemohon' => $request->idUser
            ]);

            $hasil = $this->addFileIstura($request->key, $id);

            $token = array(
                "key" => $hasil,
                "pesan" => "Simpan berhasil",
                "kode" => 200
            );

            // Tambah Log Istura
            DB::table('tm_log_istura')->insert([
                'i_id_user' => $request->idUser,
                'i_id_istura' => $id,                   
                'n_aktivitas' => 'Menambahkan permohonan istura',
                'c_status' => 0
            ]);
        }
        return response()->json($token);
    }

    public function addFileIstura($keyArr, $id) {
        $hasil = 0;
        foreach ($keyArr as $v) {
            if ($v["column"] == 'c_file_peserta') {
                $filePeserta = $v["value"];
                $hasil += DB::update("UPDATE 
                                           tm_istura
                                       SET 
                                           c_file_peserta = :c_file_peserta 
                                       WHERE 
                                           i_id_istura = :i_id_istura", 
                                       [
                                           'c_file_peserta' => $filePeserta, 
                                           'i_id_istura' => $id
                                       ]);
            }

            if ($v["column"] == 'c_file_surat_permohonan') {
                $fileSP = $v["value"];
                $hasil += DB::update("UPDATE 
                                           tm_istura
                                       SET 
                                           c_file_surat_permohonan = :c_file_surat_permohonan 
                                       WHERE 
                                           i_id_istura = :i_id_istura", 
                                       [
                                           'c_file_surat_permohonan' => $fileSP, 
                                           'i_id_istura' => $id
                                       ]);
            }
        } 
        return $hasil;
    }

    public function editistura(Request $request)
    {
        $updateFile = 0;
        $data = array(
            'i_id_istana' => $request->idIstana,
            'n_rombongan' => $request->rombongan,
            'n_penanggung_jawab' => $request->penanggungJawab,
            'c_telpon' => $request->telpon,
            'c_kategori' => $request->kategori,
            'd_tanggal_kedatangan' => $request->tanggalKedatangan,
            'd_jam_kedatangan' => $request->jamKedatangan,
            'c_jumlah_peserta' => $request->jumlahPeserta,
            'c_no_permohonan' => $request->noPermohonan,
            'd_tanggal_surat' => $request->tanggalSurat,
            'c_status' => 0,
            'i_id_pemohon' => $request->idUser,
            'i_id_istura' => $request->id     
        );

        $hasil = DB::update("UPDATE 
                                tm_istura 
                             SET
                                i_id_istana = :i_id_istana,
                                n_rombongan = :n_rombongan,
                                n_penanggung_jawab = :n_penanggung_jawab,
                                c_telpon = :c_telpon,
                                c_kategori = :c_kategori,
                                d_tanggal_kedatangan = :d_tanggal_kedatangan,
                                d_jam_kedatangan = :d_jam_kedatangan,
                                c_jumlah_peserta = :c_jumlah_peserta,
                                c_no_permohonan = :c_no_permohonan,
                                d_tanggal_surat = :d_tanggal_surat,
                                c_status = :c_status,
                                i_id_pemohon = :i_id_pemohon
                             WHERE
                                i_id_istura = :i_id_istura", $data);  
        
        if ($request->key != -1) {
            $updateFile = $this->addFileIstura($request->key, $request->id);
        }

        $token = array(
            'data' => $hasil + $updateFile,
            'pesan' => $hasil + $updateFile > 0 ? 'Edit Istura Berhasil!' : 'Tidak Ada Perubahan!',
        );

        if ($hasil + $updateFile > 0) {
            // Tambah Log Istura
            DB::table('tm_log_istura')->insert([
                'i_id_user' => $request->idUser,
                'i_id_istura' => $request->id,                   
                'n_aktivitas' => 'Mengedit permohonan istura',
                'c_status' => 0
            ]);
        }

        return response()->json($token);
    }

    private function gen_uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function doUpload(Request $request): \Illuminate\Http\JsonResponse
    {
        $token = array(
            "key" => -1,
            "pesan" => "Upload kosong",
            "kode" => 500
        );

        $finalArray=array();
        $a = array();

        if (isset($request->c_file_peserta)) {
            $filePeserta = $request->file('c_file_peserta');
            $arr1 = array('c_file_peserta' => $filePeserta);
            $a = $a + $arr1;
        }
        if (isset($request->c_file_surat_permohonan)) {
            $fileSP = $request->file('c_file_surat_permohonan');
            $arr2 = array('c_file_surat_permohonan' => $fileSP);
            $a = $a + $arr2;
        }

        $fileExtensionsAllowed = ['jpeg','jpg','png','JPG','JPEG','PNG','pdf','xlsx','xls','docx','doc'];
        
        if ($a != null) {
            foreach ($a as $key => $value) {
                $files = $a[$key];
                $fileName = $files->getClientOriginalName();
                $fileSize = $files->getSize();
                $fileExtension =  $files->getClientOriginalExtension();
                $errors= [];
    
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
                    $didUpload = $this->uploadFile($files, $fileName,$fileExtension);
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
        }

        return response()->json($token);

    }

    private function uploadFile($files, $fileName, $fileExtension)
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

    public function listIsturaDt(Request $request)
    {
        $hasil = $this->listIsturaByPemohon($request);
        $banyak = $this->getBanyakListIstura($request);

        $token = array(
            'recordsTotal' => $banyak,
            'data' => $hasil
        );

        return response()->json($token);
    }

    public function getBanyakListIstura(Request $request)
    {
        $where = '';
        if (isset($request->columnFilters)) {
            if (
                !empty($request->columnFilters["rombongan"]) &&
                $request->columnFilters["rombongan"] !== "null"
            ) {
                $where .= " and upper(n_rombongan)  like concat('%',upper('" .
                $request->columnFilters["rombongan"] . "'),'%') "; 
            }
            if (
                !empty($request->columnFilters["tanggal"]) &&
                $request->columnFilters["tanggal"] !== "null"
            ) {
                $where .= " and d_tanggal_kedatangan = " . $request->columnFilters["tanggal"] . " "; 
            }
        }

        $hasil = DB::selectOne("SELECT  
                                count(tir.i_id_istura) as banyak
                                from tm_istura tir
                                join tr_user tus on tus.i_id = tir.i_id_pemohon 
                                where tus.i_id = :id
                                $where", ['id' => $request->id]);
        return $hasil->banyak;
    }

    public function listIsturaById(string $id)
    {
        $hasil = DB::select("SELECT  
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
        where tir.i_id_istura = :id ", [':id' => $id]);

        foreach($hasil as $value) {
            if ($value->file_peserta != null) {
                $dataFilePeserta = $this->_getpresignedurl($value->file_peserta)->getData();
                $value->file_peserta = $dataFilePeserta->presignedUrl;
                $value->nama_file_peserta = $dataFilePeserta->originalFileName;
            }
            if ($value->file_surat_permohonan != null) {
                $dataFileSP = $this->_getpresignedurl($value->file_surat_permohonan)->getData();
                $value->file_surat_permohonan = $dataFileSP->presignedUrl;
                $value->nama_file_surat_permohonan = $dataFileSP->originalFileName;
            }
        }

        $token = array(
            "data" => $hasil
        );

        return response()->json($token);
    }
    
    public function listIsturaByPemohon(Request $request)
    {
        $where = '';
        if (isset($request->columnFilters)) {
            if (
                !empty($request->columnFilters["rombongan"]) &&
                $request->columnFilters["rombongan"] !== "null"
            ) {
                $where .= " and upper(n_rombongan)  like concat('%',upper('" .
                $request->columnFilters["rombongan"] . "'),'%') "; 
            }
            if (
                !empty($request->columnFilters["tanggal"]) &&
                $request->columnFilters["tanggal"] !== "null"
            ) {
                $where .= " and d_tanggal_kedatangan = '" . $request->columnFilters["tanggal"] . "'"; 
            }
        }

        $orderField = " order by tir.d_created_date desc ";
        if (!empty($request->sort[0]['field']) && !empty($request->sort[0]['type'])) {
            $typesort = $request->sort[0]['type'] === "none" ? "asc" : $request->sort[0]['type'];
            $orderField = " order by  " . $request->sort[0]['field'] . "      " . $typesort;
        }

        $hasil = DB::select("SELECT  
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
        tir.d_tanggal_surat as tanggal_surat,
        tir.c_surat_izin as surat_izin
        from tm_istura tir
        join tr_istana ti 
        on ti.i_id = tir.i_id_istana
        join tr_jam tjam
        on tjam.i_id_jam = tir.d_jam_kedatangan
        join tr_user tus 
        on tus.i_id = tir.i_id_pemohon
        where tus.i_id = :id
        $where $orderField 
        limit " . $request->perPage . "  offset  " . $request->page , [':id' => $request->id]
        );

        foreach ($hasil as $key => $value) {
            $value->tanggal_kedatangan = Carbon::createFromFormat('Y-m-d', $value->tanggal_kedatangan) //
                                                 ->locale("id")                                        // Carbon format
                                                 ->settings(['formatFunction' => 'translatedFormat'])  // untuk mengubah format tanggal MySql ke Format tanggal Indonesia
                                                 ->format('j F Y');                                    //     
            
            if($value->surat_izin != null) {
                $keySurat = DB::selectOne("SELECT c_file_surat as file_surat
                                      FROM tm_surat ts 
                                      JOIN tm_istura tis ON tis.c_surat_izin = ts.i_id
                                      WHERE ts.i_id = :id", [':id' => $value->surat_izin]);
                                                      
                $hasil[$key]->surat_izin = $this->_getpresignedurl($keySurat->file_surat)->getData()->presignedUrl;
            }                                                 
        }

        return $hasil;
    }

    public function deleteIstura(Request $request)
    {
        $token = array(
        "data" => -1,
        "pesan" => "Hapus gagal, data masih digunakan",
        "kode" => 200
        );

        $files = DB::select("SELECT c_file_peserta as file_peserta, c_file_surat_permohonan as file_surat_permohonan
                             FROM tm_istura
                             WHERE i_id_istura = :id", [':id' => $request->id]);


        $hasil = DB::delete('DELETE
                            from 
                                tm_istura
                            WHERE
                                i_id_istura = ?', [$request->id]);
        $token["data"] = $hasil;
        $token["pesan"] = "Hapus Berhasil";
        
        foreach ($files as $value) {
            $this->doDelete($value->file_peserta);
            $this->doDelete($value->file_surat_permohonan);
        }

        // Tambah Log Istura
        DB::table('tm_log_istura')->insert([
            'i_id_user' => $request->idUser,
            'i_id_istura' => $request->id,                   
            'n_aktivitas' => 'Menghapus permohonan istura',
            'c_status' => 0
        ]);

        return response()->json($token);
    }

    public function getJamIstura(Request $request) {
        $getCurrentSelectedJam = "";
        if (isset($request->id)) { // untuk get jam ketika edit istura
            $getCurrentSelectedJam .= "UNION
                                       SELECT tj.i_id_jam as id, tj.d_jam as jam
                                       from tm_istura ti
                                       join tr_jam tj on tj.i_id_jam = ti.d_jam_kedatangan 
                                       where ti.i_id_istura = '$request->id'
                                       order by id asc";
        }            
        $sql = "SELECT distinct tjam.i_id_jam as id, tjam.d_jam as jam
                from tr_jam tjam
                join tm_jadwal tjad
                on tjam.i_id_jam between tjad.i_tr_jam_buka and tjad.i_tr_jam_tutup 
                where tjad.i_id_istana = :id_istana_jad
                and tjam.i_id_jam not between 21 and 27 -- jam istirahat (12:00 - 13:30)
                and tjam.d_jam not in (
                    select tjam.d_jam
                    from tm_istura tis
                    join tr_jam tjam 
                    on tjam.i_id_jam = tis.d_jam_kedatangan 
                    where tis.d_tanggal_kedatangan = :tanggal
                    and tis.i_id_istana = :id_istana_lib
                )
                $getCurrentSelectedJam
                ";    
        $data = array(
            'id_istana_jad' => $request->idIstana, 
            'tanggal' => $request->tanggal,
            'id_istana_lib' => $request->idIstana
        );
        $hasil = DB::select($sql, $data);
        $token = array(
            "data" => $hasil
        );
        return response()->json($token);                
    }

    public function getTanggalLibur(Request $request)
    {
        $sql = "SELECT tis.d_tanggal_kedatangan as tanggal
                from tm_istura tis 
                where tis.i_id_istana = :id_istana_1
                and tis.d_tanggal_kedatangan >= DATE_ADD(NOW(), INTERVAL 1 DAY)
                group by d_tanggal_kedatangan 
                having GROUP_CONCAT(tis.d_jam_kedatangan order by tis.d_jam_kedatangan separator ',') = (
                    select GROUP_CONCAT(tjam.i_id_jam)
                    from tr_jam tjam
                    join tm_jadwal tj 
                    on tjam.i_id_jam BETWEEN tj.i_tr_jam_buka and tj.i_tr_jam_tutup 
                    where tj.i_id_istana = :id_istana_2
                )
                or SUM(tis.c_jumlah_peserta) = (
                    select ti.c_kuota_istura 
                    from tr_istana ti 
                    where ti.i_id = :id_istana_3
                ) 
                union
                select tl.d_tanggal as tanggal
                from tm_libur tl
                where tl.i_id_istana = :id_istana_4
                and tl.d_tanggal >= DATE_ADD(NOW(), INTERVAL 1 DAY)  
                ";
        $data = array(
            'id_istana_1' => $request->idIstana,
            'id_istana_2' => $request->idIstana,
            'id_istana_3' => $request->idIstana,
            'id_istana_4' => $request->idIstana
        );
        $hasil =  DB::select($sql, $data);
        $token = array(
            "data" => $hasil
        );
        return response()->json($token);
    }

    public function getKuotaIstura(Request $request)
    {
        $jumlahPesertaIstura = 0;
        if (isset($request->id)) {
            $getJumlahPeserta = DB::selectOne("SELECT c_jumlah_peserta as jumlah_peserta FROM tm_istura WHERE i_id_istura = '$request->id'");
            $jumlahPesertaIstura = $getJumlahPeserta->jumlah_peserta;

            $getTanggalIstura = DB::selectOne("SELECT d_tanggal_kedatangan as tanggal FROM tm_istura WHERE i_id_istura = '$request->id'");
            $tanggalIstura = $getTanggalIstura->tanggal;

            if ($tanggalIstura != $request->tanggal) {
                $jumlahPesertaIstura = 0;
            }
        }
        $sql = "SELECT SUM(kuota.kuota + $jumlahPesertaIstura) as kuota
                from (
                    SELECT  
                                case 
                                    when (SELECT sum(ti.c_kuota_istura - (                          
                                                select sum(c_jumlah_peserta)
                                                from tm_istura                                              
                                                where i_id_istana = :id_istana_1
                                                and d_tanggal_kedatangan = :tanggal_1
                                                and c_status != 2 -- status ditolak
                                            )) as kuota from tr_istana ti where ti.i_id = :id_istana_2) > 0 -- jika kuota istura masih ada
                                    then 
                                        (SELECT sum(ti.c_kuota_istura - (
                                                select sum(c_jumlah_peserta)
                                                from tm_istura
                                                where i_id_istana = :id_istana_3
                                                and d_tanggal_kedatangan = :tanggal_2
                                                and c_status != 2 -- status ditolak
                                            )) as kuota from tr_istana ti where ti.i_id = :id_istana_4)
                                    when (SELECT sum(ti.c_kuota_istura - (                          
                                                select sum(c_jumlah_peserta)
                                                from tm_istura                                              
                                                where i_id_istana = :id_istana_5
                                                and d_tanggal_kedatangan = :tanggal_3
                                                and c_status != 2 -- status ditolak
                                            )) as kuota from tr_istana ti where ti.i_id = :id_istana_6) <= 0 -- jika kuota istura sudah habis
                                    then 0       
                                    else (select ti.c_kuota_istura -- jika tidak ada istura di tanggal tersebut
                                            from tr_istana ti 
                                            where ti.i_id = :id_istana_7)
                                end as kuota
                ) as kuota";
        $data = array(
            'id_istana_1' => $request->idIstana,
            'id_istana_2' => $request->idIstana,
            'id_istana_3' => $request->idIstana,
            'id_istana_4' => $request->idIstana,
            'id_istana_5' => $request->idIstana,
            'id_istana_6' => $request->idIstana,
            'id_istana_7' => $request->idIstana,
            'tanggal_1' => $request->tanggal,
            'tanggal_2' => $request->tanggal,
            'tanggal_3' => $request->tanggal
        );
        $hasil = DB::select($sql, $data);
        $token = array(
            "data" => $hasil
        );
        return response()->json($token);        
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
            "url" => $url,
            "presignedUrl" => $request->getUri(),
            "imgProxyUrl" => $proxyImg,
            "originalFileName" => $originalFileName,
            "pesan" => "Preview berhasil",
            "kode" => 200
        );
        return response()->json($token);
    }

    public function getpresignedurl(Request $request)
    {
        try {
            $key = $request["key_gambar"];
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
                "url" => $url,
                "presignedUrl" => $request->getUri(),
                "imgProxyUrl" => $proxyImg,
                "originalFileName" => $originalFileName,
                "pesan" => "Preview berhasil",
                "kode" => 200
            );
            return response()->json($token);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                "pesan" => "Preview gagal",
                "kode" => 500,
                "error" => $th->getMessage()
            ]);
        }
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

    private function doDelete($key)
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

        $result = $s3->deleteObject([
            'Bucket' => getenv('MINIO_BUCKET'),
            'Key' => $key
        ]);

        return $result;
    }
}


