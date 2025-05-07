<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;


class IstanaController extends Controller
{
    public function getActiveIstana()
    {
        $idIstanaAll = 'deb3a401-232f-4525-8a7b-9afdb354df6f';
        $sql = "SELECT 
                    i_id as id, 
                    n_nama_istana as nama_istana,
                    c_gambar as gambar,
                    e_deskripsi_istana as deskripsi_istana 
                FROM 
                    tr_istana  
                WHERE 
                    c_aktif = 1
                AND 
                    i_id != '$idIstanaAll'";

        $hasil =  DB::select($sql);

        if ($hasil != null) {
            foreach ($hasil as $value) {
                $value->gambar = $this->getpresignedurl(null, $value->gambar)->getData()->presignedUrl;
            }
        }

        $token = array(
            "data" => $hasil
        );
        return response()->json($token);
    }

    public function getAllIstana()
    {
        $idIstanaAll = 'deb3a401-232f-4525-8a7b-9afdb354df6f';
        $sql = "SELECT 
                    i_id as id, 
                    n_nama_istana as nama_istana,
                    c_gambar as gambar,
                    e_deskripsi_istana as deskripsi_istana 
                FROM 
                    tr_istana  
                WHERE
                    i_id != '$idIstanaAll'";

        $hasil =  DB::select($sql);

        if ($hasil != null) {
            foreach ($hasil as $value) {
                $value->gambar = $this->getpresignedurl(null, $value->gambar)->getData()->presignedUrl;
            }
        }

        $token = array(
            "data" => $hasil
        );
        return response()->json($token);
    }

    public function getIdNamaIstanaAll()
    {
        $idIstanaAll = 'deb3a401-232f-4525-8a7b-9afdb354df6f';
        $orderField = " ORDER BY FIELD(
            i_id,
            'df065cf0-622f-49f8-8d4f-b66eef2ebce5',
            'c0785194-c033-414f-961c-52461e794b0b',
            'ad779b6f-6af6-4974-ad2e-9385be62a0cd', 
            'ea48432d-127a-458a-903b-d7d5bfd0cb66', 
            '393c9f8c-5ca8-49bc-9a57-df572389f275'
            ) ";

        $sql = "SELECT 
                    i_id as id, 
                    n_nama_istana as nama_istana 
                FROM 
                    tr_istana  
                WHERE
                    i_id != '$idIstanaAll'
                $orderField";

        $hasil =  DB::select($sql);

        $token = array(
            "data" => $hasil
        );

        return response()->json($token);
    }

    public function getIstanaById(string $id)
    {
        $results = DB::select(
            "SELECT 
                i_id as id, 
                n_nama_istana as nama_istana,
                e_deskripsi_istana as deskripsi,
                c_gambar,
                c_gambar as link_gambar 
            FROM 
                tr_istana  
            WHERE 
                i_id= :id",
            [':id' => $id]
        );

        $token = array(
            "data" => $results[0]
        );

        $results[0]->link_gambar = $this->getpresignedurl(null, $results[0]->c_gambar)->getData()->presignedUrl;

        return response()->json($token);
    }

    public function getHariLiburIstana(string $id)
    {
        $results = DB::selectOne(
            "SELECT 
                i_id as id, 
                n_nama_istana as nama_istana,
                c_hari_libur as hari_libur
            FROM 
                tr_istana  
            WHERE 
                i_id= :id",
            [':id' => $id]
        );

        $results->hari_libur = json_encode($results->hari_libur);
        
        $token = array(
            "data" => $results
        );

        return response()->json($token);
    }

    public function getHeaderIstana(string $id)
    {
        $data = DB::selectOne('SELECT
                                i_id,
                                n_nama_istana,
                                c_header
                            FROM
                                tr_istana
                            WHERE
                                i_id = ?', [$id]);
        
        $header = array();
        if ($data != null && $data->c_header != null) {
            $data->c_header = json_decode($data->c_header, true);
            foreach ($data->c_header as $v) {
                $temp = array(
                    "gambar" => $v["gambar"] != null ? $this->getpresignedurl(null, $v["gambar"])->getData()->presignedUrl : null,
                    "deskripsi" => $v["deskripsi"]
                );
                array_push($header, $temp);
            }
        } else {
            $data = null;
        }

        $data->c_header = $header;

        $token = array(
            'data' => $data
        );

        return response()->json($token);
    }

    public function getpresignedurl(Request $request = null, $key = null): \Illuminate\Http\JsonResponse
    {
        $key = $request["key_gambar"] ?? $key;

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
}
