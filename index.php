<?php 
/*
Plugin Name: Content Stats
Plugin URI: http://www.inkthemes.com
Description: This is a widget to show Current Hits, All time and Just seen hits to posts/pages By Users.
Version: 1.0.5
Author: InkThemes
Author URI: http://www.inkthemes.com/
*/
global $wpdb, $view_table, $count_table, $table_prefix;
add_action("init", "flower_stat_jquery_enqueue");
function flower_stat_jquery_enqueue() 
{
wp_enqueue_script('jquery');
wp_enqueue_style( 'tplp1_style1', plugins_url( '/include/style_curr_graph.css' , __FILE__ ));
wp_enqueue_style( 'tp_views_style', plugins_url( '/include/style1.css' , __FILE__ ));
wp_enqueue_script( 'tp_views_script',  plugins_url( '/include/script1.js' , __FILE__ ), '', '1.0', true);
}
$siteurl1 = get_option('siteurl');
define('PRO_FOLDER1', dirname(plugin_basename(__FILE__)));
define('PRO_URL1', $siteurl1.'/wp-content/plugins/' . PRO_FOLDER1);
add_action("admin_menu", "flower_stats_counter");
function flower_stats_counter()
{
add_menu_page('Content Stats', 'Content Stats', '10', 'stats-counter', 'flower_stats_page', PRO_URL1.'/include/images/16x16.png', '89');
}

$table_prefix=$wpdb->prefix;
$count_table=$table_prefix."views_count";
define('TABLE_PREFIX', $table_prefix);
define('VIEW_TABLE', $table_prefix."views_date");
define('COUNT_TABLE', $table_prefix."views_count");
register_activation_hook(__FILE__,'flower_stat_install');
register_deactivation_hook(__FILE__ , 'flower_stat_uninstall' );



function flower_stat_install()
{
    global $wpdb;
    $table = TABLE_PREFIX."views_date";
    $structure = "CREATE TABLE $table (
        id INT(9) NOT NULL AUTO_INCREMENT,
        post_id INT(11) NOT NULL,
		ip_address varchar(255),
        view_date DATETIME,
		UNIQUE KEY id (id)
    );";
     $wpdb->query($structure); 
	 $table1 = TABLE_PREFIX."views_count";
    $structure1 = "CREATE TABLE $table1 (
        post_id INT(11) NOT NULL,
        count INT(11) NOT NULL
		 );";
     $wpdb->query($structure1); 
}
function flower_stat_uninstall()
{
    global $wpdb;
    $table = TABLE_PREFIX."views_date";
	 $table1 = TABLE_PREFIX."views_count";
    $structure = "drop table $table, $table1";
    $wpdb->query($structure);  
}

class Stat_Views extends WP_Widget 
{
	function Stat_Views() 
	{
		$widget_ops = array('classname' => 'Stat_Views',
                    'description' => 'A widget to show views to pages/posts statistics');
		$this->WP_Widget('Stat_Views', 'Content Stats', $widget_ops);
    }


	function widget($args, $instance) {
		extract($args);
		$limit = intval($instance['limit']);
		?>

<div id="TabsPostsTabber">
  <ul class="TabsPostsTabs contentstats">
    <li class="list"><a href="#TabsPostsLeft">Current Hits</a></li>
    <li class="list"><a href="#TabsPostsCenter">All Time</a></li>
    <li class="list"><a href="#TabsPostsRight">Just Seen</a></li>
  </ul>
  <div class="clear"></div>
  <div class="TabsPostsInside">
    <div id="TabsPostsLeft">
      <?php flower_stat_weekly_viewed($limit); ?>
    </div>
    <div id="TabsPostsCenter">
      <?php flower_stat_most_viewed($limit);?>
    </div>
    <div id="TabsPostsRight">
      <?php flower_stat_currently_viewed($limit); ?>
    </div>
    <div class="clear" style="display: none;"></div>
  </div>
  <div class="clear"></div>
</div>
<?php	
	}

