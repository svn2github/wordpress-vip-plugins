<?php
/*
Plugin Name: Add Meta Tags
Plugin URI: http://www.g-loaded.eu/2006/01/05/add-meta-tags-wordpress-plugin/
Description: Adds the <em>Description</em> and <em>Keywords</em> XHTML META tags to your blog's <em>front page</em> and to each one of the <em>posts</em>, <em>static pages</em> and <em>category archives</em>. This operation is automatic, but the generated META tags can be fully customized. Also, the inclusion of other META tags, which do not need any computation, is possible. Please read the tips and all other info provided at the <a href="options-general.php?page=add-meta-tags.php">configuration panel</a>.
Version: 1.6-WPCOM
Author: George Notaras, Thorsten Ott
Author URI: http://www.g-loaded.eu/
*/

/*
  Copyright 2007 George Notaras <gnot [at] g-loaded.eu>, CodeTRAX.org

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

/*

INTERNAL Configuration Options

1 - Include/Exclude the "keywords" metatag.

    The following option exists ONLY for those who do not want a "keywords"
    metatag META tag to be generated in "Single-Post-View", but still want the
    "description" META tag.
    
    Possible values: TRUE, FALSE
    Default: TRUE
*/
$include_keywords_in_single_posts = TRUE;

/*
Translation Domain

Translation files are searched in: wp-content/plugins
*/
load_plugin_textdomain('add-meta-tags');

/*
Custom fields that hold post/page related seo content
*/
$mt_seo_fields = array(
                       'mt_seo_title' => array( __( 'Title (optional) :', 'add-meta-tags' ), 'text', __( 'The text entered here will alter the &lt;title&gt; tag using the wp_title() function. Use <code>%title%</code> to include the original title or leave empty to keep original title.<br /><strong>Example:</strong> altered title <code>%title%</code>', 'add-meta-tags' ) ),
                       'mt_seo_description' => array( __( 'Description (optional) :', 'add-meta-tags' ), 'textarea', __( 'This text will be used as description meta information. Left empty a description is automatically generated<br /><strong>Example:</strong> an other description text', 'add-meta-tags' ) ),
                       'mt_seo_keywords' => array( __( 'Keywords (optional) :', 'add-meta-tags' ), 'text', __( 'Provide a comma-delimited list of keywords for your blog. Leave it empty to use the post\'s keywords for the "keywords" meta tag. When overriding the post\'s keywords, the tag <code>%cats%</code> can be used to insert the post\'s categories, add the tag <code>%tags%</code>, to include the post\'s tags.<br /><strong>Example:</strong> keyword1, keyword2,%tags% %cats%', 'add-meta-tags' ) ),
                       'mt_seo_meta' => array( __( 'Additional Meta tags (optional) :', 'add-meta-tags' ), 'textarea', __( 'Provide the full XHTML code of META tags you would like to be included in this post/page.<br /><strong>Example:</strong> &lt;meta name="robots" content="index,follow" /&gt;', 'add-meta-tags' ) ),
                       );

/*
Admin Panel
*/

function amt_add_pages() {
	add_options_page(__('Meta Tags Options', 'add-meta-tags'), __('Meta Tags', 'add-meta-tags'), 'administrator', 'amt_options', 'amt_options_page');
}

function amt_show_info_msg($msg) {
	echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
}

