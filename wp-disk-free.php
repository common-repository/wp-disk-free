<?php
/**
 * Plugin Name: WP Disk Free
 * Description: Plugin used to check how much free space is available on the disk/partition used to host a Wordpress installation
 * Version: 0.2.3
 * Author: Davide Airaghi
 * Author URI: http://www.airaghi.net
 * License: GPLv2 or later
 */
 
defined('ABSPATH') or die("No script kiddies please!");

class WP_Disk_Free {
    
    private $lang         = array();
    private $is_multisite = false;
    
    public function __construct() {
        // language
        require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages.php' );
        $lang = get_bloginfo('language','raw');
        if (!isset($airaghi_wpdf_lang[$lang])) {
            $lang = 'en-US';
        }
        $this->lang = $airaghi_wpdf_lang[$lang];
        $this->is_multisite = is_multisite();
    }

    public function summary() {
        echo '<b>'.htmlentities($this->lang['TEXT_SPACE_FREE']) . ': ' . 
             round(disk_free_space(dirname(__FILE__))/(1024*1024),2) . '/' . 
             round(disk_total_space(dirname(__FILE__))/(1024*1024),2) . ' MB' .
             '</b><br><br>';
    }

    public function settings() {
        add_settings_section('wpdf_section','',array($this,'summary'),'wp-disk-free-page');
        add_settings_field('wpdf_quota',$this->lang['TEXT_QUOTA'],array($this,'quota_field'),'wp-disk-free-page','wpdf_section');
        register_setting('wp-disk-free-page','wpdf_quota');
        add_settings_field('wpdf_email',$this->lang['TEXT_EMAIL'],array($this,'email_field'),'wp-disk-free-page','wpdf_section');
        register_setting('wp-disk-free-page','wpdf_email');
        //register_setting('wp-disk-free-page','wpdf_notification');
    }

    public function admin() {
    
        add_submenu_page( 
            ( $this->is_multisite ? 'settings.php' : 'options-general.php'),
            $this->lang['ADMIN_PAGE_TITLE'] , $this->lang['ADMIN_MENU_TITLE'], 
            'manage_options', 
            'wp-disk-free-page', 
            array($this,'admin_page')
        );
        add_action( 'admin_init', array($this,'settings') );
        if ($this->is_multisite) {
            if (get_site_option('wpdf_quota','')=='') {
                update_site_option('wpdf_quota','1');
            }
            if (get_site_option('wpdf_email','')=='') {
                update_site_option('wpdf_email','');
            }
            if (get_site_option('wpdf_notification','')=='') {
                update_site_option('wpdf_notification','');
            }
        } else {
            if (get_option('wpdf_quota','')=='') {
                update_option('wpdf_quota','1');
            }
            if (get_option('wpdf_email','')=='') {
                update_option('wpdf_email','');
            }
            if (get_option('wpdf_notification','')=='') {
                update_option('wpdf_notification','');
            }
        }
    }
    
    public function admin_page() {
        $msg = '';
        if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
        if (isset($_POST['do_save']) && $_POST['do_save']==='1') {
            $email = strval(filter_var($_POST['wpdf_email'], FILTER_VALIDATE_EMAIL));
            $quota = intval($_POST['wpdf_quota']);
            if ($quota < 1) { $quota = 1; }
            if ($this->is_multisite) {
            update_site_option('wpdf_quota',$quota);
            update_site_option('wpdf_email',$email);
            update_site_option('wpdf_notification','');
            } else {
            update_option('wpdf_quota',$quota);
            update_option('wpdf_email',$email);
            update_option('wpdf_notification','');
            }
            $msg = htmlentities($this->lang['SAVEOK']);
        }
        $page = $this->is_multisite ? 'settings.php' : 'options-general.php';
        ?>
        <div class="wrap">
        <h2><?php echo htmlentities($this->lang['ADMIN_PAGE_TITLE']);?></h2>
        <?php if ($msg!='') { ?><p><b><?php echo $msg; ?></b></p><?php } ?>
        <form  method="post" action="<?php echo $page;?>?page=wp-disk-free-page" name="wpdf_form">
            <input type="hidden" name="do_save" value="1" />
            <?php settings_fields( 'wp-disk-free-page' ); ?>
            <?php do_settings_sections( 'wp-disk-free-page' ); ?>
            <p><?php echo $this->lang['TEXT_NOTES'];?></p>
            <?php submit_button(); ?>
        </form>
        </div>
        <?php
    }
    
    public function email_field() {
        $option = $this->is_multisite ? get_site_option('wpdf_email','') : get_option('wpdf_email','');
        echo '<input type="email" name="wpdf_email" id="wpdf_email" value="'.esc_attr($option).'" required>';
    }
    
    public function quota_field() {
        $option = $this->is_multisite ? get_site_option('wpdf_quota','') : get_option('wpdf_quota','1');
        echo '<input type="number" name="wpdf_quota" id="wpdf_quota" value="'.esc_attr($option).'" required min="1" >';
    }
    
    public function checkFreeSpace() {
        if ($this->is_multisite) {
        $quota = get_site_option('wpdf_quota','1');
        $email = get_site_option('wpdf_email','');
        $last  = get_site_option('wpdf_notification','');
        } else {
        $quota = get_option('wpdf_quota','1');
        $email = get_option('wpdf_email','');
        $last  = get_option('wpdf_notification','');
        }
        $time  = time();
        $quota = intval($quota) * 1024 * 1024;
        $free  = disk_free_space(dirname(__FILE__));
        $last  = intval($last);
        if ($free <= $quota && $last=='' && filter_var($email,FILTER_VALIDATE_EMAIL)) {
        // we are in danger and we have someone to notify ... send notification and set time
        if ($this->is_multisite) {
            update_site_option('wpdf_notification',$time);
        } else {
            update_option('wpdf_notification',$time);
        }
        $quota = round($quota/(1024*1024),2);
        $free  = round($free/(1024*1024),2);
        $subject  = $this->lang['EMAIL_SUBJECT'].' '.get_bloginfo('wpurl');
        $message  = $this->lang['EMAIL_MESSAGE'].' '.$quota.' ('.$free.') MB';
        // echo $subject.' '.$message;die;
        wp_mail($email,$subject,$message);
        } elseif ($free > $quota && $last != '') {
            // we are no more in danger ... reset notification time
          if ($this->is_multisite) {
            update_site_option('wpdf_notification','');
          } else {
            update_option('wpdf_notification','');
          }
          if (filter_var($email,FILTER_VALIDATE_EMAIL)) {
            $quota = round($quota/(1024*1024),2);
            $free  = round($free/(1024*1024),2);
            $subject  = $this->lang['EMAIL_OK_SUBJECT'].' '.get_bloginfo('wpurl');
            $message  = $this->lang['EMAIL_OK_MESSAGE'].' '.$quota.' MB';
            // echo $subject.' '.$message;die;
            wp_mail($email,$subject,$message);
          }
        }
    }

}

$airaghi_wpdf_obj = new \WP_Disk_Free();

$airaghi_wpdf_is_multisite = is_multisite();

if ($airaghi_wpdf_is_multisite) {
    add_action('network_admin_menu', array($airaghi_wpdf_obj,'admin'));
} else {
    add_action('admin_menu', array($airaghi_wpdf_obj,'admin'));
}

add_action('wp_loaded',array($airaghi_wpdf_obj,'checkFreeSpace'));

