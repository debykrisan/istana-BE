<?php
namespace App\Http\Controllers;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Error;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\String_;

class BeritaController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  public function getPublishedBerita(Request $request)
  {
    $hasilHeadline = $this->getHeadlineBerita($request);

    $sql =
      " SELECT 
          ROW_NUMBER() OVER( ORDER BY d_publish_date DESC) as rn, 
          i_id_berita as id,
          i_id_istana, 
          c_id_gambar, 
          n_keterangan_foto,
          n_judul, 
          e_berita, 
          date(d_publish_date) as tanggal, 
          n_tag
        FROM 
          tm_berita  
        WHERE 
          c_is_publish = 1
        ORDER BY 
          d_publish_date DESC
      ";

    $hasil =  DB::select($sql);

    foreach ($hasil as $value) {
      $value->c_id_gambar = $this->_getpresignedurl($value->c_id_gambar)->getData()->presignedUrl;
      $value->tanggal = Carbon::createFromFormat('Y-m-d', $value->tanggal)->locale("id")->translatedFormat('j F Y');
    }

    $token = array(
      "data" => $hasil,
      "headline" => $hasilHeadline
    );
    return response()->json($token);
  }


  public function getBeritaById(string $id)
  {
    $results = DB::select(
      " SELECT 
          ROW_NUMBER() OVER( ORDER BY i_id_berita) as rn, 
          i_id_berita as id,
          i_id_istana, 
          c_id_gambar,
          n_keterangan_foto, 
          n_judul,
          n_fotografer, 
          e_berita, 
          d_publish_date, 
          n_tag
        FROM 
          tm_berita  
        WHERE 
          c_is_publish = 1
        AND 
          i_id_berita= :id  ",
      [':id' => $id]
    );

    foreach ($results as $value) {
      $value->c_id_gambar = $this->_getpresignedurl($value->c_id_gambar)->getData()->presignedUrl;
    }

    $token = array(
      "data" => $results[0]
    );
    return response()->json($token);
  }

  public function getBeritaByIstana(Request $request)
  {
    $hasilHeadline = $this->getHeadlineBerita($request);

    $results = DB::select(
      " SELECT 
          ROW_NUMBER() OVER( ORDER BY d_publish_date desc) as rn, 
          i_id_berita as id,
          i_id_istana, 
          c_id_gambar, 
          n_keterangan_foto,
          n_judul, 
          e_berita, 
          d_publish_date, 
          n_tag
        FROM 
          tm_berita  
        WHERE 
          c_is_publish = 1
        AND 
          i_id_istana = :id  
        ORDER BY
          d_publish_date DESC",
      [':id' => $request->idIstana]
    );

    foreach ($results as $value) {
      $value->c_id_gambar = $this->_getpresignedurl($value->c_id_gambar)->getData()->presignedUrl;
    }

    $token = array(
      "data" => $results,
      "headline" => $hasilHeadline
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

  public function getpresignedurl(Request $request): \Illuminate\Http\JsonResponse
  {
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

  private function getHeadlineBerita(Request $request){
    $orderField = "ORDER BY 
                    d_headline_date DESC, 
                    FIELD(i_id_istana,
                      'df065cf0-622f-49f8-8d4f-b66eef2ebce5',
                      'c0785194-c033-414f-961c-52461e794b0b',
                      'ad779b6f-6af6-4974-ad2e-9385be62a0cd', 
                      'ea48432d-127a-458a-903b-d7d5bfd0cb66', 
                      '393c9f8c-5ca8-49bc-9a57-df572389f275')
                     ";

    $where = "";
    $params = [];

    if (isset($request->idIstana)) {
      $where .= " AND i_id_istana = :idIstana ";
      $params['idIstana'] = $request->idIstana;
    }

    $results = DB::select(
      " SELECT 
          ROW_NUMBER() OVER($orderField) as rn, 
          i_id_berita as id,
          i_id_istana, 
          c_id_gambar,
          n_keterangan_foto, 
          n_judul, 
          e_berita, 
          date(d_publish_date) as tanggal, 
          n_tag
        FROM 
          tm_berita  
        WHERE 
          c_is_publish = 1
        AND 
          c_is_headline = 1
        $where
        $orderField",
      $params
    );

    foreach ($results as $value) {
      $value->c_id_gambar = $this->_getpresignedurl($value->c_id_gambar)->getData()->presignedUrl;
      $value->tanggal = Carbon::createFromFormat('Y-m-d', $value->tanggal)->locale("id")->translatedFormat('j F Y');
    }
    $results = array_slice($results, 0, 2);

    return $results;

  }
}
