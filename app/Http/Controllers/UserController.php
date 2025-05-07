<?php

namespace App\Http\Controllers;

use DateTime;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

use DateTimeZone;



class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function getuserbyid(string $id)
    {

        $results = DB::select("
        SELECT i_id as id, 
        n_email as email, 
        c_kartu_identitas as kartu_identitas, 
        c_nomor_identitas as nomor_identitas, 
        c_nama_lengkap as nama_lengkap, 
        c_nomor_telpon as nomor_telpon, 
        n_alamat as alamat  
        FROM tr_user
        WHERE i_id = :id ;", [':id' => $id]
        );

        $token = array(
            "data" => $results
        );

        if (count($results) > 0) {
            return response()->json($token);
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }

    }

    private function get_users_dt(Request $request)
    {
        $where = '';
        if (isset($request->columnFilters)) {
            if (!empty($request->columnFilters["namaUser"]) &&
                $request->columnFilters["namaUser"] !== "null") {
                $where .= "  and  upper(a.username)  like concat('%',upper('" .
                    $request->columnFilters["namaUser"] . "'),'%') ";
            }

            if (!empty($request->columnFilters["namaDepan"]) &&
                $request->columnFilters["namaDepan"] !== "null") {
                $where .= "  and  upper(a.first_name)  like concat('%',upper('" .
                    $request->columnFilters["namaDepan"] . "'),'%') ";
            }

            if (!empty($request->columnFilters["istana"]) &&
                $request->columnFilters["istana"] !== "null") {
                $where .= "  and   a.company = '" .
                    $request->columnFilters["istana"] . "'  ";
            }

        }

        $orderField = "  ";
        if (!empty($request->sort[0]['field']) && !empty($request->sort[0]['type'])) {
            $typesort = $request->sort[0]['type'] === "none" ? "asc" : $request->sort[0]['type'];
            $orderField = " order by  " . $request->sort[0]['field'] . "      " . $typesort;
        }


        return DB::select("  SELECT
                    DISTINCT (a.id) as id,
                    a.username,
                    a.email,
                    a.active,
                    a.first_name,
                    a.last_name,
                    ti.n_istana
                FROM
                    users a left join tm_istana ti on ti.id = a.company
                      where 1=1
                                $where   $orderField

                      limit  " . $request->perPage . "  offset  " . $request->page);

    }

    private function get_banyak_usersdt(Request $request)
    {
        $where = '';
        if (isset($request->columnFilters)) {
            if (!empty($request->columnFilters["namaUser"]) &&
                $request->columnFilters["namaUser"] !== "null") {
                $where .= "  and  upper(a.username)  like concat('%',upper('" .
                    $request->columnFilters["namaUser"] . "'),'%') ";
            }

            if (!empty($request->columnFilters["namaDepan"]) &&
                $request->columnFilters["namaDepan"] !== "null") {
                $where .= "  and  upper(a.first_name)  like concat('%',upper('" .
                    $request->columnFilters["namaDepan"] . "'),'%') ";
            }

            if (!empty($request->columnFilters["istana"]) &&
                $request->columnFilters["istana"] !== "null") {
                $where .= "  and   a.company = '" .
                    $request->columnFilters["istana"] . "'  ";
            }

        }
        $hasil = DB::selectOne("  SELECT
                COUNT( DISTINCT (a.id) ) as banyak
            FROM
                users a left join tm_istana ti on ti.id = a.company  where 1=1     $where  ");
        return $hasil->banyak;

    }

    public function listuserdt(Request $request)
    {

        $hasil = $this->get_users_dt($request);
        $banyak = $this->get_banyak_usersdt($request);
        $token = array(
            "recordsTotal" => $banyak,
            "recordsFiltered" => $banyak,
            "data" => $hasil
        );
        return response()->json($token);

    }

    private function cekemail($email)
    {

        $results = DB::selectOne("
            SELECT
            CASE count(n_email) 
                when 1 then true
                else false
            END as cek
            FROM
                tr_user
            where
                n_email = :email  ", [':email' => $email]
        );

        return $results->cek;

    }

    private function editUser(Request $request)
    {
        $hasil = null;
        $data = array(
            "email" => $request->email,
            "kartu_identitas" => $request->kartu_identitas,
            "nomer_identitas" => $request->nomer_identitas,
            "nama_lengkap" => $request->nama_lengkap,
            "phone" => $request->phone,
            "alamat"=> $request->alamat,
            "id" => $request->id
        );
        $hasil =  DB::update(" UPDATE
                        users
                    SET
                        email = :email,
                        kartu_identitas = :kartu_identitas,
                        nomer_identitas = :nomer_identitas,
                        nama_lengkap = :nama_lengkap,
                        phone = :phone,
                        alamat = :alamat
                    WHERE
                        id = :id  ", $data);

        if(isset( $request->password) && !empty( $request->password)){
            $data = array(
                'password' => base64_encode(password_hash( $request->password, PASSWORD_BCRYPT)),
                "id"=> $request->id
                
            );
            $hasil =  DB::update(" UPDATE
                            users
                        SET
                            password = :password
                        WHERE
                            id = :id ", $data);
        }
        return $hasil ;
    }

    public function updateUser(Request $request)
    {
        $hasil = null;
        $data = array(
            "n_email" => $request->email,
            "c_kartu_identitas" => $request->kartu_identitas,
            "c_nomor_identitas" => $request->nomor_identitas,
            "c_nama_lengkap" => $request->nama_lengkap,
            "c_nomor_telpon" => $request->nomor_telpon,
            "n_alamat"=> $request->alamat,
            "i_id" => $request->id
        );

        $hasil =  DB::update("UPDATE tr_user
                        SET n_email=:n_email, c_kartu_identitas=:c_kartu_identitas, 
                            c_nomor_identitas=:c_nomor_identitas, c_nama_lengkap=:c_nama_lengkap, 
                            c_nomor_telpon=:c_nomor_telpon, n_alamat =:n_alamat
                        WHERE i_id=:i_id ", $data);

        if (isset($request->password) && !empty($request->password)) {
            $data = array(
                'n_password' => base64_encode(password_hash($request->password, PASSWORD_BCRYPT)),
                "i_id"=> $request->id
                
            );
            $hasil =  DB::update(" UPDATE tr_user
                        SET n_password = :n_password
                        WHERE i_id = :i_id ", $data);
        }

        return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully'
            ], 201);
        

    }

    private function save_users_roles(Request $request)
    {
        $data = array(
            "id"=> $request->id
        );
        DB::delete(" DELETE FROM tm_users WHERE i_id_user = (:id)  ",$data);
        $groups = $request->groups;
        $iMax = count($groups);
        for ($i = 0; $i < $iMax; $i++) {
            DB::insert(" INSERT INTO users_groups (user_id, group_id) VALUES('" . $request->id . "', '" . $groups[$i] . "' ) ");
        }
        
    }
    private function addUser(Request $request)
    {
       $ver_code = substr(number_format(time()*rand(),0,'',''),0,6);
       
       $status =  $this->cekemail($request->email);
        if(!$status) {
            $id = DB::table('tr_user')->insertGetId([
                "n_email" => $request->email,
                'n_password' =>base64_encode(password_hash( $request->password, PASSWORD_BCRYPT)),
                "c_kartu_identitas" => $request->kartu_identitas,
                "c_nomor_identitas" => $request->nomer_identitas,
                "c_nama_lengkap" => $request->nama_lengkap,
                "c_nomor_telpon" => $request->phone,
                "n_alamat"=> $request->alamat,
                "c_ver_code"=> $ver_code,
                "c_is_verified" => 0
            ]);    
    
            $hasil = DB::select('select i_id, n_email, c_nama_lengkap, c_nomor_telpon, n_alamat from tr_user where n_email = :n_email', [':n_email' => $request->email]);
            if ($id) {
                $this->createMail($request);
                $data = array(
                    'i_id_user' => $id,
                    'i_id_roles' => 7 // id roles user publik
                );
                DB::insert("INSERT into tm_users
                            (i_id_user, i_id_roles, d_create_date)
                            VALUES (:i_id_user, :i_id_roles, CURRENT_TIMESTAMP())", $data);            
            }
            return $hasil;
        } else {
            return -1;
        }
    }

    public function adduserjson(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
            'kartu_identitas' => 'required',
            'nomer_identitas' => 'required',
            'nama_lengkap' => 'required',
            'phone' => 'required',
            'alamat' => 'required'
        ]);

        $id = $request->id ?? null;
        $hasil = null;
        if (isset($id) && !empty($id)) {
            $this->editUser($request);
            $this->save_users_roles($request);
            $hasil = $id;
        } else {
            $hasil = $this->addUser($request);
        }

        $token = array(
            "data" => $hasil,
            "pesan" => $hasil > 0 ? "Simpan Data Pengguna Berhasil":" Simpan gagal email sudah digunakan"
        );

        return response()->json($token);
    }

    public function updateVerCode(Request $request)
    {
        $ver_code = substr(number_format(time()*rand(),0,'',''),0,6);
        $hasil = DB::update("UPDATE tr_user SET c_ver_code = $ver_code WHERE n_email = '$request->email'");

        if ($hasil > 0) {
            $this->createMail($request);
        }

        $token = array(
            "data" => $hasil,
            "pesan" => $hasil > 0 ? "Kode Berhasil Dikirim!" : "Gagal Mengirim Ulang Kode"
        );

        return response()->json($token);
    }
    
    public function verifikasiUser(Request $request) {
        
        $this->validate($request, [
            'finalCode' => 'required',
            'email' => 'required'
        ]);

        $token = array(
            "data" => 0,
            "pesan" => "Verifikasi Pengguna Gagal"
        );
    
        $verCodeDb =  DB::selectOne('select c_ver_code from tr_user where n_email = :n_email', [':n_email' => $request->email]);
        if ($verCodeDb->c_ver_code == $request->finalCode) {
            $hasil = DB::update('update tr_user set c_is_verified = 1, c_is_active = 1 where n_email = :n_email', [':n_email' => $request->email]);
            $token = array(
                "data" => $hasil,
                "pesan" => $hasil > 0 ? "Verifikasi Pengguna Berhasil! Silahkan Login" : "Verifikasi Pengguna Gagal"
            );
        }
        return response()->json($token);
    }

    public function createMail(Request $request) {
        try {
            $email = $request->email;
            $results = DB::select("  SELECT c_nama_lengkap, c_ver_code, n_email FROM tr_user where n_email = '" . $email . "';");

            $token = array(
                "data" => $results
            );
    
            // Render the email view using Blade
            $html = view('mail', $token)->render();

            $client = new Client();

            $response = $client->post(env('SPRING_BOOT_SMTP_ENDPOINT'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'recipient' => $email,
                    'msgBody' => $html,
                    'subject' => 'Verifikasi Email',
                ],
            ]);
            
            return $response->getBody()->getContents();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    private function del_users(Request $request)
    {
        $hasil = -1;
        DB::beginTransaction();
        DB::delete(" DELETE FROM users_groups WHERE user_id='" . $request->id . "'  ");
        DB::delete(" DELETE FROM users WHERE id='" . $request->id . "'  ");
        $hasil = 1;
        DB::commit();
        return $hasil ;

    }
    public function deleteuserbyid(Request $request)
    {

        $hasil = $this->del_users($request);
        $token = array(
            "data" => $hasil,
            "pesan" =>$hasil > 0 ? "Hapus Data Pengguna Berhasil":" Hapus gagal "
        );
        return response()->json($token);

    }

    public function checkpass(Request $request, $id)
    {
        //validate password
        $this->validate($request, [
            'password' => 'required'
        ]);

        $data = array(
            'i_id' => $id
        );

        $hasil = DB::selectOne("SELECT n_password FROM tr_user WHERE i_id = :i_id", [':i_id' => $id]);

        $pass = base64_decode($hasil->n_password);

        if (password_verify($request->password, $pass)) {
            return response()->json(
                ['pesan' => 'success',], 
                200);
        } else {
            return response()->json(['pesan' => 'password tidak sama'], 401);
        }

    }

    public function updatepass(Request $request, $id) {
        $this->validate($request, [
            'password_baru' => 'required',
        ]);

        $uppercase = preg_match('@[A-Z]@', $request->password_baru);
        $lowercase = preg_match('@[a-z]@', $request->password_baru);
        $number    = preg_match('@[0-9]@', $request->password_baru);
        $specialChars = preg_match('@[^\w]@', $request->password_baru);

        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($request->password_baru) < 8) {
            return response()->json(
                ['pesan' => 'password minimal 8 karakter, 1 huruf besar, 1 angka, 1 karakter spesial'], 
                400
            );
        }
      
        $data = array(
            'i_id' => $id,
            'n_password' => base64_encode(password_hash($request->password_baru, PASSWORD_BCRYPT))
        );
        $hasil = DB::update("UPDATE tr_user SET n_password = :n_password WHERE i_id = :i_id", $data);

        if ($hasil > 0) {
            //update c_ver_code to null
            DB::update("UPDATE tr_user
                        SET c_ver_code = null
                        WHERE i_id = :i_id ", [':i_id' => $id]);
            return response()->json(['status' => 'success'], 201);
        } else {
            return response()->json(['status' => 'password gagal diubah'], 400);
        }
    }


    public function submitResetPasswordForm(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $email = $request->email;

        $results = DB::select("  SELECT c_nama_lengkap, c_ver_code, n_email FROM tr_user where n_email = '" . $email . "';");
        
        if (count($results) != 0) {
            try {
                $namaLengkap =  $results[0]->c_nama_lengkap;
                $code = $this->getCode($email);
            
                $createdDate = $this->getTime();
        
                $original_string = $code . "||" . $createdDate;  // Plain text/String
                $cipher_algo = "AES-128-CTR"; //The cipher method, in our case, AES 
                $iv_length = openssl_cipher_iv_length($cipher_algo); //The length of the initialization vector
                $option = 0; //Bitwise disjunction of flags
                $encrypt_iv = '8746376827619797'; //Initialization vector, non-null
                $encrypt_key = "tabeldata!"; // The encryption key
                
                $encryptedToken  = openssl_encrypt($original_string, $cipher_algo, $encrypt_key, $option, $encrypt_iv);
                $encoded = base64_encode($encryptedToken);
        
                //link url to reset password
                $url = url(env('PORTAL_BASE_URL') . '/reset-password/' . $encoded);
        
                $token = array(
                    "data" => [
                        'url' => $url,
                        'email' => $namaLengkap,
                    ]
                );
        
                // Render the email view using Blade
                $html = view('forgotmail', $token)->render();

                $client = new Client();

                $response = $client->post(env('SPRING_BOOT_SMTP_ENDPOINT'), [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'recipient' => $email,
                        'msgBody' => $html,
                        'subject' => 'Reset Password',
                    ],
                ]);

                return $response->getBody()->getContents();   
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 'error',
                    'pesan' => $th->getMessage()
                ], 500);
            }
        } else {
            return response()->json([
                'pesan' => 'Email tidak ditemukan'
            ], 400);
        }
    }

    public function validTokenPasswordReset(string $token)
    {
       //decrypt token
        $cipher_algo = "AES-128-CTR"; //The cipher method, in our case, AES 
        $iv_length = openssl_cipher_iv_length($cipher_algo); //The length of the initialization vector
        $option = 0; //Bitwise disjunction of flags
        $encrypt_iv = '8746376827619797'; //Initialization vector, non-null
        $encrypt_key = "tabeldata!"; // The encryption key

        $decoded = base64_decode($token);
        $decryptedToken = openssl_decrypt($decoded, $cipher_algo, $encrypt_key, $option, $encrypt_iv);

        $tokenParts = explode("||", $decryptedToken);
        $code = $tokenParts[0];
        $createdDate = $tokenParts[1];

        $vervCode = DB::selectOne("SELECT i_id FROM tr_user WHERE c_ver_code = :c_ver_code", [':c_ver_code' => $code]);


        if ($vervCode) {
            $now = $this->getTime();
            $time = strtotime($now) - strtotime($createdDate);
            $minutes = round($time / 60);
            if ($minutes > 15) {
                return response()->json(
                    ['pesan' => 'token', 'status' => 'expired'], 403);
            } else {
                return response()->json([
                    'pesan' => 'token', 'status' => 'valid',
                    'time' => $minutes . ' menit',
                    'createdDate' => $createdDate,
                    'now' => $now,
                    'id' => $vervCode->i_id,
                ], 200);
            }
        } else {
            return response()->json(['pesan' => 'token', 'status' => 'invalid'], 401);
        }
    }
    

    private function getCode($email) {
        //if update code is error because error of unique code, then try again
        $code = substr(number_format(time()*rand(),0,'',''),0,6);
        $vervCode = DB::selectOne("SELECT i_id FROM tr_user WHERE c_ver_code = :c_ver_code", [':c_ver_code' => $code]);
        if ($vervCode) {
            $this->getCode($email);
        } else {
            DB::update("UPDATE tr_user SET c_ver_code = :c_ver_code WHERE n_email = :n_email", [':c_ver_code' => $code, ':n_email' => $email]);
            return $code;
        }
    }

    private function getTime() {
        $tz_object = new DateTimeZone('Asia/Jakarta');
        $datetime = new DateTime();
        $datetime->setTimezone($tz_object);
        return date('Y-m-d H:i:s', strtotime('+15 minutes', strtotime($datetime->format('Y-m-d H:i:s'))));
    }
}