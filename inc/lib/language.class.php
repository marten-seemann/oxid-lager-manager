<?php
/**
* provide multilanguage support in the Category Master, get OXID languages
*
* provide everything that is needed for handling internalization:
*
* - functions to get translation from the lang-files
* - functions to generate the javascript lang-files
* - functions to get the country associated with a language (needed for displaying the flag in a language selector)
* - query the OXID database to get the available OXID languages
*
* @author Marten Seemann <martenseemann@gmail.com>
* @package OXID Category Master
*/
class LanguageHelper {
  private $db;
  /**
  * base path of the category master
  *
  * needs to be set if lang files should be included from anywhere else than base (base: the directory where the index.php lies in)
  * @var string
  */
  private $path;
  /**
  * language code of the language
  * @var string
  */
  private $language;
  /**
  * language code of the default language
  * @var string
  */
  private $default_language;
  /**
  * language strings of the language
  * @var string[]
  */
  private $lang;
  /**
  * language strings of the default language
  * @var string[]
  */
  private $lang_default;
  /**
  * the names of languages in the desired language
  * @example "de" => "Deutsch"
  * @var string[]
  */
  private $language_names;
  /**
  * the names of languages in the default language
  * @example "de" => "German"
  * @var string[]
  */
  private $language_names_default;
  /**
  * language in which the OXID translations should be fetched
  * @var string
  */
  private $oxid_language;
  /**
  * number of a language as stored in the OXID database
  *
  * if one has to query <i>OXTITLE_1</i> to get the <i>OXTITLE</i> in the desired language, this variable must be set to 1
  * @var string
  */
  private $oxid_lang_number;
  /**
  * languages in which the OXID translations are available
  * @var string[]
  */
  private $oxid_languages;

  /**
  * Constructor
  *
  * loads the needed language files according to the specified language, as well as for the default language
  *
  * sets the internationalization settings via <i>setlocale</i>
  *
  * @param string $lang the language code of the language we want to have the translations of
  * @param string $path root path, needed to require the correct files
  * @param bool $javascript if set to <i>true</i>, only the javascript langstrings will be loaded. only needed for generating the javascript lang file
  */
  public function __construct($lang, $path = "", $javascript = false) {
    global $db;
    $this->db = &$db;
    $this->path = $path;
    $this->oxid_languages = false;
    $lang = preg_replace("/[^a-z]/","", $lang);
    // if the desired language is not found, terminate. check if language exists MUST be performed before constructing this object
    if(!in_array($lang, $this->availableLanguages())) {
      throw new Exception("Language $lang does not exist.");
    }
    $this->default_language = "en";
    $this->language = $lang;
    $js_ext = "";
    if($javascript) $js_ext=".js";
    // set locale, needed for time and number formatting based on the localization
    setlocale(LC_ALL, $this->getLocale());
    require($this->path."lang/lang.{$this->default_language}{$js_ext}.php");
    $this->lang_default = $lang;
    // unset($lang);
    require($this->path."lang/lang.{$this->language}{$js_ext}.php");
    $this->lang = $lang;
    if(is_object($this->db)) $this->db->setLanguage($this);
  }

  /**
  * get the language code for the actual language
  * @example "de"
  *
  * @return string the language code
  */
  public function getLanguageCode() {
    return $this->language;
  }

  /**
  * get all langstrings defined in the default language
  *
  * needed e.g. to generate the js lang file
  * @return $string[] all langstring in the default language
  */
  public function getDefaultLangKeys() {
    return array_keys($this->lang_default);
  }

  /**
  * get the language chosen for displaying data from the OXID database
  * @return string the language code
  */
  public function getOxidLanguage() {
    return $this->oxid_language;
  }

  /**
  * get the number of a language as stored in the OXID database
  *
  * if one has to query <i>OXTITLE_1</i> to get the <i>OXTITLE</i> in the desired language, this function will return 1
  * @return int the number of the language
  */