function amt_clean_array( $array ) {
	$clean = array();
	foreach( $array as $key => $value ) {
		$clean[$key] = (boolean) $value;
	}
	return $clean;
}
function amt_options_page() {
	if (isset($_POST['info_update'])) {
		/*
		For a little bit more security and easier maintenance, a separate options array is used.
		*/

		$options = array(
			"site_description"	=> esc_attr( $_POST["site_description"] ),
			"site_keywords"		=> esc_attr( $_POST["site_keywords"] ),
			"site_wide_meta"	=> strip_tags( $_POST["site_wide_meta"], '<meta>' ),
            "post_options"      => ( is_array( $_POST["post_options"] ) ) ? amt_clean_array( $_POST["post_options"] ) : array(),
            "page_options"      => ( is_array( $_POST["page_options"] ) ) ? amt_clean_array( $_POST["page_options"] ) : array(),
			);
		update_option("add_meta_tags_opts", $options);
		amt_show_info_msg(__('Add-Meta-Tags options saved.', 'add-meta-tags'));

	} elseif (isset($_POST["info_reset"])) {

		delete_option("add_meta_tags_opts");
		amt_show_info_msg(__('Add-Meta-Tags options deleted from the WordPress database.', 'add-meta-tags'));

		/*
		The following exists for deleting old add-meta-tags options (version 1.0 or older).
		The following statement have no effect if the options do not exist.
		This is 100% safe (TM).
		*/
		delete_option('amt_site_description');
		delete_option('amt_site_keywords');

	} else {

		$options = get_option("add_meta_tags_opts");

	}

    $post_options = $options['post_options'];
    $page_options = $options['page_options'];
    
    // good defaults is the hallmark of good software
    if ( !is_array( $post_options ) )
        $post_options = array( 'mt_seo_title' => true, 'mt_seo_description' => true, 'mt_seo_keywords' => true, 'mt_seo_meta' => true );
    if ( !is_array( $page_options ) )
        $page_options = array( 'mt_seo_title' => true, 'mt_seo_description' => true, 'mt_seo_keywords' => true, 'mt_seo_meta' => true );
    
    
	/*
	Configuration Page
	*/
	
	print('
	<div class="wrap" id="amt-header">
		<h2>'.__('Add-Meta-Tags', 'add-meta-tags').'</h2>
		<p>'.__('This is where you can configure the Add-Meta-Tags plugin and read about how the plugin adds META tags in the WordPress pages.', 'add-meta-tags').'</p>
		<p>'.__('Modifying any of the settings in this page is completely <strong>optional</strong>, as the plugin will add META tags automatically.', 'add-meta-tags').'</p>
		<p>'.__("For more information about the plugin's default behaviour and how you could customize the metatag generation can be found in detail in the sections that follow.", "add-meta-tags").'</p>
	</div>


    <form name="formamt" method="post" action="' . $_SERVER['REQUEST_URI'] . '">

	<div class="wrap" id="amt-config-site-wide">
		<h2>'.__('Configuration', 'add-meta-tags').'</h2>

			<fieldset class="options">
				<legend>'.__('Site Description', 'add-meta-tags').'<br />
					<p>'.__('The following text will be used in the "description" meta tag on the <strong>homepage only</strong>. If this is left <strong>empty</strong>, then the blog\'s description from the <em>General Options</em> (Tagline) will be used.', 'add-meta-tags').'</p>
					<p><textarea name="site_description" id="site_description" cols="40" rows="3" style="width: 80%; font-size: 14px;" class="code">' . stripslashes($options["site_description"]) . '</textarea></p>
				</legend>
			</fieldset>

			<fieldset class="options">
				<legend>'.__('Site Keywords', 'add-meta-tags').'<br />
					<p>'.__('The following keywords will be used for the "keywords" meta tag on the <strong>homepage only</strong>. Provide a comma-delimited list of keywords for your blog. If this field is left <strong>empty</strong>, then all of your blog\'s categories will be used as keywords for the "keywords" meta tag.', 'add-meta-tags').'</p>
					<p><textarea name="site_keywords" id="site_keywords" cols="40" rows="3" style="width: 80%; font-size: 14px;" class="code">' . stripslashes($options["site_keywords"]) . '</textarea></p>
					<p><strong>'.__('Example', 'add-meta-tags').'</strong>: <code>'.__('keyword1, keyword2, keyword3', 'add-meta-tags').'</code></p>
				</legend>
			</fieldset>

			<fieldset class="options">
				<legend>'.__('Site-wide META tags', 'add-meta-tags').'<br />
					<p>'.__('Provide the <strong>full XHTML code</strong> of META tags you would like to be included in <strong>all</strong> of your blog pages.', 'add-meta-tags').'</p>
					<p><textarea name="site_wide_meta" id="site_wide_meta" cols="40" rows="10" style="width: 80%; font-size: 14px;" class="code">' . stripslashes($options["site_wide_meta"]) . '</textarea></p>
					<p><strong>'.__('Example', 'add-meta-tags').'</strong>: <code>&lt;meta name="robots" content="index,follow" /&gt;</code></p>
				</legend>
			</fieldset>

			<p class="submit">
				<input type="submit" name="info_update" value="'.__('Update Options', 'add-meta-tags').' &raquo;" />
			</p>

	</div>

	<div class="wrap" id="amt-header-frontpage"> 
		<h2>'.__('Meta Tags on the Front Page', 'add-meta-tags').'</h2>
		<p>'.__('If a site description and/or keywords have been set in the Add-Meta-Tags options above, then those will be used in the "<em>description</em>" and "<em>keywords</em>" META tags respectively.', 'add-meta-tags').'</p>
		<p>'.__('Alternatively, if the above options are not set, then the blog\'s description from the <em>General</em> WordPress options will be used in the "<em>description</em>" META tag, while all of the blog\'s categories, except for the "Uncategorized" category, will be used in the "<em>keywords</em>" META tag.', 'add-meta-tags').'</p>
	</div>

	<div class="wrap" id="amt-config-single">
		<h2>'.__('Meta Tags on Single Posts', 'add-meta-tags').'</h2>
		<p>'.__('Although no configuration is needed in order to put meta tags on single posts, the following information will help you customize them.', 'add-meta-tags').'</p>
		<p>'.__('By default, when a single post is displayed, the post\'s excerpt and the post\'s categories and tags are used in the "description" and the "keywords" meta tags respectively.', 'add-meta-tags').'</p>
		<p>'.__('It is possible to override them by providing a custom description in a custom field named "<strong>description</strong>" and a custom comma-delimited list of keywords by providing it in a custom field named "<strong>keywords</strong>".', 'add-meta-tags').'</p>
		<p>'.__("Furthermore, when overriding the post's keywords, but you need to include the post's categories too, you don't need to type them, but the tag <code>%cats%</code> can be used. In the same manner you can also include your tags in this custom field by adding the word <code>%tags%</code>, which will be replaced by your post's tags.", "add-meta-tags").'</p>
		<p><strong>'.__('Example', 'add-meta-tags').':</strong> <code>'.__('keyword1, keyword2, %cats%, keyword3, %tags%, keyword4', 'add-meta-tags').'</code></p>

        <p><strong>' . __('Enable the following options for posts:', 'add-meta-tags') . '</strong>
        ' . __( 'Title', 'add-meta-tags' ) . ' : <input type="checkbox" name="post_options[mt_seo_title]" value="true" ' . ( ( $post_options["mt_seo_title"] ) ? 'checked="checked"' : '' ) . ' /> , 
        ' . __( 'Description', 'add-meta-tags' ) . ' : <input type="checkbox" name="post_options[mt_seo_description]" value="true" ' . ( ( $post_options["mt_seo_description"] ) ? 'checked="checked"' : '' ) . ' /> , 
        ' . __( 'Keywords', 'add-meta-tags' ) . ' : <input type="checkbox" name="post_options[mt_seo_keywords]" value="true" ' . ( ( $post_options["mt_seo_keywords"] ) ? 'checked="checked"' : '' ) . ' /> , 
        ' . __( 'Meta', 'add-meta-tags' ) . ' : <input type="checkbox" name="post_options[mt_seo_meta]" value="true" ' . ( ( $post_options["mt_seo_meta"] ) ? 'checked="checked"' : '' ) . ' />
        </p>
        <p class="submit">
			<input type="submit" name="info_update" value="'.__('Update Options', 'add-meta-tags').' &raquo;" />
		</p>

    </div>

	<div class="wrap" id="amt-config-pages">
		<h2>'.__('Meta Tags on Pages', 'add-meta-tags').'</h2>
		<p>'.__('By default, meta tags are not added automatically when viewing Pages. However, it is possible to define a description and a comma-delimited list of keywords for the Page, by using custom fields named "<strong>description</strong>" and/or "<strong>keywords</strong>" as described for single posts.', 'add-meta-tags').'</p>
		<p>'.__('<strong>WARNING</strong>: Pages do not belong to categories in WordPress. Therefore, the tag <code>%cats%</code> will not be replaced by any categories if it is included in the comma-delimited list of keywords for the Page, so <strong>do not use it for Pages</strong>.', 'add-meta-tags').'</p>

        <p><strong>' . __('Enable the following options for pages:', 'add-meta-tags') . '</strong>
        ' . __( 'Title', 'add-meta-tags' ) . ' : <input type="checkbox" name="page_options[mt_seo_title]" value="true" ' . ( ( $page_options["mt_seo_title"] ) ? 'checked="checked"' : '' ) . ' /> , 
        ' . __( 'Description', 'add-meta-tags' ) . ' : <input type="checkbox" name="page_options[mt_seo_description]" value="true" ' . ( ( $page_options["mt_seo_description"] ) ? 'checked="checked"' : '' ) . ' /> , 
        ' . __( 'Keywords', 'add-meta-tags' ) . ' : <input type="checkbox" name="page_options[mt_seo_keywords]" value="true" ' . ( ( $page_options["mt_seo_keywords"] ) ? 'checked="checked"' : '' ) . ' /> , 
        ' . __( 'Meta', 'add-meta-tags' ) . ' : <input type="checkbox" name="page_options[mt_seo_meta]" value="true" ' . ( ( $page_options["mt_seo_meta"] ) ? 'checked="checked"' : '' ) . ' />
        </p>
        <p class="submit">
			<input type="submit" name="info_update" value="'.__('Update Options', 'add-meta-tags').' &raquo;" />
		</p>

    </div>
    </form>
	<div class="wrap" id="amt-header-category">
		<h2>'.__('Meta Tags on Category Archives', 'add-meta-tags').'</h2>
		<p>'.__('META tags are automatically added to Category Archives, for example when viewing all posts that belong to a specific category. In this case, if you have set a description for that category, then this description is added to a "description" META tag.', 'add-meta-tags').'</p>
		<p>'.__('Furthermore, a "keywords" META tag - containing only the category\'s name - is always added to Category Archives.', 'add-meta-tags').'</p>
    </div>

    <div class="wrap" id="amt-config-reset">
		<h2>'.__('Reset Plugin', 'add-meta-tags').'</h2>
		<form name="formamtreset" method="post" action="' . $_SERVER['REQUEST_URI'] . '">
			<p>'.__('By pressing the "Reset" button, the plugin will be reset. This means that the stored options will be deleted from the WordPress database. Although it is not necessary, you should consider doing this before uninstalling the plugin, so no trace is left behind.', 'add-meta-tags').'</p>
			<p class="submit">
				<input type="submit" name="info_reset" value="'.__('Reset Options', 'add-meta-tags').'" />
			</p>
		</from>
	</div>

	');

}



