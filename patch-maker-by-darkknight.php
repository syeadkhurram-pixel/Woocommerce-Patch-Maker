<?php
/**
 * Plugin Name: Patch Maker By Nexstair
 * Description: Creates a Custom system to handle patch selections
 * Version: 1.0
 * Author: Nexstair
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



register_activation_hook( __FILE__, 'create_patch_selections_table' );

function create_patch_selections_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'patch_selections';
    $table_name2 = $wpdb->prefix . 'patch_options';
    
    
    $charset_collate = $wpdb->get_charset_collate();


    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        ID mediumint(9) NOT NULL AUTO_INCREMENT,
        patch_type varchar(255) NOT NULL,
        patch_shape varchar(255) NOT NULL,
        patch_color varchar(255) NOT NULL,
        thumbnail_id bigint(20) UNSIGNED,
        PRIMARY KEY  (ID)
    ) $charset_collate;";


    $sql2 = "CREATE TABLE IF NOT EXISTS $table_name2 (
        ID mediumint(9) NOT NULL AUTO_INCREMENT,
        type varchar(255) NOT NULL,
        `key` varchar(255) NOT NULL,
        thumb varchar(255) NOT NULL,
        PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql1);
    dbDelta( $sql2 );


}


function my_plugin_enqueue_scripts() {
      wp_register_script(
        'imageProcessor', 
        plugin_dir_url( __FILE__ ) . 'js/imageProcessor.js', 
        array(), 
        '1.0.0', 
        true 
    );
    wp_register_script(
        'selection_handler',
        plugin_dir_url( __FILE__ ) . 'js/handle-selections.js',
        array( 'jquery' ), 
        '1.0.0', 
        true 
    );
	
	

    wp_enqueue_script( 'imageProcessor' );
    wp_enqueue_script( 'selection_handler' );
	wp_enqueue_style( 'swatches___css', plugin_dir_url( __FILE__ ) . 'css/style.css' );
	
}

add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_scripts' );

add_action( 'admin_menu', 'patch_maker_add_admin_menu' );

function patch_maker_add_admin_menu() {
    add_menu_page( 'Patch Options', 'Patch Options', 'manage_options', 'patch-options', 'patch_options_page' );
    add_menu_page(  'Patch Selections', 'Patch Selections', 'manage_options', 'patch-selections', 'patch_selections_page' );
    //add_submenu_page( 'patch-options', 'Patch Selections', 'Patch Selections', 'manage_options', 'patch-selections', 'patch_selections_page' );
}

function patch_options_page() {
    global $wpdb;

    if ( isset( $_POST['submit_patch_option'] ) ) {
        $patch_type = sanitize_text_field( $_POST['patch_type'] );
        $patch_shape_name = sanitize_text_field( $_POST['patch_shape_name'] );
		
		
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
		
		 $uploadedfile = $_FILES['shape_thumbnail'];
         $upload_overrides = array( 'test_form' => false );
		
        $upload_dir = wp_upload_dir();
        $patch_upload_dir = $upload_dir['basedir'] . '/patch_uploads/options_attachments';
        if ( ! file_exists( $patch_upload_dir ) ) {
            mkdir( $patch_upload_dir, 0755, true );
        }

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $attachment = array(
                'guid'           => $upload_dir['url'] . '/patch_uploads/options_attachments/' . basename( $movefile['file'] ),
                'post_mime_type' => $movefile['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            );

            $thumbnail_id = wp_insert_attachment( $attachment, $movefile['file'] );

            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            $attach_data = wp_generate_attachment_metadata( $thumbnail_id, $movefile['file'] );
            wp_update_attachment_metadata( $thumbnail_id, $attach_data );


        $wpdb->insert(
            $wpdb->prefix . 'patch_options',
            array(
                'type'  => $patch_type,
                'key' => ucfirst(strtolower($patch_shape_name)),
                'thumb' => $thumbnail_id,
            )
        );
        echo '<div class="updated"><p>Patch option added successfully.</p></div>';
		}
		else{
			    
            echo '<div class="error"><p>Failed to upload file.</p></div>';
        
		}
    }

    $patch_options = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}patch_options" );

    
    ?>
    <div class="wrap">
        <h1>Patch Options</h1>
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label for="patch_type">Option Type</label></th>
					<td><select id="patch_type" name="patch_type" required>
						<option selected disabled value>Select Type</option>
						<option value="Shape">Shape</option>
						<option value="Color">Color</option>
						<option value="Leather">Leather</option>
						</select>
					</td>
                </tr>
                <tr>
                    <th><label for="patch_shape">Patch Shape Name</label></th>
                    <td><input placeholder="arrow, circle, diamond etc." type="text" id="patch_shape_name" name="patch_shape_name" required /></td>
                </tr>
               <tr>
                    <th><label for="patch_thumbnail">Patch Shape Image</label></th>
                    <td><input type="file" id="shape_thumbnail" name="shape_thumbnail" /></td>
                </tr>
            </table>
            <p><input type="submit" name="submit_patch_option" class="button-primary" value="Add Patch Option"></p>
        </form>

        <h2>Existing Patch Options</h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Shape Name</th>
                    <th>Shape Thumbnail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $patch_options as $option ) : ?>
                    <tr>
                        <td><?php echo esc_html( $option->ID ); ?></td>
                        <td><?php echo esc_html( $option->type ); ?></td>
                        <td><?php echo esc_html( $option->key ); ?></td>
                        <td>
							<?php 
                            if ( $option->thumb ) {
                                echo wp_get_attachment_image( $option->thumb, 'thumbnail' );
                            } else {
                                echo 'No image';
                            }
                            ?>
						</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function patch_selections_page() {
    global $wpdb;

    if ( isset( $_POST['submit_patch_selection'] ) ) {
        $patch_type = sanitize_text_field( $_POST['patch_type'] );
        $patch_shape = sanitize_text_field( $_POST['patch_shape'] );
        $patch_color = sanitize_text_field( $_POST['patch_color'] );

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        
        $uploadedfile = $_FILES['patch_thumbnail'];
        $upload_overrides = array( 'test_form' => false );
        
        $upload_dir = wp_upload_dir();
        $patch_upload_dir = $upload_dir['basedir'] . '/patch_uploads';
        if ( ! file_exists( $patch_upload_dir ) ) {
            mkdir( $patch_upload_dir, 0755, true );
        }

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $attachment = array(
                'guid'           => $upload_dir['url'] . '/patch_uploads/' . basename( $movefile['file'] ),
                'post_mime_type' => $movefile['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            );

            $thumbnail_id = wp_insert_attachment( $attachment, $movefile['file'] );

            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            $attach_data = wp_generate_attachment_metadata( $thumbnail_id, $movefile['file'] );
            wp_update_attachment_metadata( $thumbnail_id, $attach_data );

            $wpdb->insert(
                $wpdb->prefix . 'patch_selections',
                array(
                    'patch_type'  => $patch_type,
                    'patch_shape' => $patch_shape,
                    'patch_color' => $patch_color,
                    'thumbnail_id' => $thumbnail_id,
                )
            );

            echo '<div class="updated"><p>Patch selection added successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to upload file.</p></div>';
        }
    }

    $patch_selections = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}patch_selections" );

    ?>
    <div class="wrap">
        <h1>Patch Selections</h1>
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label for="patch_type">Patch Type</label></th>
                    <td><input type="text" id="patch_type" name="patch_type" required /></td>
                </tr>
                <tr>
                    <th><label for="patch_shape">Patch Shape</label></th>
                    <td><input type="text" id="patch_shape" name="patch_shape" required /></td>
                </tr>
                <tr>
                    <th><label for="patch_color">Patch Color</label></th>
                    <td><input type="text" id="patch_color" name="patch_color" required /></td>
                </tr>
                <tr>
                    <th><label for="patch_thumbnail">Patch Thumbnail</label></th>
                    <td><input type="file" id="patch_thumbnail" name="patch_thumbnail" /></td>
                </tr>
            </table>
            <p><input type="submit" name="submit_patch_selection" class="button-primary" value="Add Patch Selection"></p>
        </form>

        <h2>Existing Patch Selections</h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Shape</th>
                    <th>Color</th>
                    <th>Thumbnail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $patch_selections as $selection ) : ?>
                    <tr>
                        <td><?php echo esc_html( $selection->ID ); ?></td>
                        <td><?php echo esc_html( $selection->patch_type ); ?></td>
                        <td><?php echo esc_html( $selection->patch_shape ); ?></td>
                        <td><?php echo esc_html( $selection->patch_color ); ?></td>
                        <td>
                            <?php 
                            if ( $selection->thumbnail_id ) {
                                echo wp_get_attachment_image( $selection->thumbnail_id, 'thumbnail' );
                            } else {
                                echo 'No image';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}


add_action( 'add_meta_boxes', 'patch_maker_add_meta_box' );

function patch_maker_add_meta_box() {
    add_meta_box(
        'patch_selections_meta_box', 
        'Patch Selections',          
        'patch_maker_meta_box_callback',
        'product',                  
        'side'                      
    );

    add_meta_box(
        'patch_display_meta_box',   
        'Display Patch Selections',  
        'patch_display_meta_box_callback', 
        'product',                   
        'side'                       
    );
}

function patch_maker_meta_box_callback( $post ) {
    global $wpdb;

    $selected_patch_selection = get_post_meta( $post->ID, '_selected_patch_selection', true );

    $patch_selections = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}patch_selections GROUP BY patch_type" );

    $grouped_patch_selections = [];
    foreach ( $patch_selections as $selection ) {
        $grouped_patch_selections[ $selection->patch_type ][] = $selection;
    }

    wp_nonce_field( 'patch_selection_save', 'patch_selection_nonce' );
    ?>
    <label for="patch_selection">Select a Patch:</label>
    <select name="patch_selection" id="patch_selection" class="widefat">
        <option value="">Select a Patch</option>
        <?php
        foreach ( $grouped_patch_selections as $type => $selections ) {
            
            foreach ( $selections as $selection ) {
                ?>
                <option value="<?php echo esc_attr( $selection->patch_type ); ?>" <?php selected( $selected_patch_selection, $selection->patch_type ); ?>>
                    <?php echo esc_html( $selection->patch_type ); ?>
                </option>
                <?php
            }
            echo '</optgroup>';
        }
        ?>
    </select>
    <?php
}

function patch_display_meta_box_callback( $post ) {
    $display_option = get_post_meta( $post->ID, '_display_patch_selections', true );

	$render_option = get_post_meta( $post->ID, '_render_patch_selections', true );
	
	$logosizing_option = get_post_meta( $post->ID, '_logo_sizing', true );
	
    wp_nonce_field( 'patch_display_save', 'patch_display_nonce' );
    ?>
    <p>
        <label for="display_patch_selections">
            <input type="checkbox" name="display_patch_selections" id="display_patch_selections" value="1" <?php checked( $display_option, '1' ); ?> />
            Enable Display of Patch Selections on Front End
        </label>
		<br>
		<label for="render_patch_selections">
            <input type="checkbox" name="render_patch_selections" id="render_patch_selections" value="1" <?php checked( $render_option, '1' ); ?> />
            Enable Rendering of Patch Selections on Hats
        </label>
		<br>
		<label for="_logo_sizing">
            <input type="checkbox" name="_logo_sizing" id="_logo_sizing" value="1" <?php checked( $logosizing_option, '1' ); ?> />
            Enable Logo Sizing Option
        </label>
    </p>
    <?php
}


add_action( 'save_post', 'patch_maker_save_meta_box_data' );

function patch_maker_save_meta_box_data( $post_id ) {
    if ( isset( $_POST['patch_selection_nonce'] ) && ! wp_verify_nonce( $_POST['patch_selection_nonce'], 'patch_selection_save' ) ) {
        return $post_id;
    }

    if ( isset( $_POST['patch_display_nonce'] ) && ! wp_verify_nonce( $_POST['patch_display_nonce'], 'patch_display_save' ) ) {
        return $post_id;
    }


    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }

    if ( 'product' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
    } else {
        return $post_id;
    }

    if ( isset( $_POST['patch_selection'] ) ) {
        $selected_patch_selection = sanitize_text_field( $_POST['patch_selection'] );
        update_post_meta( $post_id, '_selected_patch_selection', $selected_patch_selection );
    }

    $display_patch_selections = isset( $_POST['display_patch_selections'] ) ? '1' : '0';
    update_post_meta( $post_id, '_display_patch_selections', $display_patch_selections );
	
	 $render_patch_selections = isset( $_POST['render_patch_selections'] ) ? '1' : '0';
    update_post_meta( $post_id, '_render_patch_selections', $render_patch_selections );
	
	 $logo_sizing_option = isset( $_POST['_logo_sizing'] ) ? '1' : '0';
    update_post_meta( $post_id, '_logo_sizing', $logo_sizing_option );
}

add_action('woocommerce_before_add_to_cart_button', 'add_image_field');
function add_image_field(){
	
	 global $post;
	

    echo '<p class="form-row form-row-wide">';
    echo '<label for="patch_image">' . __('Upload Logo', 'textdomain') . '</label>';
    echo '<input type="file" id="patch_image" name="patch_image" accept="image/*" />';
    echo '</p>';
	
	$logo_sizing = get_post_meta( $post->ID, '_logo_sizing', true );
	if($logo_sizing === '1'){
	echo '<div class="select-field logo_sizing">';
    woocommerce_form_field( 'logo_sizing', array(
        'type'          => 'select',
        'label'         => __('Logo Sizing', 'textdomain'),
        'required'      => true,
        'class'         => array('form-row-wide'),
        'options'       => array(
            ''          => __('Choose an option', 'textdomain'),
            'option_1'  => __('*Size to fit (Our experts will size artwork)', 'your-textdomain'),
            'option_2'  => __('*Size logo as shown in Preview', 'your-textdomain'),
        ),
    ));
    echo '</div>';
	}
	echo '<div class="name-field ">';
    woocommerce_form_field( 'name_field', array(
        'type'          => 'textarea',
        'label'         => __('Personalized Text', 'textdomain'),
        'required'      => false,
        'class'         => array('form-row-wide'),
        'placeholder'   => __('Enter Text here' , 'textdomain'),
    ));
    echo '</div>';
}


function validate_image_data($passed, $product_id, $quantity) {
	if (isset($_POST['image_data']) && empty($_POST['image_data'])) {
		wc_add_notice(__('Please Make a Valid Selection for Patch.'), 'error');
		$passed = false;
	}
	
	if(isset($_POST['logo_sizing']) && empty($_POST['logo_sizing'])){
		wc_add_notice(__('Please choose logo sizing option.'), 'error');
		$passed = false;
	}
	
	return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'validate_image_data', 10, 3);

function save_image_data_custom_field($cart_item_data, $product_id) {
	if (isset($_POST['image_data'])) {
		$image_url = upload_patch_image($_POST['image_data']);
		$cart_item_data['image_data'] = $image_url;
	}
	if (isset($_FILES['patch_image']) && !empty($_FILES['patch_image']['name'])) {
        $upload = wp_upload_bits($_FILES['patch_image']['name'], null, file_get_contents($_FILES['patch_image']['tmp_name']));
        if (!$upload['error']) {
            $cart_item_data['patch_image'] = $upload['url'];
        }
    }

    if (isset($_POST['patch-color'])) {
        $cart_item_data['patch_color'] = $_POST['patch-color'];
    }

    if (isset($_POST['patch-shape'])) {
        $cart_item_data['patch_shape'] = $_POST['patch-shape'];
    }
	
	if(isset($_POST['logo_sizing'])){
		$options = array(
			'option_1' => 'Size to fit (Our experts will size artwork)',
			'option_2' => 'Size logo as shown in Preview'
		);
		
		$cart_item_data['logo_sizing'] = $options[$_POST['logo_sizing']];
	}
	
	if(isset($_POST['name_field'])){
		$cart_item_data['name_field'] = $_POST['name_field'];
	}
	
	return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'save_image_data_custom_field', 10, 2);

function display_image_data_custom_fields_in_cart($item_data, $cart_item) {
	if (isset($cart_item['image_data']) && $cart_item['image_data']) {
		$item_data[] = array(
			'key'   => __('Image'),
			'value' => '<img src="'.$cart_item['image_data'].'" />',
		);
	}
	if (isset($cart_item['patch_image'])) {
        $item_data[] = array(
            'name' => __('Logo', 'textdomain'),
            'value' => '<img src="'.esc_url($cart_item['patch_image']).'" style="max-width:100px;">'
        );
    }

    if (isset($cart_item['patch_color'])) {
        $item_data[] = array(
            'name' => __('Patch Color', 'textdomain'),
            'value' => sanitize_text_field($cart_item['patch_color'])
        );
    }

    if (isset($cart_item['patch_shape'])) {
        $item_data[] = array(
            'name' => __('Patch Shape', 'textdomain'),
            'value' => sanitize_text_field($cart_item['patch_shape'])
        );
    }
	
	 if (isset($cart_item['logo_sizing'])) {
        $item_data[] = array(
            'name' => __('Logo Sizing', 'textdomain'),
            'value' => sanitize_text_field($cart_item['logo_sizing'])
        );
    }
	
	if (isset($cart_item['name_field'])) {
        $item_data[] = array(
            'name' => __('Name Field', 'textdomain'),
            'value' => sanitize_text_field($cart_item['name_field'])
        );
    }
	
	return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_image_data_custom_fields_in_cart', 10, 2);

function replace_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key) {
    if (isset($cart_item['image_data']) && $cart_item['image_data']) {
        $thumbnail = '<img src="' . esc_url($cart_item['image_data']) . '" alt="' . esc_attr__('Custom Image', 'your-textdomain') . '" />';
    }
    return $thumbnail;
}
add_filter('woocommerce_cart_item_thumbnail', 'replace_cart_item_thumbnail', 10, 3);




function add_image_data_meta_to_order_items($item, $cart_item_key, $values, $order) {
	if (isset($values['image_data'])) {
		$item->add_meta_data(__('Image'), $values['image_data'], true);
	}
	if (isset($values['patch_image'])) {
        $item->add_meta_data(__('Logo', 'textdomain'), esc_url($values['patch_image']));
    }
    if (isset($values['patch_color'])) {
        $item->add_meta_data(__('Patch Color', 'textdomain'), sanitize_text_field($values['patch_color']));
    }
    if (isset($values['patch_shape'])) {
        $item->add_meta_data(__('Patch Shape', 'textdomain'), sanitize_text_field($values['patch_shape']));
    }
	if (isset($values['logo_sizing'])) {
        $item->add_meta_data(__('Logo Sizing', 'textdomain'), sanitize_text_field($values['logo_sizing']));
    }
	if (isset($values['name_field'])) {
        $item->add_meta_data(__('Name Field', 'textdomain'), sanitize_text_field($values['name_field']));
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'add_image_data_meta_to_order_items', 10, 4);


function get_data($type){
	global $wpdb;
	
        $patch_selection = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}patch_selections WHERE patch_type = %s",
            $type
        ) );
	
	if($patch_selection){
		foreach ( $patch_selection as $patch ) {
               $thumbnail_url = wp_get_attachment_url( $patch->thumbnail_id );
  				$patch->thumbnail_url = $thumbnail_url;
         }
	}
	
	return $patch_selection;
	
} 


function display_patch_selections() {
    global $post, $wpdb;

    $display_option = get_post_meta( $post->ID, '_display_patch_selections', true );
	$render_option = get_post_meta( $post->ID, '_render_patch_selections', true );
	
	$selected_patch_selection = get_post_meta( $post->ID, '_selected_patch_selection', true );

	echo '<div class="discount-pricing">';
			echo do_shortcode('[yith_ywdpd_quantity_table]');
		echo '</div>';
	
    if ( $display_option === '1' ) {
        
        $selected_patch_id = get_post_meta( $post->ID, '_selected_patch_selection', true );

        $patch_selection = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}patch_selections WHERE patch_type = %d",
            $selected_patch_id
        ) );
		
		wp_localize_script( 'selection_handler', 'data', array(
			'patch_data' => get_data($selected_patch_id),
		));

        if ( $patch_selection ) {
            
            if($selected_patch_selection=="Color"){
				$patches = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}patch_options WHERE type = %s",
					"Color"
				) );
			}
			else{
				$patches = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}patch_options WHERE type = %s",
					"Leather"
				) );
			}
            

            if ( $patches ) {
                echo '<h5>Select Leather</h5>
				
				<div class="patch-selections">';
			
                foreach ( $patches as $patch ) {
                    $thumbnail_url = wp_get_attachment_url( $patch->thumb );
                    ?>
	<input id="patch<?php echo $patch->ID?>" type="radio" name="patch-color" value="<?php echo $patch->key; ?>">
                    <label for="patch<?php echo $patch->ID?>" class="patch-item patch-color" data-color="<?php echo $patch->key; ?>">
                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $patch->key ); ?>" /> 
						
                    </label>
				
                    <?php
                }
                echo '</div>';
				
            }
			

			$shape = "Shape";
			
			if($selected_patch_selection=="Color"){
				$color_patch_sels_dk = "Crest,Diamond,Arrow,Circle,Georgia,Rectangle,Oval,Texas,Hexagon,Shield";

				
				$shapes_array = explode(',', $color_patch_sels_dk);
				$placeholders = implode(',', array_fill(0, count($shapes_array), '%s'));

				$query = $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}patch_options WHERE type = %s AND `key` IN ($placeholders)",
					array_merge([$shape], $shapes_array)
				);

				$patch_shapes = $wpdb->get_results($query);

			}
			else if($selected_patch_selection=="Stitch"){
				$stitch_patch_sels_dk = "Diamond,Hexagon,Oval,Arrow,Pocket,Rectangle,Circle,Square,Triangle";

				$shapes_array = explode(',', $stitch_patch_sels_dk);
				$placeholders = implode(',', array_fill(0, count($shapes_array), '%s'));

				$query = $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}patch_options WHERE type = %s AND `key` IN ($placeholders)",
					array_merge([$shape], $shapes_array)
				);

				$patch_shapes = $wpdb->get_results($query);
			}
			else{
				$nostitch_patch_sels_dk = "Crest,Diamond,Georgia,Hexagon,Oval,Pocket,Rectangle,Circle,Shield,Square,Triangle,Texas";

				$shapes_array = explode(',', $nostitch_patch_sels_dk);
				$placeholders = implode(',', array_fill(0, count($shapes_array), '%s'));

				$query = $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}patch_options WHERE type = %s AND `key` IN ($placeholders)",
					array_merge([$shape], $shapes_array)
				);

				$patch_shapes = $wpdb->get_results($query);
			}
			
			
			

            if ( $patch_shapes ) {
                echo '<h5>Select Patch Shape</h5>
				
				<div class="patch-selections">';
				
                foreach ( $patch_shapes as $patch ) {
                    $thumbnail_url = wp_get_attachment_url( $patch->thumb );
                    ?>
					<input type="radio" id="shape_<?php echo $patch->key ?>" value="<?php echo $patch->key; ?>" name="patch-shape" >
                    <label for="shape_<?php echo $patch->key ?>" class="patch-item patch-shape" data-shape="<?php echo esc_html( $patch->key ); ?>">
                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $patch->key ); ?>" />
						
                    </label>
                    <?php
                }
                echo '</div>';

            }
			else{
				echo 'No Shapes';
			}
			
        }
	
    }
	
	if($render_option == '1'){
		
			woocommerce_form_field('image_data', array(
					'type'  => 'url',
					'class' => array('form-row-wide'),
					'label' => __('Base64/Data URL'),
			), '');
		
		wp_localize_script( 'selection_handler', 'is_render', "true");
	}
	else{
		wp_localize_script( 'selection_handler', 'is_render', "");
	}
	
}


add_action( 'woocommerce_before_single_variation', 'display_patch_selections' );


function upload_patch_image($image_b64){
	$upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['basedir'] . '/patch_uploads/';

        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
        }

        $base64_image = $image_b64;
        $base64_image = str_replace('data:image/png;base64,', '', $base64_image);
        $base64_image = str_replace(' ', '+', $base64_image);
        $image_data = base64_decode($base64_image);

        $filename = uniqid() . '.png';
        $filepath = $upload_path . $filename;

        file_put_contents($filepath, $image_data);

        $image_url = $upload_dir['baseurl'] . '/patch_uploads/' . $filename;
	
		return $image_url;
}