	function update($new_instance, $old_instance) {
		if (!isset($new_instance['submit'])) {
			return false;
		}
		$instance = $old_instance;
		$instance['limit'] = intval($new_instance['limit']);
		return $instance;
	}

	
	function form($instance) {
		global $wpdb;
		$instance = wp_parse_args((array) $instance, array('limit' =>10));
		$limit = intval($instance['limit']);
		?>
<p>
  <label for="<?php echo $this->get_field_id('limit'); ?>">
    <?php _e('Number of Records To Show:', 'stat'); ?>
    <input id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" />
  </label>
</p>
<input type="hidden" id="<?php echo $this->get_field_id('submit'); ?>" name="<?php echo $this->get_field_name('submit'); ?>" value="1" />
<?php
	}
}


add_action('widgets_init', 'flower_stat_widget_init');
function flower_stat_widget_init() 
{
	register_widget('Stat_Views');
}



if(!function_exists('flower_stat_weekly_viewed')) {
	function flower_stat_weekly_viewed($limit, $display = true) {
		global $wpdb, $flower_stat_week;
		$flower_stat_week = array();
                $view_table=TABLE_PREFIX."views_date";
                $count_table=TABLE_PREFIX."views_count";
                $weekly_views = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title, $wpdb->posts.*, $view_table.view_date, count($view_table.post_id) AS views FROM $wpdb->posts, $count_table, $view_table where  $count_table.post_id = $wpdb->posts.ID AND $view_table.post_id= $count_table.post_id AND $wpdb->posts.post_status = 'publish' AND  DATEDIFF(NOW(),$view_table.view_date)<=7 GROUP BY $view_table.post_id ORDER BY views DESC LIMIT $limit");
		
		if($weekly_views) {
                    
			foreach ($weekly_views as $post) {
				$post_views = intval($post->views);
                                $link=get_permalink($post);
				$post_title = get_the_title($post);
				$flower_stat_week = $post_title;
				echo "<li class=\"result\"><a id=\"title\" href=$link>".$flower_stat_week."</a></li>";
			}
		} else {
			echo "<li>N/A</li>";
			
		}
		
	}
}
if(!function_exists('flower_stat_most_viewed')) {
	function flower_stat_most_viewed($limit, $display = true) {
	     global $wpdb, $flower_stat_most;
	     $flower_stat_most = array();
             $view_table=TABLE_PREFIX."views_date";
             $count_table=TABLE_PREFIX."views_count";
                
              	$most_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title, $wpdb->posts.*, $count_table.count AS views FROM $wpdb->posts, $count_table where $count_table.post_id = $wpdb->posts.ID AND $wpdb->posts.post_status = 'publish' ORDER BY views DESC LIMIT $limit");
		if($most_viewed) {
                    
				foreach ($most_viewed as $post) {
				$post_views = intval($post->views);
				$post_title = get_the_title($post);
				$link=get_permalink($post);
				$flower_stat_most = $post_title;
				echo "<li class=\"result\"><a id=\"title\" href=$link>".$flower_stat_most."</a></li>";
			}
		} else {
		echo "<li>N/A</li>";
		}
		
	}
}

if(!function_exists('flower_stat_currently_viewed')) {
	function flower_stat_currently_viewed($limit, $display = true) {
		global $wpdb, $flower_stat_curr;
		$flower_stat_curr = array();
                $view_table=TABLE_PREFIX."views_date";
                $count_table=TABLE_PREFIX."views_count";
                $current_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title, $wpdb->posts.*, count($view_table.post_id) AS views FROM $wpdb->posts, $view_table where $view_table.post_id = $wpdb->posts.ID AND $wpdb->posts.post_status = 'publish' GROUP BY $view_table.post_id ORDER BY MAX($view_table.view_date) DESC LIMIT $limit");
		if($current_viewed) {
			foreach ($current_viewed as $post) {
				$post_views = intval($post->views);
				$post_title = get_the_title($post);
				$link=get_permalink($post);
				$flower_stat_curr = $post_title;
				echo "<li class=\"result\"><a id=\"title\" href=$link>".$flower_stat_curr."</a></li>";
				}
		} else {
			
			echo "<li>N/A</li>";
		}
		
	}
}