function amt_clean_desc($desc) {
	/*
	This is a filter for the description metatag text.
	*/
	$desc = stripslashes($desc);
	$desc = strip_tags($desc);
	$desc = htmlspecialchars($desc);
	//$desc = preg_replace('/(\n+)/', ' ', $desc);
	$desc = preg_replace('/([\n \t\r]+)/', ' ', $desc); 
	$desc = preg_replace('/( +)/', ' ', $desc);
	return trim($desc);
}


function amt_get_the_excerpt($excerpt_max_len = 300, $desc_avg_length = 250, $desc_min_length = 150) {
	/*
	Returns the post's excerpt.
	This was written in order to get the excerpt *outside* the loop
	because the get_the_excerpt() function does not work there any more.
	This function makes the retrieval of the excerpt independent from the
	WordPress function in order not to break compatibility with older WP versions.
	
	Also, this is even better as the algorithm tries to get text of average
	length 250 characters, which is more SEO friendly. The algorithm is not
	perfect, but will do for now.
	*/
	global $posts;

	if ( empty($posts[0]->post_excerpt) ) {

		$post_content = strip_tags( strip_shortcodes( $posts[0]->post_content ) );

		/*
		Get the initial data for the excerpt
		*/
		$amt_excerpt = substr( $post_content, 0, $excerpt_max_len );

		/*
		If this was not enough, try to get some more clean data for the description (nasty hack)
		*/
		if ( strlen($amt_excerpt) < $desc_avg_length ) {
			$amt_excerpt = substr( $post_content, 0, (int) ($excerpt_max_len * 1.5) );
			if ( strlen($amt_excerpt) < $desc_avg_length ) {
				$amt_excerpt = substr( $post_content, 0, (int) ($excerpt_max_len * 2) );
			}
		}

		$end_of_excerpt = strrpos($amt_excerpt, ".");

		if ($end_of_excerpt) {
			/*
			if there are sentences, end the description at the end of a sentence.
			*/
			$amt_excerpt_test = substr($amt_excerpt, 0, $end_of_excerpt + 1);

			if ( strlen($amt_excerpt_test) < $desc_min_length ) {
				/*
				don't end at the end of the sentence because the description would be too small
				*/
				$amt_excerpt .= "...";
			} else {
				/*
				If after ending at the end of a sentence the description has an acceptable length, use this
				*/
				$amt_excerpt = $amt_excerpt_test;
			}
		} else {
			/*
			otherwise (no end-of-sentence in the excerpt) add this stuff at the end of the description.
			*/
			$amt_excerpt .= "...";
		}

	} else {
		/*
		When the post excerpt has been set explicitly, then it has priority.
		*/
		$amt_excerpt = $posts[0]->post_excerpt;
	}

	return $amt_excerpt;
}


