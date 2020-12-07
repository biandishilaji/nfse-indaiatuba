<?php


namespace App\Http\Controllers;


use App\Http\Services\Abrasf;
use App\Http\Services\Signer;
use App\Models\Certificate;

class TestesController extends ApiController
{

    public function generateNFSe(){
        try {


            $xml = file_get_contents(storage_path().'/teste2.xml');

            $canonical = [true,false,null,null];
            $algorithm = OPENSSL_ALGO_SHA1;

            $pfx = file_get_contents(storage_path().'/certificate.pfx');

            $certificate  = Certificate::readPfx($pfx, 'nfe1234');

            $signed = Signer::sign($certificate, $xml, 'GerarNfseEnvio', 'Rps', $algorithm, $canonical, 'Rps');

            file_put_contents(storage_path().'/teste2.xml', $signed);

            $Abrasf = new Abrasf();

            $Abrasf->send($certificate);

        }catch (\Exception $e){

        }
    }

    public function Soap(){
        try {


//            soap puro ainda em testes
            
//            para testes com o Soap, utilize $soapClient->GerarNfseEnvio

            $urlWsdl = 'https://deiss.indaiatuba.sp.gov.br/homologacao/nfse?wsdl';

            $soapClient = new \SoapClient(null, [
                'exceptions' => true
            ]);

            $requestData = [ 'country' => 'brazil' ];

            $response = $soapClient->GetAirportInformationByCountry($requestData);



        }catch (\Exception $e){
            echo $e->getMessage();
        }
        catch (SoapFault $exception){
            echo $exception->getMessage();
        }

    }
}
