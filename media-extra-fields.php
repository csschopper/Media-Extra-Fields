<?php
/*
Plugin Name: Media Extra Fields
Author: Hemant 
Version: 1.0
Description: This plugins is developed for add field to media uploader while adding media to wordpress. 
Author Email: drhemant.raj02@gmail.com 
*/
@session_start();
if( !class_exists('MEF_Media_Extra_Fields')){
  Class MEF_Media_Extra_Fields
   {
	
	function __construct(){
		add_action( 'admin_menu', array($this,'mef_register_media_extra_page') );
		register_activation_hook(__FILE__, array(&$this, 'mef_plugin_install'));
		add_filter( 'attachment_fields_to_save', array(&$this,'mef_attachment_field_credit_save'), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array(&$this,'mef_attachment_field_credit'), 10, 2 );
	}	
	
	function mef_register_media_extra_page(){
		 add_menu_page( 'Media Extra Field', 'Media Extra Field', 'manage_options', 'mef-media-extra-field', array( $this, 'mef_menu_handle' ));
	}
	
	function mef_menu_handle() {
		return wp_iframe(array($this,"mef_media_fieldprocess"));
	}
	
	function mef_check_field_exists($field_ID)	{
		global $wpdb;
	        $prefix=$wpdb->prefix;
		$wpdb->query("SELECT * FROM ".$prefix."mef_media_extra_field WHERE fieldId = '".str_replace(' ','_',strtolower($field_ID))."'"	);
		return $wpdb->num_rows;
	}	
	
	function mef_media_fieldprocess(){
		$status = '';
		global $wpdb;
                $prefix=$wpdb->prefix;
		
		$fields = $this->mef_getExtraFields();
		if(isset($_POST['submit']))	{
			if(!empty ($_POST['fieldName'])){
				if(!$this->mef_check_field_exists($_POST['fieldName']))	{
					$wpdb->query(
						$wpdb->prepare("INSERT INTO ".$prefix."mef_media_extra_field (fieldType, fieldName, fieldId) values (%s, %s, %s)" , $_POST['fieldType'], $_POST['fieldName'],  str_replace(' ','_',strtolower($_POST['fieldName'])))
					);
					
					$status = " <span style='color:green'>Field Inserted</span>";
				}else{
					$status = " <span style='color:red'>Field already exists, kindly use another field name.</span>";
				}
			}else{
				$status =" <span style='color:red'>Please insert field name.</span>";
			}
			$_SESSION['Status'] = $status;
			 @header('location: ' . $_SERVER['REQUEST_URI']);
		}
		$options_type = array(
			'textbox' => 'TextBox',
			'checkbox' => 'CheckBox',
			'textarea' => 'TextArea',
		
		);
		?>
		<h2> <?php _e('Add field to media uploader '); ?> </h2>
		<form action="" method="post" enctype="multipart/form-data">
		<table>
			<tr>
				<td Colspan="2"><?php if(isset($_SESSION['Status'])) : echo $_SESSION['Status'];unset($_SESSION['Status']);  endif;	?></td>
			</tr>
			<tr>
				<td><?php _e('Select Field Type'); ?></td>
				<td>  <select name="fieldType" style="width:187px">
					<?php foreach($options_type  as $key => $value) :?>
						<option value="<?php echo $key; ?>"><?php echo $value;?> </option>
					<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php _e('Field Name'); ?></td>
				<td><input type="text" name="fieldName" style="width:186px;" /></td>
			</tr>
			
			<tr>
				<td Colspan="2"><input type="submit" class="button button-primary button-large" name="submit" value="Add"></td>
			</tr>
		</table>
		</form>
		
	  <?php if($fields) : ?>
	  <div class="updated" style="margin:20px 0;">
			<p><?php  _e('Use'); ?> <strong>mef_get_me_field(postID, Field ID) </strong><?php  _e('to get value of extra field.'); ?></p>
	  </div>
	  <fieldset>
		<legend><h2><?php _e('Added Fields'); ?></h2></legend>
		<table cellspacing="2" style="background-color: #F0F8FF;text-align: center;width: 500px;">
			<thead>
				<th>
					<?php  _e('SL No.'); ?>
				</th>
				<th>
					<?php  _e('Field Name'); ?>
				</th>
				<th>
					<?php  _e('Field Type'); ?>
				</th>
				<th>
					<?php  _e('Field ID'); ?>
				</th>
				<th>
					<?php  _e('Delete'); ?>
				</th>
			</thead>
			<tbody>
			<?php if(!empty($fields)){ foreach($fields as $key => $field) : ?>	
				<tr>
					<td><?php echo $key+1; ?></td>
					<td><?php echo $field->fieldName; ?></td>
					<td><?php echo $field->fieldType; ?></td>
					<td><?php echo $field->fieldId; ?></td>
					<td><a href="javascript::void(0)" onClick="mef_deleteField(<?php echo $field->ID;?>)">Delete</a></td>
				</tr>
				<?php endforeach;} ?>
			</tbody>
		</table>
	</fieldset>
	<?php endif;	
		
	}
	
	function mef_plugin_install() {
		global $wpdb;
	    $prefix=$wpdb->prefix;	  
	    if($wpdb->get_var("SHOW TABLES LIKE '".$prefix."mef_media_extra_field'") != $prefix.'mef_media_extra_field'){    
		    $sqlField="CREATE TABLE ".$prefix."mef_media_extra_field (
		   `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY Key,
  		   `fieldType` varchar(255) NOT NULL,
           `fieldName` varchar(255) NOT NULL,
           `fieldId` varchar(255) NOT NULL)";
		    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		    dbDelta($sqlField);
		}
	}	
	
	function mef_getExtraFields(){
		global $wpdb;
	    $prefix=$wpdb->prefix;	  
	    $fields = $wpdb->get_results("SELECT * FROM ".$prefix."mef_media_extra_field");
	    
	    return $fields;
	}	
	
	
	function mef_attachment_field_credit( $form_fields, $post ) {
	
		 $fields = $this->mef_getExtraFields();
                  if(!empty($fields)){
                     foreach($fields as $field) {
			 switch($field->fieldType){
					case 'textbox' : 
						$form_fields[$field->fieldId] = array(
						  'label' => __( $field->fieldName, 'mef' ),
						  'input' => 'text',
						  'value' => get_post_meta( $post->ID, $field->fieldId, true ),
						 );
					break;	
					
					case 'textarea' : 
						$form_fields[$field->fieldId] = array(
						  'label' => __( $field->fieldName, 'mef' ),
						  'input' => 'html',
						  'html' =>'<textarea id="attachments-' . $post->ID .'-'.$field->fieldId.'" name="attachments[' . $post->ID . ']['.$field->fieldId.']">'.get_post_meta( $post->ID, $field->fieldId, true ).'</textarea>',
						 );
					break;	
					
					case 'checkbox' : 
						$repeat = (bool)get_post_meta( $post->ID, $field->fieldId, true );
						$form_fields[$field->fieldId] = array(
						  'label' => __( $field->fieldName, 'mef' ),
						  'input' => 'html',
						  'html' => '<input type="checkbox" id="attachments-' . $post->ID .'-'.$field->fieldId.'" name="attachments[' . $post->ID . ']['.$field->fieldId.']" value="1"' . ( $repeat ? ' checked="checked"' : '' ) . ' />',
						 );
					break;	 
				}
		}	
           }
		return $form_fields;
	}
	
	
	 function mef_attachment_field_credit_save( $post, $attachment ) {
		 
		  $fields = $this->mef_getExtraFields();
                  if(!empty($fields)){
                    foreach($fields as $field) {
				switch($field->fieldType){
					case 'checkbox' :
							if ( isset( $attachment[$field->fieldId] ) )
								update_post_meta( $post['ID'], $field->fieldId,$attachment[$field->fieldId] );
							else
								update_post_meta( $post['ID'], $field->fieldId,0 );
						break;
						
					default:
						if ( isset( $attachment[$field->fieldId] ) )
						update_post_meta( $post['ID'], $field->fieldId,$attachment[$field->fieldId] );
						break;
				}	  
		   }	
                }
		  return $post;
		}
		
 }
}
new MEF_Media_Extra_Fields();

if(!function_exists('mef_get_me_field') ){
	function mef_get_me_field($post_id, $field)	{
			$args = array( 
					 'post_type' => 'attachment',
					 'numberposts' => -1, 
					 'post_parent' => $post_id, 
					 'post_mime_type' => array('image/png','image/jpg','image/jpeg')
			 ); 
			$attach_fields = get_posts($args);
			$attachement = $attach_fields;
			$result = get_post_meta($attachement[0]->ID, $field, true);
			return $result;
	}
}

add_action( 'admin_footer', 'mef_field_action_delete' );

function mef_field_action_delete() {
?>
<script type="text/javascript" >
$ = jQuery.noConflict();	
function mef_deleteField(id) 
{	
	var data = {
		action: 'mef_delete_media_field',
		field_id: id
	};
	$.post(ajaxurl, data, function(response) {
		alert(response);
		location.reload();
		
	});
}
</script>
<?php
}

add_action('wp_ajax_delete_media_field', 'mef_delete_media_field_callback');

function mef_delete_media_field_callback() {
	global $wpdb; // this is how you get access to the database
    if(!empty($_POST['field_id'])) {
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM ".$wpdb->prefix."mef_media_extra_field
				 WHERE ID = %d",$_POST['field_id'] 
				)
		);
		echo "Field Deleted";
	}else{
		echo "Could not Delete Field";
	}
	die(); // this is required to return a proper result
}
	
