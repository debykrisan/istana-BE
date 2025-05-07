<?php

namespace App\Http\Controllers;


use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Error;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\String_;

class GaleriController extends Controller
{
    public function listMediaDt(Request $request)
    {
        $hasil = $this->listMedia($request);
        $banyak = $this->getBanyakMedia($request);
        $token = array(
            'recordsTotal' => $banyak,
            'data' => $hasil
        );
        
        return response()->json($token);
    }

    public function listMedia(Request $request)
    {
        $hasil = DB::select("SELECT 
                                i_id_media, 
                                c_media, 
                                n_judul, 
                                n_fotografer, 
                                e_keterangan, 
                                d_tanggal,
                                d_jam,
                                c_key, 
                                JSON_EXTRACT(c_key, '$[0]') as link_gambar -- ambil link gambar pertama
                            FROM 
                                tm_media 
                            WHERE 
                                c_media = :c_media
                            AND
                                c_is_publish = 1    
                            ORDER BY
                                d_created_date desc",
                            ['c_media' => $request->media]
        );

        foreach ($hasil as $value) {
            if ($value->d_tanggal != null) {
                $value->d_tanggal = Carbon::createFromFormat('Y-m-d', $value->d_tanggal)->locale("id")->translatedFormat('l, j F Y');
            }
            if ($value->d_jam != null) {
                $value->d_jam = Carbon::createFromFormat('H:i:s', $value->d_jam)->format('H:i');
            }
            $value->c_key = json_decode($value->c_key);
            if ($value->link_gambar != null) {
                $value->link_gambar = $this->getpresignedurl(null, json_decode($value->link_gambar))->getData()->presignedUrl;
            }
        }

        return $hasil;
    }
    public function getBanyakMedia(Request $request)
    {
        $hasil = DB::selectOne("
            SELECT 
                count(*) as banyak 
            FROM 
                tm_media 
            WHERE 
                c_media = :c_media
            AND
                c_is_publish = 1 
        ", ['c_media' => $request->media]);

        return $hasil->banyak;
    }

    public function listMediaById(string $media, string $id)
    {
        $sql = "SELECT 
                    i_id_media, 
                    c_media, 
                    n_judul, 
                    n_fotografer, 
                    e_keterangan, 
                    d_tanggal,
                    d_jam,
                    c_key,
                    c_key as link_gambar,
                    date(d_created_date) as tanggal,
                    time(d_created_date) as waktu 
                FROM 
                    tm_media 
                WHERE 
                    c_media = $media
                    and i_id_media = '$id'";

        $hasil =  DB::selectOne($sql);
        
        $hasil->c_key = json_decode($hasil->c_key);
        $hasil->link_gambar = json_decode($hasil->link_gambar);
        if ($hasil->d_tanggal != null) {
            $hasil->d_tanggal = Carbon::createFromFormat('Y-m-d', $hasil->d_tanggal)->locale("id")->translatedFormat('l, j F Y');
        }
        if ($hasil->d_jam != null) {
            $hasil->d_jam = Carbon::createFromFormat('H:i:s', $hasil->d_jam)->format('H:i');
        }

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
