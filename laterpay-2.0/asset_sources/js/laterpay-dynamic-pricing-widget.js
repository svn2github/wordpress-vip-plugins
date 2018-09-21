var margin = {
    top: 37,
    right: 40,
    bottom: 15,
    left: 50,
};
margin.xAxis = margin.left + margin.right;
margin.yAxis = margin.top + margin.bottom;

var DynamicPricingWidget = function (container) {
    var self = this,
        svg;

    // default settings
    this.container = container;
    this.interpolation = 'linear';
    this.minPrice = 0;
    this.maxPrice = 5;
    this.defaultPrice = 0.49;
    this.currentPrice = 0;
    this.pubDays = 0;
    this.currency = lpVars.currency;
    this.i18nDefaultPrice = lpVars.i18nDefaultPrice;
    this.i18nDays = lpVars.i18nDays;
    this.i18nToday = lpVars.i18nToday;
    this.dragging = false;
    this.currentStartPricePosition = null;
    this.currentEndPricePosition = null;


    // set up D3 graph in container
    svg = d3.select(container)
        .append('svg')
        .attr('class', 'lp_dynamic-pricing__svg')
        .append('g')
        .attr('class', 'lp_dynamic-pricing__svg-group');


    // add graph background
    svg.append('rect')
        .attr('class', 'lp_dynamic-pricing__graph-background');


    // add x-axis
    svg.append('g')
        .attr('class', 'lp_dynamic-pricing__axis lp_dynamic-pricing__axis--x');


    // add y-axis
    svg.append('g')
        .attr('class', 'lp_dynamic-pricing__axis lp_dynamic-pricing__axis--y');


    // draw x-axis with arrowhead
    svg.append('defs')
        .append('marker')
        .attr({
            id: 'lp_dynamic-pricing__axis-arrowhead--x',
            class: 'lp_dynamic-pricing__axis-arrowhead',
            refX: 2,
            refY: 2,
            markerWidth: 4,
            markerHeight: 4,
            orient: 'auto',
        })
        .append('path')
        .attr('d', 'M0,0 V4 L4,2 Z');


    // draw y-axis with arrowhead
    svg.append('defs')
        .append('marker')
        .attr({
            id: 'lp_dynamic-pricing__axis-arrowhead--y',
            class: 'lp_dynamic-pricing__axis-arrowhead',
            refX: 2,
            refY: 2,
            markerWidth: 4,
            markerHeight: 4,
            orient: 'auto',
        })
        .append('path')
        .attr('d', 'M0,4 H4 L2,0 Z');


    // draw default price marker
    svg.append('line')
        .attr('class', 'lp_dynamic-pricing__default-price-marker');
    svg.append('rect')
        .attr({
            class: 'lp_dynamic-pricing__default-price-label-background',
            width: 66,
            height: 16,
        });
    svg.append('text')
        .attr('transform', 'translate(0, 2.5)')
        .attr('class', 'lp_dynamic-pricing__default-price-label')
        .attr('text-anchor', 'middle')
        .text(this.i18nDefaultPrice);


    // draw price curve
    svg.append('path').attr('class', 'lp_dynamic-pricing__price-curve');


    // draw start price handle with text and input and everything
    svg.append('rect')
        .attr({
            class: 'lp_dynamic-pricing__start-price-handle',
            width: 32,
            rx: 3,
            height: 29,
            ry: 3,
        });
    svg.append('path')
        .attr('class', 'lp_dynamic-pricing__start-price-handle-triangle');
    svg.insert('foreignObject')
        .attr({
            class: 'lp_dynamic-pricing__start-price-input-wrapper',
            // foreign objects do not render without a width and height, so we have to provide those
            width   : '56px',
            height  : '30px',
        })
        .html('<input type="text" class="lp_dynamic-pricing__start-price-input" maxlength="6">');
    svg.append('text')
        .attr('class', 'lp_dynamic-pricing__start-price-value lp_dynamic-pricing__handle-text')
        .attr('text-anchor', 'end');
    svg.append('text')
        .attr('class', 'lp_dynamic-pricing__start-price-currency ' +
            'lp_dynamic-pricing__handle-text ' +
            'lp_dynamic-pricing__handle-unit')
        .attr('text-anchor', 'end')
        .text(this.currency);


    // draw end price handle with text and input and everything
    svg.append('rect')
        .attr({
            class   : 'lp_dynamic-pricing__end-price-handle',
            width   : 32,
            rx      : 3,
            height  : 29,
            ry      : 3,
        });
    svg.append('path')
        .attr('class', 'lp_dynamic-pricing__end-price-handle-triangle');
    svg.insert('foreignObject')
        .attr({
            class: 'lp_dynamic-pricing__end-price-input-wrapper',
            // foreign objects do not render without a width and height, so we have to provide those
            width   : '56px',
            height  : '30px',
        })
        .html('<input type="text" class="lp_dynamic-pricing__end-price-input" maxlength="6">');
    svg.append('text')
        .attr('class', 'lp_dynamic-pricing__end-price-value lp_dynamic-pricing__handle-text')
        .attr('text-anchor', 'end');
    svg.append('text')
        .attr('class', 'lp_dynamic-pricing__end-price-currency ' +
            'lp_dynamic-pricing__handle-text ' +
            'lp_dynamic-pricing__handle-unit')
        .attr('text-anchor', 'end')
        .text(this.currency);


    this.svg = svg;


    // redraw on resize
    jQuery(window).bind('resize', function () {
        self.plot();
    });


    // bind events to start price handle
    jQuery('body')
        // bind only single click event, without dragging
        .on('mousedown',
            '.lp_dynamic-pricing__start-price-handle, ' +
            '.lp_dynamic-pricing__start-price-value, ' +
            '.lp_dynamic-pricing__start-price-currency',
        function (e) {
            self.currentStartPricePosition = e.pageY;
            jQuery('body').on('mousemove', function startPriceMoveHandler (e) {
                self.currentStartPricePosition = e.pageY;
                jQuery('body').off('mousemove', startPriceMoveHandler);
            });
        })
        .on('mouseup',
            '.lp_dynamic-pricing__start-price-handle, ' +
            '.lp_dynamic-pricing__start-price-value, ' +
            '.lp_dynamic-pricing__start-price-currency',
            function (e) {
                if (e.pageY === self.currentStartPricePosition) {
                    dynamicPricingWidget.toggleStartInput('show');
                }
        })
        // bind events to start price input
        .on('focusout',
        '.lp_dynamic-pricing__start-price-input',
        function () {
            dynamicPricingWidget.toggleStartInput('save');
        })
        .on('keydown',
        '.lp_dynamic-pricing__start-price-input',
        function (e) {
            // save price on Enter
            if (e.keyCode === 13) {
                e.preventDefault();
                dynamicPricingWidget.toggleStartInput('save');
            }
        })
        .on('keydown',
        '.lp_dynamic-pricing__start-price-input',
        function (e) {
            // cancel editing on Esc
            if (e.keyCode === 27) {
                e.preventDefault();
                dynamicPricingWidget.toggleStartInput('cancel');
            }
        });


    // bind events to end price handle
    jQuery('body')
        // bind only single click event, without dragging
        .on('mousedown',
            '.lp_dynamic-pricing__end-price-handle, ' +
            '.lp_dynamic-pricing__end-price-value, ' +
            '.lp_dynamic-pricing__end-price-currency',
            function (e) {
                self.currentEndPricePosition = e.pageY;
                jQuery('body').on('mousemove', function endPriceMoveHandler (e) {
                    self.currentEndPricePosition = e.pageY;
                    jQuery('body').off('mousemove', endPriceMoveHandler);
                });
        })
        .on('mouseup',
            '.lp_dynamic-pricing__end-price-handle, ' +
            '.lp_dynamic-pricing__end-price-value, ' +
            '.lp_dynamic-pricing__end-price-currency',
            function (e) {
                if (e.pageY === self.currentEndPricePosition) {
                    dynamicPricingWidget.toggleEndInput('show');
                }
        })
        // bind events to end price input
        .on('focusout',
        '.lp_dynamic-pricing__end-price-input',
        function () {
            dynamicPricingWidget.toggleEndInput('save');
        })
        .on('keydown',
        '.lp_dynamic-pricing__end-price-input',
        function (e) {
            // save price on Enter
            if (e.keyCode === 13) {
                e.preventDefault();
                dynamicPricingWidget.toggleEndInput('save');
            }
        })
        .on('keydown',
        '.lp_dynamic-pricing__end-price-input',
        function (e) {
            // cancel editing on Esc
            if (e.keyCode === 27) {
                e.preventDefault();
                dynamicPricingWidget.toggleEndInput('cancel');
            }
        });
};