function amt_get_keywords_from_post_cats() {
	/*
	Returns a comma-delimited list of a post's categories.
	*/
	global $posts;

	$postcats = "";
	foreach((get_the_category($posts[0]->ID)) as $cat) {
		$postcats .= $cat->cat_name . ', ';
	}
	$postcats = substr($postcats, 0, -2);

	return $postcats;
}

function amt_get_post_tags() {
	/*
	Retrieves the post's user-defined tags.
	
	This will only work in WordPress 2.3 or newer. On older versions it will
	return an empty string.
	*/
	global $posts;
	
	if ( version_compare( get_bloginfo('version'), '2.3', '>=' ) || 'MU' == get_bloginfo('version') ) {
		$tags = get_the_tags($posts[0]->ID);
		if ( empty( $tags ) ) {
			return false;
		} else {
			$tag_list = "";
			foreach ( $tags as $tag ) {
				$tag_list .= $tag->name . ', ';
			}
			$tag_list = strtolower(rtrim($tag_list, " ,"));
			return $tag_list;
		}
	} else {
		return "";
	}
}


function amt_get_all_categories($no_uncategorized = TRUE) {
	/*
	Returns a comma-delimited list of all the blog's categories.
	The built-in category "Uncategorized" is excluded.
	*/

	$category_ids = get_all_category_ids();
	if ( !empty( $category_ids ) ) {
		foreach( $category_ids as $cat_id ) {
			$cat_name = get_cat_name( $cat_id );
			$categories[] = $cat_name;
		}
		if ( empty( $categories ) ) {
			return "";
		} else {
			$all_cats = "";
			foreach ( $categories as $cat ) {
				if ( $no_uncategorized && $cat != "Uncategorized" ) {
					$all_cats .= $cat . ', ';
				}
			}
			$all_cats = strtolower( rtrim( $all_cats, " ," ) );
			return $all_cats;
		}
	}
}


