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

    // Whois sever lookup function
    public static function queryWhois($domain, $registrar = NULL)
    {
        // fixing the domain name format
        $domain = strtolower(trim($domain));
        $domain = preg_replace("/^http:\/\//i", '', $domain);
        $domain = preg_replace("/^www\./i", '', $domain);
        $domain = explode('/', $domain);
        $domain = trim($domain[0]);

        // server output string
        $output = FALSE;

        if (empty($registrar))
        {
            $servers = array();

            // retrieving server resoures and lists
            foreach (iniParser(self::$domain_file) as $tld => $entity)
            {
                if (!empty($entity))
                {
                    list($server,) = explode('|', $entity, 2);

                    // if there are multiple TLD servers (pick the first one)
                    if (strpos($server, ',') !== FALSE)
                    {
                        $server = explode(',', $server);
                        $server = trim($server[0]);
                    }

                    $servers[strtolower($tld)] = strtolower($server);
                }
            }

            // split the TLD from domain name
            $_domain = explode('.', $domain);
            $lst     = count($_domain)-1;
            $ext     = $_domain[$lst];

            if (!isset($servers[$ext]))
            {
                trigger_error('No matching nic server found!', E_USER_ERROR);
                // http://www.nirsoft.net/whois-servers.txt
            }

            $nic_server = $servers[$ext];
        }
        else
        {
            $nic_server = strip_tags(trim($registrar));
        }

        // connect to whois server
        if ($conn = fsockopen($nic_server, 43))
        {
            fputs($conn, $domain."\r\n");

            while (!feof($conn))
            {
                $output .= fgets($conn, 128);
            }
            fclose($conn);
        }
        else
        {
            trigger_error('Could not connect to '.$nic_server.'!', E_USER_ERROR);
        }

        return $output;
    }

    // findWhois($string) {}
}

if ($result = Domtooz::queryWhois('travisfont.com'))
{
    echo '<pre>';
    print_r($result);
}

#var_dump(Domtooz::checkTLD(Domtooz::explodeTLD('111-222-1933email@address.com')));