DynamicPricingWidget.prototype.interpolate = function (i) {
    this.interpolation = i;

    return this;
};

DynamicPricingWidget.prototype.setPrice = function (min, max, defaultPrice) {
    this.minPrice = min;
    this.maxPrice = max;
    if (defaultPrice) {
        this.defaultPrice = defaultPrice;
    }

    return this;
};

DynamicPricingWidget.prototype.set_data = function (data) {
    this.data = data;

    return this;
};

DynamicPricingWidget.prototype.get_data = function () {
    return this.data;
};

DynamicPricingWidget.prototype.set_today = function (pubDays, currentPrice) {
    this.pubDays = pubDays;
    this.currentPrice = currentPrice;

    return this;
};

DynamicPricingWidget.prototype._setDimensions = function () {
    this.dimensions = {
        width: jQuery(this.container).width() - margin.xAxis,
        height: jQuery(this.container).height() - margin.yAxis
    };
};

DynamicPricingWidget.prototype._setScale = function () {
    this.scale = {
        x: d3.scale.linear().range([0, this.dimensions.width + 10]),
        y: d3.scale.linear().range([this.dimensions.height, 0])
    };
};

DynamicPricingWidget.prototype._plotAxes = function () {
    this.xExtent = d3.extent(this.data, function (d) {
        return d.x;
    });
    this.yExtent = [0.00, this.maxPrice];

    var xAxis = d3.svg.axis()
            .scale(this.scale.x)
            .tickSize(-this.dimensions.height, 0, 0)
            .ticks(7)
            .orient('bottom'),
        yAxis = d3.svg.axis()
            .scale(this.scale.y)
            .tickSize(-this.dimensions.height, 0, 0)
            .ticks(7)
            .orient('left');

    this.scale.x.domain(this.xExtent);
    this.scale.y.domain(this.yExtent);


    // x-axis
    this.svg.select('.lp_dynamic-pricing__axis--x')
        .attr({
            transform: 'translate(0,' + this.dimensions.height + ')',
            'marker-end': 'url(#lp_dynamic-pricing__axis-arrowhead--x)'
        })
        .transition().duration(this.dragging ? 0 : 250)
        .call(xAxis);


    // y-axis
    this.svg.select('.lp_dynamic-pricing__axis--y')
        .attr('marker-start', 'url(#lp_dynamic-pricing__axis-arrowhead--y)')
        .transition().duration(this.dragging ? 0 : 250)
        .call(yAxis);


    // ticks (grid lines of graph)
    d3.selectAll('.tick').select('line')
        .attr('class', 'lp_dynamic-pricing__grid-line');
    d3.selectAll('.tick').select('text')
        .attr('class', 'lp_dynamic-pricing__grid-line-label');


    // position default price marker
    this.svg.select('.lp_dynamic-pricing__default-price-marker')
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x1: 0,
            y1: this.scale.y(this.defaultPrice),
            x2: this.dimensions.width + 10,
            y2: this.scale.y(this.defaultPrice)
        });
    this.svg.select('.lp_dynamic-pricing__default-price-label-background')
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: (this.dimensions.width - 66) / 2, // center horizontally
            y: this.scale.y(this.defaultPrice) - 9 // center vertically
        });
    this.svg.select('.lp_dynamic-pricing__default-price-label')
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: this.dimensions.width / 2,
            y: this.scale.y(this.defaultPrice)
        });
};

