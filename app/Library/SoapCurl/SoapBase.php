<?php


namespace App\Library\SoapCurl;


class SoapBase
{
    /**
     * @var int
     */
    protected $soapprotocol = self::SSL_DEFAULT;
    /**
     * @var int
     */
    protected $soaptimeout = 20;
    /**
     * @var string
     */
    protected $proxyIP;
    /**
     * @var int
     */
    protected $proxyPort;
    /**
     * @var string
     */
    protected $proxyUser;
    /**
     * @var string
     */
    protected $proxyPass;
    /**
     * @var array
     */
    protected $prefixes = [1 => 'soapenv', 2 => 'soap'];
    /**
     * @var Certificate|null
     */
    protected $certificate;
    /**
     * @var LoggerInterface|null
     */
    protected $logger;
    /**
     * @var string
     */
    protected $tempdir;
    /**
     * @var string
     */
    protected $certsdir;
    /**
     * @var string
     */
    protected $debugdir;
    /**
     * @var string
     */
    const SSL_DEFAULT = 0; //default
    const SSL_TLSV1 = 1; //TLSv1
    const SSL_SSLV2 = 2; //SSLv2
    const SSL_SSLV3 = 3; //SSLv3
    const SSL_TLSV1_0 = 4; //TLSv1.0
    const SSL_TLSV1_1 = 5; //TLSv1.1
    const SSL_TLSV1_2 = 6; //TLSv1.2

    protected $prifile;
    /**
     * @var string
     */
    protected $pubfile;
    /**
     * @var string
     */
    protected $certfile;
    /**
     * @var string
     */
    protected $casefaz;
    /**
     * @var bool
     */
    protected $disablesec = false;
    /**
     * @var bool
     */
    protected $disableCertValidation = false;
    /**
     * @var \League\Flysystem\Adapter\Local
     */
    protected $adapter;
    /**
     * @var \League\Flysystem\Filesystem
     */
    protected $filesystem;
    /**
     * @var string
     */
    protected $temppass = '';
    /**
     * @var bool
     */
    protected $encriptPrivateKey = true;
    /**
     * @var bool
     */
    protected $debugmode = false;
    /**
     * @var string
     */
    public $responseHead;
    /**
     * @var string
     */
    public $responseBody;
    /**
     * @var string
     */
    public $requestHead;
    /**
     * @var string
     */
    public $requestBody;
    /**
     * @var string
     */
    public $soaperror;
    /**
     * @var array
     */
    public $soapinfo = [];
    /**
     * @var int
     */
    public $waitingTime = 45;

    /**
     * Constructor
     * @param Certificate|null $certificate
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        $certificate)
    {
        $this->certificate = $certificate;
        $this->setTemporaryFolder(sys_get_temp_dir() . '/sped/');
    }

    public function setTemporaryFolder($folderRealPath)
    {
        $this->tempdir = $folderRealPath;
        $this->setLocalFolder($folderRealPath);
    }

    /**
     * Set Local folder for flysystem
     * @param string $folder
     */
    protected function setLocalFolder($folder = '')
    {
        $this->adapter = new Local($folder);
        $this->filesystem = new Filesystem($this->adapter);
    }

    public function timeout($timesecs)
    {
        return $this->soaptimeout = $timesecs;
    }

    /**
     * Set security protocol
     * @param int $protocol
     * @return int
     */
    public function protocol($protocol = self::SSL_DEFAULT)
    {
        return $this->soapprotocol = $protocol;
    }
    public function setSoapPrefix($prefixes)
    {
        return $this->prefixes = $prefixes;
    }

    /**
     * Set proxy parameters
     * @param string $ip
     * @param int $port
     * @param string $user
     * @param string $password
     */
    public function proxy($ip, $port, $user, $password)
    {
        $this->proxyIP = $ip;
        $this->proxyPort = $port;
        $this->proxyUser = $user;
        $this->proxyPass = $password;
    }
//    abstract public function send(
//        $operation,
//        $url,
//        $action,
//        $envelope,
//        $parameters
//    );

