<?php
namespace Inklings\IndieAuth;

require_once base_path('vendor/indieauth/client/src/IndieAuth/Client.php');

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Log;

class AuthController extends Controller
{
 
    public function login(Request $request)
    {
        $me = $request->input('me');
        $after_redir = $request->input('redirect') ?: '/';
        $scope = $request->input('scope');
        if(empty($me)){
            return redirect($after_redir)->with('error', 'No URL entered');
        } else {
            $me = \IndieAuth\Client::normalizeMeURL($me);
            //TODO requie indieauth
            $auth_endpoint = \IndieAuth\Client::discoverAuthorizationEndpoint($me);
            if (!$auth_endpoint) {
                return redirect($after_redir)->with('error', 'No Auth Endpoint Found');
            } else {
                $redir_url = config('app.url') . '/indieauth_login/complete'. ($after_redir ? '?r=' . $after_redir : '');
                if (!empty($scope)) {
                    // if a scope is given we are actually looking to get a token
                    $redir_url = config('app.url') . '/indieauth_login/token'. ($after_redir ? '?r=' . $after_redir : '');
                }

                //build our get request
                $trimmed_me = trim($me, '/'); //in case we get it back without the /
                $data_array = array(
                    'me' => $me,
                    'redirect_uri' => $redir_url,
                    'response_type' => 'id',
                    'state' => substr(md5($trimmed_me . config('app.url') ), 0, 8),
                    'client_id' => config('app.url')
                );
                //$this->log->write(print_r($data_array,true));
                if (!empty($scope)) {
                    $data_array['scope'] = $scope;
                    $data_array['response_type'] = 'code';
                }

                $get_data = http_build_query($data_array);

                //redirect to their provider
                return redirect($auth_endpoint . (strpos($auth_endpoint, '?') === false ? '?' : '&') . $get_data);
            }
        }
    }
    public function login_complete(Request $request)
    {

        // where we are going after we process
        $after_redir = config('app.url') .  $request->input('r') ?: '/';

        //recalculate the callback url
        $redir_url = config('app.url') . '/indieauth_login/complete'. ($request->input('r') ? '?r=' . $request->input('r') : '');

        $me = \IndieAuth\Client::normalizeMeURL($request->input('me'));
        $code = $request->input('code');
        $state = $request->input('state');

        $result = $this->confirmAuth($me, $code, $redir_url, $state);

        if ($result) {

            session(['indieauthclient_me' => $me]);

            return redirect($after_redir)->with('success', 'Logged In As ' . $me);

        } else {
            return redirect($after_redir)->with('error', 'Authorization Failed');
        }

    }

    public function token(Request $request)
    {
        // where we are going after we process
        $after_redir = config('app.url') .  $request->input('r') ?: '/';

        //recalculate the callback url
        $redir_url = config('app.url') . '/indieauth_login/token'. ($request->input('r') ? '?r=' . $request->input('r') : '');

        $me = \IndieAuth\Client::normalizeMeURL($request->input('me'));
        $code = $request->input('code');
        $state = $request->input('state');

        $result = $this->confirmAuth($me, $code, $redir_url, $state);

        if ($result) {
            session(['indieauthclient_me' => $me]);

            $token_results = $this->getToken($me, $code, $redir_url, $state);

            session(['indieauthclient_token' => $token_results['access_token']]);
            session(['indieauthclient_scope' => $token_results['scope']]);

            return redirect($after_redir)->with('success', 'Logged In As ' . $me);
        } else {
            return redirect($after_redir)->with('error', 'Authorization Failed');
        }

    }

    public function logout(Request $request)
    {
        session()->forget('indieauthclient_me');
        session()->forget('indieauthclient_token');
        session()->forget('indieauthclient_scope');
        $after_redir = $request->input('redirect') ?: '/';
        return redirect($after_redir)->with('success', 'Logged Out');
        
    }


    private function confirmAuth($me, $code, $redir, $state = null)
    {

        $client_id = config('app.url');

        //look up user's auth provider
        $auth_endpoint = \IndieAuth\Client::discoverAuthorizationEndpoint($me);

        $post_array = array(
            'code'          => $code,
            'redirect_uri'  => $redir,
            'client_id'     => $client_id
        );
        if ($state) {
            $post_array['state'] = $state;
        }

        //Log::debug(print_r($post_array, true));
        $post_data = http_build_query($post_array);
        //$this->log->write('post_data: '.print_r($post_array,true));
        //$this->log->write('endpoint: '.$auth_endpoint);

        $ch = curl_init($auth_endpoint);

        if (!$ch) {
            //$this->log->write('error with curl_init');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($ch);

        $results = array();
        parse_str($response, $results);
        if(isset($results['error'])){
            Log::debug(print_r($results, true));
        }


        $results['me'] = \IndieAuth\Client::normalizeMeURL($results['me']);

        $trimmed_me = trim($me, '/');
        $trimmed_result_me = trim($results['me'], '/');

        if ($state) {
            //$this->log->write('state = '.$state. ' ' .substr(md5($trimmed_me.$client_id),0,8));
            return ($trimmed_result_me == $trimmed_me && $state == substr(md5($trimmed_me . $client_id), 0, 8));
        } else {
            return $trimmed_result_me == $trimmed_me ;
        }

    }



    private function getToken($me, $code, $redir, $state = null)
    {

        $client_id = config('app.url');

        //look up user's token provider
        $token_endpoint = \IndieAuth\Client::discoverTokenEndpoint($me);


        $post_array = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redir,
            'client_id'     => $client_id,
            'me'            => $me
        );
        if ($state) {
            $post_array['state'] = $state;
        }

        $post_data = http_build_query($post_array);

        $ch = curl_init($token_endpoint);

        if (!$ch) {
            //$this->log->write('error with curl_init');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($ch);

        $results = array();
        parse_str($response, $results);

        //$this->log->write(print_r($results, true));

        return $results;
    }

}
