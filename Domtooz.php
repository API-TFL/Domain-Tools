<?php

require_once 'iniparser.php';

class Domtooz
{
    // Please reference: http://data.iana.org/TLD/tlds-alpha-by-domain.txt
    // To find whois servers: https://gwhois.org

    private static $domain_file = 'domains.ini';

    /**
     * Returns true or false
     * indicating whether or not the URL
     * passes a TLD check.
     */
    public static function checkTLD($host)
    {
        // TODO: do a file exist check here
        $tlds =  iniParser(self::$domain_file);

        return preg_match('/\.('.implode('|', array_keys($tlds)).')$/i', $host);
    }

    public static function explodeTLD($address)
    {
        if (strpos($address, '@') !== FALSE)
        {
            list(, $tld) = explode('@', $address, 2);

            return (string) $tld;
        }
        else
        {
            return FALSE;
        }
    }
}


var_dump(Domtooz::checkTLD(Domtooz::explodeTLD('111-222-1933email@address.com')));