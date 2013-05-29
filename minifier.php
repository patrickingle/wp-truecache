<?php

/**
 * Copyright (C) 2011+ Martin Lacroix
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @license GNU General Public License Version 3 (GPLv3)
 * @link http://www.gnu.org/licenses/
 */

/**
 * PHP_VER   : 5.3+
 * LIBRARIES :
 * KEYWORDS  : PHP MINIFIER MINIFY SOURCE CODE COMPRESS COMPRESSOR
 *             PHP MINIFIER MINIFICATION CODE SOURCE COMPRESSER COMPRESSION
 *
 * Class that minify the php source code of one or many files and store the result in another unique file
 *
 * Classe qui réduit le code source d'un ou plusieurs fichiers php et enregistre le résultat dans un fichier unique
 *
 * @package tools
 * @version 1.0.0
 * @author Martin Lacroix
 */

class PhpMinifier {
   /**
    * List of tokens that can be written without sourrounding spaces
    * @var array Array([] => token)
    */
   static private $noSpaces = array(
      T_AND_EQUAL,                // &=
      T_ARRAY_CAST,               // (array)
      T_BOOLEAN_AND,              // &&
      T_BOOLEAN_OR,               // ||
      T_BOOL_CAST,                // (bool), (boolean)
      T_CLOSE_TAG,                /* ?> */
      T_CONCAT_EQUAL,             // .=
      T_CONSTANT_ENCAPSED_STRING, // 'string'
      T_DEC,                      // -- (one exception, see below)
      T_DIV_EQUAL,                // /=
      T_DNUMBER,                  // float number
      T_DOLLAR_OPEN_CURLY_BRACES, // ${
      T_DOUBLE_ARROW,             // =>
      T_DOUBLE_CAST,              // (real), (double), (float)
      T_DOUBLE_COLON,             // ::
      T_INC,                      // ++ (one exception, see below)
      T_INCLUDE,                  // include
      T_INCLUDE_ONCE,             // include_once
      T_INT_CAST,                 // (int), (integer)
      T_IS_EQUAL,                 // ==
      T_IS_GREATER_OR_EQUAL,      // >=
      T_IS_IDENTICAL,             // ===
      T_IS_NOT_EQUAL,             // != or <>
      T_IS_NOT_IDENTICAL,         // !==
      T_IS_SMALLER_OR_EQUAL,      // <=
      T_LNUMBER,                  // integer number
      T_MINUS_EQUAL,              // -=
      T_MOD_EQUAL,                // %=
      T_MUL_EQUAL,                // *=
      T_NS_SEPARATOR,             // \
      T_NUM_STRING,               // $a[0]
      T_OBJECT_CAST,              // (object)
      T_OBJECT_OPERATOR,          // ->
      T_OPEN_TAG_WITH_ECHO,       // <?= ou <%=
      T_OR_EQUAL,                 // |=
      T_PAAMAYIM_NEKUDOTAYIM,     // ::
      T_PLUS_EQUAL,               // +=
      T_REQUIRE,                  // require
      T_REQUIRE_ONCE,             // require_once
      T_SL,                       // <<
      T_SL_EQUAL,                 // <<=
      T_SR,                       // >>
      T_SR_EQUAL,                 // >>=
      T_STRING_CAST,              // (string)
      T_UNSET_CAST,               // (unset)
      T_XOR_EQUAL                 // ^=
   );

   /**
    * Minify PHP source code of files in the $paths argument
    * and store the minified code in the $outputFile argument
    * @param array $paths Array([] => path)
    * @param string $outputFile
    */
   static function minify(array $paths, $outputFile) {
      $openTag = FALSE;
      $code = '';
      foreach($paths as $path) {
         if (is_file($path)) {
            $min = self::compress($path, TRUE);
            if (strlen($min['code'])) {
               if ($openTag) {
                  if ( ! $min['openTag']) {
                     $code .= '?>';
                     $openTag = FALSE;
                  }
               }
               else {
                  if ($min['openTag']) {
                     $code .= '<?php ';
                     $openTag = TRUE;
                  }
               }
               $code .= $min['code'];
            }
         }
      }
      if ($openTag) {
         $code .= '?>';
      }
      file_put_contents($outputFile, $code);
   }

