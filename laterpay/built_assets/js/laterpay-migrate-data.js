!function(i){i(function(){function a(e,t){var n={action:"laterpay_start_migration",security:migration_nonce,migrate:e,offset:t};i.post(ajaxurl,n,function(e){"string"===i.type(e)&&(e=JSON.parse(e)),"subscription_migrated"in e&&e.subscription_migrated!==!0?a("subscription",e.offset):"subscription_migrated"in e&&e.subscription_migrated===!0?(i("<span />").addClass("dashicons dashicons-yes").appendTo(s),i("<br />").appendTo(s),s.append(lp_i18n.MigratingTimepasses),a("time_pass",e.offset)):"time_pass_migrated"in e&&e.time_pass_migrated!==!0?a("time_pass",e.offset):"time_pass_migrated"in e&&e.time_pass_migrated===!0?(i("<span />").addClass("dashicons dashicons-yes").appendTo(s),i("<br />").appendTo(s),s.append(lp_i18n.MigratingCategoryPrices),a("category_price",e.offset)):"category_price_migrated"in e&&e.category_price_migrated!==!0?a("category_price",e.offset):"category_price_migrated"in e&&e.category_price_migrated===!0&&(i("<span />").addClass("dashicons dashicons-yes").appendTo(s),i("<br />").appendTo(s),i("<br />").appendTo(s),s.append(lp_i18n.MigrationCompleted),i("<button/>").attr("type","button").addClass("notice-dismiss").appendTo(s),i("#migration-loader").remove(),s.removeClass("notice-info").addClass("notice-success"),i(".notice-dismiss").click(function(){s.remove()}))})}var e=jQuery("#lp_js_startDataMigration"),s=jQuery("#lp_migration_notice");e.on("click",function(){s.removeClass("notice-error").addClass("notice-info"),s.html("").append(lp_i18n.MigratingData),i("<img />").attr("id","migration-loader").attr("src","/wp-admin/images/loading.gif").appendTo(s),i("<br />").appendTo(s),i("<br />").appendTo(s),s.append(lp_i18n.MigratingSubscriptions),a("subscription",0)})})}(jQuery);