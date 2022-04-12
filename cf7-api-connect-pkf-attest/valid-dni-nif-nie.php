<?php
   /*
    *   This function validates a Spanish identification number
    *   verifying its check digits.
    *
    *   NIFs and NIEs are personal numbers.
    *   CIFs are corporates.
    *
    *   This function requires:
    *       - isValidCIF and isValidCIFFormat
    *       - isValidNIE and isValidNIEFormat
    *       - isValidNIF and isValidNIFFormat
    *
    *   This function returns:
    *       TRUE: If specified identification number is correct
    *       FALSE: Otherwise
    *
    *   Usage:
    *       echo isValidIdNumber( 'G28667152' );
    *   Returns:
    *       TRUE
    */
    function isValidIdNumber( $docNumber ) {
        $fixedDocNumber = strtoupper( $docNumber );
        return isValidNIF( $fixedDocNumber ) || isValidNIE( $fixedDocNumber ) || isValidCIF( $fixedDocNumber );
    }

   /*
    *   This function validates a Spanish identification number
    *   verifying its check digits.
    *
    *   This function is intended to work with NIF numbers.
    *
    *   This function is used by:
    *       - isValidIdNumber
    *
    *   This function requires:
    *       - isValidCIFFormat
    *       - getNIFCheckDigit
    *
    *   This function returns:
    *       TRUE: If specified identification number is correct
    *       FALSE: Otherwise
    *
    *   Algorithm works as described in:
    *       http://www.interior.gob.es/dni-8/calculo-del-digito-de-Check-del-nif-nie-2217
    *
    *   Usage:
    *       echo isValidNIF( '33576428Q' );
    *   Returns:
    *       TRUE
    */
    function isValidNIF( $docNumber ) {
        $isValid = FALSE;
        $fixedDocNumber = "";

        $correctDigit = "";
        $writtenDigit = "";

        if( !preg_match( "/^[A-Z]+$/i", substr( $fixedDocNumber, 1, 1 ) ) ) {
            $fixedDocNumber = strtoupper( substr( "000000000" . $docNumber, -9 ) );
        } else {
            $fixedDocNumber = strtoupper( $docNumber );
        }

        $writtenDigit = strtoupper(substr( $docNumber, -1, 1 ));

        if( isValidNIFFormat( $fixedDocNumber ) ) {
            $correctDigit = getNIFCheckDigit( $fixedDocNumber );

            if( $writtenDigit == $correctDigit ) {
                $isValid = TRUE;
            }
        }

        return $isValid;
    }

   /*
    *   This function validates a Spanish identification number
    *   verifying its check digits.
    *
    *   This function is intended to work with NIE numbers.
    *
    *   This function is used by:
    *       - isValidIdNumber
    *
    *   This function requires:
    *       - isValidNIEFormat
    *       - isValidNIF
    *
    *   This function returns:
    *       TRUE: If specified identification number is correct
    *       FALSE: Otherwise
    *
    *   Algorithm works as described in:
    *       http://www.interior.gob.es/dni-8/calculo-del-digito-de-control-del-nif-nie-2217
    *
    *   Usage:
    *       echo isValidNIE( 'X6089822C' )
    *   Returns:
    *       TRUE
    */
    function isValidNIE( $docNumber ) {
        $isValid = FALSE;
        $fixedDocNumber = "";

        if( !preg_match( "/^[A-Z]+$/i", substr( $fixedDocNumber, 1, 1 ) ) ) {
            $fixedDocNumber = strtoupper( substr( "000000000" . $docNumber, -9 ) );
        } else {
            $fixedDocNumber = strtoupper( $docNumber );
        }

        if( isValidNIEFormat( $fixedDocNumber ) ) {
            if( substr( $fixedDocNumber, 1, 1 ) == "T" ) {
                $isValid = TRUE;
            } else {
                /* The algorithm for validating the check digits of a NIE number is
                    identical to the altorithm for validating NIF numbers. We only have to
                    replace Y, X and Z with 1, 0 and 2 respectively; and then, run
                    the NIF altorithm */
                $numberWithoutLast = substr( $fixedDocNumber, 0, strlen($fixedDocNumber)-1 );
                $lastDigit = substr( $fixedDocNumber, strlen($fixedDocNumber)-1, strlen($fixedDocNumber) );
                $numberWithoutLast = str_replace('Y', '1', $numberWithoutLast);
                $numberWithoutLast = str_replace('X', '0', $numberWithoutLast);
                $numberWithoutLast = str_replace('Z', '2', $numberWithoutLast);
                $fixedDocNumber = $numberWithoutLast . $lastDigit;
                $isValid = isValidNIF( $fixedDocNumber );
            }
        }

        return $isValid;
    }

   /*
    *   This function validates a Spanish identification number
    *   verifying its check digits.
    *
    *   This function is intended to work with CIF numbers.
    *
    *   This function is used by:
    *       - isValidDoc
    *
    *   This function requires:
    *       - isValidCIFFormat
    *       - getCIFCheckDigit
    *
    *   This function returns:
    *       TRUE: If specified identification number is correct
    *       FALSE: Otherwise
    *
    * CIF numbers structure is defined at:
    *   BOE number 49. February 26th, 2008 (article 2)
    *
    *   Usage:
    *       echo isValidCIF( 'F43298256' );
    *   Returns:
    *       TRUE
    */
    function isValidCIF( $docNumber ) {
        $isValid = FALSE;
        $fixedDocNumber = "";

        $correctDigit = "";
        $writtenDigit = "";

        $fixedDocNumber = strtoupper( $docNumber );
        $writtenDigit = substr( $fixedDocNumber, -1, 1 );

        if( isValidCIFFormat( $fixedDocNumber ) == 1 ) {
            $correctDigit = getCIFCheckDigit( $fixedDocNumber );

            if( $writtenDigit == $correctDigit ) {
                $isValid = TRUE;
            }
        }

        return $isValid;
    }

   /*
    *   This function validates the format of a given string in order to
    *   see if it fits with NIF format. Practically, it performs a validation
    *   over a NIF, except this function does not check the check digit.
    *
    *   This function is intended to work with NIF numbers.
    *
    *   This function is used by:
    *       - isValidIdNumber
    *       - isValidNIF
    *
    *   This function returns:
    *       TRUE: If specified string respects NIF format
    *       FALSE: Otherwise
    *
    *   Usage:
    *       echo isValidNIFFormat( '33576428Q' )
    *   Returns:
    *       TRUE
    */
    function isValidNIFFormat( $docNumber ) {
        return respectsDocPattern(
            $docNumber,
            '/^[KLM0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][a-zA-Z0-9]/' );
    }

   /*
    *   This function validates the format of a given string in order to
    *   see if it fits with NIE format. Practically, it performs a validation
    *   over a NIE, except this function does not check the check digit.
    *
    *   This function is intended to work with NIE numbers.
    *
    *   This function is used by:
    *       - isValidIdNumber
    *       - isValidNIE
    *
    *   This function requires:
    *       - respectsDocPattern
    *
    *   This function returns:
    *       TRUE: If specified string respects NIE format
    *       FALSE: Otherwise
    *
    *   Usage:
    *       echo isValidNIEFormat( 'X6089822C' )
    *   Returns:
    *       TRUE
    */
    function isValidNIEFormat( $docNumber ) {
        return respectsDocPattern(
            $docNumber,
            '/^[XYZT][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/' );
    }

   /*
    *   This function validates the format of a given string in order to
    *   see if it fits with CIF format. Practically, it performs a validation
    *   over a CIF, but this function does not check the check digit.
    *
    *   This function is intended to work with CIF numbers.
    *
    *   This function is used by:
    *       - isValidIdNumber
    *       - isValidCIF
    *
    *   This function requires:
    *       - respectsDocPattern
    *
    *   This function returns:
    *       TRUE: If specified string respects CIF format
    *       FALSE: Otherwise
    *
    *   Usage:
    *       echo isValidCIFFormat( 'H24930836' )
    *   Returns:
    *       TRUE
    */
    function isValidCIFFormat( $docNumber ) {
        return
            respectsDocPattern(
                $docNumber,
                '/^[PQSNWR][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/' )
        or
            respectsDocPattern(
                $docNumber,
                '/^[ABCDEFGHJUV][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]/' );
    }

   /*
    *   This function calculates the check digit for an individual Spanish
    *   identification number (NIF).
    *
    *   You can replace check digit with a zero when calling the function.
    *
    *   This function is used by:
    *       - isValidNIF
    *
    *   This function requires:
    *       - isValidNIFFormat
    *
    *   This function returns:
    *       - Returns check digit if provided string had a correct NIF structure
    *       - An empty string otherwise
    *
    *   Usage:
    *       echo getNIFCheckDigit( '335764280' )
    *   Returns:
    *       Q
    */
    function getNIFCheckDigit( $docNumber ) {
        $keyString = 'TRWAGMYFPDXBNJZSQVHLCKE';

        $fixedDocNumber = "";

        $position = 0;
        $writtenLetter = "";
        $correctLetter = "";

        if( !preg_match( "/^[A-Z]+$/i", substr( $fixedDocNumber, 1, 1 ) ) ) {
            $fixedDocNumber = strtoupper( substr( "000000000" . $docNumber, -9 ) );
        } else {
            $fixedDocNumber = strtoupper( $docNumber );
        }

        if( isValidNIFFormat( $fixedDocNumber ) ) {
            $writtenLetter = substr( $fixedDocNumber, -1 );

            if( isValidNIFFormat( $fixedDocNumber ) ) {
                $fixedDocNumber = str_replace( 'K', '0', $fixedDocNumber );
                $fixedDocNumber = str_replace( 'L', '0', $fixedDocNumber );
                $fixedDocNumber = str_replace( 'M', '0', $fixedDocNumber );

                $position = substr( $fixedDocNumber, 0, 8 ) % 23;
                $correctLetter = substr( $keyString, $position, 1 );
            }
        }

        return $correctLetter;
    }

   /*
    *   This function calculates the check digit for a corporate Spanish
    *   identification number (CIF).
    *
    *   You can replace check digit with a zero when calling the function.
    *
    *   This function is used by:
    *       - isValidCIF
    *
    *   This function requires:
    *     - isValidCIFFormat
    *
    *   This function returns:
    *       - The correct check digit if provided string had a
    *         correct CIF structure
    *       - An empty string otherwise
    *
    *   Usage:
    *       echo getCIFCheckDigit( 'H24930830' );
    *   Prints:
    *       6
    */
    function getCIFCheckDigit( $docNumber ) {
        $fixedDocNumber = "";

        $centralChars = "";
        $firstChar = "";

        $evenSum = 0;
        $oddSum = 0;
        $totalSum = 0;
        $lastDigitTotalSum = 0;

        $correctDigit = "";

        $fixedDocNumber = strtoupper( $docNumber );

        if( isValidCIFFormat( $fixedDocNumber ) ) {
            $firstChar = substr( $fixedDocNumber, 0, 1 );
            $centralChars = substr( $fixedDocNumber, 1, 7 );

            $evenSum =
                substr( $centralChars, 1, 1 ) +
                substr( $centralChars, 3, 1 ) +
                substr( $centralChars, 5, 1 );

            $oddSum =
                sumDigits( substr( $centralChars, 0, 1 ) * 2 ) +
                sumDigits( substr( $centralChars, 2, 1 ) * 2 ) +
                sumDigits( substr( $centralChars, 4, 1 ) * 2 ) +
                sumDigits( substr( $centralChars, 6, 1 ) * 2 );

            $totalSum = $evenSum + $oddSum;

            $lastDigitTotalSum = substr( $totalSum, -1 );

            if( $lastDigitTotalSum > 0 ) {
                $correctDigit = 10 - ( $lastDigitTotalSum % 10 );
            } else {
                $correctDigit = 0;
            }
        }

        /* If CIF number starts with P, Q, S, N, W or R,
            check digit sould be a letter */
        if( preg_match( '/[PQSNWR]/', $firstChar ) ) {
            $correctDigit = substr( "JABCDEFGHI", $correctDigit, 1 );
        }

        return $correctDigit;
    }

   /*
    *   This function validates the format of a given string in order to
    *   see if it fits a regexp pattern.
    *
    *   This function is intended to work with Spanish identification
    *   numbers, so it always checks string length (should be 9) and
    *   accepts the absence of leading zeros.
    *
    *   This function is used by:
    *       - isValidNIFFormat
    *       - isValidNIEFormat
    *       - isValidCIFFormat
    *
    *   This function returns:
    *       TRUE: If specified string respects the pattern
    *       FALSE: Otherwise
    *
    *   Usage:
    *       echo respectsDocPattern(
    *           '33576428Q',
    *           '/^[KLM0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z]/' );
    *   Returns:
    *       TRUE
    */
    function respectsDocPattern( $givenString, $pattern ) {
        $isValid = FALSE;

        $fixedString = strtoupper( $givenString );

        if( is_int( substr( $fixedString, 0, 1 ) ) ) {
            $fixedString = substr( "000000000" . $givenString , -9 );
        }

        if( preg_match( $pattern, $fixedString ) ) {
            $isValid = TRUE;
        }

        return $isValid;
    }

   /*
    *   This function performs the sum, one by one, of the digits
    *   in a given quantity.
    *
    *   For instance, it returns 6 for 123 (as it sums 1 + 2 + 3).
    *
    *   This function is used by:
    *       - getCIFCheckDigit
    *
    *   Usage:
    *       echo sumDigits( 12345 );
    *   Returns:
    *       15
    */
    function sumDigits( $digits ) {
        $total = 0;
        $i = 1;

        while( $i <= strlen( $digits ) ) {
            $thisNumber = substr( $digits, $i - 1, 1 );
            $total += $thisNumber;

            $i++;
        }

        return $total;
    }

   /*
    *   This function obtains the description of a document type
    *   for Spanish identification number.
    *
    *   For instance, if A83217281 is passed, it returns "Sociedad Anónima".
    *
    *   This function requires:
    *       - identificationType (table)
    *       - isValidCIFFormat
    *       - isValidNIEFormat
    *       - isValidNIFFormat
    *
    *   Usage:
    *       echo getIdType( 'A49640873' )
    *   Returns:
    *       Sociedad Anónima
    */

    $identificationType = array(
        'K' => 'Español menor de catorce años o extranjero menor de dieciocho',
        'L' => 'Español mayor de catorce años residiendo en el extranjero',
        'M' => 'Extranjero mayor de dieciocho años sin NIE',

        '0' => 'Español con documento nacional de identidad',
        '1' => 'Español con documento nacional de identidad',
        '2' => 'Español con documento nacional de identidad',
        '3' => 'Español con documento nacional de identidad',
        '4' => 'Español con documento nacional de identidad',
        '5' => 'Español con documento nacional de identidad',
        '6' => 'Español con documento nacional de identidad',
        '7' => 'Español con documento nacional de identidad',
        '8' => 'Español con documento nacional de identidad',
        '9' => 'Español con documento nacional de identidad',

        'T' => 'Extranjero residente en España e identificado por la Policía con un NIE',
        'X' => 'Extranjero residente en España e identificado por la Policía con un NIE',
        'Y' => 'Extranjero residente en España e identificado por la Policía con un NIE',
        'Z' => 'Extranjero residente en España e identificado por la Policía con un NIE',

        /* As described in BOE number 49. February 26th, 2008 (article 3) */
        'A' => 'Sociedad Anónima',
        'B' => 'Sociedad de responsabilidad limitada',
        'C' => 'Sociedad colectiva',
        'D' => 'Sociedad comanditaria',
        'E' => 'Comunidad de bienes y herencias yacentes',
        'F' => 'Sociedad cooperativa',
        'G' => 'Asociación',
        'H' => 'Comunidad de propietarios en régimen de propiedad horizontal',
        'J' => 'Sociedad Civil => con o sin personalidad jurídica',
        'N' => 'Entidad extranjera',
        'P' => 'Corporación local',
        'Q' => 'Organismo público',
        'R' => 'Congregación o Institución Religiosa',
        'S' => 'Órgano de la Administración del Estado y Comunidades Autónomas',
        'U' => 'Unión Temporal de Empresas',
        'V' => 'Fondo de inversiones o de pensiones, agrupación de interés económico, etc',
        'W' => 'Establecimiento permanente de entidades no residentes en España' );

    function getIdType( $docNumber ) {
        global $identificationType;

        $docTypeDescription = "";
        $firstChar = substr( $docNumber, 0, 1 );

        if( isValidNIFFormat( $docNumber ) or
            isValidNIEFormat( $docNumber ) or
            isValidCIFFormat( $docNumber ) ) {

            $docTypeDescription = $identificationType[ $firstChar ];
        }

        return $docTypeDescription;
    }

    


//IBAN
function isValidIBAN ($iban) {
    $iban = strtolower($iban);
    $Countries = array(
      'al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,
      'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,
      'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,
      'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,
      'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24
    );
    $Chars = array(
      'a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,
      'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35
    );
  
    if (strlen($iban) != $Countries[ substr($iban,0,2) ]) { return false; }
  
    $MovedChar = substr($iban, 4) . substr($iban,0,4);
    $MovedCharArray = str_split($MovedChar);
    $NewString = "";
  
    foreach ($MovedCharArray as $k => $v) {
  
      if ( !is_numeric($MovedCharArray[$k]) ) {
        $MovedCharArray[$k] = $Chars[$MovedCharArray[$k]];
      }
      $NewString .= $MovedCharArray[$k];
    }
    if (function_exists("bcmod")) { return bcmod($NewString, '97') == 1; }
  
    // http://au2.php.net/manual/en/function.bcmod.php#38474
    $x = $NewString; $y = "97";
    $take = 5; $mod = "";
  
    do {
      $a = (int)$mod . substr($x, 0, $take);
      $x = substr($x, $take);
      $mod = $a % $y;
    }
    while (strlen($x));
  
    return (int)$mod == 1;
  }