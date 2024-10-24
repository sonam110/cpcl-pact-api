<?php
use App\Models\User;
use App\Models\AssigneModule;
use DB as db;
use Mail as mail;
use Str as str;
use App\Mail\WelcomeMail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use mervick\aesEverywhere\AES256;

function prepareResult($error, $data, $msg)
{
    return ['error' => $error, 'data' => $data, 'message' => $msg];
}

function generateRandomString($len = 12) {
    return Str::random($len);
}

function getUser() {
    return auth('api')->user();
}

function checkUserExist($email)
{
   $users = User::select('email')->get();
   foreach ($users as $key => $user) 
   {
     if($email==$user->email)
     {
        return true;
     }
   }
   return false;
}

function timeDiff($time)
{
    return strtotime($time) - time();
}

function addUser($email)
{
    $randomNo = generateRandomString(10);
    $password = Hash::make($randomNo);

    $checkEmails = User::get();
    $user = null;
    foreach ($checkEmails as $key => $checkEmail) 
    {
        if($checkEmail->email==$email)
        {
            $user = $checkEmail;
            break;
        }
    }
    
    if(empty($user))
    {
        $user = new User;
        $user->role_id = '2';
        $user->name = $email;
        $user->email  = $email;
        $user->password = $password;
        $user->created_by = auth()->user()->id;
        $user->save();

        //Delete if entry exists
        DB::table('password_resets')->where('email', $email)->delete();

        $token = \Str::random(64);
        DB::table('password_resets')->insert([
          'email' => $email, 
          'token' => $token, 
          'created_at' => \Carbon\Carbon::now()
        ]);

        $baseRedirURL = env('FRONT_URL');
        // Login credentials are following - email:'.$user->email.' , password:'.$randomNo.'.
        $content = [
            "name" => $user->name,
            "body" => 'You have been registered.<br>To reset your password Please click on the link -> <a href='.$baseRedirURL.'/reset-password/'.$token.' style="color: #000;font-size: 18px;text-decoration: underline, font-family: Roboto Condensed, sans-serif;"  target="_blank">Reset your password </a>',
        ];

        if (env('IS_MAIL_ENABLE', false) == true) {
           
            $recevier = Mail::to($email)->send(new WelcomeMail($content));
        }

        


        //Role and permission sync
        $role = Role::where('id','2')->first();
        $permissions = $role->permissions->pluck('name');
        
        $user->assignRole($role->name);
        foreach ($permissions as $key => $permission) {
            $user->givePermissionTo($permission);
        }
    }
    
    return $user;
}

function validatePassword($val) {
  $re = array();
  if ($val) {
  	// check password must contain at least one number
      if (preg_match('/\d/', $val)) {
        array_push($re, true);
      }
      // check password must contain at least one special character
      if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $val)) {
        array_push($re, true);
      }
      // check password must contain at least one uppercase letter
      if (preg_match('/[A-Z]/', $val)) {
        array_push($re, true);
      }
      // check password must contain at least one lowercase letter
      if (preg_match('/[a-z]/', $val)) {
        array_push($re, true);
      }
  }
  return count($re)>=3;
}


function getWhereRawFromRequest(Request $request) {
    

}


function cpclDecrypt($value)
{
    $ciphertext = base64_decode($value);
    $key = hex2bin(env('ENCRYPTION_KEY'));
    $iv = hex2bin(env('IV_HEX_KEY'));
    $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}
