<?php

define('REDIRECT_URI', 'https://wwww.sanlinmail.site/auth.php');
define('AUTH_URL', 'https://global-selling.mercadolibre.com/authorization');
define('TOKEN_URL', 'https://api.mercadolibre.com/oauth/token');


define('CLIENT_ID', '356387630354899');
define('CLIENT_SECRET', '0ys7KXrWafQcBFuXGQ2A2tXqzDjPtjkI');
define('SHOP_ID', '34');

//https://global-selling.mercadolibre.com/authorization?response_type=code&client_id=$APP_ID&state=$RANDOM_ID&redirect_uri=$REDIRECT_URL
function getAuthorizationCode() {
    $authorization_redirect_url = AUTH_URL . "?response_type=code&client_id="
        . CLIENT_ID . "&state=".time()."&redirect_uri=" . REDIRECT_URI;
    ?>
    <html>
    <body>
    <a href="<?php echo $authorization_redirect_url; ?>">登录</a>
    </body>
    </html>
    <?php
}


function getCurl($headers, $content) {
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => TOKEN_URL,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $content
    ));
    return $ch;
}

function getAccessToken($authorization_code) {
    $authorization_code = urlencode($authorization_code);
    $headers = array("accept: application/json","Content-Type: application/x-www-form-urlencoded");
    $content = "grant_type=authorization_code&code={$authorization_code}&client_id=".CLIENT_ID."&client_secret=".CLIENT_SECRET."&redirect_uri=" . REDIRECT_URI;
    $ch = getCurl($headers, $content);
    $tokenResult = curl_exec($ch);
    $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokenResult === false || $resultCode !== 200) {
        exit ("Something went wrong $resultCode $tokenResult");
    }
    $result = json_decode($tokenResult,true);

    echo json_encode(['refresh_token'=>$result['refresh_token'],'shop_id'=>SHOP_ID]);
    echo "\n";
    return $result['access_token'];
}


function reAccessToken($authorization_code) {
    $authorization = base64_encode(CLIENT_ID.':'.CLIENT_SECRET);
    $authorization_code = urlencode($authorization_code);
    $headers = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");
    $content = "grant_type=refresh_token&refresh_token=${authorization_code}&redirect_uri=" . REDIRECT_URI;
    $ch = getCurl($headers, $content);
    $tokenResult = curl_exec($ch);
    $resultCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokenResult === false || $resultCode !== 200) {
        exit ("Something went wrong $resultCode $tokenResult");
    }
    $result = json_decode($tokenResult);

    var_dump($result);
    return $result->access_token;
}

function main(){
    if ($_GET["code"]) {
        $access_token = getAccessToken($_GET["code"]);
        echo "access_token = ", $access_token;
    } else if ($_GET["re"]) {
        $access_token = reAccessToken('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX25hbWUiOiI5ODMzMzQ5NiIsInNjb3BlIjpbImFsbGVncm86YXBpOm9yZGVyczpyZWFkIiwiYWxsZWdybzphcGk6cHJvZmlsZTp3cml0ZSIsImFsbGVncm86YXBpOnNhbGU6b2ZmZXJzOndyaXRlIiwiYWxsZWdybzphcGk6YmlsbGluZzpyZWFkIiwiYWxsZWdybzphcGk6Y2FtcGFpZ25zIiwiYWxsZWdybzphcGk6ZGlzcHV0ZXMiLCJhbGxlZ3JvOmFwaTpzYWxlOm9mZmVyczpyZWFkIiwiYWxsZWdybzphcGk6YmlkcyIsImFsbGVncm86YXBpOm9yZGVyczp3cml0ZSIsImFsbGVncm86YXBpOmFkcyIsImFsbGVncm86YXBpOnBheW1lbnRzOndyaXRlIiwiYWxsZWdybzphcGk6c2FsZTpzZXR0aW5nczp3cml0ZSIsImFsbGVncm86YXBpOnByb2ZpbGU6cmVhZCIsImFsbGVncm86YXBpOnJhdGluZ3MiLCJhbGxlZ3JvOmFwaTpzYWxlOnNldHRpbmdzOnJlYWQiLCJhbGxlZ3JvOmFwaTpwYXltZW50czpyZWFkIl0sImFsbGVncm9fYXBpIjp0cnVlLCJhdGkiOiJlZTFjMTgxMi1mMjI2LTQ5MWUtODBhYS00N2U5ZWQ0OTA2NjYiLCJleHAiOjE2MzA4NDAyMTYsImp0aSI6ImNlNzJhMjYwLTNiYjAtNDM4YS1iNTk4LTU1Y2EwNzZmNWIwNCIsImNsaWVudF9pZCI6Ijg1YmFjMGM4MDdmMDQxMGNhYWRhNzUwOTk2Mzk1NGQxIn0.fKpB82wl5F8OeLWwVLBc-ECPSXV3V_lHgMCBlVkhc8A9j8pPv7HkkZP1JRpCa0xa9rp92nqC0Z_wYE70v6Pg6U8w2bmncKJoIu78yliR2WLBMCwQCDjsMPMzVrCCrAjNo00J3YUH8hL2gnLAm0onwJs7sRy42zNj5rLXmrszeWDh8Am3-6WKKLNynCFwYKnnS0J2h06oChZeqfWSqpNuJkSxtj4U_HGAt7VH1CDxlXF8pRs8fGOcjsetdsLeXIR2fSwDWtO00DGuNOMa1dfpHNk3-jBnzdp9uU09GNrhOmS40J5x1AR9IDNCWnXgkUwdDQmEONsGFBxjPlFNNtu1Pw');
        echo "access_token = ", $access_token;
    } else {
        getAuthorizationCode();
    }
}


main();

?>