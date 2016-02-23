<!-- 
Base assumption:
The php application's controller functions will interact with this module in a similar way as my python application's View. Changes to the script have been described with comments.
 -->
<?php 
// Third party library that simplifies http request/response handling. It has been modeled after the python library I utilized in the python implementation. https://github.com/rmccue/Requests
// Assumption: not using a dependency manager like Composer.
require_once "/path/to/Requests/library/Requests.php";
// If i were using Composer, I'd require the autoloader which has been preconfigured to load the Requests library e.g. require_once "/path/to/vendor/autoload.php";

class _23AndMeClient
{
    // The python version had these variables outside of the class structure. I've included them within the class structure in this iteration because it seems more natural to have the class encapsulate them. Plus, it gives the user the ability to modify them when they instantiate the object.
    public $GRANT_TYPE = 'authorization_code';
    public $SCOPE = 'basic genomes names';
    public $TOKEN_URL = 'https://API.23andme.com/token/';
    public $BASE_URL = 'https://api.23andme.com/1/';

    // Variables that are unique to each 23andMe developer account. I decided to include them within the class structure too. This also allows me to keep this sensitive information private. 
    private $CLIENT_ID;
    private $CLIENT_SECRET;
    private $CALLBACK_URL;
    
    public function __construct($access_token=null, $client_id, $client_secret, $callback_url)
    {
        $this->access_token = $access_token;
        // Originally these variables were located in the application's settings file for quick and clean configuration. I have decided to give the developer the power to decide how and where to store these values. Now, the Controller must pass these values in when it instantiates this class. 
        // I would love to hear your thoughts on this one... Initially, I wrote this method to accept a "path/to/filename.ini" and it would parse the .ini file with parse_ini_file or Zend_Config_Ini and pull the relevant values. 
        $this->CLIENT_ID = $client_id;
        $this->CLIENT_SECRET = $client_secret;
        $this->CALLBACK_URL = $callback_url;
    }
    public function get_token($auth_code)
    {
        // Given an auth_code, this method will retrieve an access token, save the access token to the object ($this->access_token), and return array(access & refresh token)
        $headers = array('Accept' => 'application/json');
        $data = array(
            'client_id' => $this->CLIENT_ID,
            'client_secret': $this->CLIENT_SECRET,
            'grant_type': $this->GRANT_TYPE,
            'code': $auth_code,
            'redirect_uri': $this->CALLBACK_URL,
            'scope': $this->SCOPE,
         );
        $response = Requests::post($url=$this->TOKEN_URL, $headers=$headers, $data=$data);
        $token_data = json_decode($response->body, $assoc=true);
        if ($token_data['error']) {
            return $token_data['error'];
        }
        else{
            $this->access_token = $token_data['access_token']
            return array($token_data['access_token'], $token_data['refresh_token']);
        }
    }
    public function get_resource($resource)
    {
        //I have made this function public just in case the developer wants to query resources beyond that which is defined in this class-- namely: get_genotypes, get_user, and get_names.
        if ($this->access_token==null){
            // Assumption: Controller function that calls this method can catch this Exception and then redirect the user to start/restart the Oauth2 authentication protocol. 
            throw new Exception("access_token cannot be null");
        }
        $headers = array("Authorization"=>"Bearer $this->access_token");
        $url = $this->BASE_URL . $resource;
        $response = Requests::get($url=$url, $headers=$headers);
        if ($response->status_code==200) {
            return json_decode($response->body, $assoc=true);
        }
        else{
            // What should happen if the API does not serve you a successful 200 response? I'm deferring judgement to the Controller function that called this method. 
            return $response;
        }
    }
    public function get_genotype($profile_id, $locations)
    {
        // returns basepairs of the given location (Rs...)
        // e.g. AA, DD, DI, __, --
        return $this->get_resource("demo/genotypes/$profile_id/?locations=$locations")
    }
    public function get_user()
    {
        return $this->get_resource("demo/user/")
    }
    public function get_names()
    {
        return $this->get_resource("demo/names/")
    }
}

 ?>