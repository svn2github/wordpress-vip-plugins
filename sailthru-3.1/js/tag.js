if (tag.isCustom) {
    Sailthru.init({
        customerId: tag.options.customerId,
        isCustom: true,
        autoTrackPageview: tag.options.autoTrackPageview,
        useStoredTags: tag.options.useStoredTags,
        excludeContent: tag.options.excludeContent,
    });
} else {
    Sailthru.init({
        customerId: tag.options.customerId
    });
}