function amt_get_site_wide_metatags($site_wide_meta) {
	/*
	This is a filter for the site-wide meta tags.
	*/
	$site_wide_meta = stripslashes($site_wide_meta);
	$site_wide_meta = trim($site_wide_meta);
	return $site_wide_meta;
}

function amt_add_meta_tags() {
	/*
	This is the main function that actually writes the meta tags to the
	appropriate page.
	*/
	global $posts, $include_keywords_in_single_posts, $mt_seo_fields;

	/*
	Get the options the DB
	*/
	$options = get_option("add_meta_tags_opts");
	$site_wide_meta = $options["site_wide_meta"];

    if ( isset( $posts[0] ) && 'page' == $posts[0]->post_type )
        $cmpvalues = $options['page_options'];
    elseif ( isset( $posts[0] ) && 'post' == $posts[0]->post_type )
        $cmpvalues = $options['post_options'];
	else 
		$cmpvalues = array();

    if ( !is_array( $cmpvalues ) )
        $cmpvalues = array( 'mt_seo_title' => true, 'mt_seo_description' => true, 'mt_seo_keywords' => true, 'mt_seo_meta' => true );

	$cmpvalues = amt_clean_array( $cmpvalues );
	$my_metatags = "";

    // nothing allowed so just return
    if ( empty( $cmpvalues ) )
        return;
    
	if ( is_single() || is_page() ) {
		/*
		Add META tags to Single Page View or Page
		*/

        foreach( (array) $mt_seo_fields as $field_name => $field_data ) 
            ${$field_name} = (string) get_post_meta( $posts[0]->ID, $field_name, true );

		/*
		Description
		Custom post field "description" overrides post's excerpt in Single Post View.
		*/
        if ( true == $cmpvalues['mt_seo_description'] ) {    

            if ( !empty($mt_seo_description) ) {
                /*
                  If there is a custom field, use it
                */
                $my_metatags .= "\n<meta name=\"description\" content=\"" . amt_clean_desc($mt_seo_description) . "\" />";
            } elseif ( is_single() ) {
                /*
                  Else, use the post's excerpt. Only for Single Post View (not valid for Pages)
                */
                                                                    $my_metatags .= "\n<meta name=\"description\" content=\"" . amt_clean_desc(amt_get_the_excerpt()) . "\" />";
            }
        }
        /*
		Meta
		Custom post field "mt-seo-meta" adds additional meta tags
		*/
        if ( !empty($mt_seo_meta) && true == $cmpvalues['mt_seo_meta'] ) {
			/*
			If there is a custom field, use it
			*/
            $my_metatags .= "\n" . $mt_seo_meta;
        }


        /*
        Title
        Rewrite the title in case a special title is given
        */
        //if ( !empty( $mt_seo_title ) ) {
        // see function mt_seo_rewrite_tite() which is added as filter for wp_title
        //}

        
		/*
		Keywords
		Custom post field "keywords" overrides post's categories and tags (tags exist in WordPress 2.3 or newer).
		%cats% is replaced by the post's categories.
		%tags% us replaced by the post's tags.
		NOTE: if $include_keywords_in_single_posts is FALSE, then keywords
		metatag is not added to single posts.
		*/
        if ( true == $cmpvalues['mt_seo_keywords'] ) {                                                       
            if ( ($include_keywords_in_single_posts && is_single()) || is_page() ) {
                if ( !empty($mt_seo_keywords) ) {
                    /*
                      If there is a custom field, use it
                    */
                    if ( is_single() ) {
                        /*
                          For single posts, the %cat% tag is replaced by the post's categories
                        */
                            $mt_seo_keywords = str_replace("%cats%", amt_get_keywords_from_post_cats(), $mt_seo_keywords);
                        /*
                          Also, the %tags% tag is replaced by the post's tags (WordPress 2.3 or newer)
                        */
                            if ( version_compare( get_bloginfo('version'), '2.3', '>=' ) || 'MU' == get_bloginfo('version') ) {
                                $mt_seo_keywords = str_replace("%tags%", amt_get_post_tags(), $mt_seo_keywords);
                            }
                    }
                    $my_metatags .= "\n<meta name=\"keywords\" content=\"" . strtolower($mt_seo_keywords) . "\" />";
                } elseif ( is_single() ) {
                    /*
                      Add keywords automatically.
                      Keywords consist of the post's categories and the post's tags (tags exist in WordPress 2.3 or newer).
                      Only for Single Post View (not valid for Pages)
                    */
                    $my_metatags .= "\n<meta name=\"keywords\" content=\"" . strtolower(amt_get_keywords_from_post_cats());
                    $post_tags = strtolower(amt_get_post_tags());
                    if ( $post_tags ) {
                        $my_metatags .= ", " . $post_tags;
                    }
                    $my_metatags .= "\" />";
                }
            }
        }
	} elseif ( is_home() ) {
		/*
		Add META tags to Home Page
		*/
		
		/*
		Description and Keywords from the options override default behaviour
		*/
		$site_description = $options["site_description"];
		$site_keywords = $options["site_keywords"];

		/*
		Description
		*/
		if ( empty($site_description) ) {
			/*
			If $site_description is empty, then use the blog description from the options
			*/
			$my_metatags .= "\n<meta name=\"description\" content=\"" . amt_clean_desc(get_bloginfo('description')) . "\" />";
		} else {
			/*
			If $site_description has been set, then use it in the description meta-tag
			*/
			$my_metatags .= "\n<meta name=\"description\" content=\"" . amt_clean_desc($site_description) . "\" />";
		}
		/*
		Keywords
		*/
		if ( empty($site_keywords) ) {
			/*
			If $site_keywords is empty, then all the blog's categories are added as keywords
			*/
			$my_metatags .= "\n<meta name=\"keywords\" content=\"" . amt_get_all_categories() . "\" />";
		} else {
			/*
			If $site_keywords has been set, then these keywords are used.
			*/
			$my_metatags .= "\n<meta name=\"keywords\" content=\"" . $site_keywords . "\" />";
		}


	} elseif ( is_category() ) {
		/*
		Writes a description META tag only if a description for the current category has been set.
		*/

		$cur_cat_desc = category_description();
		if ( $cur_cat_desc ) {
			$my_metatags .= "\n<meta name=\"description\" content=\"" . amt_clean_desc($cur_cat_desc) . "\" />";
		}
		
		/*
		Write a keyword metatag if there is a category name (always)
		*/
		$cur_cat_name = single_cat_title($prefix = '', $display = false );
		if ( $cur_cat_name ) {
			$my_metatags .= "\n<meta name=\"keywords\" content=\"" . strtolower($cur_cat_name) . "\" />";
		}
	}

	if ($my_metatags) {
		echo "\n<!-- META Tags added by Add-Meta-Tags WordPress plugin. Get it at: http://www.g-loaded.eu/ -->" . $my_metatags . "\n" . amt_get_site_wide_metatags($site_wide_meta) . "\n\n";
	}
}

