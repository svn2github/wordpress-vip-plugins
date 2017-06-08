# GettyImages Wordpress plugin

A plugin to search for images and insert them into posts directly form the Wordpress admin pages.

## Quick Overview: 
There are 2 versions of the GI WordPress plugin. 
* Wordpress.org (com)– self hosted version of WP; Users can install the plugin and then download images through the plugin against a subscription/Ultrapack or generate Embedded image.
* Wordpress VIP – Enterprise WP solution; Getty Images plugin is one of the limited plugins offered. Users can download images through the plugin against a subscription/Ultrapack. Does not include Embed feature.

This is the codebase for both plugins. The `isWPcom` variable in php and js is used to identify behavior-splitting points.

## Wordpress plugin page
* https://wordpress.org/plugins/getty-images/

## Wordpress SVN repo
Wordpress plugins must live in a central SVN repository hosted by Wordpress.
* https://plugins.svn.wordpress.org/getty-images/  
Development has moved away from the SVN repo in favor of this Git repo. We only push to SVN when releasing new versions. You should clone the svm repository in a separate folder, you will need it to release a new plugin version.

## Releasing a new Plugin version
### Wordpress.org
1. Make sure you have all your changes working, tested and committed to master in the git repo.
2. Add a new commit that updates version and changelog:
    * Change the version number in the header comment of the `getty-images.php` file to the new version number.
    * Change the version number in the `'Stable tag'` field of the readme.txt file.
    * Add a new sub-section in the `'changelog'` section of the readme.txt file, briefly describing what changed compared to the last release. This will be listed on the 'Changelog' tab of the plugin page.
3. Push to master and create a tag with the new version in git:  
  `git tag <new_version_number>; git push --tags`
4. Copy all the files from the git master branch (except the `.git` folder) to your local SVN inside the `\trunk` folder.
5. Commit changes in your SVN repo:  
  `svn commit -m '<commit message describing what changed from previous version>'`
6. Tag your release in SVN. **This is a MUST** so that Wordpress knows there is a new version available:
    * Create a new Tag with the version number, for example, for version 2.4.5 do: `svn cp trunk tags/2.4.5`
    * Commit the new tag with the specific message: `svn ci -m "2.4.5 release"`
7. Wait a few minutes for Wordpress to process  and then check the wordpress.org Plugin page to verify the new version is available.

### Wordpress VIP
New versions for Wordpress VIP plugin need to be reviewed and approved by the Wordpress team.
Zip up the code, open a ticket in the support portal (https://wordpressvip.zendesk.com/requests) and send it.



### Troubleshooting:
* The Plugin's page on wordpress.org still lists the old version. Have you updated the 'stable tag' field in the trunk folder? Just creating a tag and updating the readme.txt in the tag folder is not enough!
* The Plugin's page offers a zip file with the new version, but the button still lists the old version number and no update notification happens in your WordPress installations. Have you remembered to update the 'Version' comment in the main PHP file?
* For other problems check Otto's good write-up of common problems: [The Plugins directory and readme.txt files](https://make.wordpress.org/plugins/2012/06/09/the-plugins-directory-and-readme-txt-files/)

## Documentation: 
* FAQs (for support team): [https://gettyimages.sharepoint.com/Technology/API/Plugins/Shared%20Documents/Wordpress%20Support%20Materials/Wordpress_ESS_FAQs_Troubleshooting.docx?d=w491932630a494eb0b322193e5e2390a6](https://gettyimages.sharepoint.com/Technology/API/Plugins/Shared%20Documents/Wordpress%20Support%20Materials/Wordpress_ESS_FAQs_Troubleshooting.docx?d=w491932630a494eb0b322193e5e2390a6
)
* WP VIP Screenshots: [https://gettyimages.sharepoint.com/Technology/API/Plugins/Shared%20Documents/Wordpress%20Support%20Materials/Wordpress_ScreenshotWalkthrough.docx?d=wcc94459be4e247a6b8819514c0f94ce1](https://gettyimages.sharepoint.com/Technology/API/Plugins/Shared%20Documents/Wordpress%20Support%20Materials/Wordpress_ScreenshotWalkthrough.docx?d=wcc94459be4e247a6b8819514c0f94ce1)
* Wordpress - Writing a plugin: [https://codex.wordpress.org/Writing_a_Plugin](https://codex.wordpress.org/Writing_a_Plugin)
* Wordpress - How to use Subversion and create a new plugin version: [https://wordpress.org/plugins/about/svn/#task-3](https://wordpress.org/plugins/about/svn/#task-3)
* Wordpress VIP - How to submit a VIP plugin: [https://vip.wordpress.com/documentation/vip/code-and-theme-review-process/#how-to-submit-a-plugin](https://vip.wordpress.com/documentation/vip/code-and-theme-review-process/#how-to-submit-a-plugin)
* Wordpress source code:  
[https://core.trac.wordpress.org/browser/tags/4.7.4/src]
(https://core.trac.wordpress.org/browser/tags/4.7.4/src)


