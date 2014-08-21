<?php namespace ahb\custom;
/**
 * Plugin Name: Custom Stuff for AHB Site
 * Plugin URI: http://cahnrs.wsu.edu/communications
 * Description: Custom settings for AHB.
 * Version: 0.1
 * Author: CAHNRS Communicaitons, Danial Bleile
 * Author URI: http://cahnrs.wsu.edu/communications
 * License: GPL2
 */

define( __NAMESPACE__ . '\URL' , plugins_url( 'ahb-custom' ) );
define( __NAMESPACE__ . '\DIR' , plugin_dir_path( __FILE__ ) );
 
 class init_ahb_custom {
	 public $navs = array( 'primary-navigation-menu','secondary-navigation-menu' , 'tertiary-navigation-menu' );
	  public $navTitles = array(
		  'primary-navigation-menu' => 'Primary Navigation',
		  'secondary-navigation-menu' => 'Secondary Navigation',
		  'tertiary-navigation-menu' => 'Tertiary Navigation'
	  );
	  public $legacyNav = array(
		  'primary-navigation-menu' => 'primary-menu',
		  'secondary-navigation-menu' => 'secondary-menu',
		  'tertiary-navigation-menu' => 'tertiary-menu'
	  );
	 
	 public function __construct(){
		 //\add_filter( 'the_content', array( $this, 'filter_ahb_content' ) );
		 if ( is_admin() ) {
    		\add_action( 'load-post.php', array( $this , 'add_metabox' ) );
    		\add_action( 'load-post-new.php', array( $this , 'add_metabox' ) );
		}
		if ( !is_admin() ){
			\add_action( 'template_redirect', array( $this , 'ahb_redirect' ) );
		}
		\add_action( 'init', array( $this , 'create_post_types') );
		//add_action( 'add_meta_boxes', array( $this , 'add_ahb_metabox' ) );
		//add_action( 'save_post', array( $this, 'save_metaboxs' ) );
		
		\add_action( 'init' , array( $this , 'add_menus' ) );
		\add_filter( 'wp_nav_menu_items', array( $this , 'custom_menu_items' ), 10, 2 );
		if( is_admin() ){
			\add_action( 'load-post.php', array( $this , 'add_edit_nav') );
    		\add_action( 'load-post-new.php', array( $this , 'add_edit_nav') );
		}
		\add_action('init' , array( $this , 'add_image_sizes' ) );
		\add_filter( 'image_size_names_choose', array( $this , 'add_custom_image_sizes' ) );
		
		\add_action( 'admin_init', array( $this ,'add_taxes') );
		
		\add_action( 'after_setup_theme',    array( $this, 'add_support' ), 11 );
		
		\add_action( 'init' , array( $this ,'init_custom_filter' ) );
		
		\add_filter( 'the_content', array( $this, 'filter_ahb_content' ), 2 );
		
	 }
	 
	 public function init_custom_filter(){
		\add_filter( 'ahb_the_content', 'wptexturize'        );
		\add_filter( 'ahb_the_content', 'convert_smilies'    );
		\add_filter( 'ahb_the_content', 'convert_chars'      );
		\add_filter( 'ahb_the_content', 'wpautop'            );
		\add_filter( 'ahb_the_content', 'shortcode_unautop'  );
		\add_filter( 'ahb_the_content', 'prepend_attachment' );
	 }
	 
	 
	 public function add_support(){
		 add_theme_support( 'post-thumbnails');
	 }
	 
	 public function add_taxes() {  
		// Add tag metabox to page
		register_taxonomy_for_object_type('post_tag', 'page'); 
		// Add category metabox to page
		register_taxonomy_for_object_type('category', 'page');  
	}
	 
	 public function add_image_sizes(){
		 add_image_size( '4x3-medium', 400, 300, true );
		 add_image_size( '3x4-medium', 300, 400, true );
		 add_image_size( '16x9-medium', 400, 225, true );
		 add_image_size( '16x9-large', 800, 450, true );
	 }
	 
	 public function add_custom_image_sizes( $sizes ){
		 return array_merge( $sizes, array(
        	'4x3-medium' => '4x3-medium',
			'3x4-medium' => '3x4-medium',
			'16x9-medium' => '16x9-medium',
			'16x9-large' => '16x9-large',
    		) );
	 }
	 
	 public function add_edit_nav(){
		add_action( 'add_meta_boxes', array( $this , 'add_nav_metabox' ) );
		add_action( 'save_post', array( $this, 'save_nav' ) );
	}
	public function save_nav( $post_id ){
		foreach( $this->navs as $nav ){
			//if( !$this->check_user_permissions() ) return $post_id;
			if( isset( $_POST[ $nav ] ) ){
				$safeData = \sanitize_text_field( $_POST[$nav] );
				\update_post_meta( $post_id, $nav , $safeData );
			}
		}
	}
	
	public function add_nav_metabox( $post_type ){
		add_meta_box(
			'advanced_navigation_editor'
			,'Navigation Editor'
			,array( $this, 'render_navigation_metabox' )
			,$post_type
			,'side'
			,'core'
		);
	}
	
	public function custom_menu_items( $items, $args ){
		global $post;
		if( $post && $args->theme_location ){
			if ( in_array( $args->theme_location , $this->navs ) ) {
				
				
					$menu = $this->check_inherit_key( $post->ID , $args->theme_location , $this->legacyNav[ $args->theme_location ] );
					
					
					if( $menu ) {
						if( !is_numeric( $menu ) ){ $menu = \get_term_by('name', $menu, 'nav_menu');}
						$menuArgs = array(
							'echo' => 0,
							'menu' => $menu,
							'container_class' => false,
							'container' => '',
							'$menu_class' => false,
							'items_wrap' => '%3$s',
						);
						return wp_nav_menu( $menuArgs );
					}
					return $items;
			}
			return $items;
    	}
   		return $items;
	}
	
	public function check_inherit_key( $postid , $metaKey , $legacyKey = false ){
		if( !$postid ) return false;
		$meta = get_post_meta( $postid, $metaKey, true  );
		if( $meta ) { return $meta;
		} else {
			if( $legacyKey ){
				$legacyMeta = get_post_meta( $postid, $legacyKey, true  ); // LEGACY CHECK
				if( $legacyMeta ) return $legacyMeta;
			}
			// CHECK ANCESTORS
			$parents = \get_post_ancestors( $postid );
			if(!$parents ) return false;
			foreach( $parents as $parentid ){
				$pMeta = \get_post_meta( $parentid, $metaKey, true  );
				if( $pMeta ) return $pMeta;
				if( $legacyKey ){
					$legacyMeta = \get_post_meta( $parentid , $legacyKey, true  ); // LEGACY CHECK
					if( $legacyMeta ) return $legacyMeta; // LEGACY CHECK
				}
			}
			return false;
		}
	}
	
	public function add_menus(){
		register_nav_menu('primary-navigation-menu',__( 'Primary Navigation' ));
		register_nav_menu('secondary-navigation-menu',__( 'Secondary Navigation' ));
		register_nav_menu('tertiary-navigation-menu',__( 'Tertiary Navigation' ));
	}
	
	public function render_navigation_metabox( $post ){
		//$navBox = new metabox_navigation_view( $post->ID , $this->navs , $this->navTitles , $this->legacyNav );
		$menus = get_terms( 'nav_menu', array( 'hide_empty' => false ) );
		$menuArray = array();
		foreach( $menus as $menu ){
			$menuArray[] = array( 'name' => $menu->name , 'value' => $menu->term_id );
		}
    	foreach( $this->navs as $nav ){
			$hasValue = \get_post_meta( $post->ID ,$nav, true  );?>
        <h4><?php echo '<h4>'.$this->navTitles[$nav];?></h4>
        <select id="<?php echo $nav;?>" name="<?php echo $nav;?>" >
        	<option value="">Default/Inherit</option>
        <?php
			foreach( $menuArray as $menuItem ){;
				$selected = ( $hasValue && $menuItem['value'] == $hasValue )?  'selected': '';?>
            	<option value="<?php echo $menuItem['value'];?>" <?php echo $selected;?> ><?php echo $menuItem['name'];?></option>
			<?php	
			}
		?>
        </select>
    	<?php
		}
	}
	 
	 public function add_ahb_metabox( $post_type ){
		 add_meta_box(
			'video_meta',
			__( 'Video Settings' ),
			array( $this , 'render_video_metabox' ),
			'video',
			'normal',
			'high'
		);
		add_meta_box(
			'collaborator_meta',
			__( 'Collaborator Info' ),
			array( $this , 'render_col_metabox' ),
			'collaborators',
			'normal',
			'high'
		);
		$redirect_types = array( 'page','post');
		if( in_array( $post_type , $redirect_types ) ){
			add_meta_box(
				'ahb_redirect'
				,'Redirect To:'
				,array( $this, 'render_redirect_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);
		}
	 }
	 
	 public function save_metaboxes( $post_id ){
		  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
		 // Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
		$fields = array(
			'_redirect_to',
			'video_id',
			'Collaborator phone',
			'Collaborator email'
		);
		
		foreach( $fields as $field ){
			$mydata = sanitize_text_field( $_POST[$field] );
			// Update the meta field.
			update_post_meta( $post_id, $field, $mydata );
		}
	 }
	 
	 public function render_video_metabox( $post ){
		 $id_meta = get_post_meta( $post->ID , 'video_id', true )?>
		 <label>Video ID</label><br />
         <input type="text" name="video_id" value="<?php echo $id_meta;?>"/>
	 <?php }
	 
	 public function ahb_redirect(){
		 global $post;
		 $redirect_types = array( 'page','post');
		 if( in_array( $post_type , $redirect_types ) && ( is_singular('post') || is_singular( 'page') ) && is_main_query() ){
			 $meta = \get_post_meta( $post->ID , '_redirect_to' , true );
			 if( $meta ){
				 \wp_redirect( $meta , 302 );
			 }
		 }
	 }
	 
	 public function render_col_metabox( $post ){
		 $phone = \get_post_meta( $post->ID ,'Collaborator phone' , true );
		 $email = \get_post_meta( $post->ID , 'Collaborator email' , true );
		 echo '<label>Phone</label><br />';
         echo '<input type="text" name="Collaborator phone" value="'.$phone.'"/><br />';
		 echo '<label>Email</label><br />';
         echo '<input type="text" name="Collaborator email" value="'.$email.'"/>';
	 }
	 
	 public function add_metabox(){
		 \add_action( 'add_meta_boxes', array( $this, 'add_ahb_metabox' ) );
		//\add_action( 'add_meta_boxes', array( $this, 'add_redirect_box' ) );
		//\add_action( 'add_meta_boxes', array( $this, 'add_redirect_box' ) );
		\add_action( 'save_post', array( $this, 'save_metaboxes' ) );
	 }
	 
	/* public function add_redirect_box( $post_type ){
		  $post_types = array('post', 'page');     //limit meta box to certain post types
			add_meta_box(
				'ahb_redirect'
				,'Redirect To:'
				,array( $this, 'render_redirect_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);
	 }*/
	 
	 public function render_redirect_meta_box_content( $post ){
		 $redirect_meta = get_post_meta( $post->ID , '_redirect_to', true );
		 echo '<input type="text" style="width: 80%;" name="_redirect_to" value="'.$redirect_meta.'" />';
	 }
	 
	/* public function save_redirect( $post_id ){
		 if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
		 // Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
		$mydata = sanitize_text_field( $_POST['_redirect_to'] );
		// Update the meta field.
		update_post_meta( $post_id, '_redirect_to', $mydata );
	 }*/
	 
	 public function create_post_types(){
		 $this->ahb_feature_slides();
		 $this->ahb_collaborators();
		$item_array = array(
			array( 
				'post_type' => 'rich_text',
				'title' => 'Rich Text (+HTML)',
				'supports' => array('title','editor'),
				),
			array( 
				'post_type' => 'video',
				'title' => 'Videos',
				'supports' => array('title','excerpt','custom-fields','thumbnail'),
				)
		);
		foreach( $item_array as $item ){
			register_post_type( $item['post_type'],
				array(
					'labels' => array( 'name' => $item['title'], 'singular_name' => $item['title'] ),
					'public' => true,
					'has_archive' => true,
					'rewrite' => array('slug' => $item['post_type']),
					'supports' => $item['supports'],
					)
				);
		}
	}
	
	//*********************
	
/* NEED A CUSTOM POST TYPE FOR THE PROFILES */
public function ahb_collaborators() {
	
	$labels = array(
		'name'               => _x( 'Collaborators', 'post type general name' ),
		'singular_name'      => _x( 'Collaborator', 'post type singular name' ),
		'add_new'            => _x( 'Add New', 'collaborator' ),
		'add_new_item'       => __( 'Add New Collaborator' ),
		'edit_item'          => __( 'Edit Collaborator' ),
		'new_item'           => __( 'New Collaborator' ),
		'all_items'          => __( 'All Collaborators' ),
		'view_item'          => __( 'View Collaborator' ),
		'search_items'       => __( 'Search Collaborators' ),
		'not_found'          => __( 'No collaborators found' ),
		'not_found_in_trash' => __( 'No collaborators found in the Trash' ), 
		'parent_item_colon'  => '',
		'menu_name'          => 'Collaborators'
		);
	
	$args = array(
		'labels'        	=> $labels,
		'description'   	=> 'Collaborators are profiles of associates and contributors found throughout the AHB website',
		'public'        	=> true,
		'show_in_nav_menus' => false,
		'menu_position'		=> 20,
		'supports'      	=> array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'has_archive'   	=> true,
		'taxonomies'		=> array('category'),
		);
	register_post_type('collaborators', $args );	
	}
public function ahb_feature_slides() {
	
	$labels = array(
		'name'               => _x( 'Feature Slides', 'post type general name' ),
		'singular_name'      => _x( 'Feature Slide', 'post type singular name' ),
		'add_new'            => _x( 'Add New', 'slide' ),
		'add_new_item'       => __( 'Add New Slide' ),
		'edit_item'          => __( 'Edit Slide' ),
		'new_item'           => __( 'New Slide' ),
		'all_items'          => __( 'All Slides' ),
		'view_item'          => __( 'View Slide' ),
		'search_items'       => __( 'Search Slides' ),
		'not_found'          => __( 'No slides found' ),
		'not_found_in_trash' => __( 'No slides found in the Trash' ), 
		'parent_item_colon'  => '',
		'menu_name'          => 'Feature Slides'
		);
	
	$args = array(
		'labels'        	=> $labels,
		'description'   	=> 'Contains feature slides shown throughout the website',
		'public'        	=> true,
		'show_in_nav_menus' => false,
		'menu_position'		=> 20,
		'supports'      	=> array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'has_archive'   	=> true,
		'taxonomies'		=> array('category'),
		);
	register_post_type('feature_slides', $args );	
	}
	 
	 
	 
	 public function filter_ahb_content( $content ){
		 global $post;
		 if( 'collaborators' == $post->post_type ){
			 $meta = get_post_meta( $post->ID );
			 $cats = wp_get_post_categories( $post->ID );
			 
			 $cat_array = array();
			 foreach( $cats as $categories ){
				 $cat_data = get_category( $categories );
				 $cat_array[] = $cat_data->slug;
			 }
			 $address = ( $meta['Collaborator email'] )? implode( $meta['Collaborator email'] ): false;
			 $phone = ( $meta['Collaborator phone'] )? implode( $meta['Collaborator phone'] ): false;
			 $email =  ( $address )? '<a href="mailto:'.$address.'">'.$address.'</a><br />' : ''; 
			 $phone_full =  ( $phone )? $phone.'<br />' : '&nbsp;<br />';
			 $projects = array();
			 $projects[] = '<div class="icon-set '.implode(' ', $cat_array ).'" >';
             $projects[] = '<a href="/feedstock" class="project-icon feedstock" ><img src="'.URL.'/images/feedstock.jpg" /></a>';
             $projects[] = '<a href="/conversion" class="project-icon conversion" ><img src="'.URL.'/images/conversion.jpg" /></a>';
             $projects[] = '<a href="/sustainability" class="project-icon sustainability" ><img src="'.URL.'/images/sustainability.jpg" /></a>';
             $projects[] = '<a href="/education" class="project-icon education" ><img src="'.URL.'/images/education.jpg" /></a>';
             $projects[] = '<a href="/extension" class="project-icon extension" ><img src="'.URL.'/images/extension.jpg" /></a>';
             $projects[] = '</div> ';
			 return implode( $projects ).$email.$phone_full.$content;
		 }
		 else if( 'video' == $post->post_type ){
			 $video = get_post_meta( $post->ID , 'video_id', true );
			 ob_start();?>
			 <article class="post-info">
			 <?php if( $video ):?>
             	<iframe width="100%" height="400" src="//www.youtube.com/embed/<?php echo $video;?>?autoplay=1 " frameborder="0" allowfullscreen>'
			 	</iframe>
             <?php endif;?>
             <h1 class="video-title"><?php echo $post->post_title;?></h1>
             <style type="text/css">h1.basic {display: none;} h1.video-title {display: block;}</style>
             <div class="video-content">
             	<?php if( has_excerpt( $post->ID ) ):?>
             	<div class="video-excerpt" style="margin-bottom: 30px; font-weight: bold;">
                	<?php the_excerpt();?>
                </div>
                <?php endif;?>
             	<div class="video-excerpt" style="margin-bottom: 30px; font-weight: bold;">
                	<?php echo \apply_filters('ahb_the_content', $post->post_content );?>
                </div>
             </div>
             </article>
			<?php
			 return ob_get_clean();
		 } 
		 else {
			 return $content;
		 }
	 }
	 
	 
 }
 
 $init_ahb = new init_ahb_custom();
 ?>