/*
SEO Write panel
*/
function mt_seo_meta_box( $post, $meta_box ) {
    global $mt_seo_fields, $pagenow;
	if ( $post_id = (int) $post->ID ) {
        foreach( (array) $mt_seo_fields as $field_name => $field_data ) 
            ${$field_name} = (string) get_post_meta( $post_id, $field_name, true );
    } else {
        foreach( (array) $mt_seo_fields as $field_name => $field_data ) 
            ${$field_name} = '';
    }
    $tabindex = $tabindex_start = 5000;

    $options = get_option("add_meta_tags_opts");

    if ( stristr( $pagenow, 'page' ) )
        $cmpvalues = $options['page_options'];
    elseif ( stristr( $pagenow, 'post' ) )
        $cmpvalues = $options['post_options'];

    if ( !is_array( $cmpvalues ) )
        $cmpvalues = array( 'mt_seo_title' => true, 'mt_seo_description' => true, 'mt_seo_keywords' => true, 'mt_seo_meta' => true );

	$cmpvalues = amt_clean_array( $cmpvalues );
	
    foreach( (array) $mt_seo_fields as $field_name => $field_data ) {
        if ( true != $cmpvalues[$field_name] )
            continue;
        
        ${$field_name} = format_to_edit( ${$field_name} );
        if( 'textarea' == $field_data[1] ) {
            echo '<p><label for="' . $field_name . '">' . $field_data[0] . '</label><br />';
            echo '<textarea class="wide-seo-box" rows="4" cols="40" tabindex="' . $tabindex . '" name="' . $field_name . '"';
            echo 'id="' . $field_name .'">' . ${$field_name} . '</textarea><br />';
            echo $field_data[2] . "</p>\n";
        } else if ( 'text' == $field_data[1] ) {
            echo '<p><label for="' . $field_name .'">' . $field_data[0] . '</label>';
            echo '<input type="text" class="wide-seo-box" tabindex="' . $tabindex . '" name="' . $field_name . '" id="' . $field_name . '" value="' . ${$field_name} . '" /><br />';
            echo $field_data[2] . "</p>\n";
        }
        $tabindex++;
    }
    if ( $tabindex == $tabindex_start )
        echo '<p>' . __( 'No SEO fields were enabled. Please enable post fields in the Meta Tags options page', 'add-meta-tags' )  . '</p>';
    
	wp_nonce_field( 'mt-seo', 'mt_seo_nonce', false );
}

