var mosaic = {
    addImageToRow: function(padding, row, imageItem, targetHeight) {
        // Either retrieve or set the row height
        if (row.images.length === 0) {
            row.width = 0;
            row.minHeight = Math.min(imageItem.height, targetHeight || imageItem.height);
        }
        else {
            row.minHeight || _.pluck(row.images, "height").min()
        }

        // If this is the shortest image (and there were others), rescale the row
        if (imageItem.height < row.minHeight) {
            var totalPad = row.images.length * padding * 2;
            row.width = Math.ceil((row.width - totalPad) * imageItem.height / row.minHeight + totalPad);
            row.minHeight = imageItem.height;
        }

        // scale this image down
        var scaledWidth = Math.ceil(imageItem.width * row.minHeight / imageItem.height);

        // add image to totals
        row.width += scaledWidth;
        row.images.push(imageItem);

        return row;
    },
    splitImageSequence: function(width, padding, targetHeight, memo, imageItem) {
        var activeRow = memo[0];

        // always add the next image because we can't scale up
        this.addImageToRow(padding, activeRow, imageItem, targetHeight);

        // If this image was enough to fill the row, add a new blank row
        if (activeRow.width >= width) {
            memo.unshift({ width: 0, images: [] });
        }

        // Otherwise add the padding for the next image
        //else {
            activeRow.width += 2 * padding;
        //}

        return memo;
    },
    buildMosaicRows: function(containerWidth, padding, imageList, targetHeight) {
        var rows = imageList.reduce(this.splitImageSequence.bind(this, containerWidth, padding, targetHeight),
            [{ width: 0, images: [] }]);
        return rows;
    }
};
