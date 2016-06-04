<?php

// the exception used by the parser
class iniParserException extends \Exception
{

    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}

// the parser
function iniParser($filename, $processSections = false, $processEmptyValues = TRUE)
{
    $initext = file_get_contents($filename);
    $ret = [];
    $section = null;
    $lineNum = 0;
    $lines = explode("\n", str_replace("\r\n", "\n", $initext));

    foreach ($lines as $line)
    {
        ++$lineNum;

        $line = trim(preg_replace('/[;#].*/', '', $line));
        if(strlen($line) === 0) {
            continue;
        }

        if ($processSections && $line{0} === '[' && $line{strlen($line)-1} === ']')
        {
            // section header
            $section = trim(substr($line, 1, -1));
        }
        else
        {
            $eqIndex = strpos($line, '=');
            if($eqIndex !== false) {
                $key = trim(substr($line, 0, $eqIndex));
                $matches = [];
                preg_match('/(?<name>\w+)(?<index>\[\w*\])?/', $key, $matches);
                if(!array_key_exists('name', $matches)) {
                    throw new iniParserException("Variable name must not be empty! In file \"$filename\" in line $lineNum.");
                }
                $keyName = $matches['name'];
                if(array_key_exists('index', $matches)) {
                    $isArray = true;
                    $arrayIndex = trim($matches['index']);
                    if(strlen($arrayIndex) == 0) {
                        $arrayIndex = null;
                    }
                } else {
                    $isArray = false;
                    $arrayIndex = null;
                }

                $value = trim(substr($line, $eqIndex+1));

                if (!empty($value))
                {
                    if ($value{0} === '"' && $value{strlen($value)-1} === '"')
                    {
                        // TODO: to check for multiple closing " let's assume it's fine
                        $value = str_replace('\\"', '"', substr($value, 1, -1));
                    }
                    else
                    {
                        // special value
                        switch(strtolower($value))
                        {
                            case 'yes':
                            case 'true':
                            case 'on':
                                $value = true;
                                break;
                            case 'no':
                            case 'false':
                            case 'off':
                                $value = false;
                                break;
                            case 'null':
                            case 'none':
                                $value = null;
                                break;
                            default:
                                if (is_numeric($value))
                                {
                                    $value = $value + 0; // make it an int/float
                                }
                        }
                    }
                }


                if($section !== null) {
                    if($isArray) {
                        if(!array_key_exists($keyName, $ret[$section])) {
                            $ret[$section][$keyName] = [];
                        }
                        if($arrayIndex === null) {
                            $ret[$section][$keyName][] = $value;
                        } else {
                            $ret[$section][$keyName][$arrayIndex] = $value;
                        }
                    } else {
                        $ret[$section][$keyName] = $value;
                    }
                }
                else
                {
                        if($isArray)
                        {
                            if (!array_key_exists($keyName, $ret))
                            {
                                $ret[$keyName] = [];
                            }
                            if ($arrayIndex === NULL)
                            {
                                if ($processEmptyValues === TRUE || (!empty($value) && $processEmptyValues === FALSE))
                                {
                                    $ret[$keyName][] = $value;
                                }
                            }
                            else
                            {
                                if ($processEmptyValues === TRUE || (!empty($value) && $processEmptyValues === FALSE))
                                {
                                    $ret[$keyName][$arrayIndex] = $value;

                                }
                            }
                        }
                        else
                        {
                            if ($processEmptyValues === TRUE || (!empty($value) && $processEmptyValues === FALSE))
                            {
                                $ret[$keyName] = $value;
                            }
                        }

                }
            }
        }
    }

    return $ret;
}