function mt_seo_save_meta( $post_id ) {
    global $mt_seo_fields;
    foreach( (array) $mt_seo_fields as $field_name => $field_data ) 
        mt_seo_save_meta_field( $post_id, $field_name );
}

function mt_seo_save_meta_field( $post_id, $field_name ) {
	// Checks to see if we're POSTing
	if ( 'post' !== strtolower( $_SERVER['REQUEST_METHOD'] ) || !isset($_POST[$field_name]) )
		return;

    if( !isset( $_POST['post_type'] ) || !in_array( $_POST['post_type'], array( 'post', 'page' ) ) )
        return;

    $post_type = $_POST['post_type'];

	// Checks to make sure we came from the right page
	if ( !wp_verify_nonce( $_POST['mt_seo_nonce'], 'mt-seo' ) )
		return;

	// Checks user caps
	if ( !current_user_can( 'edit_' . $post_type, $post_id ) )
		return;

	// Already have data?
	$old_data = get_post_meta( $post_id, $field_name, true );

	// Sanitize
    if( 'mt_seo_meta' == $field_name ) {
        if ( preg_match_all( '/<[\s]*meta[\s]*name="?' . '([^>"]*)"?[\s]*' . 'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', stripslashes( $_POST[$field_name] ), $matches ) ) {
            if (isset($matches) && is_array($matches) ) {
                foreach ( $matches[1] AS $key => $name ) 
                    $data .= "<meta name=\"" . wp_specialchars( $name ) . "\" content=\"" . wp_specialchars( $matches[2][$key] ) . "\" />\n"; 
            } else {
                $data = wp_filter_post_kses( $_POST[$field_name] );
                $data = trim( stripslashes( $data ) );
            }
        } else {
            $data = wp_filter_post_kses( $_POST[$field_name] );
            $data = trim( stripslashes( $data ) );
        }
    } else {
        $data = wp_filter_post_kses( $_POST[$field_name] );
        $data = trim( stripslashes( $data ) );
    }

	// nothing new, and we're not deleting the old
	if ( !$data && !$old_data )
		return;

	// Nothing new, and we're deleting the old
	if ( !$data && $old_data ) {
		delete_post_meta( $post_id, $field_name );
		return;
	}

	// Nothing to change
	if ( $data === $old_data )
		return;

	// Save the data
	if ( $old_data ) {
		update_post_meta( $post_id, $field_name, $data );
	} else {
		if ( !add_post_meta( $post_id, $field_name, $data, true ) )
			update_post_meta( $post_id, $field_name, $data ); // Just in case it was deleted and saved as ""
	}
}

