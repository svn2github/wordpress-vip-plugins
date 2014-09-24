//ellipsis plugin http://devongovett.wordpress.com/2009/04/06/text-overflow-ellipsis-for-firefox-via-jquery/ + comments + custom mods
(function($) {
    $.fn.ellipsis = function(lines, enableUpdating, moreText, lessText) {
        return $(this).each(function() {
            var el = $(this);
            var resetDescription = function(height, originalText) {
            el.html(originalText);
            el.animate({ "height": height }, "normal", null, function() {
                el.ellipsis(true, true, moreText, lessText);
          });
            };
 
            if (el.css("overflow") == "hidden") {
  
                var originalText = el.html();
                var availWidth = el.width();
                var availHeight = el.height();
  
                var MoreLessTag;
                if (moreText) {
                    enableUpdating = true;
                    MoreLessTag = " <a class='MoreLessTag' href='#' >" + moreText + "</a>";
                }
                else MoreLessTag = "";
  
                var t = $(this.cloneNode(true))
                    .hide()
                    .css({
                        'position': 'absolute',
                        'overflow': 'visible',
                        'max-width': 'none',
                        'max-height': 'none'
                    });
                if (lines) t.css("height", "auto").width(availWidth);
                else t.css("width", "auto");
                el.after(t);
                
                var fullHeight = t.height() + 16;
  
                var avail = (lines) ? availHeight : availWidth;
                var test = (lines) ? t.height() : t.width();
                var foundMin = false, foundMax = false;
                if (test > avail) {
                    //Binary search style trimming of the temp element to find its optimal size
                    var min = 0;
                    var max = originalText.length;
                    while (min <= max) {
                        var trimLocation = (min + max) / 2;
                        var text = originalText.substr(0, trimLocation);
                        t.html(text + "&hellip;" + MoreLessTag);
  
                        test = (lines) ? t.height() : t.width();
                        if (test > avail) {
                            if (foundMax)
                                foundMin = true;
  
                            max = trimLocation - 1;
                            if (min > max) {
                                //If we would be ending decrement the min and regenerate the text so we don't end with a
                                //slightly larger text than there is space for
                                trimLocation = (max + max - 2) / 2;
                                text = originalText.substr(0, trimLocation);
                                t.html(text + "&hellip;" + MoreLessTag);
                                break;
                            }
                        }
                        else if (test < avail) {
                            min = trimLocation + 1;
                        }
                        else {
                            if (foundMin && foundMax && ((max - min) / max < .2))
                                break;
                            foundMax = true;
                            min = trimLocation + 1;
                        }
                    }
                }
  
                el.html(t.html());
                t.remove();
  
  
                if (moreText) {
                    jQuery(".MoreLessTag", this).click(function(event) {
                        event.preventDefault();
                        setTimeout(function(){el.html(originalText);},150);
                        el.animate({ "height": fullHeight }, "1200", null, function() {
                            setTimeout(function(){
                                el.append(" <a class='MoreLessTag' href='#' >" + lessText + "</a>");
                                jQuery(".MoreLessTag", el).click(function(event) {
                                    event.preventDefault();
                                    resetDescription(availHeight, originalText);
                                });
                            },150);                            
                        });
                    });
                }
                else {
                    var replaceTags = new RegExp(/<\/?[^>]+>/gi);
                    el.attr("alt", originalText.replace(replaceTags, ''));
                    el.attr("title", originalText.replace(replaceTags, ''));
                }
  
                if (enableUpdating == true) {
                    var oldW = el.width();
                    var oldH = el.height();
                    el.one("resize", function() {
                        if (el.width() != oldW || (lines && el.height != oldH)) {
                            el.html(originalText);
                            el.ellipsis(lines, enableUpdating, moreText, lessText);
                        }
                    });
                }
            }
  
        });
    };
})(jQuery);