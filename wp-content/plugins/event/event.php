<?php
   /*
   Plugin Name: Event
   Plugin URI: 
   Version: 1.2
   Author: 
   Author URI: 
   License: GPL2
   */

function event_list(){
   global $wpdb;

   $res = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS
      wp_posts.ID, wp_posts.post_title, wp_postmeta.meta_value, wp_posts.post_content
      FROM wp_posts 
      INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id )
         WHERE ( wp_postmeta.meta_key = 'event_date')
      AND wp_posts.post_type = 'events' 
      AND wp_posts.post_status = 'publish'
      GROUP BY wp_posts.ID 
      ORDER BY wp_postmeta.meta_value 
         ASC");
   ?>
   <table>
      <?php foreach($res AS $r){ 
         $event_url = get_post_meta($r->ID, 'event_url', true);
         $event_location = get_post_meta($r->ID, 'event_location', true);
         $cal = date('Ymd', strtotime($r->meta_value)) .'T'. date('Hi', strtotime($r->meta_value)). '00/' . date('Ymd', strtotime($r->meta_value)) .'T'. date('Hi', strtotime($r->meta_value)) . '00';
         ?>
      <tr>
         <td><a target="_blank" href="<?php echo $event_url; ?>"><?php echo $r->post_title; ?></a></td>
         <td><?php echo date('d.m.Y H:m A', strtotime($r->meta_value)); ?></td>
         <td><a target="_blank" href="https://calendar.google.com/calendar/u/0/r/eventedit?text=<?php echo $r->post_title; ?>&dates=<?php echo $cal; ?>&details=<?php echo $r->post_content.' '.$event_url; ?>&location=<?php echo $event_location ?>&sf=true&output=xml&pli=1">Calendar</a></td>

      </tr>
   <?php } ?>
   </table>
   <?php
}


add_shortcode('event_list', 'event_list'); 

function create_posttype() {
register_post_type( 'events',

array(
  'labels' => array(
   'name' => __( 'Events' ),
   'singular_name' => __( 'Events' )
  ),
  'public' => true,
  'has_archive' => false,
  'rewrite' => array('slug' => 'events'),
 )
);
}

function event_location() {
   global $post;

   wp_nonce_field( basename( __FILE__ ), 'event_fields' );

   $event_location = get_post_meta( $post->ID, 'event_location', true );

   echo '<input type="text" name="event_location" value="' . esc_textarea( $event_location )  . '" class="widefat">';

}

function event_date() {
   global $post;

   wp_nonce_field( basename( __FILE__ ), 'event_fields' );

   $event_date = get_post_meta( $post->ID, 'event_date', true );

   echo '<input type="datetime-local" name="event_date" value="' . esc_textarea( $event_date )  . '" class="widefat">';

}

function event_url() {
   global $post;

   wp_nonce_field( basename( __FILE__ ), 'event_fields' );

   $event_url = get_post_meta( $post->ID, 'event_url', true );

   echo '<input type="text" name="event_url" value="' . esc_textarea( $event_url )  . '" class="widefat">';

}


add_action( 'init', 'create_posttype' );
add_action( 'add_meta_boxes', 'add_event_metaboxes' );

   function add_event_metaboxes() {
      add_meta_box(
         'event_location',
         'Event Location',
         'event_location',
         'events',
         'normal',
         'default'
      );

      add_meta_box(
         'event_date',
         'Event Date',
         'event_date',
         'events',
         'normal',
         'default'
      );

      add_meta_box(
         'event_url',
         'Event Url',
         'event_url',
         'events',
         'normal',
         'default'
      );      
   }

function save_events_meta( $post_id, $post ) {

   if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return $post_id;
   }

   if(!wp_verify_nonce($_POST['event_fields'], basename(__FILE__))){
      return $post_id;
   }

   if(empty($_POST['event_location']) || empty($_POST['event_date']) || empty($_POST['event_url'])){
      return $post_id;
   }

   $events_meta['event_location'] = esc_textarea( $_POST['event_location'] );
   $events_meta['event_date'] = esc_textarea( $_POST['event_date'] );
   $events_meta['event_url'] = esc_textarea( $_POST['event_url'] );

   foreach ( $events_meta as $key => $value ) :

      if ( 'revision' === $post->post_type ) {
         return;
      }

      if ( get_post_meta( $post_id, $key, false ) ) {
         update_post_meta( $post_id, $key, $value );
      } else {
         add_post_meta( $post_id, $key, $value);
      }

      if ( ! $value ) {
         delete_post_meta( $post_id, $key );
      }

   endforeach;

}
add_action( 'save_post', 'save_events_meta', 1, 2 );