DynamicPricingWidget.prototype._plotPriceCurve = function () {
    var self = this;

    // D3.js provides us with a path data generator function for lines
    var priceCurve = d3.svg.line()
        .interpolate(this.interpolation)
        .x(function (d) {
            return self.scale.x(d.x);
        })
        .y(function (d) {
            return self.scale.y(d.y);
        });

    // .attr('d', lineFunction(lineData)) is where the magic happens:
    // this is where we send the data to the accessor function which returns the SVG path commands
    this.svg.select('.lp_dynamic-pricing__price-curve')
        .datum((this.data))
        .transition().duration(this.dragging ? 0 : 250)
        .attr('d', priceCurve);

};

DynamicPricingWidget.prototype._setDragBehavior = function () {
    // DRAG PRICE FUNCTIONS --------------------------------------------------------------------------------------------
    function dragstartPrice() {
        this.dragging = true;
    }

    function dragPrice(d, i) {
        var p = this.scale.y.invert(d3.event.y);
        if (p < this.yExtent[0]) {
            p = this.yExtent[0];
        }
        if (p > this.yExtent[1]) {
            p = this.yExtent[1];
        }
        d.y = p;

        // we have to keep the starting price in sync with the first / second point
        if (i === 0 && this.data[0].x === d.x) {
            // the second check is to make sure we are dragging the first point
            // since the handles have only one element of the data array, i is always 0
            this.data[1].y = d.y;
        } else if (i === 1) {
            this.data[0].y = d.y;
        } else if (i === 0 && this.data[this.data.length - 1].x === d.x) {
            // we have to keep the starting price in sync with the last / last but one point
            this.data[this.data.length - 2].y = d.y;
        } else if (i === this.data.length - 2) {
            this.data[this.data.length - 1].y = d.y;
        }

        this.plot();
    }

    function dragendPrice() {
        this.dragging = false;
    }


    // DRAG DAYS FUNCTIONS ---------------------------------------------------------------------------------------------
    var fps = 60,
        dragInterval;

    function dragstartDays() {
        this.dragging = true;
    }

    function dragDays(d, i) {
        var targetDate = this.scale.x.invert(d3.event.x),
            isDraggingLastPoint = (i === this.data.length - 2),
            isDragHandler = (i === this.data.length - 3),
            cappedTargetDate;

        if (isDraggingLastPoint) {
            var dragDelta = (targetDate - d.x) / (1000 / fps), // 30 fps
                dragStep = function () {
                    cappedTargetDate = +d.x + dragDelta;
                    cappedTargetDate = Math.max(cappedTargetDate, this.data[i].x + 0.51);
                    cappedTargetDate = Math.max(cappedTargetDate, 29.51); // minimum: 30 days
                    cappedTargetDate = Math.min(cappedTargetDate, 60.49); // maximum: 60 days

                    // update the scale.x value, as it could have changed
                    d.x = cappedTargetDate;
                    this.scale.x.domain(d3.extent(this.data, function (d) {
                        return d.x;
                    }));

                    this.plot();
                };

            clearInterval(dragInterval);

            dragInterval = setInterval(dragStep, 1000 / fps); // 30 fps

            dragStep();
        } else if (isDragHandler) {
            cappedTargetDate = targetDate;
            cappedTargetDate = Math.max(cappedTargetDate, this.data[i].x + 0.51);
            cappedTargetDate = Math.min(cappedTargetDate, 60.49); // maximum: 60 days

            if (cappedTargetDate >= 25) {
                this.data[i + 2].x = cappedTargetDate + 5;
            } else {
                this.data[i + 2].x = 30;
            }

            // update the scale.x value, as it could have changed
            d.x = cappedTargetDate;
            this.scale.x.domain(d3.extent(this.data, function (d) {
                return d.x;
            }));

            this.plot();
        } else {
            cappedTargetDate = targetDate;
            cappedTargetDate = Math.max(cappedTargetDate, this.data[i].x + 0.51);
            cappedTargetDate = Math.min(cappedTargetDate, this.data[i + 2].x - 0.51);

            // update the scale.x value, as it could have changed
            d.x = cappedTargetDate;
            this.scale.x.domain(d3.extent(this.data, function (d) {
                return d.x;
            }));

            this.plot();
        }
    }

    function dragendDays() {
        clearInterval(dragInterval);

        this.dragging = false;

        var i = 0,
            l = this.data.length;
        for (; i < l; i++) {
            this.data[i].x = Math.round((this.data)[i].x);
        }

        this.plot();
    }

    // dragging behavior of 'days' on x-axis
    this.dragBehavior = {
        x: d3.behavior.drag()
            .on('dragstart', dragstartDays.bind(this))
            .on('drag', dragDays.bind(this))
            .on('dragend', dragendDays.bind(this)),
        y: d3.behavior.drag()
            .on('dragstart', dragstartPrice.bind(this))
            .on('drag', dragPrice.bind(this))
            .on('dragend', dragendPrice.bind(this))
    };
};

