<?php


namespace App\Models;


class Certificate
{
    public function __construct(PrivateKey $privateKey, PublicKey $publicKey, CertificationChain $chainKeys)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->chainKeys = $chainKeys;
    }

    public static function readPfx($content, $password)
    {
        $certs = [];
        if (!openssl_pkcs12_read($content, $certs, $password)) {
            throw CertificateException::unableToRead();
        }
        $chain = '';
        if (!empty($certs['extracerts'])) {
            foreach ($certs['extracerts'] as $ec) {
                $chain .= $ec;
            }
        }
        return new static(
            new PrivateKey($certs['pkey']),
            new PublicKey($certs['cert']),
            new CertificationChain($chain)
        );
    }
    public function writePfx($password)
    {
        $password = trim($password);
        if (empty($password)) {
            return '';
        }
        $x509_cert = openssl_x509_read("{$this->publicKey}");
        $privateKey_resource = openssl_pkey_get_private("{$this->privateKey}");
        $pfxstring = '';
        openssl_pkcs12_export(
            $x509_cert,
            $pfxstring,
            $privateKey_resource,
            $password,
            $this->chainKeys->getExtraCertsForPFX()
        );
        return $pfxstring;
    }
    public function getCompanyName()
    {
        return $this->publicKey->commonName;
    }
    public function getValidFrom()
    {
        return $this->publicKey->validFrom;
    }

    /**
     * Gets end date.
     * @return \DateTime Returns end date.
     */
    public function getValidTo()
    {
        return $this->publicKey->validTo;
    }

    /**
     * Check if certificate has been expired.
     * @return bool Returns true when it is truth, otherwise false.
     */
    public function isExpired()
    {
        return $this->publicKey->isExpired();
    }

    /**
     * Gets CNPJ by OID '2.16.76.1.3.3' from ASN.1 certificate struture
     * @return string
     */
    public function getCnpj()
    {
        return $this->publicKey->cnpj();
    }

    /**
     * Gets CPF by OID '2.16.76.1.3.1' from ASN.1 certificate struture
     * @return string
     */
    public function getCpf()
    {
        return $this->publicKey->cpf();
    }

    /**
     * {@inheritdoc}
     */
    public function sign($content, $algorithm = OPENSSL_ALGO_SHA1)
    {
        return $this->privateKey->sign($content, $algorithm);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($data, $signature, $algorithm = OPENSSL_ALGO_SHA1)
    {
        return $this->publicKey->verify($data, $signature, $algorithm);
    }

    /**
     * Returns public key and chain in PEM format
     * @return string
     */
    public function __toString()
    {
        $chainKeys = '';
        if ($this->chainKeys != null) {
            $chainKeys = "{$this->chainKeys}";
        }
        return "{$this->publicKey}{$chainKeys}";
    }
}
