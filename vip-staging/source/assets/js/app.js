jQuery(document).ready(function(){
    vipStaging.init();
});

var vipStaging = {

    ajaxPath: "https://vipstagingblog.wordpress.com/wp-admin/admin-ajax.php",
    cookieName: 'wpvip_staging_info_showing',

    init: function() {
        vipStaging.bindActions();
        vipStaging.sendNotification();

        if( "true" != Cookies.get( this.cookieName) ) {
            jQuery('.staging__info').removeClass('visible');
        }

    },

    bindActions: function() {
        jQuery('.staging__button').on('click', function() {
            vipStaging.toggleInfo();
        });

        jQuery('.staging__toggle input').on('click', function() {
            vipStaging.toggleSwitch();
        });
    },

    toggleInfo: function()  {
        jQuery('.staging__info').toggleClass('visible');

        // Set the cookie with the current visible value
        Cookies.set( this.cookieName, jQuery( '.staging__info').hasClass('visible') )

        event.preventDefault();

        vipStaging.pullDeployInfo();
        vipStaging.killNotification();
    },

    toggleSwitch: function() {
        if ( jQuery('#staging-vip').hasClass('live') ) {
            jQuery('#staging-vip').removeClass('live').addClass('staging loading');
            // When switching to staging, it should set the cookie to true
            Cookies.set( this.cookieName, true );
        } else if ( jQuery('#staging-vip').hasClass('staging') ) {
            jQuery('#staging-vip').removeClass('staging').addClass('live loading');
        }

        jQuery('.staging__toggle input').attr( 'disabled', true );

        vipStaging.ajaxToggleAndReload();
    },

    ajaxToggleAndReload: function() {
        jQuery.ajax({
            url: this.ajaxPath,
            method: 'POST',
            data: {
                'action': 'vip_staging_toggle',
                'is_staging': wp_vip_staging.is_staging ? '0' : '1',
            },
            success: function( data ) {
                window.setTimeout( function() {
                    location.reload(true); // Reload without caching
                }, 300);
            }
        })
    },

    pullDeployInfo: function() {
        jQuery.ajax({
            url: this.ajaxPath,
            method: 'POST',
            data: {
                'action': 'vip_staging_deploy_info'
            },
            success: function( data ) {
                vipStaging.updateDeployInfoMarkup( data );
            }
        });
    },

    sendNotification: function() {
        // @todo: grab real data, push notification when updated
        var timeout = window.setTimeout( function() {
            //jQuery('.staging__notice').addClass('visible');
        }, Math.floor( Math.random() * 3000 ) );
    },

    killNotification: function() {
        jQuery('.staging__notice').removeClass('visible');
    },

    updateDeployInfoMarkup: function( data ) {
        // Grab only the data
        var info = data.data;

        // Initialize the need variables
        var deploy_rev, commit_rev;

        if ( info.has_child ) {
            deploy_rev = "r" + info.child_theme.committed_rev;
            commit_rev = "r" + info.child_theme.deployed_rev;
        } else if ( false !== info.parent_child ) {
            deploy_rev = "r" + info.parent_theme.committed_rev;
            commit_rev = "r" + info.parent_theme.deployed_rev;
        } else {
            deploy_rev = "unknown";
            commit_rev = "unknown";
        }

        jQuery(".staging__revisions .revision-live").text(deploy_rev);
        jQuery(".staging__revisions .revision-staging").text(commit_rev);

    },

}