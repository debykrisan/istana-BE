<?php

namespace App\Http\Controllers;


use Firebase\JWT\JWT;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class AuthController extends Controller
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

    public function haspass($parameter)
    {
        //return response()->json(base64_encode(password_hash($parameter, PASSWORD_BCRYPT)));
        return response()->json(env('JWT_SECRET'));
    }

    // public function listIstana()
    // {
    //     $hasil = DB::select("    SELECT id, n_istana, e_istana   FROM tm_istana  order by id_urutan asc ");
    //     $token = array(
    //         "data" => $hasil
    //     );

    //     return response()->json($token);
    // }

    public function autentifikasi(Request $request)
    {

        try {
            $this->validate($request, [
                'email' => 'required',
                'password' => 'required'
            ]);

            $results = DB::selectOne(
                "   SELECT 	n_password FROM tr_user where n_email = :email",
                [':email' => $request->email]
            );

            if ($results == null) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Email atau Password Salah!'
                    ],
                    403
                );
            }

            $pass = base64_decode($results->n_password);
            $roles = DB::select(
                "   SELECT 
                        tr.n_role  
                    FROM 
                        tr_user u 
                    join 
                        tm_users tu on tu.i_id_user = u.i_id 
                    join 
                        tr_roles tr on tr.i_id = tu.i_id_roles 
                    where u.n_email  = :email  ",
                [':email' => $request->email]
            );
            if (password_verify($request->password, $pass)) {
                $isActive = DB::selectOne(
                    "   SELECT 	c_is_active FROM tr_user where n_email = :email",
                    [':email' => $request->email]
                )->c_is_active;
    
                if ($isActive == 0) {
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => 'Akun Anda Terblokir!'
                        ],
                        403
                    );
                }

                $user = DB::select('select i_id, n_email, c_nama_lengkap as nama, c_nomor_telpon as phone, n_alamat as alamat, c_is_verified as verified 
                                    from tr_user where n_email = :email', [':email' => $request->email]);
                if ($user[0]->verified == 1) {
                    $now = time();
                    $token = array(
                        "iss" => env('JWT_ISS'),
                        "aud" => env('JWT_AUD'),
                        "iat" => $now,
                        "nbf" => $now,
                        "exp" => (3 * (60 * 60)) + $now,
                        "idUser" => strval($user[0]->i_id),
                        "email" => $request->email,
                        "nama" => $user[0]->nama,
                        "phone" => $user[0]->phone,
                        "alamat" => $user[0]->alamat,
                        "roles" => $roles
                    );
    
                    return response()->json(JWT::encode(
                        $token,
                        env('JWT_SECRET'),
                        env('JWT_ALGO')
                    ));
                } else {
                    $token = array(
                        "email" => $request->email,
                        "nama" => $user[0]->nama,
                        "allowed" => false,
                        "message" => "Akun Belum Terverifikasi"
                    );
                    return response()->json($token, 401);
                }
            } else {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Email atau Password Salah!',
                        'statusCode' => 403
                    ],
                    403
                );
            }
        } catch (ValidationException $e) {
            $token = array(
                "email" => $request->email,
                "message" => "Terjadi Kesalahan, Silahkan Coba Lagi Nanti",
                "error" => $e->getMessage()
            );
            return response()->json($token);
        }
    }

    //
}
