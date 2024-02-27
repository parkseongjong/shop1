<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

class G5_PACK_MINIFIER {
    // Hook 포함 클래스 작성 요령
    // https://github.com/Josantonius/PHP-Hook/blob/master/tests/Example.php
    /**
     * Class instance.
     */
    
    public $folder_name = 'cache';

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public static function singletonMethod()
    {
        return self::getInstance();
    }

    public function __construct() {
        $this->minify_manage_setup();
        $this->add_hooks();
    }
    
    public function minify_manage_setup(){
        spl_autoload_register(function($className) {
            if (substr($className, 0, 14) === 'MatthiasMullie') {
                $filename = str_replace('\\', DIRECTORY_SEPARATOR, trim($className, '\\')) . '.php';
                require_once $filename;
            }
        });
    }

    public function add_hooks(){
        add_event('admin_common', array($this, 'admin_minify_css_check') );
        add_event('adm_cache_file_delete_before', array($this, 'add_admin_cache_delete_css') );
        add_replace('head_css_url', array($this, 'minify_theme_css'), 10, 2);
        add_replace('html_process_css_files', array($this, 'minify_skin_css'), 10, 1);

        add_replace('html_process_script_files', array($this, 'minify_skin_js'), 10, 1);
    }
    
    public function minify_theme_js($theme_js_url, $theme_url){

        global $config;
        $is_theme = ($config['cf_theme'] && stripos($theme_js_url, '/'.G5_THEME_DIR.'/'.$config['cf_theme']) !== false) ? 1 : 0;

        $g5_view_path = $is_theme ? G5_THEME_PATH : G5_PATH;

        $tmp_js_url = preg_replace('/\?.*/', '', $theme_js_url);
        $js_file_path = str_replace($theme_url, $g5_view_path , $tmp_js_url);
        
        if( !file_exists($js_file_path) ){
            return $theme_js_url;
        }

        $stat = stat($js_file_path);
        
        $tmp_js_url = preg_replace('/(.js)$/i', '', str_replace($theme_url, '', $tmp_js_url));

        $cache_file_name = preg_replace('/(^_)([a-z0-9])/i', '$2', preg_replace('/[^0-9a-z_]/i', '', str_replace(array('/', '\\', '//'), '_', $tmp_js_url))).$stat['mtime'].'.js';

        $cache_file_path = G5_DATA_PATH.'/'.$this->folder_name.'/'.$cache_file_name;
        
        if( file_exists($cache_file_path) ){

            $theme_js_url = G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name;

        } else {
            $minifier = new MatthiasMullie\Minify\JS($js_file_path);
            $save_data = $minifier->minify($cache_file_path);

            if ( $save_data ){
                $theme_js_url = G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name;
            }
        }

        return $theme_js_url;
    }

    public function minify_skin_js($jss){
        
        $return_jss = $minify_jss = $minify_files = $mtimes = array();
        
        $minifier = null;
        
        foreach((array) $jss as $js){
            if(!isset($js[1]) || !trim($js[1])){
                continue;
            }

            $url = preg_match('~(?<=src=")[^"]+\.js~', $js[1], $match);

            if( isset($match[0]) && $match[0] ){

                if( G5_SKIN_FILES_CASE ){
                    $theme_url = ($config['cf_theme'] && stripos($match[0], '/'.G5_THEME_DIR.'/'.$config['cf_theme']) !== false) ? G5_THEME_URL : G5_URL;
                    $return_jss[] = array(0=>0, 1=>'<script src="'.$this->minify_theme_js($match[0], $theme_url).'"></script>');
                } else {
                    
                    $tmp_js_url = preg_replace('/\?.*/', '', $match[0]);
                    $js_file_path = str_replace(G5_URL, G5_PATH , $tmp_js_url);
                    
                    if( stripos($tmp_js_url, G5_URL) !== false && file_exists($js_file_path) ){
                        $stat = stat($js_file_path);
                        
                        $mtimes[] = $stat['mtime'];
                        $minify_files[] = $js_file_path;
                    } else {
                        $return_jss[] = $js;
                    }
                }
                
            } else {
                $return_jss[] = $js;
            }
        }

        if( $minify_files ){
            $max_mtiles = max($mtimes);

            $cache_file_name = 'conbined_'.md5(implode('', $minify_files)).'_'.$max_mtiles.'.js';
            $cache_file_path = G5_DATA_PATH.'/'.$this->folder_name.'/'.$cache_file_name;
            
            if( file_exists($cache_file_path) ){
                
                $minify_jss[] = array(0=>0, 1=>'<script src="'.G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name.'"></script>');

            } else {
                
                foreach((array) $minify_files as $js_file_path ){

                    if( empty($js_file_path) ) continue;

                    if( $minifier === null ){
                        $minifier = new MatthiasMullie\Minify\JS($js_file_path);
                    } else {
                        $minifier->add($js_file_path);
                    }

                }
                
                if( $minifier !== null ){
                    $save_data = $minifier->minify($cache_file_path);

                    if ( $save_data ){
                        $minify_jss[] = array(0=>0, 1=>'<script src="'.G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name.'"></script>');
                    }
                }
            }

            $return_jss = $minify_jss ? array_merge($minify_jss, $return_jss) : $return_jss;
        }

        return $return_jss ? $return_jss : $jss;
    }

