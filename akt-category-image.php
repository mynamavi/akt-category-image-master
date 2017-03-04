<?php
/*
 * Plugin Name:WP Category Images
 * Plugin URI:https://ms-dynamics365.co.uk/member/avinash-tripathi
 * Description:Add images in category.
 * Author:AKT
 * Author URI:https://ms-dynamics365.co.uk/member/avinash-tripathi
 * Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if ( ! class_exists( 'AKTCategoriesImages' ) ) :

class AKTCategoriesImages {
	
	private static $instance = null;
	public $version = '1.0';

	private $nonce = 'wp_akt_cat_images';

	public static function get_instance() {

		if( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	}
	private function __construct() {
		$this->define_constants();
		add_action( 'admin_init', array( $this, 'akt_init' ) );
		add_action('edit_term', array( $this, 'akt_save_taxonomy_image'));
		add_action('create_term', array( $this, 'akt_save_taxonomy_image'));
		add_action('admin_menu', array( $this,'akt_add_options_menu'));
	}
	
	private function define_constants() {
		define( 'AKT_CI_PLUGIN_FILE', __FILE__ );
		define( 'AKT_CI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'AKT_CI_VERSION', $this->version );			
	}

	
	public function akt_init(){
		$akt_all_taxonomies = get_taxonomies();
		if (is_array($akt_all_taxonomies)) {
			$akt_taxonomy_options = get_option('akt_taxonomy_options');
			if (empty($akt_taxonomy_options['excluded_taxonomies']))
				$akt_taxonomy_options['excluded_taxonomies'] = array();
			
			foreach ($akt_all_taxonomies as $akt_taxonomy) {
				if (in_array($akt_taxonomy, $akt_taxonomy_options['excluded_taxonomies']))
					continue;
				add_action($akt_taxonomy.'_add_form_fields', array( $this, 'akt_add_taxonomy_field'));
				add_action($akt_taxonomy.'_edit_form_fields', array( $this, 'akt_edit_taxonomy_field'));
				add_filter( 'manage_edit-' . $akt_taxonomy . '_columns', array( $this, 'akt_taxonomy_columns') );
				add_filter( 'manage_' . $akt_taxonomy . '_custom_column', array( $this, 'akt_taxonomy_column'), 10, 3 );
				add_action('quick_edit_custom_box',  array($this,'akt_quick_edit_custom_box'), 10, 3);
			}
		}
	}

	public function akt_add_taxonomy_field(){
		if (get_bloginfo('version') >= 3.5)
			wp_enqueue_media();
		else {
			wp_enqueue_style('thickbox');
			wp_enqueue_script('thickbox');
		}
				
		$image_id = "";
		
		echo '<div class="form-field akt-category-image">
			<label for="akt_cat_featured_image_url">' . __('Image') . '</label>';
		echo'<img class="cat_featured_image" src="" style="display:none;"/><br/>';
		echo '<input type="text" name="akt_cat_featured_image_url" placeholder="Click on Upload Image Button" id="akt_cat_featured_image_url" value="" readonly /><br />
			<input type="hidden" name="akt_cat_featured_image" value="' . $image_id . '"/>
			<button class="akt_cat_featured_image_add_btn button">' . __('Upload image') . '</button>
			<button class="akt_cat_featured_image_remove_btn button">' . __('Remove image') . '</button>			
		</div>';
		$this->akt_upload_script();
	}
	public function akt_edit_taxonomy_field($taxonomy){
		if (get_bloginfo('version') >= 3.5)
			wp_enqueue_media();
		else {
			wp_enqueue_style('thickbox');
			wp_enqueue_script('thickbox');
		}
				
		if(get_term_meta($taxonomy->term_id,"akt_cat_featured_image",true)){
			$image_id = get_term_meta($taxonomy->term_id,"akt_cat_featured_image",true);
			$image_url = wp_get_attachment_image_src( $image_id,"thumbnail");
		}else{
			$image_id = "";
			$image_url[0] = "";
		}
		
		echo '<tr class="form-field akt-category-image">
			<th scope="row" valign="top"><label for="akt_cat_featured_image_url">' . __('Image') . '</label></th>
			<td>';
		if($image_id)
			echo'<img class="cat_featured_image" src="' . $image_url[0] . '" /><br/>';
		else
			echo'<img class="cat_featured_image" src="' . $image_url[0] . '" style="display:none;"/><br/>';
		echo '<input type="text" name="akt_cat_featured_image_url" placeholder="Click on Upload Image Button" id="akt_cat_featured_image_url" value="'.$image_url[0].'" readonly /><br />
			<input type="hidden" name="akt_cat_featured_image" value="' . $image_id . '"/>
			<button class="akt_cat_featured_image_add_btn button">' . __('Upload image') . '</button>
			<button class="akt_cat_featured_image_remove_btn button">' . __('Remove image') . '</button>
			</td>
		</tr>';
		$this->akt_upload_script();
	}

	
	function akt_save_taxonomy_image($term_id) {
		if(isset($_POST['akt_cat_featured_image']))
			update_term_meta($term_id,'akt_cat_featured_image', $_POST['akt_cat_featured_image']);
	}
	
	public function akt_upload_script(){
		ob_start();
		?>
			<script>
				jQuery(document).ready(function(){
				
					var blog_version = "<?= get_bloginfo("version")?>", upload_button;
					jQuery(".akt_cat_featured_image_add_btn").click(function(event) {
						var mythis = jQuery(this);
						var cat_img_uploader;
						if (blog_version >= "3.5") {
							event.preventDefault();
							if (cat_img_uploader) {
								cat_img_uploader.open();
								return;
							}
							cat_img_uploader = wp.media.frames.file_frame = wp.media({
								title: 'Choose Category Image',
								button: {
									text: 'Choose Category Image'
								},
								multiple: false
							});
							cat_img_uploader.on('select', function() {
								attachment = cat_img_uploader.state().get('selection').first().toJSON();
								cat_img_uploader.close();
								mythis.parent().find("input[name=akt_cat_featured_image_url]").val(attachment.url);
								mythis.parent().find(".cat_featured_image").attr("src",attachment.sizes.thumbnail.url);
								mythis.parent().find(".cat_featured_image").css("display","block");
								mythis.parent().find("input[name=akt_cat_featured_image]").val(attachment.id);
							});
							cat_img_uploader.open();						
						}
						else {
							tb_show("", "media-upload.php?type=image&amp;TB_iframe=true");
							return false;
						}
					});
					
					jQuery(".akt_cat_featured_image_remove_btn").click(function() {
						var mythis = jQuery(this);
						mythis.parent().find(".cat_featured_image").css("display","none");
						mythis.parent().find(".cat_featured_image").attr("src","");
						mythis.parent().find("input[name=akt_cat_featured_image_url]").val("");
						mythis.parent().find("input[name=akt_cat_featured_image]").val("");
						return false;
					});
							
				});
			</script>
		<?php
		return ob_get_contents();
	}
	public function akt_taxonomy_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['thumb'] = __('Image');

		unset( $columns['cb'] );

		return array_merge( $new_columns, $columns );
	}
	public function akt_taxonomy_column( $columns, $column, $id ) {
		if ( $column == 'thumb' ){
			if(get_term_meta($id,"akt_cat_featured_image",true)){
				$image_id = get_term_meta($id,"akt_cat_featured_image",true);
				$image_url = wp_get_attachment_image_src( $image_id,"thumbnail");
			}else{
				$image_url[0] = "";
			}
			$columns = '<span><img src="' .$image_url[0] . '" alt="' . __('Thumbnail') . '" class="wp-post-image" width="100" /></span>';
		}
		return $columns;
	}
	public  function akt_quick_edit_custom_box($column_name, $screen, $name) {
		if ($column_name == 'thumb')
			echo '<fieldset>
			<div class="thumb inline-edit-col">
				<label>
					<span class="title"><img src="" alt="Thumbnail" class="cat_featured_image"/></span>
					<span class="input-text-wrap">
						<input type="text" name="akt_cat_featured_image_url" placeholder="Click on Upload Image Button" id="akt_cat_featured_image_url" value="" readonly />
						<input type="hidden" name="akt_cat_featured_image" value=""/>
						<button class="akt_cat_featured_image_add_btn button">' . __('Upload Image') . '</button>
						<button class="akt_cat_featured_image_remove_btn button">' . __('Remove Image') . '</button>
					</span>					
				</label>
			</div>
		</fieldset>';
	}

	public function akt_add_options_menu(){
		//~ add_options_page(__('Categories Images settings'), __('Categories Images'), 'manage_options', array($this,'akt_cat_images_options'), 'akt_cat_images_options');
		//~ add_action('admin_init', array($this,'akt_register_settings'));
	}
	public function akt_register_settings(){
		register_setting('akt_cat_images_options', array($this,'akt_cat_images_options'), 'akt_cat_images_options_check');
		add_settings_section('akt_cat_images_settings', __('Categories Images settings'), array($this,'akt_cat_images_section_text'), 'akt_cat_images_options');
		//~ add_settings_field('akt_cat_images_excluded_taxonomies', __('Excluded Taxonomies'), 'akt_cat_images_excluded_taxonomies', array($this,'akt_cat_images_option'), 'zci_settings');
	}
	public function akt_cat_images_section_text() {
		echo '<p>'.__('Please select the taxonomies you want to exclude it from Categories Images plugin', 'zci').'</p>';
	}
}

endif;


AKTCategoriesImages::get_instance();