DynamicPricingWidget.prototype._plotStartPriceHandle = function () {
    var self = this;

    this.svg.select('.lp_dynamic-pricing__start-price-handle')
        .datum((this.data)[0])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                return -38;
            },
            y: function (d) {
                return self.scale.y(d.y) - 14.5;
            }
        });
    this.svg.select('.lp_dynamic-pricing__start-price-handle-triangle')
        .datum((this.data)[0])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr('d', function (d) {
            var x = -6;
            var y = self.scale.y(d.y) - 5;

            return  'M ' + x + ' ' + y + ' l 5 5 l -5 5 z';
        });
    this.svg.select('.lp_dynamic-pricing__start-price-value')
        .datum((this.data)[0])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                return -7;
            },
            y: function (d) {
                return self.scale.y(d.y) - 0.5;
            }
        })
        .text(function (d) {
            return (lpVars.locale.indexOf( 'de_DE' ) !== -1) ? d.y.toFixed(2).replace('.', ',') : d.y.toFixed(2);
        });
    this.svg.select('.lp_dynamic-pricing__start-price-currency')
        .datum((this.data)[0])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                return -8;
            },
            y: function (d) {
                return self.scale.y(d.y) + 9.5;
            }
        });
    this.svg.select('.lp_dynamic-pricing__start-price-input-wrapper')
        .datum((this.data)[0])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                return -38;
            },
            y: function (d) {
                return self.scale.y(d.y) - 14;
            }
        });
};

