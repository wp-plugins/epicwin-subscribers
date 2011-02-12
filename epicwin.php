<?php
/*
Plugin Name: Epicwin Plugin
Plugin URI: http://www.epicwindesigns.com/projects
Description: This plugin allows your blog visitors to subscribe to your blog via email and receive notifications whenever you create a new post. You can control everything from the Wordpress admin.
Version: 1.4
Author: Antonio V Mendes De Araujo
Author URI: http://www.epicwindesigns.com
*/

/*  Copyright 2010 Antonio V. Mendes De Araujo (email: antonio@epicwindesigns.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

DEFINE('ADMIN_URL', admin_url('admin.php?page=epicwin-subscribers'));

// Plugin activation:
function epicwin_install() {
	global $wp_version;
	global $wpdb;
	
	if (version_compare($wp_version, '2.9', '<')) {
		deactivate_plugins(basename(__FILE__));
		wp_die('This plugin requires WordPress version 2.9 or higher.');
	} else {
		$wpdb->query($wpdb->prepare("CREATE TABLE epicwin_feed (id TINYINT(5) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, email VARCHAR(100) NOT NULL, name VARCHAR(30), opt_in TINYINT(1), UNIQUE(email));"));
	}
	
}
register_activation_hook(__FILE__, 'epicwin_install');

// Plugin Deactivation:
function epicwin_uninstall() {
	global $wpdb;
	$wpdb->query($wpdb->prepare("DROP TABLE epicwin_feed"));
	unregister_widget('Epicwin_Widget');
}
register_deactivation_hook(__FILE__, 'epicwin_uninstall');

// Register the email settings:
function epicwin_register_settings() {
	register_setting('epicwin_settings_group', 'epicwin_email_subject');
	register_setting('epicwin_settings_group', 'epicwin_email_message');
}
add_action('admin_init', 'epicwin_register_settings');

// Attach the plugin stylesheet to the header:
function epicwin_styles() {
	echo "<link rel='stylesheet' href='" . WP_PLUGIN_URL . "/epicwin-subscribers/style.css' type='text/css' media='all' />";
}
add_action('wp_head', 'epicwin_styles');

// Return the current url in order to set the method attribute for the forms:
function currentUrl() {
	$url = add_query_arg(array());
	return $url;
}

function get_epicwin_box() { 
	global $wpdb; ?>
	<div class="widget_epicwin_widget">
		<form method="post" action="<?php echo currentUrl() ?>" class="epicwin-subscription">
			<table>
				<tr>
					<td><label><?php _e('Name') ?>: </label></td>
					<td><input type="text" name="sub_name" /></td>
				</tr>
				<tr>
					<td><label><?php _e('Email') ?>: </label></td>
					<td><input type="text" name="sub_email" /></td>
				</tr>
				<tr>
					<td><input type="hidden" name="action" value="subscribe" /></td>
					<td><button type="submit"><?php _e('Subscribe') ?></button></td>
				</tr>
			</table>
		</form>
	<?php
	if($_POST['action'] == 'subscribe') {
		$errors = array();
		
		if (empty($_POST['sub_name'])) {
			$errors[] = 'Please type in your name';
		} else {
			$sub_name = strip_tags($_POST['sub_name']);
		}
		
		if (filter_input(INPUT_POST, 'sub_email', FILTER_VALIDATE_EMAIL)) {
			$sub_email = $_POST['sub_email'];
		} else {
			$errors[] = 'Please type in a valid email';
		}
		
		if ($errors) {
			echo '<div class="errors">';
			
			foreach ($errors as $error) {
				echo '<p class="error">- ' . $error . '</p>';
			}
			echo '</div>';
			
		}	else {
			$res = $wpdb->query($wpdb->prepare("SELECT email FROM epicwin_feed WHERE email='{$_POST['sub_email']}'"));
			if ($res > 0) {
				if ($wpdb->query($wpdb->prepare("UPDATE epicwin_feed SET opt_in=1 WHERE email='{$_POST['sub_email']}'"))) {
					echo '<p class="success">You have been subscribed!</p>';
				} else {
					echo '<p class="success">You are already subscribed!</p>';
				}
			}
			
			if($wpdb->insert('epicwin_feed', array('email' => $sub_email, 'name' => $sub_name, 'opt_in' => 1), array('%s', '%s', '%d'))) {
				echo '<p class="success">You have been subscribed!</p>';
			}
		}
	}
	echo '</div>';
}

// Create the plugin settings page:
function epicwin_settings_page() {
	global $wpdb;

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page'));
	} else {
		// Export as a comma separate value:
		if ($_POST['csv_export'] == 'csv_export') {
			$q = $wpdb->get_results($wpdb->prepare("SELECT * FROM epicwin_feed"));
			if (file_exists(str_replace('\\', '/', WP_PLUGIN_DIR) . '/epicwin-subscribers/subscribers.csv')) {
				if (count($q) > 0) {
					$file = fopen(str_replace('\\', '/', WP_PLUGIN_DIR) . '/epicwin-subscribers/subscribers.csv', 'w');
					
					foreach ($q as $row) {
						fwrite($file, $row->name . ', ' . $row->email . ', ' . $row->opt_in . "\n");
					}
					fclose($file);
					echo '<div class="updated"><p>The CSV file was updated.</p></div>';
				}
			} else {
				if (count($q) > 0) {
					$file = fopen(str_replace('\\', '/', WP_PLUGIN_DIR) . '/epicwin-subscribers/subscribers.csv', 'w');
					
					foreach ($q as $row) {
						fwrite($file, $row->name . ', ' . $row->email . ', ' . $row->opt_in . "\n");
					}
					fclose($file);
					echo '<div class="updated"><p>The CSV file was created.</p></div>';
				}
			}
		}
		
		if(isset($_POST['delete'])) {
			if (unlink(str_replace('\\', '/', WP_PLUGIN_DIR) . '/epicwin-subscribers/subscribers.csv')) {
				echo '<div class="updated"><p>The file was deleted succesfully.</p></div>';
			} else {
				echo '<div class="error">The file could not be deleted, please verify if the file exists and try again.</p></div>';
			}
		}
		
		if (isset($_GET['opt'])) {
			$update = $wpdb->query($wpdb->prepare("DELETE FROM epicwin_feed WHERE id=" . $_GET['id']));
			if($delete) {
				echo '<div class="updated"><p>The record was deleted succesfully.</p></div>';
			} else {
				echo '<div class="error">There was an error while trying to delete the record. Please try again.</p></div>';
			}
		}
		
		if (isset($_GET['id'])) {
			$delete = $wpdb->query($wpdb->prepare("DELETE FROM epicwin_feed WHERE id=" . $_GET['id']));
			if($delete) {
				echo '<div class="updated"><p>The record was deleted succesfully.</p></div>';
			} else {
				echo '<div class="error"><p>There was an error while trying to delete the record. Please try again.</p></div>';
			}
		}
		
		if (isset($_POST['import'])) {
			if ($_FILES['upload']['size'] > 0) {
				if (end(explode('.', strtolower($_FILES['upload']['name']))) == 'csv') {
					$query = "INSERT INTO epicwin_feed VALUES ";
					$row = file($_FILES['upload']['tmp_name']);
					foreach($row as $key => $value) {
						$entry[$key] = explode(',', $value);
						$query .= "(null, '{$entry[$key][1]}', '{$entry[$key][0]}', '{$entry[$key][2]}'), ";
					}
					$query = substr($query, 0, (strlen($query) - 2)) . ';';
					if($wpdb->query($wpdb->prepare($query))) {
						echo '<div class="updated"><p>The CSV file has been imported.</p></div>';
					}
				} else {
					echo '<div class="error"><p>Error: Only CSV files are allowed.</p></div>';
				}
			} else {
				echo '<div class="error"><p>Error: Please select a CSV file to upload.</p></div>';
			}
		}
		
		if (isset($_GET['updated'])) {
			echo '<div class="updated"><p>Email Settings Saved</p></div>';
		}
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32">&nbsp;</div>
			<h2><?php _e('Epicwin Settings') ?></h2>
			<h3><?php _e('Subscription Settings'); ?></h3>
			<table class="form-table" style="width: 980px;">
				<tbody>
					<tr valign="top">
						<td width="80"><?php _e('Export data') ?>:</td>
						<td>
							<form action="<?php echo ADMIN_URL ?>" method="post" class="export" style="float: left; width: auto; margin-right: 10px;">
								<?php if(file_exists(str_replace('\\', '/', WP_PLUGIN_DIR) . '/epicwin-subscribers/subscribers.csv')): ?>
									<input type="submit"  value="<?php _e('Update CSV') ?>" />
								<?php else: ?>
									<input type="submit" value="<?php _e('Create CSV') ?>" />
								<?php endif; ?>
								<input type="hidden" name="csv_export" value="csv_export" />
							</form>
							
						<?php if (file_exists(str_replace('\\', '/', WP_PLUGIN_DIR) . '/epicwin-subscribers/subscribers.csv')):?>
							<form action ="<?php echo ADMIN_URL ?>" method="post" style="float: left; width: auto; margin-right: 10px;">
								<input type="submit" value="<?php _e('Delete CSV') ?>" />
								<input type="hidden" value="true" name="delete" />
							</form>
							<a href="<?php echo WP_PLUGIN_URL .'/epicwin-subscribers/subscribers.csv' ?>" style="float: left;" class="button-secondary"><?php _e('Download CSV') ?></a>
						<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php _e('Import data'); ?>: </td>
						<td>
							<form action ="<?php echo ADMIN_URL ?>" method="post"  enctype="multipart/form-data">
								<input type="file" name="upload" />
								<input type="submit" value="<?php _e('Import Subscribers'); ?>" />
								<input type="hidden" value="true" name="import" />
							</form>
						</td>
					</tr>
					<tr>
						<td><?php _e('Filters') ?>: </td>
						<td>
							<form action="<?php echo ADMIN_URL . '?' ?>" method="get">
								<table>
									<tr>
										<td>
											<input type="hidden" name="page" value="epicwin-subscribers" />
											<label for="sort"><?php _e('Sort By') ?>: </label>
											<select name="sort" style="width: 70px">
											<?php if ($_GET['sort'] == 'email'): ?>
												<option value="name"><?php _e('Name') ?></option>
												<option value="email" selected="selected"><?php _e('Email') ?></option>
											<?php else: ?>
												<option value="name" selected="selected"><?php _e('Name') ?></option>
												<option value="email"><?php _e('Email') ?></option>
											<?php endif; ?>
											</select>
										</td>
										<td>
											<label for="sort"><?php _e('Sort Order') ?>: </label>
											<select name="sort_order" style="width: 100px">
											<?php if ($_GET['sort_order'] == 'DESC'): ?>
												<option value="ASC"><?php _e('Ascending') ?></option>
												<option value="DESC" selected="selected"><?php _e('Descending') ?></option>
											<?php else: ?>
												<option value="ASC" selected="selected"><?php _e('Ascending') ?></option>
												<option value="DESC"><?php _e('Descending') ?></option>
											<?php endif; ?>
											</select>
										</td>
										<td>
											<label for="sort"><?php _e('Records Per Page') ?>: </label>
											<select name="record_count" style="width: 50px">
											<?php
												switch ($_GET['record_count']) {
													case '20':
														echo '<option value="10">10</option>
														<option value="20" selected="selected">20</option>
														<option value="30">30</option>';
														break;
														
													case '30':
														echo '<option value="10">10</option>
														<option value="20">20</option>
														<option value="30" selected="selected">30</option>';
														break;
														
													default:
														echo '<option value="10" selected="selected">10</option>
														<option value="20">20</option>
														<option value="30">30</option>';
														break;
												}
											?>
											</select>
										</td>
										<td>
											<input type="submit" value="<?php _e('Apply Filters') ?>" />
										</td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
					<tr valign="top">
						<td><?php _e('Subscribers') ?>:</td>
						<td>
							<table cellspacing="0" border="0" class="widefat">
								<thead>
									<tr>
										<th><?php _e('Name') ?>:</th>
										<th><?php _e('Email') ?>:</th>
										<th><?php _e('Opt-In') ?>:</th>
										<th><?php _e('Action') ?>: </th>
									</tr>
								</thead>
								<tbody>
								<?php
								$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM epicwin_feed"));
								
								$numRows = count($results);
								$rowsPerPage = (isset($_GET['record_count'])) ? $_GET['record_count'] : 10;
								$totalPages = (isset($_GET['total_pages'])) ? $_GET['total_pages'] : ceil($numRows / $rowsPerPage);
								$startPage = (isset($_GET['start_page'])) ? $_GET['start_page'] : 0;
								$sort = (isset($_GET['sort'])) ? $_GET['sort'] : 'name';
								$sortOrder = (isset($_GET['sort_order'])) ? $_GET['sort_order'] : 'ASC';
								
								$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM epicwin_feed ORDER BY $sort  $sortOrder LIMIT $startPage, $rowsPerPage"));
								
								if (count($results) > 0):
								
									foreach ($results as $row):
									?>
										<tr>
											<td><?php echo $row->name ?></td>
											<td><?php echo $row->email ?></td>
											<?php if($row->opt_in == 0): ?>
												<td><?php _e('No') ?></td> 
											<?php else: ?>
												<td><?php _e('Yes') ?></td>
											<?php endif; ?>
											<td><a href="<?php echo ADMIN_URL . '&id=' . $row->id ?>"><?php _e('Delete') ?></a></td>
										</tr>
									<?php endforeach; ?>
									
								<?php else: ?>
									<tr>
										<td colspan="4" align="center"><?php _e('There aren\'t any subscribers at this moment.') ?></td>
									</tr>
								<?php endif ?>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td><?php _e('Pagination') ?>: </td>
						<td><?php
							if ($totalPages > 1) {
								$currentPage = ($startPage/$rowsPerPage) + 1;
								
								if ($currentPage != 1) {
									echo '<a href="' . currentUrl() . '&start_page=' . ($startPage - $rowsPerPage) . '&total_pages=' . $totalPages . '">' . _e('Previous') . '</a> ';
								}
								
								for ($i = 1; $i <= $totalPages; $i++) {
									if ($i !=$currentPage) {
										echo '<a href="' . currentUrl() . '&start_page=' . (($rowsPerPage * ($i - 1))) . '&total_pages=' .$totalPages . '">' . $i . '</a> ';
									} else {
										echo $i . ' ';
									}
								}
								
								if ($currentPage != $totalPages) {
									echo '<a href="' . currentUrl() . '&start_page=' . ($startPage + $rowsPerPage) . '&total_pages=' . $totalPages . '">' . _e('Next') . '</a>';
								}
							} else {
								_e('There are not enough records to generate a pagination.');
							}
						?>
						</td>
					</tr>
				</tbody>
			</table>
			<h3><?php _e('Email Settings') ?></h3>
			<form action="options.php" method="post">
				<?php settings_fields('epicwin_settings_group'); ?>
				<table class="form-table" style="width: 980px">
					<tbody>
						<tr>
							<td><label for="email_subject"><?php _e('Subject') ?>: </label> </td>
							<td><input class="regular-text" type="text" id="email_subject" name="epicwin_email_subject" value="<?php echo (get_option('epicwin_email_subject')) ? get_option('epicwin_email_subject') : ''; ?>" /> <span class="description"><?php _e('If you want the post name to be the subject just leave this blank') ?>.</span></td>
						</tr>
						<tr>
							<td valign="top"><label for="email_message"><?php _e('Message') ?>: </label></td>
							<td><textarea class="large-text" rows="10" cols="25" id="email_message" name="epicwin_email_message"><?php echo get_option('epicwin_email_message'); ?></textarea><span class="description"><?php _e('Use the tag {link} to display the post link in the email. You may use HTML tags to format the message. Ex: "New post from Wordpress Blog! Click on the following link to see it: {link}"') ?></span></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<p class="submit"><input type="submit" value="Save Changes" name="submit" class="button-primary" /></p>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<table>
				<tbody>
					<tr>
						<td colspan="2">
							<h3><?php _e('If you like this plugin and you use it, please help keep the development going. <br />Your donation ensures the continuous development of the plugin. <br />Any donation amount is appreciated.') ?></h3>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
								<input type="hidden" name="cmd" value="_s-xclick" />
								<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" />
								<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
								<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
							</form>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	<?php
	}
	
}

// Create the settings menu in the admin area:
function create_epicwin_menu() {
	add_menu_page('Epicwin Settings', 'Epicwin Settings', 'administrator', 'epicwin-subscribers', 'epicwin_settings_page', WP_PLUGIN_URL . '/epicwin-subscribers/generic.png');
}
add_action('admin_menu', 'create_epicwin_menu');

// Function to run when someone unsubscribes to the email list:
function unsubscribe() {
	global $wpdb;
	
	if ($_GET['action'] == 'unsubscribe'): ?>
		<div id="unsubscribe">
			<form action="<?php echo currentUrl() ?>" method="get">
				<p><?php _e('Enter your email bellow') ?></p>
				<p><input size="10" type="text" name="unsub_email" /></p>
				<p>
					<input type="submit" value="Unsubscribe" />
					<input type="hidden" value="yes" name="unsub" />
				</p>
			</form>
		</div>
	<?php endif; ?>
	
	<?php if(isset($_GET['unsub_email']) && $_GET['unsub'] == 'yes'): ?>
		<?php $queryResult = $wpdb->query($wpdb->prepare("UPDATE epicwin_feed SET opt_in=0 WHERE email='" . $_GET['unsub_email'] . "'"));
		if ($queryResult > 0): ?>
			<div id="unsubscribe">
				<p><?php _e('You have been removed form our mailing list. If you would like to join the mailing list in the future, just fill in the subscribe box again') ?>.</p>
				<a href="#" class="close" onclick="document.getElementById('unsubscribe').style.visibility='hidden'">Close</a>
			</div>
		<?php else: ?>
			<div id="unsubscribe">
				<p><?php _e('You have either already unsubscribed or we could not find your email in our recrods') ?>.</p>
				<a href="#" class="close" onclick="document.getElementById('unsubscribe').style.visibility='hidden'">Close</a>
			</div>
		<?php endif;
	endif;
	
}
add_action('wp_footer', 'unsubscribe');

// Create the sidebar widget for the plugin:
class Epicwin_Widget extends WP_Widget {

	function Epicwin_Widget() {
		parent::WP_Widget(false, $name = 'Epicwin Widget');
	}
	
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		
		echo $before_widget;
		if ($title)  echo $before_title . $title . $after_title;
		get_epicwin_box();
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
	
	function form($instance) {
		$title = esc_attr($instance['title']); ?>
		<p><label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title: ') ?></label><input class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" type="text" value="<?php echo $title ?>" /></p>
		<?php 
	}

}

// Send an email message to alert all subscribers who have an Opt-In status when a new post has been made:
function new_post_alert() {
	global $posts;
	global $wpdb;
	
	ini_set('display_errors', E_ALL);
	
	if ($_POST['original_post_status'] !== 'publish' && $_POST['post_type'] == 'post' && $_REQUEST['screen'] !== 'edit-post' && $_POST['publish'] == 'Publish') {
		$query = $wpdb->get_results($wpdb->prepare("SELECT id, email FROM epicwin_feed WHERE opt_in=1"));
		$queryPosts = $wpdb->get_results($wpdb->prepare("SELECT post_title, guid FROM $wpdb->prefix" . "posts WHERE post_type='post' AND post_status='publish' ORDER BY ID DESC LIMIT 1"));
		
		$emails = array();
		
		if (count($query) > 0) {
			foreach($query as $entry) {
				$emails[]= $entry->email;
			}
			
			$bcc = implode(',', $emails);
			
			$epicwin_title = ($queryPosts[0]->post_title) ? $queryPosts[0]->post_title : 'Click Here';
			$from = get_userdata(1)->user_email;
			$subject = (get_option('epicwin_email_subject')) ? get_option('epicwin_email_subject') : $queryPosts[0]->post_title;
			$body = '<html><head><title>' . $subject . '</title></head><body><div>';
			$body .= (get_option('epicwin_email_message')) ? get_option('epicwin_email_message') : 'New post from ' . get_bloginfo('name') . '. Click the link bellow to view it: <br><a href="' . $queryPosts[0]->guid . '">' . $epicwin_title . '</a><br><br>';
			$body .= 'You may unsubscribe at any time using the following link: <a href="' .  get_bloginfo('url') . '/index.php?action=unsubscribe">Unsubscribe</a></div></body></html>';
			$body = preg_replace('/\{link\}/', '<a href="' . $queryPosts[0]->guid . '">' . $epicwin_title . '</a>', $body);
			
			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			// Additional headers
			$headers .= "From: $from" . "\r\n";
			$headers .= "Bcc: $bcc" . "\r\n";
			
			wp_mail($from, $subject, $body, $headers);
		}
	}
}
add_filter('publish_post', 'new_post_alert');

// Register the widget on the sidebar:
function init_epicwin_widget() {
	register_widget('Epicwin_Widget');
}
add_action('init', 'init_epicwin_widget', 1);
?>