function add_mt_seo_box() {
    add_meta_box( 'mt_seo', 'SEO', 'mt_seo_meta_box', 'post', 'normal' );
    add_meta_box( 'mt_seo', 'SEO', 'mt_seo_meta_box', 'page', 'normal' );
}


function mt_seo_style() {
    ?>
<style type="text/css">
.wide-seo-box {
    margin: 0;
    width: 98%;
}
</style>
    <?php
}

function mt_seo_rewrite_title( $title, $sep = '' , $seplocation = '' ) {
	global $posts, $include_keywords_in_single_posts, $mt_seo_fields;

    if ( !is_single() && !is_page())
        return $title;

    $options = get_option("add_meta_tags_opts");
	
    if ( isset( $posts[0] ) && 'page' == $posts[0]->post_type )
        $cmpvalues = $options['page_options'];
    elseif ( isset( $posts[0] ) && 'post' == $posts[0]->post_type )
        $cmpvalues = $options['post_options'];
	else
		$cmpvalues = array();
		
    if ( !is_array( $cmpvalues ) )
        $cmpvalues = array( 'mt_seo_title' => true, 'mt_seo_description' => true, 'mt_seo_keywords' => true, 'mt_seo_meta' => true );

	$cmpvalues = amt_clean_array( $cmpvalues );
	
    if ( true != $cmpvalues['mt_seo_title'] )
        return $title;
    
    $mt_seo_title = (string) get_post_meta( $posts[0]->ID, 'mt_seo_title', true );
    if ( empty( $mt_seo_title ) )
        return $title;
    
    $mt_seo_title = str_replace("%title%", $title, $mt_seo_title);
    $mt_seo_title = strip_tags( $mt_seo_title );
    
    if ( !empty( $sep ) ) {
		if ( 'right' == $seplocation ) {
			$mt_seo_title .= " $sep ";
		} else {
			$mt_seo_title = " $sep " . $mt_seo_title;
		}
	}
    return $mt_seo_title;
}


/*
Actions
*/

add_action( 'save_page', 'mt_seo_save_meta' );
add_action( 'save_post', 'mt_seo_save_meta' );
add_action( 'admin_menu', 'amt_add_pages' );
add_action( 'admin_menu', 'add_mt_seo_box' );
add_action( 'wp_head', 'amt_add_meta_tags', 0 );
add_action( 'admin_head', 'mt_seo_style' );
add_filter( 'wp_title', 'mt_seo_rewrite_title', 9999, 3);