DynamicPricingWidget.prototype._plotEndPriceHandle = function () {
    var self = this;

    this.svg.select('.lp_dynamic-pricing__end-price-handle')
        .datum((this.data)[this.data.length - 1])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                if (
                    jQuery('.lp_dynamic-pricing__end-price-input-wrapper') &&
                    jQuery('.lp_dynamic-pricing__end-price-input').is(':visible')
                    ) {
                    return self.dimensions.width;
                } else {
                    return self.dimensions.width + 16;
                }
            },
            y: function (d) {
                return self.scale.y(d.y) - 15;
            }
        });
    this.svg.select('.lp_dynamic-pricing__end-price-value')
        .datum((this.data)[this.data.length - 1])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                return self.dimensions.width + 47;
            },
            y: function (d) {
                return self.scale.y(d.y) - 1;
            }
        })
        .text(function (d) {
            return (lpVars.locale.indexOf( 'de_DE' ) !== -1) ? d.y.toFixed(2).replace('.', ',') : d.y.toFixed(2);
        });
    this.svg.select('.lp_dynamic-pricing__end-price-currency')
        .datum((this.data)[this.data.length - 1])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function () {
                return self.dimensions.width + 47;
            },
            y: function (d) {
                return self.scale.y(d.y) + 9;
            }
        });
    this.svg.select('.lp_dynamic-pricing__end-price-handle-triangle')
        .datum((this.data)[this.data.length - 1])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr('d', function (d) {
            var x = self.dimensions.width + 16;
            var y = self.scale.y(d.y) + 5;
            return  'M ' + x + ' ' + y + ' l 0 -10 l -5 5 z';
        });
    this.svg.select('.lp_dynamic-pricing__end-price-input-wrapper')
        .datum((this.data)[this.data.length - 1])
        .call(this.dragBehavior.y)
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function()  { return self.dimensions.width - 8; },
            y: function(d) { return self.scale.y(d.y) - 15; }
        });

};

