<?php


namespace App\Http\Services;


use App\Library\SoapCurl\SoapCurl;
use App\Models\Certificate;

class Abrasf
{
    private $urlXsi = 'http://www.w3.org/2001/XMLSchema-instance';
    private $urlXsd = 'http://www.w3.org/2001/XMLSchema';
    private $urlNfe = 'http://www.prefeitura.sp.gov.br/nfe';
    private $urlDsig = 'http://www.w3.org/2000/09/xmldsig#';
    private $urlCanonMeth = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $urlSigMeth = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private $urlTransfMeth_1 = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private $urlTransfMeth_2 = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $urlDigestMeth = 'http://www.w3.org/2000/09/xmldsig#sha1';
    private $algorithm = OPENSSL_ALGO_SHA1;

    protected $soapnamespaces = [
        'xmlns:xsi' => "http://www.w3.org/2001/XMLSchema-instance",
        'xmlns:xsd' => "http://www.w3.org/2001/XMLSchema",
        'xmlns:soap' => "http://www.w3.org/2003/05/soap-envelope",
    ];
    public $xml;

    public $dom;

    public $soap;

    public $version = 1;

    public $wsdl = "https://deiss.indaiatuba.sp.gov.br/homologacao/nfse?wsdl";

    private $Certificate;

    public function send($certificate)
    {

        $this->Certificate = $certificate;

        $SoapCurl = new SoapCurl($this->Certificate);

        $XmlAssinado = htmlentities(file_get_contents(storage_path() . '/teste.xml'));
        $cabecalho = htmlentities('<cabecalho versao="2.01" xmlns="http://www.abrasf.org.br/nfse.xsd" ><versaoDados>2.01</versaoDados></cabecalho>');

        $envelope = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body><ns2:GerarNfseRequest xmlns:ns2="http://nfse.indaiatuba.sp.gov.br">
    <nfseCabecMsg>' . $cabecalho . '</nfseCabecMsg>
    <nfseDadosMsg>' . $XmlAssinado . '</nfseDadosMsg>
        </ns2:GerarNfseRequest>
        </soap:Body></soap:Envelope>';

        $msgSize = strlen($envelope);

        $parameters = [
            "Content-Type: text/xml; charset=utf-8",
            "Content-length: $msgSize",
            "SOAPAction: http://nfse.indaiatuba.sp.gov.br/GerarNfse",
        ];

        $SoapCurl->send('GerarNfse',
            'https://deiss.indaiatuba.sp.gov.br/homologacao/nfse?wsdl',
            'http://nfse.indaiatuba.sp.gov.br/GerarNfse',
            $envelope,
            $parameters);
    }

    public function send1($xml)
    {
        $oCurl = curl_init();
//        $this->setCurlProxy($oCurl);
        curl_setopt($oCurl, CURLOPT_URL, $this->wsdl);
//        curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
//            curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->soaptimeout);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 400);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($oCurl, CURLOPT_SSLCERT, storage_path() . '/certificate.pem');
        curl_setopt($oCurl, CURLOPT_SSLKEY, storage_path() . '/certificate.pem');
        curl_setopt($oCurl, CURLOPT_KEYPASSWD, 'nfe1234');
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 300);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,
            "xmlRequest=" . $xml);
        $response = curl_exec($oCurl);
    }


    public function send3()
    {

        $XmlAssinado = htmlentities(file_get_contents(storage_path() . '/teste.xml'));
        $cabecalho = htmlentities('<cabecalho versao="2.01" xmlns="http://www.abrasf.org.br/nfse.xsd"><versaoDados>2.01</versaoDados></cabecalho>');

        $envelope =
            '<?xml version="1.0" encoding="UTF-8"?>
 <x:Envelope xmlns:x="http://schemas.xmlsoap.org/soap/envelope/" xmln s:ser="http://services.nfse">
 <x:Header/>
 <x:Body>
 <ser:GerarNfseRequest>
    <nfseCabecMsg>' . $cabecalho . '</nfseCabecMsg>
    <nfseDadosMsg>' . $XmlAssinado . '</nfseDadosMsg>
 </ser:GerarNfseRequest>
 </x:Body>
 </x:Envelope>';

        $url = 'https://deiss.indaiatuba.sp.gov.br/homologacao/nfse?wsdl';

        $headers = array(
            "Content-type: text/xml; charset=utf-8",
            "SOAPAction: http://services.nfse/RecepcionarLoteRps",
            "Content-length: " . strlen($envelope),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $envelope);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// converting
        $html = utf8_decode(curl_exec($ch));
//        file_put_contents("$arquivoRPSAssinado.ret", $html);
        curl_close($ch);

        preg_match_all('/<outputXML>(.*?)<\/outputXML>/s', $html, $matches);

        $response = html_entity_decode(count($matches) && count($matches[1]) ? $matches[1][0] : $html);

        echo "<pre>$response</pre>";


    }
}
