<?php
/**
 * Language Configuration and Translation System
 */

// Available languages
define('AVAILABLE_LANGUAGES', ['en', 'fr', 'ar']);
define('DEFAULT_LANGUAGE', 'en');

// Get current language from session or cookie, default to English
function getCurrentLanguage() {
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    if (isset($_COOKIE['language'])) {
        $lang = $_COOKIE['language'];
        if (in_array($lang, AVAILABLE_LANGUAGES)) {
            $_SESSION['language'] = $lang;
            return $lang;
        }
    }
    
    return DEFAULT_LANGUAGE;
}

// Set language
function setLanguage($lang) {
    if (in_array($lang, AVAILABLE_LANGUAGES)) {
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/'); // 1 year
        return true;
    }
    return false;
}

// Load translations
function loadTranslations($lang) {
    static $cache = [];
    
    // Return cached translations if available
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    
    $translations = [];
    $lang_file = __DIR__ . "/../lang/{$lang}.php";
    
    if (file_exists($lang_file)) {
        // Include the file - variables defined in included files are in local scope
        include $lang_file;
        
        // After include, $translations should be available if it was defined in the file
        // Check if it exists and is an array
        if (isset($translations) && is_array($translations) && count($translations) > 0) {
            $cache[$lang] = $translations;
            return $translations;
        }
    }
    
    // Fallback to English if translation file not found or empty
    if ($lang !== 'en') {
        return loadTranslations('en');
    }
    
    return [];
}

// Translation function
function t($key, $params = []) {
    static $translations = null;
    static $current_lang = null;
    
    // Get current language
    $lang = getCurrentLanguage();
    
    // Reload translations if language changed, not loaded, or empty
    if ($translations === null || $current_lang !== $lang || empty($translations)) {
        $current_lang = $lang;
        $translations = loadTranslations($current_lang);
        
        // If still empty, try loading English as fallback
        if (empty($translations) && $lang !== 'en') {
            $translations = loadTranslations('en');
        }
    }
    
    // Get translation - return key if not found
    $translation = isset($translations[$key]) ? $translations[$key] : $key;
    
    // Replace parameters if provided
    if (!empty($params)) {
        foreach ($params as $param_key => $param_value) {
            $translation = str_replace(':' . $param_key, $param_value, $translation);
        }
    }
    
    return $translation;
}

// Get language name
function getLanguageName($code) {
    $names = [
        'en' => 'English',
        'fr' => 'Français',
        'ar' => 'العربية'
    ];
    return $names[$code] ?? $code;
}


