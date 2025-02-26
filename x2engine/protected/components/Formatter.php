<?php
/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2014 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

/**
 * Consolidated class for common string formatting and parsing functions.
 *
 * @package application.components
 */
class Formatter {

    /**
     * Return a value cast after a named PHP type
     * @param type $value
     * @param type $type
     * @return type
     */
    public static function typeCast($value,$type) {
        switch($type) {
            case 'bool':
            case 'boolean':
                return (boolean) $value;
            case 'double':
                return (double) $value;
            case 'int':
            case 'integer':
                return (integer) $value;
            default:
                return (string) $value;
        }
    }
    
    /**
     * Converts a record's Description or Background Info to deal with the discrepancy
     * between MySQL/PHP line breaks and HTML line breaks.
     */
    public static function convertLineBreaks($text, $allowDouble = true, $allowUnlimited = false){

        if(preg_match("/<br \/>/", $text)){
            $text = preg_replace("/<\/b>/", "</b><br />", $text, 1);
            $text = preg_replace("/\s<b>/", "<br /><b>", $text, 1);
            return $text;
        }

        $text = mb_ereg_replace("\r\n", "\n", $text);  //convert microsoft's stupid CRLF to just LF

        if(!$allowUnlimited) {
            // replaces 2 or more CR/LF chars with just 2
            $text = mb_ereg_replace("[\r\n]{3,}", "\n\n", $text); 
        }

        if($allowDouble) {
            // replaces all remaining CR/LF chars with <br />
            $text = mb_ereg_replace("[\r\n]", '<br />', $text); 
        } else {
            $text = mb_ereg_replace("[\r\n]+", '<br />', $text);
        }

        return $text;
    }

    /**
     * Parses text for short codes and returns an associative array of them.
     *
     * @param string $value The value to parse
     * @param X2Model $model The model on which to operate with attribute replacement
     * @param bool $renderFlag The render flag to pass to {@link X2Model::getAttribute()}
     * @param bool $makeLinks If the render flag is set, determines whether to render attributes
     *  as links
     */
    public static function getReplacementTokens($value,$model,$renderFlag,$makeLinks) {
        // Pattern will match {attr}, {attr1.attr2}, {attr1.attr2.attr3}, etc.
        $codes = array();
        // Types of each value for the short codes:
        $codeTypes = array();
        $fieldTypes = array_map(function($f){return $f['phpType'];},Fields::getFieldTypes());
        $fields = $model->getFields(true);
        preg_match_all('/{([a-z]\w*)(\.[a-z]\w*)*?}/i', trim($value), $matches); // check for variables
        if(!empty($matches[0])){
            foreach($matches[0] as $match){
                $match = substr($match, 1, -1); // Remove the "{" and "}" characters
                $attr = $match;
                if(strpos($match, '.') !== false){ // We found a link attribute (i.e. {company.name})
                    $newModel = $model;
                    $pieces = explode('.',$match);
                    $first = array_shift($pieces);
                    $tmpModel = Formatter::parseShortCode($first, $newModel); // First check if the first piece is part of a short code, like "user"
                    if(isset($tmpModel) && $tmpModel instanceof CActiveRecord){
                        $newModel = $tmpModel; // If we got a model from our short code, use that
                        $attr = implode('.',$pieces); // Also, set the attribute to have the first item removed.
                    }
                    $codes['{'.$match.'}'] = $newModel->getAttribute(
                        $attr, $renderFlag, $makeLinks);
                        $codeTypes[$match] = isset($fields[$attr])
                                && isset($fieldTypes[$fields[$attr]->type])
                                ? $fieldTypes[$fields[$match]->type]
                                : 'string';
                }else{ // Standard attribute
                    if(isset($params[$match])){ // First check if we provided a value for this attribute
                        $codes['{'.$match.'}'] = $params[$match];
                        $codeTypes[$match] = gettype($params[$match]);
                    }elseif($model->hasAttribute($match)){ // Next ensure the attribute exists on the model
                        $codes['{'.$match.'}'] = $model->getAttribute(
                            $match, $renderFlag, $makeLinks);
                        $codeTypes[$match] = isset($fields[$match]) 
                                && isset($fieldTypes[$fields[$match]->type])
                                ? $fieldTypes[$fields[$match]->type]
                                : 'string';
                        
                    }else{ // Finally, try to parse it as a short code if nothing else worked
                        $shortCodeValue = Formatter::parseShortCode($match, $model);
                        if(!is_null($shortCodeValue)){
                            $codes['{'.$match.'}'] = $shortCodeValue;
                            $codeTypes[$match] = gettype($shortCodeValue);
                        }
                    }
                }
            }
        }

        // ensure that value of replacement token is of an acceptable type
        foreach ($codes as $name => $val) {
            if(!in_array(gettype ($val),array("boolean","integer","double","string","NULL"))) {
                // remove invalid value
                unset ($codes[$name]);
            } elseif(isset($codeTypes[$name])) {
                $codes[$name] = self::typeCast($val, $codeTypes[$name]);
            }
        }
        return $codes;
    }