  public function getOxidLangNumber() {
    return $this->oxid_lang_number;
  }
  /**
  * get languages provided by OXID
  *
  * languages are determined from the OXID database by getting all views named <i>oxv_oxarticles_xx</i>, where <i>xx</i> is a language code. Those language codes are the language codes we can expect translations to be existent for.
  *
  * one can expect that there are translations for values like <i>OXITLE</i> etc. into exactly those languages
  * @return string[] language codes defined by OXID
  */
  public function getOxidLanguages() {
    if(!$this->oxid_languages) {
      $this->oxid_languages = array();
      $result = $this->db->query("SHOW FULL TABLES in `".$this->db->selected_db()."` WHERE Table_type='VIEW'");
      if($result->num_rows === null || $result->num_rows == 0) { // in older OXID installations (< 4.5.x), there do not exist any views from which we could read the language
        $this->oxid_languages = array();
        return $this->oxid_languages;
      }
      while($data = $result->fetch_array()) {
        $view_name = $data[0];
        if(strpos($view_name, "oxv_oxarticles_") === false) continue;
        $this->oxid_languages[] = str_replace("oxv_oxarticles_", "", $view_name);
      }
    }
    return $this->oxid_languages;
  }

  /**
  * set language in which the attributes from the OXID database will be fetched
  *
  * this function saves the desired language and calls detectOxidLanguageCode()
  *
  * @param string $language the language code of the desired language
  * @return void
  */
  public function setOxidLanguage($language) {
    if(!in_array($language, $this->getOxidLanguages())) return false;
    $language = substr(preg_replace("/[^a-z]/","",$language), 0, 2); // validate / shorten to make sure we are dealing with a ISO 639 language code
    $this->oxid_language = $language;
    $oxid_lang_number = array_flip($this->oxid_languages);
    $oxid_lang_number = $oxid_lang_number[$this->oxid_language];
    if($oxid_lang_number == 0) $this->oxid_lang_number = false;
    else $this->oxid_lang_number = $oxid_lang_number;
  }


  /**
  * get the name of the language
  *
  * the name of a language is needed for example when printing a language selector
  * @example "de" => "German" (if language is en)
  * @example "de" => "Deutsch" (if language is de)
  * @param string $lang_code the language code which should be translated into the language name
  * @return string the name of the language
  */
  public function getLanguageName($lang_code) {
    if(!isset($language_names)) $this->loadLanguageNames();
    if(array_key_exists($lang_code, $this->language_names)) return $this->language_names[$lang_code];
    else return $this->language_names_default[$lang_code];
  }

  /**
  * load the language names used by getLanguageNames()
  *
  * we separate this from the constructor because these files are not needed on every page
  */
  private function loadLanguageNames() {
    require($this->path."lang/languagenames.{$this->default_language}.php");
    $this->language_names_default = $languageNames;
    unset($languageNames);
    require($this->path."lang/languagenames.{$this->language}.php");
    $this->language_names = $languageNames;
  }

  /**
  * get the flag code corresponding to a language code
  *
  * needed for printing a language selector
  *
  * in most cases, there will be no difference between language code and flag code
  *
  * but for <i>en</i>, the flag code must either be <i>gb</i> or <i>us</i>
  * @param string $lang_code the language code
  * @return string the flag code
  */
  public function getFlagCode($lang_code) {
    if($lang_code == "en") return "gb";
    else return $lang_code;
  }

  /**
  * get all languages that are available for the UI
  *
  * @return string[] all languages that are available for the UI
  */
  public static function availableLanguages() {
    return array("de", "en");
  }

  /**
  * get the locale corresponding to the chosen language
  *
  * Note that this choice is not unique, e.g. "de" could be mapped to "de_DE" or "de_CH" etc.
  *
  * this function "solves" this ambiguity by choosing one locale, e.g. "de_DE" for "de"
  * @return the locale corresponding to the chosen language
  */
  private function getLocale() {
    $locales = array(
      "de" => "de_DE",
      "en" => "en_US"
      );
    return $locales[$this->language];
  }

  /**
  * get the translation for a string
  *
  * gets the translation for a string in the actual language. If it is not defined in that language, the translation from the default language will be returned
  * @param string $key the key of the langstring as defined in the langfiles <i>lang.xx.php</i> or so
  * @return string the corresponding translation
  */
  public function __get($key) {
    if(array_key_exists($key, $this->lang)) return $this->lang[$key];
    else return $this->lang_default[$key];
  }

  /**
  * format a number according to the chosen language
  *
  * @param float $number the number to be formatted
  * @return string the formatted number
  */
  public function formatPrice($number) {
    $price = round($number,2);
    $price = str_replace(",", ".", $price); //round gives a localized string
    if(strpos($price, ".") === false) $price.=".00";
    if(strlen(substr($price, strpos($price, "."))) == 2) $price.="0";
    if($this->language == "de") return str_replace(".", ",", $price);
    else return $price;
  }
}
?>
