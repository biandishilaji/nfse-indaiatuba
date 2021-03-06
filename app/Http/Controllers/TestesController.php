<?php


namespace App\Http\Controllers;


use App\Http\Services\Abrasf;
use App\Http\Services\Signer;
use App\Models\Certificate;

class TestesController extends ApiController
{

    public function generateNFSe(){
        try {


            $xml = file_get_contents(storage_path().'/teste.xml');

            $canonical = [true,false,null,null];
            $algorithm = OPENSSL_ALGO_SHA1;

            $pfx = file_get_contents(storage_path().'/certificate.pfx');

            $certificate  = Certificate::readPfx($pfx, 'nfe1234');

            $signed = Signer::sign($certificate, $xml, 'Rps', 'Rps', $algorithm, $canonical, 'Rps');

            file_put_contents(storage_path().'/teste.xml', $signed);

            $Abrasf = new Abrasf();

            $xml = file_get_contents(storage_path().'/teste.xml');

            $Abrasf->send4($xml);

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

            $request = file_get_contents(storage_path().'/teste2.xml');

            $response = $soapClient->GerarNfseEnvio($request);

           // if(!$response)
           //  throw new \Exception();

        }catch (\Exception $e){
            echo $e->getMessage();
        }
        catch (SoapFault $exception){
            echo $exception->getMessage();
        }

    }
}