DynamicPricingWidget.prototype._plotDaysHandle = function () {
    var self = this;

    // handles for setting the number of days after publication, after which
    // handle 1: the price starts changing
    // handle 2: the price reaches its final value
    // There is also a third handle for setting the maximum value on the x-axis which exists as a technical workaround
    // and is visually hidden.
    var daysHandle = this.svg.selectAll('.lp_dynamic-pricing__price-change-days-handle')
        .data((this.data).slice(1, this.data.length));

    daysHandle.enter().append('rect')
        .attr('class', function (point, index) {
            var classes = 'lp_dynamic-pricing__price-change-days-handle';
            if (index === self.data.length - 2) {
                classes += ' lp_is-hidden';
            }
            return classes;
        })
        .call(this.dragBehavior.x);

    daysHandle.exit().remove();

    daysHandle.transition().duration(this.dragging ? 0 : 250)
        .attr({
            x: function (d) {
                return self.scale.x(d.x) - 15;
            },
            y: function () {
                return -35;
            },
            width: 30,
            rx: 3,
            height: 30,
            ry: 3
        });

    var daysHandleTriangle = this.svg.selectAll('.lp_dynamic-pricing__price-change-days-handle-triangle')
        .data((this.data)
            .slice(1, this.data.length));

    daysHandleTriangle.enter().append('path')
        .attr('class', function (point, index) {
            var classes = 'lp_dynamic-pricing__price-change-days-handle-triangle';
            if (index === self.data.length - 2) {
                // hide the third x-axis handle - it's only there to work around technical restrictions when
                // automatically rescaling the x-axis
                classes += ' lp_is-hidden';
            }

            return classes;
        })
        .call(this.dragBehavior.x);

    daysHandleTriangle.exit().remove();

    daysHandleTriangle.transition().duration(this.dragging ? 0 : 250)
        .attr('d', function (d) {
            var x = self.scale.x(d.x) - 5,
                y = -5;

            return  'M ' + x + ' ' + y + ' l 10 0 l -5 5 z';
        });

    var daysHandleValue = this.svg.selectAll('.lp_dynamic-pricing__price-change-days-value')
        .data((this.data).slice(1, this.data.length));

    daysHandleValue.enter().append('text')
        .attr('class', function (point, index) {
            var classes = 'lp_dynamic-pricing__price-change-days-value lp_dynamic-pricing__handle-text';
            if (index === self.data.length - 2) {
                // hide the third x-axis handle - it's only there to work around technical restrictions when
                // automatically rescaling the x-axis
                classes += ' lp_is-hidden';
            }

            return classes;
        })
        .call(this.dragBehavior.x);

    daysHandleValue.exit().remove();

    daysHandleValue.transition().duration(this.dragging ? 0 : 250)
        .text(function (d) {
            return Math.round(d.x);
        })
        .attr({
            x: function (d) {
                return self.scale.x(d.x);
            },
            y: function () {
                return -21;
            },
            height: 30,
            'text-anchor': 'middle'
        });


    var daysHandleUnit = this.svg.selectAll('.lp_dynamic-pricing__price-change-days-unit')
        .data((this.data).slice(1, this.data.length));

    daysHandleUnit.enter().append('text')
        .attr('class', function (point, index) {
            var classes = 'lp_dynamic-pricing__price-change-days-unit ' +
                'lp_dynamic-pricing__handle-text ' +
                'lp_dynamic-pricing__handle-unit';
            if (index === self.data.length - 2) {
                // hide the third x-axis handle - it's only there to work around technical restrictions when
                // automatically rescaling the x-axis
                classes += ' lp_is-hidden';
            }

            return classes;
        })
        .call(this.dragBehavior.x);

    daysHandleUnit.exit().remove();

    daysHandleUnit.transition().duration(this.dragging ? 0 : 250)
        .text(this.i18nDays)
        .attr({
            x: function (d) {
                return self.scale.x(d.x);
            },
            y: function () {
                return -11;
            },
            height: 30,
            'text-anchor': 'middle'
        });
};

