<?php


namespace App\Exceptions;


class SignerException extends \RuntimeException
{
    public static function isNotXml()
    {
        return new static('The content not is a valid XML.');
    }

    public static function digestComparisonFailed()
    {
        return new static('The XML content does not match the Digest Value. '
            . 'Probably modified after it was signed');
    }

    public static function signatureComparisonFailed()
    {
        return new static('The XML SIGNATURE does not match. '
            . 'Probably modified after it was signed.');
    }


    public static function tagNotFound($tagname)
    {
        return new static("The specified tag &lt;$tagname&gt; was not found in xml.");
    }
}