   /**
    * @param string $path
    * @param bool $removeOpenCloseTags
    * @return array Array(openTag => bool, code => string)
    */
   static private function compress($path, $removeOpenCloseTags = TRUE) {
      $src = php_strip_whitespace($path);

      $code = '';
      $openFound = FALSE;

      if(empty($src)) {
          return array('openTag' => $openFound, 'code' => $code);
      }

      $tokens = token_get_all($src);
      $nb     = count($tokens);

      $nextToken    = NULL;
      $prevToken    = NULL;
      $prevIsSymbol = FALSE;
      $prevSymbol   = NULL;

      for($i = 0; $i < $nb; ++$i) {
         $token = $tokens[$i];

         // symbols
         if ( ! is_array($token)) {
            $code         .= $token;
            $prevIsSymbol  = TRUE;
            $prevSymbol    = $token;
            continue;
         }

         // use of named variables instead of array $token
         list($index, $value) = $token;

         if ($removeOpenCloseTags) {
            // ignore open token at the begining
            if (($i === 0) && ($index === T_OPEN_TAG)) {
               $openFound = TRUE;
               continue;
            }
            // ignore close token at the end
            else
            if (($i === $nb-1) && ($index === T_CLOSE_TAG)) {
               continue;
            }
         }

         // HEREDOC/NOWDOC syntax: go to the end of the block without compression because of some special render
         if ($index === T_START_HEREDOC) {
            $code .= $value;
            while(++$i < $nb) {
               if (is_array($tokens[$i])) {
                  $code .= $tokens[$i][1];
                  if ($tokens[$i][0] === T_END_HEREDOC) {
                     $code .= ";";
                     ++$i;
                     break;
                  }
               }
               else {
                  $code .= $tokens[$i];
               }
            }
         }
         // SPACE between two keywords
         else
         if ($index === T_WHITESPACE) {
            if ($i === 1) {
               continue; // sometimes space at the begining
            }

            if ($i) {
               $prevToken = $tokens[$i-1]; // used below
            }

            if ($i < $nb) {
               $nextToken = $tokens[$i+1];

               // special user case:
               // case: $a + ++$b => minified: $a+++$b -> error: must keep space: $a+ ++$b
               // case: $a - --$b => minified: $a---$b -> error: must keep space: $a- --$b
               if ($prevIsSymbol && is_array($nextToken)) {
                  if (($nextToken[0] === T_INC) && ($prevSymbol === '+')
                        || ($nextToken[0] === T_DEC) && ($prevSymbol === '-'))
                  {
                     $code .= $value;
                     $prevIsSymbol = FALSE;
                     $prevSymbol = NULL;
                  }
                  continue;
               }
               // if the nextToken is a symbol or is in the array of "no surrounding spaces" tags
               // -> ignore the current token (T_WHITSPACE)
               else
               if (( ! is_array($nextToken)) || (in_array($nextToken[0], self::$noSpaces))) {
                  continue;
               }
               else
               if (is_array($nextToken)) {
                  // special user case: compression of "else if" to "elseif"
                  if (($nextToken[0] === T_IF) && is_array($prevToken) && ($prevToken[0] === T_ELSE)) {
                     continue;
                  }

                  if (($nextToken[0] === T_VARIABLE)
                        && (is_array($prevToken)
                        && in_array($prevToken[0], array(T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR, T_CASE, T_AS, T_RETURN, T_STATIC, T_ARRAY))))
                  {
                     continue;
                  }
               }
            $code .= $value;
         }
         // if the current token is in the array of "no surrounding spaces" tags
         // -> keep the current token and ignore the next only if it corresponds to T_WHITESPACE
         else
         if (in_array($index, self::$noSpaces)) {
            $code .= $value;
            if ($i < $nb) {
               $nextToken = $tokens[$i+1];
               if (is_array($nextToken) && ($nextToken[0] === T_WHITESPACE)) {
                  ++$i; // move forward the counter, so the next iteration will immediately stops after the T_WHITESPACE token
               }
            }
         }
         else {
            $code .= $value;
         }
         $prevIsSymbol = FALSE;
         $prevSymbol = NULL;
      }
      return array('openTag' => $openFound, 'code' => $code);
   }
}
?>