DynamicPricingWidget.prototype._plotXMarker = function () {
    var self = this,
        xMarker = this.svg.selectAll('.lp_dynamic-pricing__x-axis-marker').data((this.data).slice(1, this.data.length));

    // to make it easier to understand that the 'days' handle on the x-axis affects the point on the price curve,
    // we connect the handle with the point by a (dashed) line
    xMarker.enter().append('line')
        .attr('class', function (point, index) {
            var classes = 'lp_dynamic-pricing__x-axis-marker';
            if (index === self.data.length - 2) {
                // hide the third x-axis marker - it's only there to work around technical restrictions when
                // automatically rescaling the x-axis
                classes += ' lp_is-hidden';
            }

            return classes;
        })
        .call(this.dragBehavior.x);

    xMarker.exit().remove();

    xMarker
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            x1: function (d) {
                return self.scale.x(d.x);
            },
            y1: function () {
                return 0;
            },
            x2: function (d) {
                return self.scale.x(d.x);
            },
            y2: function (d) {
                return self.scale.y(d.y) - 4.5;
            } // subtract radius of price curve point to avoid overlap
        });
};

DynamicPricingWidget.prototype._plotPriceCurvePoints = function () {
    var self = this,
        point = this.svg.selectAll('.lp_dynamic-pricing__price-curve-point').data((this.data));

    // Returns a reference to the placeholder elements (nodes) for each data element that did not have a corresponding
    // existing DOM element and appends a circle for each element in the data.
    point.enter().append('circle')
        .attr('class', function (point, index) {
            var classes = 'lp_dynamic-pricing__price-curve-point';
            if (index === 0 || index === self.data.length - 1) {
                // hide the first and the last point on the price curve, mainly for aesthetic reasons
                classes += ' lp_is-hidden';
            }

            return classes;
        })
        .attr('r', 0);

    point.transition().duration(this.dragging ? 0 : 250)
        .attr({
            r: 4.5,
            cx: function (d) {
                return self.scale.x(d.x);
            },
            cy: function (d) {
                return self.scale.y(d.y);
            }
        });

    point.exit().remove();
};

DynamicPricingWidget.prototype._plotPriceMarker = function () {
    var self = this,
        currentPrice = this.svg.selectAll('.lp_dynamic-pricing__current-price-marker')
            .data((this.data)
                .slice(1, this.data.length));

    // Renders a vertical line indicating the current position on the set price curve and the resulting effective price.
    // Only shown, if the post was already published.
    if (this.pubDays > 0) {
        currentPrice.enter().append('line')
            .attr('class', 'lp_dynamic-pricing__current-price-marker');
        currentPrice.exit().remove();
        currentPrice
            .transition().duration(this.dragging ? 0 : 250)
            .attr({
                x1: function () {
                    return self.scale.x(dynamicPricingWidget.pubDays);
                },
                y1: function () {
                    return self.scale.y(0);
                },
                x2: function () {
                    return self.scale.x(dynamicPricingWidget.pubDays);
                },
                y2: function () {
                    return self.scale.y(dynamicPricingWidget.maxPrice);
                }
            });
        // #657: add label properly added and attach drag behavior
        // this.svg.append('text')
        //     .attr('class', 'lp_dynamic-pricing__current-price-label')
        //     .attr('text-anchor', 'middle')
        //     .text(this.i18nToday)
        //     .datum({
        //         x: dynamicPricingWidget.pubDays,
        //         y: dynamicPricingWidget.currentPrice
        //     })
        //     .call(this.dragBehavior.y)
        //     .attr({
        //         x: function() { return self.scale.x(parseInt(dynamicPricingWidget.pubDays, 10)); },
        //         y: function() { return self.scale.y(); }
        //     });
    }
};

