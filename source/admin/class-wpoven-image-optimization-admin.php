<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.wpoven.com
 * @since      1.0.0
 *
 * @package    Wpoven_Image_Optimization
 * @subpackage Wpoven_Image_Optimization/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpoven_Image_Optimization
 * @subpackage Wpoven_Image_Optimization/admin
 * @author     WPOven <contact@wpoven.com>
 */


class Wpoven_Image_Optimization_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	private $_wpoven_image_optimization;
	private $wp_filesystem;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		global $wp_filesystem;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		include_once(WPOVEN_OPTIMIZATION_PATH . 'classes/Picture/display.php');

		if (!class_exists('ReduxFramework') && file_exists(require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libraries/redux-framework/redux-core/framework.php')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/libraries/redux-framework/redux-core/framework.php';
		}

		if (! function_exists('WP_Filesystem')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			WP_Filesystem();
		}

		$this->wp_filesystem = $wp_filesystem;

		$this->actions();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpoven_Image_Optimization_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpoven_Image_Optimization_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpoven-image-optimization-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpoven_Image_Optimization_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpoven_Image_Optimization_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpoven-image-optimization-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 *  Define all action and filter here.
	 */
	function actions()
	{
		add_action('admin_footer', array($this, 'add_ajax_nonce_to_admin_footer'));
		add_action('wp_enqueue_scripts', array($this, 'load_custom_wp_admin_styles_and_script'));
		add_action('admin_enqueue_scripts', array($this, 'my_plugin_enqueue_scripts'));

		add_action('wp_ajax_wpoven_call_optimization', array($this, 'wpoven_call_optimization'));
		add_action('wp_ajax_nopriv_wpoven_call_optimization',  array($this, 'wpoven_call_optimization'));

		add_action('wp_ajax_wpoven_put_image_in_dir', array($this, 'wpoven_upload_image_into_dir'));
		add_action('wp_ajax_nopriv_wpoven_put_image_in_dir',  array($this, 'wpoven_upload_image_into_dir'));

		add_action('wp_ajax_wpoven_get_data_to_show_graph', array($this, 'wpoven_get_data_to_show_graph'));
		add_action('wp_ajax_nopriv_wpoven_get_data_to_show_graph',  array($this, 'wpoven_get_data_to_show_graph'));

		add_action('wp_ajax_wpoven_call_optimization_after_upload_image', array($this, 'call_optimization_after_upload_image'));
		add_action('wp_ajax_nopriv_wpoven_call_optimization_after_upload_image',  array($this, 'call_optimization_after_upload_image'));

		//add_action('updated_post_meta', array($this, 'do_auto_optimization_after_meta_update'), 10, 4);
		add_action('added_post_meta', array($this, 'do_auto_optimization_after_meta_update'), 10, 4);

		add_action('template_redirect', array($this, 'wpoven_picture_display_init'));

		add_action('admin_enqueue_scripts', array($this, 'enqueue_chart_js'));

		add_action('wp_ajax_wpoven_get_data_for_show_hide_modal', array($this, 'wpoven_get_data_for_show_hide_modal'));
		add_action('wp_ajax_nopriv_wpoven_get_data_for_show_hide_modal',  array($this, 'wpoven_get_data_for_show_hide_modal'));

		add_action('wp_ajax_wpoven_image_optimization_again', array($this, 'wpoven_image_optimization_again'));
		add_action('wp_ajax_nopriv_wpoven_image_optimization_again',  array($this, 'wpoven_image_optimization_again'));

		add_filter('manage_media_columns', array($this, 'wpoven_add_optimization_status_column'));
		add_action('manage_media_custom_column', array($this, 'wpoven_display_optimization_status_column'), 10, 2);
		add_filter('manage_upload_sortable_columns', array($this, 'wpoven_sortable_optimization_status_column'));
		add_action('admin_head', array($this, 'wpoven_media_column_styles'));

		add_action('wp_ajax_wpoven_optimize_single_image', array($this, 'wpoven_optimize_single_image'));
		add_action('wp_ajax_nopriv_wpoven_optimize_single_image',  array($this, 'wpoven_optimize_single_image'));

		//add_action('init', array($this, 'wp_increase_upload_limits'));
	}

	// Increase upload limits programmatically
	function wp_increase_upload_limits()
	{
		@ini_set('upload_max_filesize', '64M');  // Max upload size for individual files
		@ini_set('post_max_size', '64M');         // Max POST request size
		@ini_set('max_file_uploads', '100');      // Maximum number of files
		@ini_set('memory_limit', '256M');         // Increase memory limit (optional)
	}

	/**
	 * Enqueues the Chart.js library for use in the admin panel.
	 */
	function enqueue_chart_js()
	{
		wp_enqueue_script('chart-js', plugin_dir_url(__FILE__) . 'js/chart.js', array(), null, true);
	}


	/**
	 * Initializes the picture display process for optimized images.
	 */
	function wpoven_picture_display_init()
	{
		$display = new WPOven\Picture\Display();
		$display->start_content_process();
	}

	/**
	 * Adds an AJAX nonce to the admin footer for security in AJAX requests.
	 */
	function add_ajax_nonce_to_admin_footer()
	{
?>
		<script type="text/javascript">
			var ajax_nonce = '<?php echo wp_create_nonce('wpoven_ajax_nonce'); ?>';
			document.write('<div id="wpoven-ajax-nonce" style="display:none;">' + ajax_nonce + '</div>');
		</script>
<?php
	}

	/**
	 *  Loads custom styles and scripts for the WordPress admin.
	 */
	function load_custom_wp_admin_styles_and_script()
	{
		$wp_scripts = wp_scripts();
		wp_register_script('wpoven_admin_js', WPOVEN_IMAGE_OPTIMIZATION_PLUGIN_URL . 'admin/js/backend.js', true);
		$inline_js = 'const wpoven_ajax_url = "' . admin_url('admin-ajax.php') . '"; ';
	}

	/**
	 *  Enqueues the main script for the image optimization plugin and localizes the AJAX URL.
	 */
	function my_plugin_enqueue_scripts()
	{
		wp_enqueue_script(
			'wpoven-image-optimization-script',
			plugin_dir_url(__FILE__) . '/js/backend.js',
			array(),
			null,
			true
		);

		// Pass the URL of the icon to JavaScript
		wp_localize_script('wpoven-image-optimization-script', 'myPluginUrl', array(
			'wpoven_ajax_url' => admin_url('admin-ajax.php')
		));
	}


	/**
	 * Adds custom styles for the optimization status column in the media library.
	 */
	function wpoven_media_column_styles()
	{
		echo '<style>
			.column-optimization_status { width: 220px; }
		</style>';
	}

	/**
	 * Makes the optimization status column sortable in the media library.
	 *
	 * @param array $columns An array of columns.
	 * @return array Modified array of columns.
	 */
	function wpoven_sortable_optimization_status_column($columns)
	{
		$columns['optimization_status'] = 'optimization_status';
		return $columns;
	}

	/**
	 * Adds the optimization status column to the media library.
	 *
	 * @param array $columns An array of columns.
	 * @return array Modified array of columns.
	 */
	function wpoven_add_optimization_status_column($columns)
	{
		$columns['optimization_status'] = 'Optimization Status';
		return $columns;
	}

	/**
	 * Displays the optimization status for each image in the media library.
	 *
	 * @param string $column_name The name of the column.
	 * @param int $post_id The post ID of the image.
	 */
	function wpoven_display_optimization_status_column($column_name, $post_id)
	{
		if ('optimization_status' === $column_name) {
			$post = get_post($post_id);
			if ($post && $post->post_type === 'attachment' && strpos($post->post_mime_type, 'image') === 0) {
				$status = get_post_meta($post_id, 'wpoven_optimized_status', true);
				if ($status == true) {
					echo '<a style="cursor: not-allowed; background-color: #f0f0f0; color: green; border-color:green;" class="button">Optimized</a>';
				} else {
					echo '<a onclick="runOptimization(' . $post_id . '); return false;" class="button">Not Optimized</a>';
				}
			}
		}
	}

	/**
	 * Optimizes a single image based on the provided post ID.
	 * Returns the image data and optimization status as a JSON response.
	 */
	function wpoven_optimize_single_image()
	{
		if (isset($_POST['post_id'])) {
			$option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
			$next_gen_type = isset($option['next-gen-format']) ? $option['next-gen-format'] : 'off';
			$compression_label = isset($option['compression-label']) ? $option['compression-label'] : 0;
			$return_array = array(
				'images' => null,
				'type' => $next_gen_type,
				'compression_label' => $compression_label,
				'files-optimization-size' => $option['files-optimization'],
				'success_msg' => 'Image optimization initiate successfully.'
			);

			// Check if post_id is passed via POST request
			if (isset($_POST['post_id'])) {
				$post_id = intval($_POST['post_id']); // Sanitize post_id

				// Check if the post is an attachment and image type
				$post = get_post($post_id);
				if ($post && $post->post_type === 'attachment' && strpos($post->post_mime_type, 'image') === 0) {
					$url = wp_get_attachment_url($post_id);
					$metadata = wp_get_attachment_metadata($post_id);
					$optimized_status = get_post_meta($post_id, 'wpoven_optimized_status', true);

					// Check if image is unoptimized or matches the next-gen type
					if ($optimized_status == 0) {
						$data = array(
							'url' => $url,
							'post_id' => $post_id,
							'metadata' => $metadata,
							'optimized_status' => $optimized_status
						);
						$return_array['images'] = $data;
						$return_array['status'] = 'ok';
					}
				}

				// Return the response as JSON
				die(wp_json_encode($return_array));
			}
		}
	}


	/**
	 * Automatically optimizes images after metadata updates.
	 *
	 * @param int $meta_id The ID of the updated meta.
	 * @param int $post_id The ID of the post associated with the meta.
	 * @param string $meta_key The key of the updated meta.
	 * @param mixed $meta_value The new value of the meta.
	 */
	function do_auto_optimization_after_meta_update($meta_id, $post_id, $meta_key, $meta_value)
	{
		// Check if the meta key corresponds to an image
		$post = get_post($post_id);
		if ($post && $post->post_type === 'attachment' && strpos($post->post_mime_type, 'image') === 0) {
			// Get the full image path
			$image_path = get_attached_file($post_id);

			if ($image_path && file_exists($image_path)) {
				// Update the post meta for the attachment post ID
				update_post_meta($post_id, 'wpoven_optimized_status', 0);
			}
		}
	}

	/**
	 * Retrieves data for displaying the modal for image optimization status.
	 */
	function wpoven_get_data_for_show_hide_modal()
	{
		global $wpdb;

		$option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
		$next_gen_type = isset($option['next-gen-format']) ? $option['next-gen-format'] : 'off';

		// Count the number of rows with wpoven_optimized_status as 0
		$count_zero = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
		", 'wpoven_optimized_status', '0', 'image%'));

		// Count the number of rows with wpoven_optimized_status as 1
		// Query for total optimized images
		$total_optimized_images = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
		", 'wpoven_optimized_status', '1', 'image%'));

		// Query for other values (attachments without wpoven_optimized_status meta key)
		$other_values = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE pm.post_id IS NULL
			AND p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
		", 'wpoven_optimized_status', 'image%'));

		$total_unoptimized_images = $count_zero + $other_values;

		$return_array = array(
			'total_optimized_images' => $total_optimized_images,
			'total_unoptimized_images' => $total_unoptimized_images,
			'type' => $next_gen_type,
		);

		$return_array['status'] = 'ok';
		if ($total_unoptimized_images > 0) {
			$message = 'Image Optimization Initiate Successfully.';
		} else {
			$message = 'All Image Optimization Successfully.';
		}
		$return_array['success_msg'] = __($message, 'WPOven image optimization');

		die(wp_json_encode($return_array));
	}

	/**
	 *  Resets the optimization status for all media attachments.
	 */
	function wpoven_image_optimization_again()
	{
		$media_posts = get_posts(array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'meta_query'     => array(
				'relation' => 'AND',  // Ensure both meta keys are checked
				array(
					'key'     => '_wp_attached_file',  // Check for existence of file
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_wp_attachment_metadata',  // Check for existence of metadata
					'value'   => '',  // The value should not be empty (which implies missing or false)
					'compare' => '!=', // This excludes attachments with false or empty metadata
				),
			),
		));
		foreach ($media_posts as $media) {
			update_post_meta($media->ID, 'wpoven_optimized_status', 0);
			update_post_meta($media->ID, 'wpoven_optimized_image_size', 0);
		}


		$return_array['status'] = 'ok';

		die(wp_json_encode($return_array));
	}

	/**
	 * Automatically triggers optimization after an image upload if enabled in settings.
	 */
	function call_optimization_after_upload_image()
	{
		global $wpdb;

		$option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
		$next_gen_type = isset($option['next-gen-format']) ? $option['next-gen-format'] : 'off';
		$auto_optimized = isset($option['auto-optimized']) ? $option['auto-optimized'] : 0;
		$compression_label =  isset($option['compression-label']) ? $option['compression-label'] : 0;

		$return_array = array(
			'images' => null,
			'compression_label' => $compression_label,
			'type' => $next_gen_type,
			'files-optimization-size' => $option['files-optimization'],
		);

		if ($auto_optimized && ($next_gen_type != 'off')) {
			$results = $wpdb->get_results(
				$wpdb->prepare("
					SELECT COUNT(*)
					FROM $wpdb->postmeta pm
					INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
					WHERE pm.meta_key = %s
					AND pm.meta_value = %s
					AND p.post_type = 'attachment'
					AND p.post_mime_type LIKE %s
				", 'wpoven_optimized_status', '0', 'image%')
			);


			if (!empty($results)) {

				$data = array();
				$image_count = 0;

				foreach ($results as $row) {

					if ($image_count >= 1) {
						break; // Stop after 25 images
					}
					$post_id = $row['post_id'];
					$metadata = wp_get_attachment_metadata($post_id);
					$url = wp_get_attachment_url($post_id);
					$data[] = [
						'post_id'   => $post_id,
						'metadata'  => $metadata,
						'url'       => $url,
					];
					//update_post_meta($post_id, 'wpoven_optimized_status', 1);

					$image_count++;
				}

				$return_array['images'] = $data;
			}
		}

		$return_array['status'] = 'ok';
		$return_array['success_msg'] = __('success', 'WPOven image optimization');

		die(wp_json_encode($return_array));
	}

	/**
	 * Initiates the image optimization process by checking settings and preparing images for optimization.
	 * 
	 * @return void
	 */
	function wpoven_call_optimization()
	{
		$option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
		$next_gen_type = isset($option['next-gen-format']) ? $option['next-gen-format'] : 'off';
		$compression_label =  isset($option['compression-label']) ? $option['compression-label'] : 0;
		$return_array = array(
			'images' => null,
			'type' => $next_gen_type,
			'compression_label' => $compression_label,
			'files-optimization-size' => $option['files-optimization'],
			'success_msg' => 'Image optimization initiate successfully.'
		);

		// Check and update the image optimization type
		$image_optimization_type = get_option('wpoven_image_optimization_type');
		$upload_dir = wp_upload_dir();
		$targetDirectory = $upload_dir['basedir'] . "/wpoven_optimized_images/";


		$media_posts = get_posts(array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'meta_query'     => array(
				'relation' => 'AND',  // Ensure both meta keys are checked
				array(
					'key'     => '_wp_attached_file',  // Check for existence of file
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_wp_attachment_metadata',  // Check for existence of metadata
					'value'   => '',  // The value should not be empty (which implies missing or false)
					'compare' => '!=', // This excludes attachments with false or empty metadata
				),
			),
		));

		if (empty($image_optimization_type) || $next_gen_type != $image_optimization_type) {
			update_option('wpoven_image_optimization_type', $next_gen_type);

			// Remove the target directory
			if (is_dir($targetDirectory)) {
				require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
				require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');

				$fileSystem = new WP_Filesystem_Direct(null);

				if (!$fileSystem->rmdir($targetDirectory, true)) {
					error_log("Failed to delete directory: " . $targetDirectory);
				}
			}

			foreach ($media_posts as $media) {
				update_post_meta($media->ID, 'wpoven_optimized_status', 0);
				update_post_meta($media->ID, 'wpoven_optimized_image_size', 0);
			}
		}

		if ($next_gen_type != 'off') {

			$data = array();
			$image_count = 0;

			foreach ($media_posts as $media) {

				if ($image_count >= 1) {
					break; // Stop after 10 images
				}

				$url = wp_get_attachment_url($media->ID);
				$metadata = wp_get_attachment_metadata($media->ID);
				$optimized_status = get_post_meta($media->ID, 'wpoven_optimized_status');
				//$optimized_type = get_post_meta($media->ID, 'wpoven_optimized_type');

				if ($optimized_status[0] == 0) {
					// Only add to the data array if the optimized status is 0 and check for next-gen type
					if ($next_gen_type == 'webp' || $next_gen_type == 'avif') {
						$data[] = array(
							'url' => $url,
							'post_id' => $media->ID,
							'metadata' => $metadata
						);
					}

					$image_count++;
				}
			}
			$return_array['images'] = $data;
		}

		$return_array['images'] = $data;
		$return_array['status'] = 'ok';

		die(wp_json_encode($return_array));
	}

	/**
	 * Handles the upload of images into the specified directory and updates the media metadata.
	 * 
	 * @return void
	 */
	function wpoven_upload_image_into_dir()
	{
		global $wpdb;

		$upload_dir = wp_upload_dir(); // Get the WordPress uploads directory
		$return_array = array();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Get the number of files
			$totalFiles = count($_FILES); // Count total uploaded files

			// Loop through each file and handle the uploads
			for ($fileCounter = 0; $fileCounter < $totalFiles; $fileCounter++) {
				$file_name = sanitize_file_name($_FILES['image_' . $fileCounter]['name']);
				$key = $_POST['key_' . $fileCounter];
				$post_id = intval($_POST['post_id_' . $fileCounter]);
				$type = sanitize_text_field($_POST['type_' . $fileCounter]);
				$security = $_POST['security']; // Security nonce

				// Check nonce for security
				if (!wp_verify_nonce($security, 'wpoven_ajax_nonce')) {
					$return_array['error_msg'] = __('Security check failed', 'WPOven image optimization');
					die(wp_json_encode($return_array));
				}

				// Check if the image file is set and there are no errors
				if (isset($_FILES['image_' . $fileCounter]) && $_FILES['image_' . $fileCounter]['error'] == 0) {
					// Retrieve the upload date from the postmeta (assuming it's stored in '_wp_attachment_metadata')
					$upload_data = wp_get_attachment_metadata($post_id);
					$file_path = $upload_data['file']; // Example: '2024/09/filename.png'

					// Extract the year and month from the original file path
					$path_parts = pathinfo($file_path);
					$year_month = explode('/', $path_parts['dirname']); // ['2024', '09']
					$year = $year_month[0];
					$month = $year_month[1];

					// Create the target directory in wpoven_optimized_images folder
					$targetDir = $upload_dir['basedir'] . "/wpoven_optimized_images/{$year}/{$month}";

					// Check if the directory exists, and create it if not
					if (!$this->wp_filesystem->is_dir($targetDir)) {
						$$this->wp_filesystem->mkdir($targetDir, FS_CHMOD_DIR); // Create the directory recursively if it doesn't exist
					}

					// Set the target file path
					$target_file = $targetDir . '/' . $file_name;

					// Check for the key to process the original or resized files
					if ($key == 'original') {
						$return_array['key'][] = $key;
						if ($post_id) {
							$return_array['post_id'][] = $post_id;
							// Update the original image size and optimized image size
							$original_file_path = get_attached_file($post_id); // Get original file path
							$image_size['original_size'] = filesize($original_file_path);
							$image_size['optimized_size'] = $_FILES['image_' . $fileCounter]['size'];

							// Update post meta with optimization details
							update_post_meta($post_id, 'wpoven_optimized_status', 1);
							update_post_meta($post_id, 'wpoven_optimized_image_size', $image_size);
						} else {
							$return_array['error_msg'] = __('Media ID not found', 'WPOven image optimization');
						}
					}

					// Move the uploaded file to the target directory
					if (wp_handle_upload($_FILES['image_' . $fileCounter]['tmp_name'], $target_file)) {
						$return_array['co'][] = $_POST;
						$return_array['success_msg'] = __('Uploaded successfully', 'WPOven image optimization');
					} else {
						$return_array['error_msg'] = __('Failed to upload', 'WPOven image optimization');
					}
				}
			}

			$return_array['status'] = 'ok';
		}

		die(wp_json_encode($return_array));
	}


	/**
	 * Retrieves thumbnail sizes available in WordPress.
	 * 
	 * @return array
	 */
	function get_wpoven_thumbnail_sizes()
	{
		// All image size names.
		$intermediate_image_sizes = get_intermediate_image_sizes();
		$intermediate_image_sizes = array_flip($intermediate_image_sizes);
		// Additional image size attributes.
		$additional_image_sizes   = wp_get_additional_image_sizes();

		// Create the full array with sizes and crop info.
		foreach ($intermediate_image_sizes as $size_name => $s) {
			$intermediate_image_sizes[$size_name] = array(
				'width'  => '',
				'height' => '',
				'crop'   => false,
				'name'   => $size_name,
			);

			if (isset($additional_image_sizes[$size_name]['width'])) {
				// For theme-added sizes.
				$intermediate_image_sizes[$size_name]['width'] = (int) $additional_image_sizes[$size_name]['width'];
			} else {
				// For default sizes set in options.
				$intermediate_image_sizes[$size_name]['width'] = (int) get_option("{$size_name}_size_w");
			}

			if (isset($additional_image_sizes[$size_name]['height'])) {
				// For theme-added sizes.
				$intermediate_image_sizes[$size_name]['height'] = (int) $additional_image_sizes[$size_name]['height'];
			} else {
				// For default sizes set in options.
				$intermediate_image_sizes[$size_name]['height'] = (int) get_option("{$size_name}_size_h");
			}

			if (isset($additional_image_sizes[$size_name]['crop'])) {
				// For theme-added sizes.
				$intermediate_image_sizes[$size_name]['crop'] = (int) $additional_image_sizes[$size_name]['crop'];
			} else {
				// For default sizes set in options.
				$intermediate_image_sizes[$size_name]['crop'] = (int) get_option("{$size_name}_crop");
			}
		}

		return $intermediate_image_sizes;
	}

	/**
	 * Formats and retrieves thumbnail sizes as a string with dimensions.
	 * 
	 * @return array
	 */
	function get_thumbnail_sizes()
	{
		static $sizes;

		if (isset($sizes)) {
			return $sizes;
		}

		$sizes = $this->get_wpoven_thumbnail_sizes();

		foreach ($sizes as $size_key => $size_data) {
			$sizes[$size_key] = sprintf('%s - %d &times; %d', esc_html(stripslashes($size_data['name'])), $size_data['width'], $size_data['height']);
		}

		return $sizes;
	}

	/**
	 * Retrieves data to show in a graph representing optimization status and sizes.
	 * 
	 * @return void
	 */
	function wpoven_get_data_to_show_graph()
	{
		global $wpdb;

		// Define your meta key
		$meta_key = 'wpoven_optimized_status'; // The meta key you're checking
		$size_meta_key = 'wpoven_optimized_image_size';
		// Query for optimized attachments (meta_value = 1)
		$optimized = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
		", $meta_key, '1', 'image%'));

		// Query for non-optimized attachments (meta_value = 0)
		$not_optimized = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
		", $meta_key, '0', 'image%'));

		// Query for attachments without the specified meta key
		$other_values = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE pm.post_id IS NULL
			AND p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
		", $meta_key, 'image%'));

		// Query to get the serialized size data for optimized images
		$sizes = $wpdb->get_results($wpdb->prepare("
			SELECT pm_size.post_id, pm_size.meta_value
			FROM $wpdb->postmeta pm_size
			INNER JOIN $wpdb->postmeta pm_status ON pm_size.post_id = pm_status.post_id
			WHERE pm_size.meta_key = %s
			AND pm_status.meta_key = %s
			AND pm_status.meta_value = %s
		", $size_meta_key, $meta_key, '1'), ARRAY_A);

		// Initialize totals
		$total_original_size = 0;
		$total_compressed_size = 0;

		// Process each result to extract sizes
		foreach ($sizes as $size) {
			// Handle possible unserialization errors
			$data = @unserialize($size['meta_value']);

			if ($data !== false && is_array($data)) {
				if (isset($data['original_size'])) {
					$total_original_size += (int) $data['original_size'];
				}
				if (isset($data['optimized_size'])) {
					$total_compressed_size += (int) $data['optimized_size'];
				}
			}
		}

		// Convert bytes to megabytes
		$bytes_in_mb = 1048576; // 1024 * 1024
		$total_original_size_mb = $total_original_size / $bytes_in_mb;
		$total_compressed_size_mb = $total_compressed_size / $bytes_in_mb;

		$data = array(
			'cs' => $total_original_size,
			'status'               => 'ok',
			'optimized'            => $optimized,
			'not_optimized'        => $not_optimized + $other_values,
			'total_original_size'  => $total_original_size_mb,
			'total_compressed_size' => $total_compressed_size_mb,
			'error'                => '0'
		);

		die(wp_json_encode($data));
	}

	/**
	 * Set up the image optimization settings fields.
	 *
	 * @return array $fields Configuration fields for image optimization settings.
	 */
	function wpoven_image_optimization_settings()
	{
		$fields = array();

		$auto_optimized = array(
			'id'      => 'auto-optimized',
			'type'    => 'switch',
			'off' => 'OFF',
			'on'  => 'ON',
			'title'   => 'Auto-Optimize images on upload',
			'desc'    => 'Automatically compress each image uploaded to WordPress for optimization.',
			'default' => false,
		);

		$nextgen_format = array(
			'id'      => 'next-gen-format',
			'type'    => 'button_set',
			'off'     => 'OFF',
			'on'      => 'WEBP',
			'title'   => 'Next-Gen format',
			'desc'    => 'Choose WebP for broad compatibility. The generation process begins automatically after saving the settings.',
			'options' => array(
				'webp' => 'WEBP',
				//'avif' => 'AVIF',
				'off'  => 'OFF',
			),
			'default' => 'off',
		);

		$nextgen_format_method = array(
			'id'         => 'nextgen_format_method',
			'type'       => 'radio',
			'title'      => 'Display images in Next-Gen format on the site',
			'options'    => array(
				'rewrite_rules' => 'Use rewrite rules',
				'picture_tags'  => 'Use ' . esc_html__("<picture>") . ' tags (preferred)',
			),
			'desc'       => 'The second option uses ' . esc_html__("<picture>") . ' tags instead of ' . esc_html__("<img>") . ', which is preferred but may break some themes—verify it works. For rewrite rules, include the generated conf/wpoven.conf from /uploads folder in the server’s configuration and restart the server.',
		);

		$optimize_label = array(
			'id'       => 'compression-label',
			'type'     => 'select',
			'title'      => 'Image compression label',
			'options' => array(
				'1'   => '0 (No Compression)',
				'.10' => '1',
				'.20' => '2',
				'.30' => '3',
				'.40' => '4',
				'.50' => '5',
				'.60' => '6',
				'.70' => '7',
				'.80' => '8',
				'.90' => '9 (Preferred)'
			),
			'default' => '.90',
			'desc'    => 'This dropdown allows users to choose a label that represents different levels of image compression, ranging from 0 to 9.'
		);

		$Files_optimization = array(
			'id'      => 'files-optimization',
			'type'    => 'checkbox',
			'title'   => 'You can choose to optimize different image sizes created by WordPress here.',
			'options' => $this->get_thumbnail_sizes(),
			'desc'    => 'Here, you can select which WordPress-generated image sizes to optimize.',
		);

		$fields[] = $auto_optimized;
		$fields[] = $nextgen_format;
		$fields[] = $nextgen_format_method;
		$fields[] = $optimize_label;
		//	$fields[] = $Files_optimization;

		return $fields;
	}

	/**
	 * Set up the bulk optimization fields.
	 *
	 * @return array $fields Configuration fields for bulk optimization.
	 */
	function wpoven_bulk_optimization()
	{
		$fields = array();

		$overview = array(
			'id'      => 'overview',
			'type'    => 'content',
			'mode'    => 'heading',
			'content' => 'Overview',

		);

		$divide = array(
			'id'   => 'divide_1',
			'type' => 'divide',
		);

		$graph = array(
			'id'       => 'optimized-graph',
			'type'     => 'raw',
			'full_width' => false,
			'title'    => __('Image Optimization Status', 'redux-framework-demo'),
			//'subtitle' => __('Subtitle text goes here.', 'redux-framework-demo'),
			//'desc'     => __('This is the description field for additional info.', 'redux-framework-demo'),
			'content'    => '<div id="graphics">
								<div>
									<div id="pie"><canvas id="optimizationImageSizeChart"></canvas></div>
									<div id="doughnut"><canvas id="optimizationImageChart"></canvas></div>
								</div>
						    </div>',
		);

		$modal = array(
			'id'       => 'modal',
			'type'     => 'raw',
			'full_width' => false,
			'content'    => '<!DOCTYPE html>
                            <html>
                                    <head>
                                    <title>Page Title</title>
                                    </head>
                                    <body>
										<div class="image-optimization-modal" id="image-optimization-modal">
											<div id="image-optimization-success-card" class="image-optimization-card">
												<div class="icon"></div>
												<h2 id="line1">SUCCESS</h2>
												<p>Processing...</p>
												<p class="modal-line">Image Optimization type: <strong id="line2" style="text-transform:uppercase"></strong></p>
												<p class="modal-line">Total Optimized Images: <strong id="line3"></strong></p>
												<p class="modal-line">Total Non-optimized Images: <strong id="line4"></strong></p>
												<br>
												<button class="image-optimization-close-modal" onclick="closeModal()">CLOSE</button>
											</div>
										</div>
								    </body>
                            </html>',
		);

		$optimize_media_file = array(
			'id'      => 'optimize-media-file',
			'type'    => 'content',
			'mode'    => 'heading',
			'content' => 'Optimize Your Media Files',
			'style'   => 'warning'
		);

		$divide2 = array(
			'id'   => 'divide_2',
			'type' => 'divide',
		);

		$media_lib = array(
			'id'      => 'media',
			'type'    => 'checkbox',
			'options'   => array(
				'media' => 'Media Library'
			),
			'desc'     => 'Select the checkbox for optimized all midea libary images file.'
		);

		$button_set = array(
			'id'       => 'button-group',
			'type'     => 'raw',
			'full_width' => false,
			'content'    => '<div id="bulk-action-buttons">
								<label id="wpoven-bulk-action"> Optimize ALL </label>
								<label id="wpoven-bulk-action-again"> Re-Optimize ALL </label>
							</div> '
		);

		$fields[] = $overview;
		$fields[] = $divide;
		$fields[] = $graph;
		$fields[] = $modal;
		$fields[] = $optimize_media_file;
		$fields[] = $divide2;
		$fields[] = $media_lib;
		$fields[] = $button_set;

		return $fields;
	}


	/**
	 * Set up the GUI for plugin.
	 *
	 */
	function setup_gui()
	{
		if (!class_exists('Redux')) {
			return;
		}
		$options = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
		$opt_name = WPOVEN_IMAGE_OPTIMIZATION_SLUG;

		Redux::disable_demo();

		$args = array(
			'opt_name'                  => $opt_name,
			'display_name'              => 'WPOven Image Optimization',
			'display_version'           => ' ',
			//'menu_type'                 => 'menu',
			'allow_sub_menu'            => true,
			//	'menu_title'                => esc_html__('WPOven Plugins', 'WPOven Image Optimization'),
			'page_title'                => esc_html__('WPOven Image Optimization', 'WPOven Image Optimization'),
			'disable_google_fonts_link' => false,
			'admin_bar'                 => false,
			'admin_bar_icon'            => 'dashicons-portfolio',
			'admin_bar_priority'        => 90,
			'global_variable'           => $opt_name,
			'dev_mode'                  => false,
			'customizer'                => false,
			'open_expanded'             => false,
			'disable_save_warn'         => false,
			'page_priority'             => 90,
			'page_parent'               => 'themes.php',
			'page_permissions'          => 'manage_options',
			'menu_icon'                 => plugin_dir_url(__FILE__) . '/image/logo.png',
			'last_tab'                  => '',
			'page_icon'                 => 'icon-themes',
			'page_slug'                 => $opt_name,
			'save_defaults'             => false,
			'default_show'              => false,
			'default_mark'              => '',
			'show_import_export'        => false,
			'transient_time'            => 60 * MINUTE_IN_SECONDS,
			'output'                    => false,
			'output_tag'                => false,
			// 'footer_credit'             => 'Please rate WPOven Image Optimization ★★★★★ on WordPress.org to support us. Thank you!',
			'footer_credit'             => ' ',
			'use_cdn'                   => false,
			'admin_theme'               => 'wp',
			'flyout_submenus'           => true,
			'font_display'              => 'swap',
			'hide_reset'                => true,
			'database'                  => '',
			'network_admin'           => '',
			'search'                    => false,
			'hide_expand'            => true,
		);

		Redux::set_args($opt_name, $args);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => esc_html__('Settings', 'WPOven Image Optimization'),
				'id'         => 'img-optimization-settings',
				'subsection' => false,
				'heading'    => '<div id="settings"><h3 class="redux-heading-text">Image Optimization Settings</h3></div>',
				'fields' => $this->wpoven_image_optimization_settings(),
			)
		);

		Redux::set_section(
			$opt_name,
			array(
				'title'      => esc_html__('Bulk Optimization', 'WPOven Image Optimization '),
				'id'         => 'bulk-optimization',
				'icon'       => 'el el-picture',
				'subsection' => true,
				'heading'    => '<div id="settings"><h3 class="redux-heading-text">Bulk Optimization</h3></div>',
				'fields' => $this->wpoven_bulk_optimization(),
			)
		);
	}

	/**
	 * Add a admin menu.
	 */
	function wpoven_image_optimization_menu()
	{
		add_menu_page('WPOven Plugins', 'WPOven Plugins', '', 'wpoven', 'manage_options', plugin_dir_url(__FILE__) . '/image/logo.png');
		add_submenu_page('wpoven', 'Image Optimization', 'Image Optimization', 'manage_options', WPOVEN_IMAGE_OPTIMIZATION_SLUG);
		add_submenu_page('admin.php?page=wpoven-image-optimization', 'Bulk Optimization', 'Bulk Optimization', 'manage_options', WPOVEN_IMAGE_OPTIMIZATION_SLUG . '-bulk-optimization', array($this, 'bulk_optimization'));
	}

	/**
	 * Hook to add the admin menu.
	 */
	public function admin_main(Wpoven_Image_Optimization $wpoven_image_optimization)
	{
		$this->_wpoven_image_optimization = $wpoven_image_optimization;
		add_action('admin_menu', array($this, 'wpoven_image_optimization_menu'));
		$this->setup_gui();
	}
}
