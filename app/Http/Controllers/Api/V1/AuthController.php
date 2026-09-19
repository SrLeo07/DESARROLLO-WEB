<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Passport;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string', 'distinct', Rule::in(Passport::scopeIds())],
        ]);

        $clientId = config('passport.password_client_id');
        $clientSecret = config('passport.password_client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            return response()->json([
                'message' => 'El cliente Password Grant no esta configurado.',
            ], 500);
        }

        $passportRequest = (new ServerRequest('POST', '/oauth/token', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]))->withParsedBody([
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $credentials['email'],
            'password' => $credentials['password'],
            'scope' => implode(' ', $credentials['scopes'] ?? ['productos.read']),
        ]);

        try {
            $tokenResponse = app(AccessTokenController::class)->issueToken(
                $passportRequest,
                new Psr7Response,
            );
        } catch (OAuthServerException) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $payload = json_decode($tokenResponse->getContent(), true);

        if (! $tokenResponse->isSuccessful()) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        return response()->json($payload);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->token();

        $accessToken?->refreshToken?->revoke();
        $accessToken?->revoke();

        return response()->json([
            'message' => 'Sesion cerrada y tokens revocados correctamente.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
