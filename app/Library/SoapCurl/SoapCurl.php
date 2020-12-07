<?php


namespace App\Library\SoapCurl;


class SoapCurl extends SoapBase
{

    public function __construct($certificate)
    {
//        parent::__construct($certificate);
    }

    public function send(
        $operation,
        $url,
        $action,
        $envelope,
        $parameters
    )
    {
        $response = '';
        $this->requestHead = implode("\n", $parameters);
        $this->requestBody = $envelope;

        try {
            $oCurl = curl_init();
            $this->setCurlProxy($oCurl);
            curl_setopt($oCurl, CURLOPT_URL, $url);
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_WHATEVER);
//            curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->soaptimeout);
            curl_setopt($oCurl, CURLOPT_TIMEOUT, 400);
            curl_setopt($oCurl, CURLOPT_PORT, 443);
            curl_setopt($oCurl, CURLOPT_HEADER, 1);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
            if (!$this->disablesec) {
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);
                if (is_file($this->casefaz)) {
                    curl_setopt($oCurl, CURLOPT_CAINFO, $this->casefaz);
                }
            }
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($oCurl, CURLOPT_SSLCERT, realpath(storage_path() . '/certificate.pem'));
            curl_setopt($oCurl, CURLOPT_SSLKEY, realpath(storage_path() . '/certificate.pem'));
//            curl_setopt($oCurl, CURLOPT_KEYPASSWD, 'nfe1234');
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($oCurl, CURLOPT_POST, true);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $envelope);
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parameters);
            $response = curl_exec($oCurl);
            $this->soaperror = curl_error($oCurl);
            $ainfo = curl_getinfo($oCurl);

            $xml = simplexml_load_string($response);
            echo $xml['status'];

        } catch (\Exception $e) {
//            throw \NFePHP\Common\Exception\SoapException::unableToLoadCurl($e->getMessage());
        }
        if ($this->soaperror != '') {
//            throw SoapException::soapFault($this->soaperror . " [$url]");
        }
        return $this->responseBody;
    }

    public function wsdl($url)
    {
        $response = '';
        $this->saveTemporarilyKeyFiles();
        $url .= '?Wsdl'; //singleWsdl
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->soaptimeout);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $this->soaptimeout + 20);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, $this->soapprotocol);
        curl_setopt($oCurl, CURLOPT_SSLCERT, $this->tempdir . $this->certfile);
        curl_setopt($oCurl, CURLOPT_SSLKEY, $this->tempdir . $this->prifile);
        if (!empty($this->temppass)) {
            curl_setopt($oCurl, CURLOPT_KEYPASSWD, $this->temppass);
        }
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($oCurl);
        $soaperror = curl_error($oCurl);
        $ainfo = curl_getinfo($oCurl);
        $headsize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        $httpcode = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        curl_close($oCurl);
        if ($httpcode != 200) {
            return '';
        }
        return $response;
    }

    private function setCurlProxy(&$oCurl)
    {
        if ($this->proxyIP != '') {
            curl_setopt($oCurl, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($oCurl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($oCurl, CURLOPT_PROXY, $this->proxyIP . ':' . $this->proxyPort);
            if ($this->proxyUser != '') {
                curl_setopt($oCurl, CURLOPT_PROXYUSERPWD, $this->proxyUser . ':' . $this->proxyPass);
                curl_setopt($oCurl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            }
        }
    }

    private function getFaultString($body)
    {
        if (empty($body)) {
            return '';
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($body);
        $faultstring = '';
        $nodefault = !empty($dom->getElementsByTagName('faultstring')->item(0))
            ? $dom->getElementsByTagName('faultstring')->item(0)
            : '';
        if (!empty($nodefault)) {
            $faultstring = $nodefault->nodeValue;
        }
        return htmlentities($faultstring, ENT_QUOTES, 'UTF-8');
    }


}