function flower_stats_page(){
	echo "<h2>Content Stats</h2>";
	echo "<inline style=\"font-size:13px\"><p>This Widget Shows us number of views to particular Pages/Posts to your Website.</p>
		<p>After Activating Widget go to Appearance->Widgets and drag-drop the Content Stat Widget 
		from Available Widgets into Widget Area of your site.
	 </p></style></br>";
 ?>
<div class="content-box1"> <b>For more information about Plugin&nbsp;&nbsp;<a id="more" href="http://www.inkthemes.com/plugin/wordpress-analytics-plugin/">Click Here</a></b> </div>
</br>
</br>
<?php
$columns = array(
		'col1' => 'Title',
		'col2' => 'Views'		
      	
	);
	/*table to show Current Hits*/
	register_column_headers('flower-stat-list', $columns);
?>
<div class="content-box"> <b>Current Hits</b>
  <p style=\"font-size:12px\">(Display most popular Posts/Pages along with their views in last 7 days.)</p>
</div>
<table class="widefat page fixed" style="width:600px" cellspacing="0">
  <thead>
    <tr> <?php print_column_headers('flower-stat-list'); ?> </tr>
  </thead>
  <tbody>
    <?php global $wpdb;
 $view_table=TABLE_PREFIX."views_date";
 $count_table=TABLE_PREFIX."views_count";
                
		$weekly_views = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title, $wpdb->posts.*, $view_table.view_date, count($view_table.post_id) AS views FROM $wpdb->posts, $count_table, $view_table where $count_table.post_id = $wpdb->posts.ID AND $view_table.post_id=$count_table.post_id AND $wpdb->posts.post_status = 'publish' AND  DATEDIFF(NOW(),$view_table.view_date)<=7 GROUP BY $view_table.post_id ORDER BY views DESC");
		
		if($weekly_views) {
                  
			foreach ($weekly_views as $post) {
				$post_views = intval($post->views);
				$post_title = get_the_title($post);
				$link=get_permalink($post);
	?>
  <tbody>
  <td><?php
			  echo "<li style=\"list-style-type:none; font-size:15px\"><a id=\"title\" href=$link>".$post_title."</a></li>";
		  
		  ?></td>
    <td><?php
echo "<li style=\"list-style-type:none; font-size:15px\">".$post_views."</li>";
		  
		  ?></td>
    <?php

}
}?>
      </tbody>
</table>
</br>
</br>
<?php
/*Table to show Popular Hits*/
$columns = array(
		'col1' => 'Title',
		'col2' => 'Views'		
      	
	);
	register_column_headers('flower-stat-list', $columns);	
	?>
<div class="content-box"> <b>All Time</b>
  <p style=\"font-size:12px\">(Display All time popular Posts/Pages along with their all time views.)</p>
</div>
<table class="widefat page fixed" style="width:600px" cellspacing="0">
  <thead>
    <tr> <?php print_column_headers('flower-stat-list'); ?> </tr>
  </thead>
  <tbody>
    <?php global $wpdb;
 $view_table=TABLE_PREFIX."views_date";
 $count_table=TABLE_PREFIX."views_count";	
    $most_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title, $wpdb->posts.*, $count_table.count AS views FROM $wpdb->posts, $count_table where $count_table.post_id = $wpdb->posts.ID AND $wpdb->posts.post_status = 'publish' ORDER BY views DESC");
	if($most_viewed) {
				foreach ($most_viewed as $post) {
				$post_views = intval($post->views);
				$post_title = get_the_title($post);
				$link=get_permalink($post);
	?>
  <tbody>
  <td><?php
			  echo "<li style=\"list-style-type:none; font-size:15px\"><a id=\"title\" href=$link>".$post_title."</a></li>";
		  
		  ?></td>
    <td><?php
echo "<li style=\"list-style-type:none; font-size:15px\">".$post_views."</li>";
		  
		  ?></td>
    <?php

}
}?>
      </tbody>
</table>
</br>
</br>
<?php
$columns = array(
		'col1' => 'Title',
		'col2' => 'Views'		
      	
	);
	register_column_headers('flower-stat-list', $columns);
	?>
<div class="content-box"> <b>Just Seen</b>
  <p style=\"font-size:12px\">(Display Recently viewed Posts/Pages along with their all time views.)</p>
</div>
<table class="widefat page fixed" style="width:600px" cellspacing="0">
  <thead>
    <tr> <?php print_column_headers('flower-stat-list'); ?> </tr>
  </thead>
  <tbody>
    <?php global $wpdb;
$view_table=TABLE_PREFIX."views_date";
 $count_table=TABLE_PREFIX."views_count";		
$current_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title, $wpdb->posts.*, count($view_table.post_id) AS views FROM $wpdb->posts, $view_table where $view_table.post_id = $wpdb->posts.ID AND $wpdb->posts.post_status = 'publish' GROUP BY $view_table.post_id ORDER BY MAX($view_table.view_date) DESC");
 
if($current_viewed) 
{
foreach ($current_viewed as $post) 
{
	$post_views = intval($post->views);
       
	$post_title = get_the_title($post);
	$link=get_permalink($post);
	?>
  <tbody>
  <td><?php
			  echo "<li style=\"list-style-type:none; font-size:15px\"><a id=\"title\" href=$link>".$post_title."</a></li>";
		  
		  ?></td>
    <td><?php
echo "<li style=\"list-style-type:none; font-size:15px\">".$post_views."</li>";
		  
		  ?></td>
    <?php

}
}?>
      </tbody>
</table>
</br>
<div class="content-box"> <b>Graph</b>
  <p style=\"font-size:12px\">(Display mostly viewed Posts/Pages along with their graph and all time views.)</p>
</div>
<table class="widefat page fixed" style="width:600px" cellspacing="0">
  <thead>
  <th> <?php  echo "Title";?>
    </th>
    <th> <?php echo "Views";?> </th>
      </thead>
  <tbody>
    <?php global $wpdb;
	$view_table=TABLE_PREFIX."views_date";
 $count_table=TABLE_PREFIX."views_count";	
    $most_viewed = $wpdb->get_results("SELECT DISTINCT $wpdb->posts.post_title,$wpdb->posts.*, $count_table.count AS views FROM $wpdb->posts, $count_table where $count_table.post_id = $wpdb->posts.ID AND $wpdb->posts.post_status = 'publish' ORDER BY views DESC");
	if($most_viewed) {
				foreach ($most_viewed as $post) {
				$post_views = intval($post->views);
				$post_title = get_the_title($post);
				$link=get_permalink($post);
	?>
  <tbody>
  <td><?php
			  echo "<li style=\"list-style-type:none; font-size:15px\"><a id=\"title\" href=$link>".$post_title."</a></li>";
		  
		  ?></td>
    <td><?php
echo "<li style=\"list-style-type:none; font-size:15px\">".$post_views."</li>";
		  
		  ?>
      <?php 
			$num=$post_views/10;
				echo '<div class="graph"><strong class="bar" style="width:'.$num.'px;"></strong></div>
      <div class="clear"></div>';?></td>
    <?php

}
}?>
      </tbody>
</table>
</br>
</br>
<?php
echo "<p>This Widget is developed by InkThemes.</p>";
} 
add_filter( 'the_content','flower_stat_view_content', 1 );
function flower_stat_view_content( $content )
{
global $wpdb, $post;
$view_table=TABLE_PREFIX."views_date";
 $count_table=TABLE_PREFIX."views_count";
$id=$post->ID;

if(!is_page())
{
$viewed = $wpdb->get_results("SELECT DISTINCT count AS views FROM $count_table where post_id = $id"); 

if(viewed) 
{
    
foreach ($viewed as $post1)
{
$post_views = intval($post1->views);

$output = "Views - ".$post_views;
}
}
return $content ."\n\n". $output;
}
return $content;
}
add_action('wp_head', 'flower_stat_views');
function flower_stat_views() {
	global $post;
	if(is_int($post))
	{
	$post = get_post($post);
	}
	if(!wp_is_post_revision($post)) {
		if(is_single() || is_page()) {
			$id = intval($post->ID);
			global $wpdb;
			$ip=$_SERVER['REMOTE_ADDR'];
			global $wpdb;
			$query=$wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."views_date where post_id=$id AND ip_address='$ip' AND date(view_date)=DATE(NOW())", null));
			if(!$query)
			{
            $wpdb->insert($wpdb->prefix."views_date", 
			array( 	
			'post_id' =>$id,
			'ip_address'=>$ip,
		    'view_date'=> date( 'Y-m-d H:i:s')
		          ));
            $thepost = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."views_count WHERE post_id = $id ", null));
            $count=$thepost->count;
            if($thepost)
            {
			   $wpdb->update($wpdb->prefix."views_count", 
	           array( 
		          'count'=>$count+1
		         ), 
	           array('post_id' => $id)
	                 );
            }
           else
            {
                $wpdb->insert($wpdb->prefix."views_count", 
                array( 	
				'post_id' =>$id,
		        'count'=> 1
		             ));
            }
			}
		}
	}
}
?>
