<?php
namespace Inklings\IndieAuth;
 
use Illuminate\Support\HtmlString;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Log;

class Helpers
{

 
    public static function login_form($scope = '')
    {
        return view('indieauthclient::loginform', ['scope' => $scope]);
    }

    public static function logout_form()
    {
        return view('indieauthclient::logoutform');
    }

    public static function is_logged_in(){
        return !empty(session('indieauthclient_me'));
    }

    public static function login_logout_form($scope='')
    {
        if(empty(session('indieauthclient_me'))){
            return view('indieauthclient::loginform', ['scope' => $scope]);
        } else {
            return view('indieauthclient::logoutform');
        }
    }

    public static function is_user($url)
    {
        //TODO improve this
        $url = trim($url);
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        $logged_in_user = session('indieauthclient_me');

        return ($logged_in_user == $url);
    }

    public static function user(){
        return session('indieauthclient_me');
    }

    public static function scope(){
        return session('indieauthclient_scope');
    }

    public static function token(){
        return session('indieauthclient_token');
    }

    public static function logout(){
        session()->forget('indieauthclient_me');
        session()->forget('indieauthclient_token');
        session()->forget('indieauthclient_scope');
    }
}