    public function makeEnvelopeSoap(
        $request,
        $namespaces,
        $soapver = SOAP_1_2,
        $header = null
    )
    {
        $prefix = $this->prefixes[$soapver];
        $envelope = "<$prefix:Envelope";
        foreach ($namespaces as $key => $value) {
            $envelope .= " $key=\"$value\"";
        }
        $envelope .= ">";
        $soapheader = "<$prefix:Header/>";
        if (!empty($header)) {
            $ns = !empty($header->namespace) ? $header->namespace : '';
            $name = $header->name;
            $soapheader = "<$prefix:Header>";
            $soapheader .= "<$name xmlns=\"$ns\">";
            foreach ($header->data as $key => $value) {
                $soapheader .= "<$key>$value</$key>";
            }
            $soapheader .= "</$name></$prefix:Header>";
        }
        $envelope .= $soapheader;
        $envelope .= "<$prefix:Body>$request</$prefix:Body>"
            . "</$prefix:Envelope>";
        return $envelope;
    }
    public function saveTemporarilyKeyFiles()
    {
        if (!is_object($this->certificate)) {
            throw new RuntimeException(
                'Certificate not found.'
            );
        }

        $document = Company::where('id', $this->certificate->company_id)->value('document');
        $this->certsdir = $document . '/certs/';
        $this->prifile = $this->certsdir . Strings::randomString(10) . '.pem';
        $this->pubfile = $this->certsdir . Strings::randomString(10) . '.pem';
        $this->certfile = $this->certsdir . Strings::randomString(10) . '.pem';
        $ret = true;
        $private = $this->certificate->key_private;
        if ($this->encriptPrivateKey) {
            //cria uma senha temporária ALEATÓRIA para salvar a chave primaria
            //portanto mesmo que localizada e identificada não estará acessível
            //pois sua senha não existe além do tempo de execução desta classe
            $this->temppass = Strings::randomString(16);
            //encripta a chave privada entes da gravação do filesystem
            openssl_pkey_export(
                $this->certificate->key_private,
                $private,
                $this->temppass
            );
        }
        $ret &= $this->filesystem->put(
            $this->prifile,
            $private
        );
        $ret &= $this->filesystem->put(
            $this->pubfile,
            $this->certificate->key_public
        );
        $ret &= $this->filesystem->put(
            $this->certfile,
            $private . "{$this->certificate}"
        );
        if (!$ret) {
            throw new RuntimeException(
                'Unable to save temporary key files in folder.'
            );
        }
    }
    public function removeTemporarilyFiles()
    {
        $contents = $this->filesystem->listContents($this->certsdir, true);
        //define um limite de $waitingTime min, ou seja qualquer arquivo criado a mais
        //de $waitingTime min será removido
        //NOTA: quando ocorre algum erro interno na execução do script, alguns
        //arquivos temporários podem permanecer
        //NOTA: O tempo default é de 45 minutos e pode ser alterado diretamente nas
        //propriedades da classe, esse tempo entre 5 a 45 min é recomendável pois
        //podem haver processos concorrentes para um mesmo usuário. Esses processos
        //como o DFe podem ser mais longos, dependendo a forma que o aplicativo
        //utilize a API. Outra solução para remover arquivos "perdidos" pode ser
        //encontrada oportunamente.
        $dt = new \DateTime();
        $tint = new \DateInterval("PT" . $this->waitingTime . "M");
        $tint->invert = 1;
        $tsLimit = $dt->add($tint)->getTimestamp();
        foreach ($contents as $item) {
            if ($item['type'] == 'file') {
                if ($item['path'] == $this->prifile
                    || $item['path'] == $this->pubfile
                    || $item['path'] == $this->certfile
                ) {
                    $this->filesystem->delete($item['path']);
                    continue;
                }
                $timestamp = $this->filesystem->getTimestamp($item['path']);
                if ($timestamp < $tsLimit) {
                    //remove arquivos criados a mais de 45 min
                    $this->filesystem->delete($item['path']);
                }
            }
        }
    }

}
