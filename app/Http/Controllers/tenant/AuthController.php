<?php

namespace App\Http\Controllers\tenant;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;

// Mail
use App\Mail\AuthRegisterConfirmationCode;
use App\Mail\AuthRegisterConfirmationConfirmed;
use App\Mail\AuthPasswordResetConfirmationCode;
use App\Mail\AuthPasswordResetConfirmationConfirmed;
use App\Mail\AuthEmailChangeConfirmationCodeOld;
use App\Mail\AuthEmailChangeConfirmationCodeNew;
use App\Mail\AuthEmailChangeConfirmationConfirmed;

use App\Models\tenant\User;
use App\Models\tenant\UserRegister;
use App\Models\tenant\UserPasswordReset;
use App\Models\tenant\UserEmailChange;
use App\Models\tenant\Collection;
use App\Models\tenant\FieldType;
use App\Models\tenant\Page;
use App\Models\tenant\Setting;

class AuthController extends Controller
{

  private function generateVerificationCode($length = 6)
  {
    $characters = '0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $index = rand(0, strlen($characters) - 1);
      $randomString .= $characters[$index];
    }
    return $randomString;
  }

  public function login(Request $request)
  {
    $request->validate([
      'email' => 'required',
      'password' => 'required'
    ]);

    try {

      Log::notice('Login attempt: ' . $request->email);
      $user = User::where('email', $request->email)->first();

      if (!$user) {
        return response([
          'message' => 'We could not find a matching user.'
        ], 404);
      }

      if (!$user->email_verified_at) {
        return response([
          'message' => 'Email has not been verified.'
        ], 401);
      }

      if ($user->blocked) {
        return response([
          'message' => 'This account has been blocked.'
        ], 401);
      }

      if (!Hash::check($request->password, $user->password)) {
        return response([
          'message' => 'Credentials are invalid.'
        ], 404);
      }

      $jwtPrivateKey = sodium_bin2base64(sodium_crypto_sign_secretkey(sodium_base642bin(config("auth.jwt_key"), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING)), SODIUM_BASE64_VARIANT_ORIGINAL);

      $currentTenant = tenant();
      $currentTenantName = null;

      if ($currentTenant) {
        $currentTenantName = $currentTenant->id;
      }

      $payload = [
        'iss' => config("app.url"),
        'aud' => $user->id,
        'iat' => time(),
        'exp' => time() + (3600 * 24), // 3600 is 1 hour, multiplied by 24 is a full day
        'tenant' => $currentTenantName
      ];

      $jwt = JWT::encode($payload, $jwtPrivateKey, 'EdDSA'); // key is required in SODIUM_BASE64_VARIANT_ORIGINAL

      $user->remember_token = Str::random(10);
      $user->save();

      // Remove auth change records
      UserRegister::where('email', $user->email)->delete();
      UserPasswordReset::where('email', $user->email)->delete();
      UserEmailChange::where('email', $user->email)->delete();
      UserEmailChange::where('new_email', $user->email)->delete();

      // System Config Info
      $appConfig = [
        'pages' => Page::all(),
        'collections' => Collection::with(['fields', 'fields.type'])->get(),
        'field_types' => FieldType::all()
      ];

      return response([
        'message' => 'You have logged in successfully.',
        'access_token' => $jwt,
        'user' => $user,
        'tenant' => $currentTenantName,
        'url' => env('APP_URL', null),
        'config' => $appConfig,
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function register(Request $request)
  {
    $request->validate([
      'name' => 'required',
      'email' => 'required',
      'password' => 'required'
    ]);

    try {

      $allPublicRegister = Setting::where('key', 'public_auth_register')->first();
      if(!$allPublicRegister){
        return response([
          'message' => 'Public user registration is disabled.'
        ], 401);
      }

      // Create tenant
      $user = new User;
      $user->name = $request->name;
      $user->email = $request->email;
      $user->password = Hash::make($request->password);
      $user->public = true;
      $user->blocked = false;
      $user->save();

      // Create email verification
      $emailVerification = new UserRegister;
      $emailVerification->email = $request->email;
      $emailVerification->verification_code = $this->generateVerificationCode();
      $emailVerification->save();

      // Send mail confirmation
      Mail::to($user)->send(new AuthRegisterConfirmationCode($user->email, $emailVerification->verification_code, tenant()->id));

      return response([
        'message' => 'Register.'
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function registerConfirm(Request $request, $email, $verification_code)
  {

    try {
      $emailVerification = UserRegister::where('email', $email)->first();

      $user = User::where('email', $email)->first();

      if ($verification_code == $emailVerification->verification_code) {
        $user->email_verified_at = now();
        $user->save();

        Mail::to($user)->send(new AuthRegisterConfirmationConfirmed());

        $emailVerification->delete();
      } else {

        $emailVerification->verification_code = $this->generateVerificationCode();
        $emailVerification->save();

        Mail::to($user)->send(new AuthRegisterConfirmationCode($user->email, $emailVerification->verification_code, tenant()->id));

        return response([
          'message' => 'Verification code is incorrect, new code sent to email.'
        ], 401);
      }

      return redirect(config('app.url_spa'));
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function passwordReset(Request $request)
  {
    $request->validate([
      'email' => 'required',
      'new_password' => 'required',
    ]);

    try {

      $passwordReset = new UserPasswordReset;
      $passwordReset->email = $request->email;
      $passwordReset->verification_code = $this->generateVerificationCode();
      $passwordReset->new_password = Hash::make($request->new_password);
      $passwordReset->save();

      $user = User::where('email', $request->email)->first();

      Mail::to($user)->send(new AuthPasswordResetConfirmationCode($request->email, $passwordReset->verification_code, tenant()->id));

      return response([
        'message' => 'Password reset code sent to email.'
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function passwordResetConfirm(Request $request, $email, $verification_code)
  {
    $request->validate([]);

    try {

      $passwordReset = UserPasswordReset::where('email', $email)->first();

      $user = User::where('email', $email)->first();

      if ($verification_code == $passwordReset->verification_code) {

        $user->password = $passwordReset->new_password;
        $user->save();

        Mail::to($user)->send(new AuthPasswordResetConfirmationConfirmed());
        $passwordReset->delete();
      } else {
        $passwordReset->verification_code = $this->generateVerificationCode();
        $passwordReset->save();

        Mail::to($user)->send(new AuthPasswordResetConfirmationCode($user->email, $passwordReset->verification_code, tenant()->id));

        return response([
          'message' => 'Verification code is incorrect, new code sent to email.'
        ], 401);
      }

      return redirect(config('app.url_spa'));
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function emailChange(Request $request)
  {
    $request->validate([
      'email' => 'required',
      'new_email' => 'required'
    ]);

    try {

      $emailChange = new UserEmailChange;
      $emailChange->email = $request->email;
      $emailChange->verification_code_old = $this->generateVerificationCode();
      $emailChange->new_email = $request->new_email;
      $emailChange->verification_code_new = null;
      $emailChange->save();

      Mail::to($request->email)->send(new AuthEmailChangeConfirmationCodeOld($request->email, $emailChange->verification_code_old, tenant()->id));

      return response([
        'message' => 'Password reset code sent.'
      ], 200);
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function emailChangeConfirmOld(Request $request, $email, $verification_code)
  {
    $request->validate([]);

    try {

      $emailChange = UserEmailChange::where('email', $email)->first();

      $user = User::where('email', $request->email)->first();

      if ($verification_code == $emailChange->verification_code_old) {
        $emailChange->verification_code_new = $this->generateVerificationCode();
        $emailChange->save();

        Mail::to($emailChange->new_email)->send(new AuthEmailChangeConfirmationCodeNew($emailChange->new_email, $emailChange->verification_code_new, tenant()->id));
      } else {
        $emailChange->verification_code_old = $this->generateVerificationCode();
        $emailChange->save();

        Mail::to($request->email)->send(new AuthEmailChangeConfirmationCodeOld($emailChange->email, $emailChange->verification_code_old, tenant()->id));

        return response([
          'message' => 'Verification code is incorrect, new code sent to email.'
        ], 401);
      }

      return redirect(config('app.url_spa'));
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }

  public function emailChangeConfirmNew(Request $request, $email, $verification_code)
  {
    $request->validate([]);

    try {

      $emailChange = UserEmailChange::where('new_email', $email)->first();
      $user = User::where('email', $emailChange->email)->first();

      if ($verification_code == $emailChange->verification_code_new) {
        $user->email = $emailChange->new_email;
        $user->save();

        $emailChange->delete();

        Mail::to($user)->send(new AuthEmailChangeConfirmationConfirmed());
      } else {
        $emailChange->verification_code_new = $this->generateVerificationCode();
        $emailChange->save();

        Mail::to($request->new_email)->send(new AuthEmailChangeConfirmationCodeNew($email, $emailChange->verification_code_new, tenant()->id));

        return response([
          'message' => 'Verification code is incorrect, new code sent to email.'
        ], 401);
      }
      
      return redirect(config('app.url_spa'));
    } catch (Exception $e) {
      return response([
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