DynamicPricingWidget.prototype.plot = function () {
    this._setDimensions();
    this._setScale();

    // position entire widget
    d3.select('.lp_dynamic-pricing__svg')
        .attr({
            width: this.dimensions.width + margin.xAxis,
            height: this.dimensions.height + margin.yAxis
        })
        .select('.lp_dynamic-pricing__svg-group')
        .attr('transform', 'translate(' + (margin.left - 11) + ',' + margin.top + ')');

    // position graph background
    this.svg.select('.lp_dynamic-pricing__graph-background')
        .transition().duration(this.dragging ? 0 : 250)
        .attr({
            width: this.dimensions.width + 10,
            height: this.dimensions.height
        });

    this._plotAxes();

    this._plotPriceCurve();
    this._plotPriceCurvePoints();

    this._setDragBehavior();

    this._plotStartPriceHandle();
    this._plotEndPriceHandle();
    this._plotDaysHandle();

    this._plotXMarker();
    this._plotPriceMarker();
};

DynamicPricingWidget.prototype.toggleStartInput = function (action) {
    var data = dynamicPricingWidget.get_data(),
        plotPrice = data[0].y.toFixed(2),
        $handle = jQuery(
                '.lp_dynamic-pricing__start-price-handle, ' +
                '.lp_dynamic-pricing__start-price-handle-triangle, ' +
                '.lp_dynamic-pricing__start-price-value, ' +
                '.lp_dynamic-pricing__start-price-currency'
        ),
        $priceInput = jQuery('.lp_dynamic-pricing__start-price-input'),
        inputPrice = $priceInput.val();

    // de-localize price for processing (convert to proper float value)
    if (inputPrice.indexOf(',') > -1) {
        inputPrice = parseFloat(inputPrice.replace(',', '.'));
    } else {
        inputPrice = parseFloat(inputPrice);
    }

    // localize price for displaying
    if (lpVars.locale.indexOf( 'de_DE' ) !== -1) {
        plotPrice = plotPrice.replace('.', ',');
    }

    if (action === 'show') {
        $handle.hide();

        $priceInput
            .val(plotPrice)
            .show()
            .focus();
    } else if (action === 'save') {
        // cap prices that are outside of the valid range
        if (inputPrice > this.maxPrice) {
            inputPrice = this.maxPrice;
        } else if (inputPrice < this.minPrice && inputPrice !== 0) {
            inputPrice = this.minPrice;
        }

        data[0].y = inputPrice;
        data[1].y = inputPrice;

        $priceInput.hide();
        $handle.show();

        // update graph
        dynamicPricingWidget.set_data(data);
        dynamicPricingWidget.plot();
    } else if (action === 'cancel') {
        $priceInput.hide();
        $handle.show();
    }
};

DynamicPricingWidget.prototype.toggleEndInput = function (action) {
    var data = dynamicPricingWidget.get_data(),
        plotPrice = data[2].y.toFixed(2),
        $handle = jQuery(
                '.lp_dynamic-pricing__end-price-handle, ' +
                '.lp_dynamic-pricing__end-price-handle-triangle, ' +
                '.lp_dynamic-pricing__end-price-value, ' +
                '.lp_dynamic-pricing__end-price-currency'
        ),
        $priceInput = jQuery('.lp_dynamic-pricing__end-price-input'),
        inputPrice = $priceInput.val();

    // de-localize price for processing (convert to proper float value)
    if (inputPrice.indexOf(',') > -1) {
        inputPrice = parseFloat(inputPrice.replace(',', '.'));
    } else {
        inputPrice = parseFloat(inputPrice);
    }

    // localize price for displaying
    if (lpVars.locale.indexOf( 'de_DE' ) !== -1) {
        plotPrice = plotPrice.replace('.', ',');
    }

    if (action === 'show') {
        $handle.hide();

        $priceInput
            .val(plotPrice)
            .show()
            .focus();
    } else if (action === 'save') {
        // cap prices that are outside of the valid range
        if (inputPrice > this.maxPrice) {
            inputPrice = this.maxPrice;
        } else if (inputPrice < this.minPrice && inputPrice !== 0) {
            inputPrice = this.minPrice;
        }

        data[2].y = inputPrice;
        data[3].y = inputPrice;

        $priceInput.hide();
        $handle.show();

        // update graph
        dynamicPricingWidget.set_data(data);
        dynamicPricingWidget.plot();
    } else if (action === 'cancel') {
        $priceInput.hide();
        $handle.show();
    }
};
