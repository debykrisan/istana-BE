<?php

namespace App\Http\Middleware;

use UnexpectedValueException;
use Firebase\JWT\JWT;
use Closure;
use Firebase\JWT\Key;

class Authjwt
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */

    private $JWT_ISS = "pandyakirapratama";
    private $JWT_AUD = "DIGIVETMR";
    private $JWT_SECRET = '1TGcvT0ulyhZ3PM3FBrwQrlHGy64KVPuEQx7b73uBvg4LkvwWsI17fB0U5cQPkAn';
    private $JWT_ALGO = "HS256";

    public function handle($request, Closure $next, ...$role)
    {

        $header = $request->header('x-access-token');

        $hasilVerify = $this->verify($header, $role);

        if (!$hasilVerify) {
            return response('Unauthorized.', 405);
        } else {
            return $next($request);
        }
    }

    private function verify($header, $role)
    {
        if ($header) {
            $payload = JWT::decode($header, new Key($this->JWT_SECRET, $this->JWT_ALGO));
            $this->validatePayload($payload, $role);
            return $payload;
        } else {
            abort(403, "Incorrect access token");
        }

        //print_r($header);

    }

    private function validatePayload(object $payload, $role): void
    {

        if (!$this->checkRoles($role, $payload->roles)) {
            abort(405, "Unauthorized");
        }

        if ($payload->aud !== $this->JWT_AUD) {
            throw new UnexpectedValueException("Invalid audience: {$payload->aud}");
        }

        if ($payload->iss !== $this->JWT_ISS) {
            throw new UnexpectedValueException("Invalid issuer: {$payload->iss}");
        }
    }

    private function checkRoles($middlewareRoles, $jwtRoles)
    {
        foreach ($middlewareRoles as $role) {
            foreach ($jwtRoles as $element) {
                if ($element->n_role === $role) {
                    return true;
                }
            }
        }

        return false;
    }
}
