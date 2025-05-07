<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\IsturaController;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api', 'middleware' => 'cors'], function () use ($router) {
    // PORTAL ISTANA
    // AUTH CONTROLLER
    $router->post('/auth/authorize', "AuthController@autentifikasi");

    // USER CONTROLLER
    $router->post('/users/adduserjson', "UserController@adduserjson");
    $router->post('/users/verifikasi', "UserController@verifikasiUser");
    $router->post('/users/kirim-ulang-kode', "UserController@updateVerCode");
    $router->get('/users/{id}', "UserController@getuserbyid");
    $router->put('/users/{id}', "UserController@updateUser");
    $router->post('/users/check-password/{id}', "UserController@checkpass");
    $router->put('/users/update-password/{id}', "UserController@updatepass");
    $router->get('/users/reset-password/{token}', "UserController@validTokenPasswordReset");
    $router->post('/users/reset-password', "UserController@submitResetPasswordForm");

    // ISTURA CONTROLLER
    $router->post('/istura/addistura', ['middleware' => 'authjwt:up', 'uses' => "IsturaController@addisturadt"]);
    $router->post('/istura/do-upload', ['middleware' => 'authjwt:up' ,'uses' => "IsturaController@doUpload"]);
    $router->post('/istura/lististura', ['middleware' => 'authjwt:up' ,'uses' => "IsturaController@listIsturaDt"]);
    $router->post('/istura/delete', ['middleware' => 'authjwt:up' ,'uses' => "IsturaController@deleteIstura"]);
    $router->get('/istura/getbyid/{id}', ['middleware' => 'authjwt:up' ,'uses' => "IsturaController@listIsturaById"]);
    $router->post('/istura/get-jam-istura', ['middleware' => 'authjwt:up' ,'uses' => "IsturaController@getJamIstura"]);
    $router->post('/istura/get-tanggal-istura', ['middleware' => 'authjwt:up' ,'uses' => "IsturaController@getTanggalLibur"]);
    $router->post('/istura/presignedurl', "IsturaController@getpresignedurl");
    $router->post('/istura/get-kuota-istura', "IsturaController@getKuotaIstura");

    // PERMINTAAN DOKUMENTASI CONTROLLER
    $router->post('/dokumentasi/addpermintaan', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@addpermintaan"]);
    $router->post('/dokumentasi/do-upload', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@doUpload"]);
    $router->post('/dokumentasi/listByIdUser', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@listDokumentasiDt"]);
    $router->get('/dokumentasi/getbyid/{id}', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@getDokumentasiById"]);
    $router->post('/dokumentasi/presignedurl', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@getpresignedurl"]);
    $router->post('/dokumentasi/list-permintaan-foto', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@listPermintaanFoto"]);
    $router->post('/dokumentasi/ubah-permintaan-foto', ['middleware' => 'authjwt:up' ,'uses' => "PermintaanHasilDokumentasiController@updateListPermintaanFoto"]);

    // BERITA CONTROLLER
    $router->get('/berita/getlistberita', "BeritaController@getPublishedBerita");
    $router->get('/berita/getbyid/{id}', "BeritaController@getBeritaById");
    $router->post('/berita/presignedurl', "BeritaController@getpresignedurl");
    $router->post('/berita/getbyistana', "BeritaController@getBeritaByIstana");

    // ISTANA CONTROLLER
    $router->get('/istana/get-active-istana', "IstanaController@getActiveIstana");
    $router->get('/istana/get-all-istana', "IstanaController@getAllIstana");
    $router->get('/istana/get-id-nama-istana', "IstanaController@getIdNamaIstanaAll");
    $router->get('/istana/getbyid/{id}', "IstanaController@getIstanaById");
    $router->post('/istana/presignedurl', "IstanaController@getpresignedurl");
    $router->get('/istana/get-header/{id}', "IstanaController@getHeaderIstana");
    $router->get('/istana/get-hari-libur/{id}', "IstanaController@getHariLiburIstana");

    // GALERI CONTROLLER
    $router->post('/galeri/list-media', "GaleriController@listMediaDt");
    $router->get('/galeri/list-media-by-id/{media}/{id}', "GaleriController@listMediaById");
    
    // BANGUNAN CONTROLLER
    $router->post('/bangunan/list-bangunan', "BangunanController@listBangunanDt");
    $router->get('/bangunan/list-bangunan-by-id/{id}', "BangunanController@listBangunanById");

    // KUISIONER CONTROLLER
    $router->get('/kuisioner/send-link-kuisioner', ['middleware' => 'kuisioner', 'uses' => "KuisionerController@sendLinkKuisioner"]);
    $router->get('/kuisioner/validate/{token}', "KuisionerController@validTokenKuisioner");
    $router->get('/kuisioner/get-pertanyaan/{idIstana}/{idIstura}', "KuisionerController@getPertanyaanKuisioner");
    $router->post('/kuisioner/submit', "KuisionerController@submitKuisioner");

    // MENU CONTROLLER
    $router->get('/menu/get-active-menu', "MenuController@getActiveMenu");

    //POPUP CONTROLLER
    $router->post('/popup', "PopupController@getPopup");

    // LIST APP CONTROLLER
    $router->get('/list-app', "ListAppController@getApp");

    // KONTAK CONTROLLER
    $router->post('/kontak', "KontakController@kontak");

    // SEARCH CONTROLLER
    $router->post('/search', "SearchController@search");
});
