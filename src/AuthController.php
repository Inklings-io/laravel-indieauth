<?php
namespace Inklings\IndieAuth;
 
use App\Http\Controllers\Controller;
use Log;

class AuthController extends Controller
{
 
    public function login()
    {
    }

    public function logout()
    {
    }

    public function standardize($url)
    {
        $url = trim($url);
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }
        echo $url;
    }
 
}
