<?php

namespace App\Http\Controllers;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BangunanController extends Controller
{
    public function listBangunanDt(Request $request)
    {
        $hasil = $this->listBangunan($request);
        $banyak = $this->getBanyakBangunan($request);
        $token = array(
            'recordsTotal' => $banyak,
            'data' => $hasil
        );
        
        return response()->json($token);
    }

    public function listBangunan(Request $request)
    {
        $where = "";
        $params = [];

        if ($request->idIstana != 0) {
            $where .= " AND i_id_istana = :idIstana";
            $params['idIstana'] = $request->idIstana;
        }

        $order = " ORDER BY d_created_date DESC";

        $query = "SELECT 
                    i_id, 
                    c_gambar, 
                    n_nama_bangunan, 
                    e_deskripsi_bangunan, 
                    d_created_date, 
                    i_id_istana, 
                    JSON_EXTRACT(c_gambar, '$[0]') as link_gambar -- ambil link gambar pertama
                FROM 
                    tm_bangunan
                WHERE
                    c_status = 1      
                    $where
                $order
                LIMIT 10";

        $hasil = DB::select($query, $params);

        foreach ($hasil as $value) {
            $value->c_gambar = json_decode($value->c_gambar);
            if ($value->link_gambar != null) {
                $value->link_gambar = $this->getpresignedurl(null, json_decode($value->link_gambar))->getData()->presignedUrl;
            }
        }

        return $hasil;

    }
    public function getBanyakBangunan(Request $request)
    {
        $where = "";
        $params = [];

        if ($request->idIstana != 0) {
            $where .= " AND i_id_istana = :idIstana";
            $params['idIstana'] = $request->idIstana;
        }

        $hasil = DB::selectOne("SELECT COUNT(*) as banyak FROM tm_bangunan WHERE c_status = 1 $where", $params);

        return $hasil->banyak;
    }

    public function listBangunanById(string $id)
    {
        $sql = "SELECT 
                    i_id, 
                    c_gambar, 
                    n_nama_bangunan, 
                    e_deskripsi_bangunan, 
                    d_created_date, 
                    i_id_istana, 
                    c_gambar as link_gambar 
                FROM 
                    tm_bangunan
                WHERE 
                    i_id = :id";

        $hasil =  DB::selectOne($sql, ['id' => $id]);
        $hasil->c_gambar = json_decode($hasil->c_gambar);
        $hasil->link_gambar = json_decode($hasil->link_gambar);

        foreach ($hasil->link_gambar as $key => $value) {
            $hasil->link_gambar[$key] = $this->getpresignedurl(null, $value)->getData()->presignedUrl;
        }

        $token = array(
            'data' => $hasil,
        );

        return response()->json($token);
    }

    public function getpresignedurl(Request $request = null, $key = null): \Illuminate\Http\JsonResponse
    {
        if (isset($request->key) && $request->key != null) {
            $key = $request->key;
        }

        if ($key == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'key is required'
            ], 400);
        }

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

        $url = $s3->getObjectUrl(env('MINIO_BUCKET'), $key);

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
