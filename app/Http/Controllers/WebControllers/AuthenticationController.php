<?php

namespace App\Http\Controllers\WebControllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Validator;
use App\User;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(route('home'));
        }

        return view('login');
    }
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function authenticate(Request $request)
    {
        $recaptchaVerificationErrors = $this->verifyRecaptcha($request);

        if ($recaptchaVerificationErrors) {
            return view('login', ['errors' => $recaptchaVerificationErrors]);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->input('remember'))) {
            return redirect()->intended(route('home'));
        }

        $mb = new MessageBag();
        $mb->add("login", "Invalid email or password");

        return view('login', ['errors' => $mb]);
    }

    /**
     * Handle a registration attempt.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function register(Request $request)
    {
        $recaptchaVerificationErrors = $this->verifyRecaptcha($request);

        if ($recaptchaVerificationErrors) {
            return view('login', ['errors' => $recaptchaVerificationErrors]);
        }

        $credentials = $request->only(
            'full_name',
            'email',
            'password',
            'password_confirmation'
        );

        $validator = Validator::make($credentials, [
            'full_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        if ($validator->fails()) {
            return view('login', ['errors' => $validator->errors()]);
        }

        $credentials['password'] = bcrypt($credentials['password']);
        $credentials['name'] = $credentials['full_name'];
        $user = User::create($credentials);

        Auth::login($user);

        return redirect()->route('home');
    }

    /**
     * Terminate a user's session
     *
     * @return Response
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('home');
    }
    /**
     * Given a request object, verify the reCAPTCHA token if it was passed and
     * return null if successful or MessageBag of errors if failed
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */
    function verifyRecaptcha(Request $request)
    {
        $recaptcha_token = $request->input('g-recaptcha-token');

        if (!$recaptcha_token) return;

        $recaptcha = new \ReCaptcha\ReCaptcha(config('recaptcha.v3_secret_key'));
        $resp = $recaptcha->setScoreThreshold(0.1)
            ->verify($recaptcha_token, $request->ip());

        if (!$resp->isSuccess()) {
            $errors = $resp->getErrorCodes();

            $mb = new MessageBag();

            foreach ($errors as $key => $error) {
                $mb->add($key, 'reCAPTCHA error: "' . $error . '"');
            }

            return $mb;
        }

        return null;
    }
}
