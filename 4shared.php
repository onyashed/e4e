<?php
// Adicionar as bibliotecas, se colocar a pasta library em outro diretório, coloque o caminho correto abaixo. No meu caso a pasta está no mesmo diretório que o arquivo .php
include_once "library/OAuthStore.php";
include_once "library/OAuthRequester.php";

define("FOURSHARED_CONSUMER_KEY", "<KEY>");
define("FOURSHARED_CONSUMER_SECRET", "<SECRET>");
define("FOURSHARED_OAUTH_HOST", "https://api.4shared.com");
define("FOURSHARED_REQUEST_TOKEN_URL", FOURSHARED_OAUTH_HOST . "/v1_2/oauth/initiate");
define("FOURSHARED_AUTHORIZE_URL", FOURSHARED_OAUTH_HOST . "/v1_2/oauth/authorize");
define("FOURSHARED_ACCESS_TOKEN_URL", FOURSHARED_OAUTH_HOST . "/v1_2/oauth/token");
define('OAUTH_TMP_DIR', function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : realpath($_ENV["TMP"]));

//Coloque aqui a URL do servidor que você utiliza para testes. No meu caso eu configurei um vhost e coloquei o caminho para o próprio script.
define("FOURSHARED_OAUTH_CALLBACK", "http://testes.loc/4shared.php");    

//  Inicia o OAuthStore
$options = array(
    'consumer_key' => FOURSHARED_CONSUMER_KEY, 
    'consumer_secret' => FOURSHARED_CONSUMER_SECRET,
    'server_uri' => FOURSHARED_OAUTH_HOST,
    'request_token_uri' => FOURSHARED_REQUEST_TOKEN_URL,
    'authorize_uri' => FOURSHARED_AUTHORIZE_URL,
    'access_token_uri' => FOURSHARED_ACCESS_TOKEN_URL
);

// Atenção: não armazene os dados em "Session" em produção. 
// Escolha uma base de dados.
OAuthStore::instance("Session", $options);

try
{
    //  Passo 1:  se não existir um OAuth token ainda, precisamos de um.
    if (empty($_GET["oauth_token"]))
    {
        $getAuthTokenParams = array(
            'scope' => FOURSHARED_OAUTH_HOST . '/v1_2',
            'xoauth_displayname' => 'Oauth 4Shared',
            'oauth_callback' => FOURSHARED_OAUTH_CALLBACK
        );

        // Solicita um request token
        $tokenResultParams = OAuthRequester::requestRequestToken(FOURSHARED_CONSUMER_KEY, 0, $getAuthTokenParams);

        // Redireciona para a página de autorização. Aqui o utilizador dará permissões na primeira vez e depois será redirecionado novamente para o seu site.
        header("Location: " . FOURSHARED_AUTHORIZE_URL . "?oauth_token=" . $tokenResultParams['token']);
    }
    else {
        //  Passo 2:  solicitar um access token
        $oauthToken = $_GET["oauth_token"];
        $tokenResultParams = $_GET;

        try {
            OAuthRequester::requestAccessToken(FOURSHARED_CONSUMER_KEY, $oauthToken, 0, 'POST', $_GET);
        }
        catch (OAuthException2 $e)
        {
            var_dump($e);
            return;
        }

        // Vamos solicitar informações do utilizador
        $request = new OAuthRequester(FOURSHARED_OAUTH_HOST . '/v1_2/user', 'GET', $tokenResultParams);
        $result = $request->doRequest(0);
        if ($result['code'] == 200) {
            // Converter string para um objeto json
            $user = json_decode($result['body']);

            // Imprimir em tela o e-mail;
            echo $user->email;
        }
        else {
            echo 'Error';
        }
    }
}
catch(OAuthException2 $e) {
    echo "OAuthException:  " . $e->getMessage();
    var_dump($e);
}