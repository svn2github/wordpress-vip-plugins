<?php

/**
 * Search Modal for Admin Posts Page
 */

?>
<div id="popup_container" class="ndn-search-container">

  <div class="ndn-search-explanation ndn-search-container">
    <p>Search for content in the Inform video library and embed them in your post or page with just one click.</p>
  </div>

  <div class="ndn-search-input ndn-search-container">
    <form name="ndn-search" id="ndn-search-form" action="admin-post.php" method="post" analytics-category="WPSearch" analytics-label="SearchInitial" accept-charset="UTF-8" novalidate>
  	<select id="ndn-search-textlist" class="ndn-select-button" style="vertical-align: inherit;" name = "search-video-type">
  	  <option value="">All Text</option>
  	  <option value="description">Description</option>
  	  <option value="title">Title</option>
  	  <option value="id">Video ID</option>
  	</select>      
	<label for="ndn-search-input"></label>
      <input class="ndn-search-query-input" name="query" type="text" analytics-category="WPSearch" analytics-label="SearchKeywords" placeholder="Enter Keywords or Video ID" />

      <input type="hidden" name="search-action" value="1" />
      <input class="button" name="submit" type="submit" value="Search" />
    </form>
  </div>

  <div class="ndn-change-settings ndn-search-container">
    <span><a href="admin.php?page=inform-plugin-settings" class="ndn-change-settings" analytics-category="WPSettings" analytics-label="SettingsPage" target="_blank">Change</a>&nbsp;general and embed settings</span>
  </div>

</div>