    public function minify_skin_css($links){
        
        global $config;

        $return_links = $minify_links = $minify_files = $mtimes = array();
        
        $minifier = null;

        foreach((array) $links as $link){
            if(!isset($link[1]) || !trim($link[1])){
                continue;
            }

            $url = preg_match('~(?<=href=")[^"]+\.css~', $link[1], $match);

            if( isset($match[0]) && $match[0] ){
                
                if( G5_SKIN_FILES_CASE ){
                    $theme_url = ($config['cf_theme'] && stripos($match[0], '/'.G5_THEME_DIR.'/'.$config['cf_theme']) !== false) ? G5_THEME_URL : G5_URL;
                    $return_links[] = array(0=>0, 1=>'<link rel="stylesheet" href="'.$this->minify_theme_css($match[0], $theme_url).'">');
                } else {
                    
                    $tmp_theme_css_url = preg_replace('/\?.*/', '', $match[0]);
                    $css_file_path = str_replace(G5_URL, G5_PATH , $tmp_theme_css_url);
                    
                    if( stripos($tmp_theme_css_url, G5_URL) !== false && file_exists($css_file_path) ){
                        $stat = stat($css_file_path);
                        
                        $mtimes[] = $stat['mtime'];
                        $minify_files[] = $css_file_path;
                    }  else {
                        $return_links[] = $link;
                    }
                }
                
            } else {
                $return_links[] = $link;
            }
        }

        if( $minify_files ){
            $max_mtiles = max($mtimes);

            $cache_file_name = 'conbined_'.md5(implode('', $minify_files)).'_'.$max_mtiles.'.css';
            $cache_file_path = G5_DATA_PATH.'/'.$this->folder_name.'/'.$cache_file_name;

            if( file_exists($cache_file_path) ){

                $minify_links[] = array(0=>0, 1=>'<link rel="stylesheet" href="'.G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name.'">');

            } else {
                
                foreach((array) $minify_files as $css_file_path ){

                    if( empty($css_file_path) ) continue;

                    if( $minifier === null ){
                        $minifier = new MatthiasMullie\Minify\CSS($css_file_path);
                    } else {
                        $minifier->add($css_file_path);
                    }

                }
                
                if( $minifier !== null ){
                    $minifier->setImportExtensions(array());
                    $save_data = $minifier->minify($cache_file_path);

                    if ( $save_data ){
                        $minify_links[] = array(0=>0, 1=>'<link rel="stylesheet" href="'.G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name.'">');
                    }
                }
            }

            $return_links = $minify_links ? array_merge($minify_links, $return_links) : $return_links;
        }

        return $return_links ? $return_links : $links;
    }

    public function minify_theme_css($theme_css_url, $theme_url){

        global $config;
        $is_theme = ($config['cf_theme'] && stripos($theme_css_url, '/'.G5_THEME_DIR.'/'.$config['cf_theme']) !== false) ? 1 : 0;

        $g5_view_path = $is_theme ? G5_THEME_PATH : G5_PATH;
        $g5_theme_name = $is_theme ? $config['cf_theme'] : 'default';

        $tmp_theme_css_url = preg_replace('/\?.*/', '', $theme_css_url);
        $css_file_path = str_replace($theme_url, $g5_view_path , $tmp_theme_css_url);

        if( !file_exists($css_file_path) ){
            return $theme_css_url;
        }

        $stat = stat($css_file_path);
        
        $tmp_theme_css_url = preg_replace('/(.css)$/i', '', str_replace($theme_url, '', $tmp_theme_css_url));
        $cache_file_name = $g5_theme_name.preg_replace('/[^0-9a-z_]/i', '', str_replace(array('/', '\\', '//'), '_', $tmp_theme_css_url)).$stat['mtime'].'.css';

        $cache_file_path = G5_DATA_PATH.'/'.$this->folder_name.'/'.$cache_file_name;

        if( file_exists($cache_file_path) ){

            $theme_css_url = G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name;

        } else {
            $minifier = new MatthiasMullie\Minify\CSS($css_file_path);
            
            $minifier->setImportExtensions(array());
            $save_data = $minifier->minify($cache_file_path);

            if ( $save_data ){
                $theme_css_url = G5_DATA_URL.'/'.$this->folder_name.'/'.$cache_file_name;
            }
        }

        return $theme_css_url;
    }

    public function admin_minify_css_check(){
        $admin_cookie_time = get_cookie('admin_visit_time1');

        if( ! $admin_cookie_time ){

            $files = glob(G5_DATA_PATH.'/'.$this->folder_name.'/*.css');
            $js_files = glob(G5_DATA_PATH.'/'.$this->folder_name.'/*.js');
            
            $files = array_merge($files, $js_files);

            if (is_array($files)) {
                foreach ($files as $cache_file) {

                    if( 10 < (time() - filemtime($cache_file)) ){    // Delete files older than 24 hours
                        @unlink($cache_file);
                    }
                }
            }

            set_cookie('admin_visit_time1', G5_SERVER_TIME, 10800);   // Check every 3 hours
        }
    }

    public function add_admin_cache_delete_css(){
        $files = glob(G5_DATA_PATH.'/'.$this->folder_name.'/*.css');
        $js_files = glob(G5_DATA_PATH.'/'.$this->folder_name.'/*.js');
        
        $files = array_merge($files, $js_files);

        if (is_array($files)) {
            foreach ($files as $cache_file) {
                $cnt++;
                unlink($cache_file);
                //echo '<li>'.$cache_file.'</li>'.PHP_EOL;

                flush();

                if ($cnt%10==0) {
                    //echo PHP_EOL;
                }
            }
        }
    }

}
?>