    /*     * * Date Format Functions ** */

    /**
     * A function to convert a timestamp into a string stated how long ago an object
     * was created.
     *
     * @param $timestamp The time that the object was posted.
     * @return String How long ago the object was posted.
     */
    public static function timestampAge($timestamp){
        $age = time() - strtotime($timestamp);
        //return $age;
        if($age < 60) {
            // less than 1 min ago
            return Yii::t('app', 'Just now'); 
        }
        if($age < 3600) {
            // minutes (less than an hour ago)
            return Yii::t('app', '{n} minutes ago', array('{n}' => floor($age / 60))); 
        }
        if($age < 86400) {
            // hours (less than a day ago)
            return Yii::t('app', '{n} hours ago', array('{n}' => floor($age / 3600))); 
        }
        
        // days (more than a day ago)
        return Yii::t('app', '{n} days ago', array('{n}' => floor($age / 86400))); 
    }

    /**
     * Format a date to be long (September 25, 2011)
     * @param integer $timestamp Unix time stamp
     */
    public static function formatLongDate($timestamp){
        if(empty($timestamp)) {
            return '';
        } else {
            return Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('long'), $timestamp);
        }
    }

    /**
     * Format dates for the date picker.
     * @param string $width A length keyword, i.e. "medium"
     * @return string
     */
    public static function formatDatePicker($width = ''){
        if(Yii::app()->locale->getId() == 'en'){
            if($width == 'medium')
                return "M d, yy";
            else
                return "MM d, yy";
        } else{
            // translate Yii date format to jquery
            $format = Yii::app()->locale->getDateFormat('medium'); 
            $format = str_replace('yy', 'y', $format);
            $format = str_replace('MM', 'mm', $format);
            $format = str_replace('M', 'm', $format);
            return $format;
        }
    }

    /**
     * Formats a time interval.
     *
     * @param integer $start Beginning of the interval
     * @param integer $duration Length of the interval
     */
    public static function formatTimeInterval($start,$end,$style=null) {
        $duration = $end-$start;
        $decHours = $duration/3600;
        $intHours = (int) $decHours;
        $intMinutes = (int) (($duration % 3600) / 60);
        if(empty($style)){
            // Default format
            $style = Yii::t('app', '{decHours} hours, starting {start}');
        }
        // Custom format
        return strtr($style, array(
                    '{decHours}' => sprintf('%0.2f', $decHours),
                    '{hoursColMinutes}' => sprintf('%d:%d',$intHours,$intMinutes),
                    '{hours}' => $intHours,
                    '{minutes}' => $intMinutes,
                    '{hoursMinutes}' => $intHours ? 
                        sprintf('%d %s %d %s', $intHours, Yii::t('app', 'hours'), 
                            $intMinutes, Yii::t('app', 'minutes')) : 
                        sprintf('%d %s', $intMinutes, Yii::t('app', 'minutes')),
                    '{quarterDecHours}' => sprintf(
                        '%0.2f '.Yii::t('app', 'hours'), 
                        round($duration / 900.0) * 0.25),
                    '{start}' => self::formatCompleteDate($start),
                    '{end}' => self::formatCompleteDate($end)
                ));
    }

    /**
     * Formats time for the time picker.
     *
     * @param string $width
     * @return string
     */
    public static function formatTimePicker($width = '',$seconds = false){
        /*if(Yii::app()->locale->getLanguageId(Yii::app()->locale->getId()) == 'zh'){
            return "HH:mm".($seconds?':ss':'');
        }*/
        $format = Yii::app()->locale->getTimeFormat($seconds?'medium':'short');

        // jquery specifies hours/minutes as hh/mm instead of HH//MM
        //$format = strtolower($format); 

        // yii and jquery have different format to specify am/pm
        $format = str_replace('a', 'TT', $format); 
        return $format;
    }

    /**
     * Check if am/pm is being used in this locale.
     */
    public static function formatAMPM(){
        if(strstr(Yii::app()->locale->getTimeFormat(), "a") === false) {
            return false;
        } /*else if(Yii::app()->locale->getLanguageId(Yii::app()->locale->getId()) == 'zh') {
            // 24 hour format for china
            return false;
        } */else {
            return true;
        }
    }

    /*     * * Date Time Format Functions ** */

    public static function formatFeedTimestamp($timestamp){
        if (Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium'), $timestamp) == 
            Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium'), time())){

            $str = Yii::t('app', 'Today').' '.
                Yii::app()->dateFormatter->format(
                    Yii::app()->locale->getTimeFormat('short'), $timestamp);
        }else{
            $str = 
                Yii::app()->dateFormatter->format(
                    Yii::app()->locale->getDateFormat('medium'), $timestamp).
                " ".
                Yii::app()->dateFormatter->format(
                    Yii::app()->locale->getTimeFormat('short'), $timestamp);
        }
        return $str;
    }

    /**
     * Returns a formatted string for the end of the day.
     * @param integer $timestamp
     * @return string
     */
    public static function formatDateEndOfDay($timestamp){
        if(empty($timestamp)) {
            return '';
        } else if(Yii::app()->locale->getId() == 'en') {
            return Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium').' '.
                    Yii::app()->locale->getTimeFormat('short'), 
                strtotime("tomorrow", $timestamp) - 60);
        } /*else if(Yii::app()->locale->getLanguageId(Yii::app()->locale->getId()) == 'zh') {
            return Yii::app()->dateFormatter->format(Yii::app()->locale->getDateFormat('short').
                ' '.'HH:mm', strtotime("tomorrow", $timestamp) - 60);
        } */else {
            return Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium').' '.
                    Yii::app()->locale->getTimeFormat('short'), 
                strtotime("tomorrow", $timestamp) - 60);
        }
    }

    /**
     * Cuts string short.
     * @param string $str String to be truncated.
     * @param integer $length Maximum length of the string
     * @param bool $encode Encode HTML special characters if true
     * @return string
     */
    public static function truncateText($str, $length = 30, $encode=false){

        if(mb_strlen($str, 'UTF-8') > $length - 3){
            if($length < 3)
                $str = '';
            else
                $str = trim(mb_substr($str, 0, $length - 3, 'UTF-8'));
            $str .= '...';
        }
        return $encode?CHtml::encode($str):$str;
    }

    /**
     * Converts CamelCased words into first-letter-capitalized, spaced words.
     * @param type $str
     * @return type
     */
    public static function deCamelCase($str){
        $str = preg_replace("/(([a-z])([A-Z])|([A-Z])([A-Z][a-z]))/", "\\2\\4 \\3\\5", $str);
        return ucfirst($str);
    }

    /**
     * Locale-dependent date string formatting.
     * @param integer $date Timestamp
     * @param string $width A length keyword, i.e. "medium"
     * @return string
     */
    public static function formatDate($date, $width = 'long', $informal = true){
        if(empty($date)){
            return '';
        }
        if(!is_numeric($date))
            $date = strtotime($date); // make sure $date is a proper timestamp

        $now = getDate();   // generate date arrays
        $due = getDate($date); // for calculations
        //$date = mktime(23,59,59,$due['mon'],$due['mday'],$due['year']);	// give them until 11:59 PM to finish the action
        //$due = getDate($date);
        $ret = '';

        if($informal && $due['year'] == $now['year']){  // is the due date this year?
            if($due['yday'] == $now['yday'] && $width == 'long') { // is the due date today?
                $ret = Yii::t('app', 'Today');
            } else if($due['yday'] == $now['yday'] + 1 && $width == 'long') { // is it tomorrow? 
                $ret = Yii::t('app', 'Tomorrow');
            } else {
                $ret = Yii::app()->dateFormatter->format(
                    Yii::app()->locale->getDateFormat($width), $date); // any other day this year
            }
        } else{
            $ret = Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat($width), $date); // due date is after this year
        }
        return $ret;
    }

    public static function formatTime($date, $width = 'medium'){
        return Yii::app()->dateFormatter->formatDateTime($date, null, $width);
    }

    public static function formatDueDate($date){
        if(!is_numeric($date))
            $date = strtotime($date); // make sure $date is a proper timestamp
        return date('l', $date)." ".Yii::app()->dateFormatter->formatDateTime($date, 'long', null).
            " - ".Yii::app()->dateFormatter->formatDateTime($date, null, 'short');
    }

    public static function formatCompleteDate($date){
        return Yii::app()->dateFormatter->formatDateTime($date, 'long');
    }

    /**
     * Returns a formatted string for the date.
     *
     * @param integer $timestamp
     * @return string
     */
    public static function formatLongDateTime($timestamp){
        if(empty($timestamp))
            return '';
        else
            return Yii::app()->dateFormatter->formatDateTime($timestamp, 'long', 'medium');
    }

    /**
     * Formats the date and time for a given timestamp.
     * @param type $timestamp
     * @return string
     */
    public static function formatDateTime($timestamp){
        if(empty($timestamp)){
            return '';
        }else if(Yii::app()->locale->getId() == 'en'){

            return Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium').' '.
                    Yii::app()->locale->getTimeFormat('short'), 
                $timestamp);
        }/*else if(Yii::app()->locale->getLanguageId(Yii::app()->locale->getId()) == 'zh') {
            return Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium').' '.'HH:mm', $timestamp);
        } */else {
            return Yii::app()->dateFormatter->format(
                Yii::app()->locale->getDateFormat('medium').' '.
                    Yii::app()->locale->getTimeFormat('short'), 
                $timestamp);
        }
    }

    /**
     * Obtain a Unix-style integer timestamp for a date format.
     *
     * @param string $date
     * @return mixed integer or false if parsing fails
     */
    public static function parseDate($date){
        if(Yii::app()->locale->getId() == 'en')
            return strtotime($date);
        else
            return CDateTimeParser::parse($date, Yii::app()->locale->getDateFormat('medium'));
    }

    /**
     * Parses both date and time into a Unix-style integer timestamp.
     * @param string $date
     * @return integer
     */
    public static function parseDateTime($date,$dateLength = 'medium', $timeLength = 'short'){
        if($date === null){
            return null;
        }elseif(is_numeric($date)){
            return $date;
        }elseif(Yii::app()->locale->getId() == 'en'){
            return strtotime($date);
        } else {
            /*AuxLib::debugLogR (Yii::app()->locale->getDateFormat($dateLength));
            //AuxLib::debugLogR (Yii::app()->locale->getTimeFormat($timeLength));
            //AuxLib::debugLogR ($date);*/
            return CDateTimeParser::parse(
                $date, 
                Yii::app()->locale->getDateFormat($dateLength).' '.
                Yii::app()->locale->getTimeFormat($timeLength));
        }
    }

    /**
     * Convert currency to the proper format
     *
     * @param String $str The currency string
     * @param Boolean $keepCents Whether or not to keep the cents
     * @return String $str The modified currency string.
     */
    public static function parseCurrency($str, $keepCents){

        $cents = '';
        if($keepCents){
            $str = mb_ereg_match('[\.,]([0-9]{2})$', $str, $matches); // get cents
            $cents = $matches[1];
        }
        $str = mb_ereg_replace('[\.,][0-9]{2}$', '', $str); // remove cents
        $str = mb_ereg_replace('[^0-9]', '', $str);  //remove all non-numbers

        if(!empty($cents))
            $str .= ".$cents";

        return $str;
    }

    /**
     * Returns the body of an email without any HTML markup.
     *
     * This function will strip out email header tags, opened email tags, and all
     * HTML markup present in an Email type action so that the Action link can be
     * properly displayed without looking terrible
     * @param String $str Input string to be formatted
     * @return String The formatted string
     */
    public static function parseEmail($str){
        $str = preg_replace('/<\!--BeginOpenedEmail-->(.*?)<\!--EndOpenedEmail--!>/s', '', $str);
        $str = preg_replace('/<\!--BeginActionHeader-->(.*?)<\!--EndActionHeader--!>/s', '', $str);
        $str = strip_tags($str);
        return $str;
    }

    /**
     * Replace variables in dynamic text blocks.
     *
     * This function takes text with dynamic attributes such as {firstName} or
     * {company.symbol} or {time} and replaces them with appropriate values in
     * the text. It is possible to directly access attributes of the model,
     * attributes of related models to the model, or "short codes" which are
     * fixed variables, so to speak. That is the variable {time} corresponds
     * to a defined piece of code which returns the current time.
     *
     * @param String $value The text which should be searched for dynamic attributes.
     * @param X2Model $model The model which attributes should be taken from
     * @param String $type Optional, the type of content we're expecting to get. This
     * can determine if we should render what comes back via the {@link X2Model::renderAttribute}
     * function or just display what we get as is.
     * @param Array $params Optional extra parameters which may include default values
     * for the attributes in question.
     * @param bool $renderFlag (optional) If true, overrides use of $type parameter to determine
     *  if attribute should be rendered
     * @param bool $makeLinks If the render flag is set, determines whether to render attributes
     *  as links
     * @return String A modified version of $value with attributes replaced.
     */
    public static function replaceVariables(
        $value, $model, $type = '', $renderFlag=true, $makeLinks=true){

        $matches = array();
        if($renderFlag && ($type === '' || $type === 'text' || $type === 'richtext')){
            $renderFlag = true;
        }else{
            $renderFlag = false;
        }
        
        $shortCodeValues = self::getReplacementTokens($value,$model,$renderFlag,$makeLinks);

        return strtr($value,$shortCodeValues);
    }

    /**
     * Parses a "formula" for the flow.
     *
     * If the first character in a string value in X2Flow is the "=" character, it
     * will be treated as valid PHP code to be executed. This function uses {@link getSafeWords}
     * to determine a list of functions which the user can execute in the code,
     * and strip any which are not allowed. This should generally be used for
     * mathematical operations, like calculating dynamic date offsets.
     *
     * @param string $input The code to be executed
     * @param array $params Optional extra parameters, notably the Model triggering the flow
     * @return array An array with the first element true or false corresponding to
     *  whether execution succeeded, the second, the value returned by the formula.
     */
    public static function parseFormula($input,$params = array()){
        if(strpos($input,'=') !== 0) {
            return array(false,Yii::t('admin','Formula does not begin with "="'));
        }
        
        $formula = substr($input, 1); // Remove the "=" character from in front
        
        // If we find a model, relace any variables inside of our formula (i.e. {lastUpdated})
        if(isset($params['model'])){ 
            $replacementTokens = self::getReplacementTokens($formula, $params['model'], false, false);
        } else {
            $replacementTokens = $params;
        }
        
        // Run through all short codes and ensure they're proper PHP expressions
        // that correspond to their value, i.e. strings will become string
        // expressions, integers will become integers, etc.
        //
        // This step is VITALLY IMPORTANT to the security and stability of
        // X2Flow's formula parsing.
        foreach(array_keys($replacementTokens) as $token) {
            $type = gettype($replacementTokens[$token]);

            if(!in_array($type,array("boolean","integer","double","string","NULL"))) {
                // Safeguard against "array to string conversion" and "warning,
                // object of class X could not be converted to string" errors.
                // This case shouldn't happen and is not valid, so nothing
                // smarter need be done here than to simply set the replacement
                // value to its corresponding token.
                $replacementTokens[$token] = var_export($token,true);

            } else if ($type === 'string') {
                // Escape/convert values into valid PHP expressions
                $replacementTokens[$token] = var_export($replacementTokens[$token],true);
            }
        }

        // Prepare formula for eval:
        if(strpos($formula, ';') !== strlen($formula) - 1){
            // Eval requires a ";" at the end to execute properly
            $formula.=';';
        }
        if(strpos($formula, 'return ') !== 0){
            // Eval requries a "return" at the front.
            $formula = 'return '.$formula;
        }

        // Validity check: ensure the formula only consists of "safe" functions,
        // the existing variable tokens, spaces, and PHP operators:
        foreach(array_keys($replacementTokens) as $token) {
            $shortCodePatterns[] = preg_quote($token,'#');
        }
        $phpOper = '[\[\]()<>=!^|?:*+%/\-\.]|and |or |xor |\s'; // PHP operators
        $singleQuotedString = '\'[\w\s\.;:,()]*?\''; // Only simple strings currently supported
        $number = '[0-9]+(?:\.[0-9]+)?';
        $boolean = '(?:false|true)';
        $validPattern = '#^return (?:'
            .self::getSafeWords()
            .(empty($shortCodePatterns)?'':('|'.implode('|',$shortCodePatterns)))
            .'|'.$phpOper
            .'|'.$number
            .'|'.$boolean
            .'|'.$singleQuotedString.')*;$#i';
        if(!preg_match($validPattern,$formula)) {
            return array(false,Yii::t('admin','Input evaluates to an invalid formula: ').strtr($formula,$replacementTokens));
        }

        try{
            $retVal = @eval(strtr($formula,$replacementTokens));
        }catch(Exception $e){
            return array(false,Yii::t('admin','Evaluated statement encountered an exception: '.$e->getMessage()));
        }

        return array(true,$retVal);
    }

    /**
     * Parses a "short code" as a part of variable replacement.
     *
     * Short codes are defined in the file protected/components/x2flow/shortcodes.php
     * and are a list of manually defined pieces of code to be run in variable replacement.
     * Because they are stored in a protected directory, validation on allowed
     * functions is not performed, as it is the user's responsibility to edit this file.
     *
     * @param String $key The key of the short code to be used
     * @param X2Model $model The model having variables replaced, some short codes
     * use a model
     * @return mixed Returns the result of code evaluation if a short code
     * existed for the index $key, otherwise null
     */
    public static function parseShortCode($key, $model){
        $path = implode(DIRECTORY_SEPARATOR,
            array(Yii::app()->basePath,'components','x2flow','shortcodes.php'));
        if(file_exists($path)){
            $shortCodes = include($path);
            if(isset($shortCodes[$key])){
                return eval($shortCodes[$key]);
            }
        }
        return null;
    }

    /**
     * Returns a list of safe functions for formula parsing
     *
     * This function will generate a string to be inserted into the regex defined
     * in the {@link parseFormula} function, where each function not listed in the
     * $safeWords array here will be stripped from code execution.
     * @return String A string with each function listed as to be inserted into
     * a regular expression.
     */
    private static function getSafeWords(){
        $safeWords = array(
            'echo[ (]',
            'time[ (]',
        );
        return implode('|',$safeWords);
    }

    /**
     * If text is greater than limit, it gets truncated and suffixed with an ellipsis  
     * @param string $text
     * @param int $limit
     * @return string 
     */
    public static function trimText ($text, $limit = 150) {
        if(mb_strlen($text,'UTF-8') > $limit) {
            return mb_substr($text, 0,$limit - 3, 'UTF-8').'...';
        } else {
            return $text;
        }
    }

    /**
     * @param float|int $value 
     * @return string value formatted as currency using app-wide currency setting
     */
    public static function formatCurrency ($value) {
        return Yii::app()->locale->numberFormatter->formatCurrency (
            $value, Yii::app()->params->currency);
    }

}

?>
